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
    CURLOPT_URL => "https://language.googleapis.com/v1beta2/documents:analyzeEntities?key=AIzaSyByjQCWlKAH_uKFlnN0fCUYduP8sXnjQLo",
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
    return $string;
}

function prilagodiZahtjev($inputText){
    $inputText = str_replace("INTELOV", "INTEL", $inputText);
    $inputText = str_replace("AMDA", "AMD", $inputText);
    
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

$inputText = "Želim Lenovo laptop.";
//$inputText = "Želim grafičku karticu od AMDa.";
//$inputText = "Molim Vas ponudu za Intel procesor.";
//$inputText = "hoću kupiti intelov procesor.";


$input = prilagodiZahtjev(strtoupper($inputText));

$translatedInput = translateInput($input, 'en');

if($translatedInput['status'] == "OK"){
    $nlpText = NLPtext($translatedInput['translate']);
}else{
    echo "Unesena je narudzba na krivom jeziku!";
    exit();
}

$translatedOutput = translateInput($nlpText, 'hr');

if($translatedOutput['status'] == "OK"){
    $translatedOutputText = $translatedOutput['translate'];
}else{
    echo "Doslo je do pogreske!";
    exit();
}

$translated = urediIzlaz($translatedOutputText);

echo "<br/>Kupac pretražuje: " . strtolower_cro($translated);