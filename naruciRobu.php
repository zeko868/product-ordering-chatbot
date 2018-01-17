<?php

ob_start();
header('Content-Type=> text/html; charset=utf-8');

$firstName = $userInfo['first_name'];
$lastName = $userInfo['last_name'];
$email = $userInfo['email'];
$address = "$userInfo[route] $userInfo[street_number]";
$postCode = $userInfo['postal_code'];  // optional if $city is specified
//$city = 'Marija Bistrica';      // optional if $postCode is specified
$phoneNum = $userInfo['phone'];
/*  // hardkodirano za testiranje
$desiredProducts = [
    'https://www.links.hr/hr/tipkovnica-mis-tt-esports-commander-gaming-gear-combo-us-layout-crna-usb-101600137' => 2,    // key represents url where the product is located and value represents its quantity
    'https://www.links.hr/hr/mp3-player-trekstor-i-beat-jump-bt-8-gb-1-8-tft-bt-pedometar-microsd-crni-350600072' => 1
];
$delivery = true;
*/

if (empty($firstName) || empty($lastName) || empty($email) || empty($address) || (empty($postCode) && empty($city)) || empty($phoneNum) || empty($desiredProducts) || min(array_values($desiredProducts)) < 1 || !isset($delivery) || (!$delivery && empty($closestStore))) {
    echo 'Nisu uneseni svi podaci potrebni za izvršenje narudžbe!';
    return;
}

if (isset($postCode)) {
    $url = "https://www.links.hr/Customer/SearchCityPostCode?term=$postCode";
}
else {
    $url = "https://www.links.hr/Customer/SearchCity?term=$city";
}
$curl = curl_init();

curl_setopt_array($curl, array(
CURLOPT_URL => $url,
CURLOPT_RETURNTRANSFER => true,
CURLOPT_CUSTOMREQUEST => 'GET',
CURLOPT_HTTPHEADER => array(
    'content-type: application/json'
)
));

$response = curl_exec($curl);
$json = json_decode($response, true);
curl_close($curl);

switch (count($json)) {
    case 1:
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
        $output = shell_exec('java -jar ~/orderer/dist/orderer.jar ' . implode(' ', $args));
        echo $output;
        break;
    case 0:
        if (isset($postCode)) {
            echo "Nije pronađeno nijedno mjesto s poštanskim brojem $postCode";
        }
        else {
            echo "Za mjesto '$city' se ne može pronaći pripadajući poštanski broj!";
        }
        break;
    default:
        echo "Za navedeni naziv mjesta '$city' je nejasno koji mu je točno poštanski broj. Molimo, navedite puni naziv mjesta ili navedite poštanski broj";
}

ob_flush();

?>