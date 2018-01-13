<?php

ob_start();
header('Content-Type=> text/html; charset=utf-8');
//hardcoded za sad
$odrediste = 'Marija Bistrica';
//hardcoded za sad
$dostupnosti = [
    ['naziv' => 'Dubrovnik', 'dostupnost' => 'OnRequest'],
    ['naziv' => 'Koprivnica', 'dostupnost' => 'OnRequest'],
    ['naziv' => 'Osijek', 'dostupnost' => 'OnRequest'],
    ['naziv' => 'Pula', 'dostupnost' => 'OnRequest'],
    ['naziv' => 'Rijeka', 'dostupnost' => 'OnRequest'],
    ['naziv' => 'Slavonski Brod', 'dostupnost' => 'OnRequest'],
    ['naziv' => 'Split', 'dostupnost' => 'OnRequest'],
    ['naziv' => 'Šibenik', 'dostupnost' => 'OnRequest'],
    ['naziv' => 'Varaždin', 'dostupnost' => 'OnRequest'],
    ['naziv' => 'Vinkovci', 'dostupnost' => 'OnRequest'],
    ['naziv' => 'Zadar', 'dostupnost' => 'Available'],
    ['naziv' => 'Zagreb Dubrava', 'dostupnost' => 'OnRequest'],
    ['naziv' => 'Zagreb Trešnjevka', 'dostupnost' => 'OnRequest']
];

const API_KEY = 'AIzaSyByjQCWlKAH_uKFlnN0fCUYduP8sXnjQLo';

function dajDostupnaSkladista() {
    global $dostupnosti;
    $skladista = [];
    foreach ($dostupnosti as $redak) {
        if ($redak['dostupnost'] === 'Available') {
            $skladista[] = $redak['naziv'];
        }
    }
    return $skladista;
}

$curl = curl_init();

curl_setopt_array($curl, array(
CURLOPT_URL => 'https://maps.googleapis.com/maps/api/distancematrix/json?key=' . API_KEY . '&origins=' . implode('|', dajDostupnaSkladista()) . '&destinations=' . $odrediste,
CURLOPT_RETURNTRANSFER => true,
CURLOPT_ENCODING => "",
CURLOPT_MAXREDIRS => 10,
CURLOPT_TIMEOUT => 30,
CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
CURLOPT_CUSTOMREQUEST => "GET",
CURLOPT_HTTPHEADER => array(
    "cache-control: no-cache",
    "content-type: application/json"
)
));

$response = curl_exec($curl);
$json = json_decode($response, true);
var_dump($response);
curl_close($curl);

if ($json['status'] === 'OK') {
    $i = 0;
    $najbliziPutTrajanje = 9999999;
    $najbliziPutIndeks = -1;
    foreach ($json['rows'] as $ishodiste) {
        $put = $ishodiste['elements'][0];
        if ($put['status'] === 'OK') {
            $trajanje = $put['duration']['value'];
            if ($trajanje < $najbliziPutTrajanje) {
                $najbliziPut = $trajanje;
                $najbliziPutIndeks = $i;
            }
        }
        $i++;
    }
    if ($najbliziPutIndeks !== -1) {
        echo "Najbliža trgovina se nalazu u mjestu $json[origin_addresses][$najbliziPutIndeks]";
    }
    else {
        echo 'Pojavio se neuspjeh kod pronalaska obližnje trgovine Vašoj lokaciji';
    }
}
else {
    echo 'Pojavio se neuspjeh kod pronalaska obližnje trgovine Vašoj lokaciji';
}

ob_flush();
?>

