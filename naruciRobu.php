<?php

$firstName = $userInfo['first_name'];
$lastName = $userInfo['last_name'];
$email = $userInfo['email'];
$address = $userInfo['route'] . ' ' . $userInfo['street_number'];
$postCode = $userInfo['postal_code'];
$phoneNum = $userInfo['phone'];
/*  // hardkodirano za testiranje
$desiredProducts = [
    'https://www.links.hr/hr/tipkovnica-mis-tt-esports-commander-gaming-gear-combo-us-layout-crna-usb-101600137' => 2,    // key represents url where the product is located and value represents its quantity
    'https://www.links.hr/hr/mp3-player-trekstor-i-beat-jump-bt-8-gb-1-8-tft-bt-pedometar-microsd-crni-350600072' => 1
];
$delivery = true;
*/

if (empty($firstName) || empty($lastName) || empty($email) || empty($address) || empty($postCode) || empty($phoneNum) || empty($desiredProducts) || min(array_values($desiredProducts)) < 1 || !isset($delivery) || (!$delivery && empty($closestStore))) {
    $answer = 'Nisu uneseni svi podaci potrebni za izvršenje narudžbe!';
    return;
}

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => "https://www.links.hr/Customer/SearchCityPostCode?term=$postCode",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        'content-type: application/json'
    )
));

$response = curl_exec($curl);
$json = json_decode($response, true);
curl_close($curl);

if (empty($json)) {
    $answer = "Nije pronađeno nijedno mjesto s poštanskim brojem $postCode na stranicama dobavljača";
}
else {
    $infos = $json[0]['info'];
    $countryId = $infos['countryId'];
    $postCode = $infos['postCode'];
    $city = $infos['city'];
    
    $args = [$firstName, $lastName, $email, $address, $postCode, $city, $countryId, $phoneNum, $closestStore];
    foreach ($desiredProducts as $productUrl => $quantity) {
        $args[] = $productUrl;
        $args[] = $quantity;
    }
    $argNum = count($args);
    for ($i=0; $i<$argNum; $i++) {
        $args[$i] = escapeshellarg($args[$i]);
    }
    $ordererOutput = shell_exec('java -jar ~/orderer/dist/orderer.jar ' . implode(' ', $args));
}

?>