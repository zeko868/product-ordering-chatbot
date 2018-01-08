<?php
ob_start();
header('Content-Type: text/html; charset=utf-8');

const API_KEY = 'AIzaSyByjQCWlKAH_uKFlnN0fCUYduP8sXnjQLo';
const TARGET_LANG = 'en';

function translateInput($inputText){
    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => "https://translation.googleapis.com/language/translate/v2?key=" . API_KEY,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => "{\r\n  \"q\": \"$inputText\",\r\n  \"target\": \"" . TARGET_LANG . "\"\r\n}",
    CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache",
        "content-type: application/json"
    ),
    ));

    $response = curl_exec($curl);
    $json = json_decode($response, true);

    curl_close($curl);

    $data = $json["data"]["translations"][0];

    if($data['detectedSourceLanguage'] == 'hr'){
        $returnValue['status'] = "OK";
        $returnValue['translate'] = $data["translatedText"];
    }else{
        $returnValue['status'] = "ERROR";
    }
    
    return $returnValue;
}

$input = "Želim Lenovo laptop.";
$translatedInput = translateInput($input);

