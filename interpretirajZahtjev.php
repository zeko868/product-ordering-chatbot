<?php
ob_start();
header('Content-Type: text/html; charset=utf-8');

const API_KEY = 'AIzaSyByjQCWlKAH_uKFlnN0fCUYduP8sXnjQLo';

function strtolower_cro($string)
{
    $low=array("Č" => "č","ć" => "ć", "Đ" => "đ", "Š" => "š", "Ž" => "ž");
    return strtolower(strtr($string,$low));
}

function translateInput($inputText, $target){
    
    $inputText = strtolower_cro($inputText);
    $curl = curl_init();
    if(strpos($inputText, "kn")){
        $inputText = str_replace("kn", "KN", $inputText);
    }


    curl_setopt_array($curl, array(
    CURLOPT_URL => "https://translation.googleapis.com/language/translate/v2?key=" . API_KEY,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => "{\r\n  \"q\": \"$inputText\",\r\n  \"target\": \"$target\"\r\n}",
    CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache",
        "content-type: application/json"
    ),
    ));

    $response = curl_exec($curl);
    $json = json_decode($response, true); 

    curl_close($curl);

    $data = $json["data"]["translations"][0];
    if($data['detectedSourceLanguage'] == 'hr' || $target == 'hr'){
        $returnValue['status'] = "OK";
        $returnValue['translate'] = strtoupper($data["translatedText"]);
    }else{
        $returnValue['status'] = "ERROR";
    }
    
    return $returnValue;
}

function NLPtext($translatedText){
    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => "https://language.googleapis.com/v1beta2/documents:analyzeEntities?key=" . API_KEY,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => "{\r\n  \"document\":{\r\n    \"type\":\"plain_text\",\r\n    \"language\": \"en\",\r\n    \"content\":\"$translatedText\"\r\n  },\r\n  \"encodingType\":\"UTF8\"\r\n}",
    CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache",
        "content-type: application/json"
    ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    
    $json = json_decode($response, true);
    $data = $json["entities"];

    foreach($data as $value){
        foreach($value as $key => $v){
            if($key=="type"){
                switch($v){
                    case "ORGANIZATION":
                        $nlp['proizvodac'] = $value["name"];;
                        break;
                    case "CONSUMER_GOOD":
                        $nlp['proizvod'] = $value["name"];;
                        break;
                }
            }
            
        }
    }
    
    $string = "";

    if(isset($nlp['proizvodac']))
        $string .= $nlp['proizvodac'] . " ";
    if(isset($nlp['proizvod']))
        $string .= $nlp['proizvod'];
    

    if(!isset($nlp['proizvodac']) && !isset($nlp['proizvod'])){
        echo "Doslo je do pogreske!";
        exit();
    }

    $curl = curl_init();
    
    $ostalo = array();

    curl_setopt_array($curl, array(
    CURLOPT_URL => "https://language.googleapis.com/v1/documents:analyzeSyntax?key=" . API_KEY,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => "{\r\n  \"document\":{\r\n    \"type\":\"plain_text\",\r\n    \"language\": \"en\",\r\n    \"content\":\"$translatedText\"\r\n  },\r\n  \"encodingType\":\"UTF8\"\r\n}",
    CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache",
        "content-type: application/json"
    ),
    ));
    
    $response = curl_exec($curl);
    
    $json = json_decode($response,true);
    $data = $json['tokens'];

    $ost = array();

    for($i = 0; $i < sizeof($data); $i++){
        if(!strpos($string, $data[$i]['text']['content']) && $data[$i]['partOfSpeech']['tag'] == "NOUN"){
            array_push($ost,translateInput($data[$i]['text']['content'],'hr')['translate']);
        }
        $ostalo['ostaliFilteri'] = $ost;
        if($data[$i]['partOfSpeech']['tag'] == "NUM" && !strpos($string, $data[$i]['text']['content'])){
            for($j = $i; $j >= 0; $j--){
                if(($data[$j]['partOfSpeech']['tag'] == "ADJ" || $data[$j]['partOfSpeech']['tag'] == "ADP") && ($data[$j]['text']['content'] == "OVER" || $data[$j]['text']['content'] == "MORE")){
                    $ostalo['cijenaOd'] = intval($data[$i]['text']['content']);
                    break;
                }
                if($data[$j]['partOfSpeech']['tag'] == "ADJ" || $data[$j]['partOfSpeech']['tag'] == "ADP" && $data[$j]['text']['content'] == "LESS"){
                    $ostalo['cijenaDo'] = intval($data[$i]['text']['content']);
                    break;
                }
            }
        }else if($data[$i]['partOfSpeech']['tag'] == "ADP" && $data[$i]['text']['content'] == "BETWEEN"){
            $ostalo['cijenaOd'] = intval($data[$i+1]['text']['content']);
            $ostalo['cijenaDo'] = intval($data[$i+3]['text']['content']);
        }
    }

    curl_close($curl);
    $s['tekst'] = $string;
    $s['ostalo'] = $ostalo;
    return $s;
}

