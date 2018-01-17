<?php

ob_start();
header('Content-Type=> text/html; charset=utf-8');
//hardcoded za sad
//$odrediste = 'Marija Bistrica';
$odrediste = $userInfo['address']['postal_code'] . ' ' . $userInfo['address']['route'] . ' ' . $userInfo['address']['street_number'];
//hardcoded za sad
/*
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
    ['naziv' => 'Zagreb Trešnjevka', 'dostupnost' => 'OnRequest'],
    ['naziv' => 'Varaždin', 'dostupnost' => 'Available'],
    ['naziv' => 'Vinkovci', 'dostupnost' => 'OnRequest'],
    ['naziv' => 'Zadar', 'dostupnost' => 'OnRequest'],
    ['naziv' => 'Zagreb Dubrava', 'dostupnost' => 'Available'],
    ['naziv' => 'Zagreb Trešnjevka', 'dostupnost' => 'Available']
];
*/

function dajDostupnaSkladista() {
    global $dostupnosti;
    $skladista = [];
    foreach ($dostupnosti as $redak) {
        if ($redak['dostupnost'] === 'Available') {
            $skladista[] = urlencode($redak['naziv']);
        }
    }
    return $skladista;
}

$curl = curl_init();

curl_setopt_array($curl, array(
CURLOPT_URL => 'https://maps.googleapis.com/maps/api/distancematrix/json?key=' . API_KEY . '&origins=' . urlencode(implode('|', dajDostupnaSkladista())) . '&destinations=' . urlencode($odrediste),
CURLOPT_RETURNTRANSFER => true,
CURLOPT_CUSTOMREQUEST => "GET",
CURLOPT_HTTPHEADER => array(
    "content-type: application/json"
)
));

$response = curl_exec($curl);
$json = json_decode($response, true);
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
        $najblizeIshodiste = $json['origin_addresses'][$najbliziPutIndeks];
    }
    else {
        $najblizeIshodiste = false;
    }
}
else {
    $najblizeIshodiste = null;
}

ob_flush();
?>

