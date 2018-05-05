<?php

function translateInput($inputText, $source, $target, $changeCase = true){
    if ($changeCase) {
        $inputText = mb_strtolower($inputText);
    }
    $curl = curl_init();
    if(strpos($inputText, 'kn') !== false){
        $inputText = str_replace('kn', 'KN', $inputText);
    }


    curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://translation.googleapis.com/language/translate/v2?key=' . API_KEY,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode(
        [
            'q' => $inputText,
            'target' => $target,
            'source' => $source
        ],
        JSON_FORCE_OBJECT
    ),
    CURLOPT_HTTPHEADER => array(
        'cache-control: no-cache',
        'content-type: application/json'
        ),
    ));

    $response = curl_exec($curl);
    $json = json_decode($response, true); 

    curl_close($curl);

    $data = $json['data']['translations'][0];
    if($data){
        $returnValue['status'] = 'OK';
        if ($changeCase) {
            $returnValue['translate'] = mb_strtoupper(htmlspecialchars_decode($data['translatedText'], ENT_QUOTES));
        }
        else {
            $returnValue['translate'] = $data['translatedText'];
        }
    }else{
        $returnValue['status'] = 'ERROR';
    }
    
    return $returnValue;
}

function NLPtext($translatedText){
	
	
    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://language.googleapis.com/v1beta2/documents:analyzeEntities?key=' . API_KEY,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode(
        [
            'document' => [
                'type' => 'plain_text',
                'language' => 'en',
                'content' => $translatedText
            ],
            'encodingType' => 'UTF8'
        ],
        JSON_FORCE_OBJECT
    ),
    CURLOPT_HTTPHEADER => array(
        'cache-control: no-cache',
        'content-type: application/json'
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    
    $json = json_decode($response, true);
    $data = $json['entities'];

    foreach($data as $value){
        foreach($value as $key => $v){
            if($key === 'type'){
                switch($v){
                    case 'ORGANIZATION':
                        $nlp['proizvodac'] = $value['name'];
                        break;
                    case 'CONSUMER_GOOD':
                        $nlp['proizvod'] = $value['name'];
                        break;
                }
            }
            
        }
    }
	
	/*if(!isset($nlp['proizvodac'])){
		$string = file_get_contents("./producers.json");
		$json = json_decode($string, true);


		foreach($json as $p){
			
			if(strpos($translatedText , strtolower($p))){
				$nlp['proizvodac'] = $p;
				break;
			}
		}
	}*/
	
	$string = file_get_contents("./producers.json");
	$json = json_decode($string, true);


	foreach($json as $p){
		
		if(strpos($translatedText , strtolower($p))){
			$nlp['proizvodac'] = $p;
			break;
		}
	}
	
	$string = file_get_contents("./components.json");
	$json = json_decode($string, true);


	foreach($json as $p){
		if(strpos($translatedText , strtolower($p))){
			$nlp['proizvod'] = $p;
			break;
		}
	}
    
    $string = '';

    if(isset($nlp['proizvodac']))
        $string .= $nlp['proizvodac'] . ' ';
    if(isset($nlp['proizvod']))
        $string .= $nlp['proizvod'];
    

    if(!isset($nlp['proizvodac']) && !isset($nlp['proizvod'])){
        echo 'Doslo je do pogreske!';
        exit();
    }

    $curl = curl_init();
    
    $ostalo = array();

    curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://language.googleapis.com/v1/documents:analyzeSyntax?key=' . API_KEY,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode(
        [
            'document' => [
                'type' => 'plain_text',
                'language' => 'en',
                'content' => $translatedText
            ],
            'encodingType' => 'UTF8'
        ],
        JSON_FORCE_OBJECT
    ),
    CURLOPT_HTTPHEADER => array(
        'cache-control: no-cache',
        'content-type: application/json'
        ),
    ));
    
    $response = curl_exec($curl);
    
    $json = json_decode($response,true);
    $data = $json['tokens'];

    $ost = array();

    for($i = 0; $i < sizeof($data); $i++){
        if(strpos($string, $data[$i]['text']['content'])===false && $data[$i]['partOfSpeech']['tag'] === 'NOUN'){
            array_push($ost,translateInput($data[$i]['text']['content'],'en','hr')['translate']);
        }
        $ostalo['ostaliFilteri'] = $ost;
        if($data[$i]['partOfSpeech']['tag'] == 'NUM' && strpos($string, $data[$i]['text']['content'])===false){
            for($j = $i; $j >= 0; $j--){
                if(($data[$j]['partOfSpeech']['tag'] === 'ADJ' || $data[$j]['partOfSpeech']['tag'] === 'ADP') && ($data[$j]['text']['content'] === 'OVER' || $data[$j]['text']['content'] === 'MORE')){
                    $ostalo['cijenaOd'] = intval($data[$i]['text']['content']);
                    break;
                }
                if(($data[$j]['partOfSpeech']['tag'] === 'ADJ' || $data[$j]['partOfSpeech']['tag'] === 'ADP') && $data[$j]['text']['content'] === 'LESS'){
                    $ostalo['cijenaDo'] = intval($data[$i]['text']['content']);
                    break;
                }
            }
        }else if($data[$i]['partOfSpeech']['tag'] === 'ADP' && $data[$i]['text']['content'] === 'BETWEEN'){
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
    $json_params = file_get_contents('./promjeneUZahtjevu.json');

    $json = json_decode($json_params, true);

    
    foreach($json as $val){
        $inputText = str_replace($val['original'], $val['promjena'], $inputText);
    }    
    
    if(($pozicijaProcesora = strpos($inputText, 'PROCESOR'))!==false && strpos($inputText, 'RAČUNALNI')===false){
        $input = substr($inputText, 0, $pozicijaProcesora) . 'RAČUNALNI ' . substr($inputText, $pozicijaProcesora, strlen($inputText));
        return $input;
    }
    
    return $inputText;
}

function urediIzlaz($inputText){
    $inputText = mb_strtolower($inputText);
    if(($pozicijaRacunalnog = strpos($inputText, 'računalni')) !== false){
        $input = substr($inputText, 0, $pozicijaRacunalnog) . substr($inputText, $pozicijaRacunalnog + 11, strlen($inputText));
        return $input;
    }
    return $inputText;
}

?>