function prilagodiZahtjev($inputText){
    $json_params = file_get_contents("./promjeneUZahtjevu.json");

    $json = json_decode($json_params, true);

    
    foreach($json as $val){
        $inputText = str_replace($val['original'], $val['promjena'], $inputText);
    }    
    
    if(strpos($inputText, "PROCESOR") && !strpos($inputText, "RAČUNALNI")){
        $input = substr($inputText, 0, strpos($inputText, "PROCESOR")) . "RAČUNALNI " . substr($inputText, strpos($inputText, "PROCESOR"), strlen($inputText));
        return $input;
    }
    
    return $inputText;
}

function urediIzlaz($inputText){
    $inputText = strtolower_cro($inputText);
    if(strpos($inputText, "računalni")){
        $input = substr($inputText, 0, strpos($inputText, "računalni")) . substr($inputText, strpos($inputText, "računalni") + 11, strlen($inputText));
        return $input;
    }
    return $inputText;
}

//$inputText = "Želim Lenovo laptop.";
//$inputText = "Želim grafičku karticu od AMDa.";
//$inputText = "Molim Vas ponudu za Intel procesor.";
//$inputText = "hoću kupiti intelov procesor.";
//$inputText = "hoću kupiti intel i3 procesor cijene manje od 1500 kuna.";
//$inputText = "Želim grafičku karticu nvidia geforce mx 440 cijene veće od 2000 kuna.";
//$inputText = "Želim grafičku karticu nvidia geforce mx 440 cijene manje od 3000 kuna.";
//$inputText = "Želim grafičku karticu nvidia geforce mx 440 cijene između 1000 i 3000 kn.";
$inputText = "Želim grafičku karticu nvidia geforce mx 440 cijene veće od 1000 i manje od 3000 kuna.";


$input = prilagodiZahtjev(strtoupper($inputText));

$translatedInput = translateInput($input, 'en');

if($translatedInput['status'] == "OK"){
    $nlpText = NLPtext($translatedInput['translate']);
}else{
    echo "Unesena je narudzba na krivom jeziku!";
    exit();
}


$translatedOutput = translateInput($nlpText['tekst'], 'hr');

if($translatedOutput['status'] == "OK"){
    $translatedOutputText = $translatedOutput['translate'];
}else{
    echo "Doslo je do pogreske!";
    exit();
}

$translated = urediIzlaz($translatedOutputText);

$input = prilagodiZahtjev(strtoupper($inputText));

$translatedInput = translateInput($input, 'en');

if($translatedInput['status'] == "OK"){
    $nlpText = NLPtext($translatedInput['translate']);
}else{
    echo "Unesena je narudzba na krivom jeziku!";
    exit();
}


$translatedOutput = translateInput($nlpText['tekst'], 'hr');

if($translatedOutput['status'] == "OK"){
    $translatedOutputText = $translatedOutput['translate'];
}else{
    echo "Doslo je do pogreske!";
    exit();
}

$trans = urediIzlaz($translatedOutputText);
$nlpText['tekst'] = $trans;
$translated = $nlpText;
//echo "<br/>Kupac pretražuje: " . strtolower_cro($translated);

include "./traziRobu.php";

$button = array();
for($i=0;$i<count($obj);$i++){
	array_push($button, array('type'=>'postback', 'title'=>$obj[$i]->naziv, 'payload' => $obj[$i]->naziv));
}


$answer = [
	'type'=>'template',
	'payload'=>[
		'template_type'=>'button',
		'text'=>'Ponudeni artikli:',
		'buttons'=> $button
	]
];
$response = [
	'recipient' => [ 'id' => "1155662414560805" ],
	'message' => [ 'attachment' => $answer ]
];

echo json_encode($response);