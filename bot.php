<?php

const API_KEY = 'AIzaSyByjQCWlKAH_uKFlnN0fCUYduP8sXnjQLo';
const APP_ID = '156070991586244';
const APP_SECRET = '242b83d9eefedcf3e996e8c505e43366';
const ACCESS_TOKEN = 'EAACN8hwDY8QBAEcLkz9b9FZB2QXVgr92ZBduX8cEU1rfZBR7kOtzurRUtiWkZCan496HmhLyiWLnk86RAKsfMSiYKxZBdnIC6KftcZBy7EODHgPBERWpjFZCgqvPYWGUQyutGc76VccANwiCvrPxa9BCO7f3jnbTs2jXjZCzXk06OgZDZD';
require "./interpretirajZahtjev.php";

// handle bot's anwser
$input = json_decode(file_get_contents("php://input"), true, 512, JSON_BIGINT_AS_STRING);
$senderId = $input['entry'][0]['messaging'][0]['sender']['id'];
$response = null;
$command = "";

if (!empty($input['entry'][0]['messaging'])) {
	$message = $input['entry'][0]['messaging'][0];
	$adresar = json_decode(file_get_contents('adresar.json'), true);

	$introGuidelines = '';
	if (!array_key_exists($senderId, $adresar)) {
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL => 'https://graph.facebook.com/v2.8/' . $senderId . '?fields=first_name,last_name&app_secret=' . APP_SECRET . '&access_token=' . ACCESS_TOKEN,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_HTTPHEADER => array(
				'content-type: application/json'
			)
		));
		$result = json_decode(curl_exec($ch), true);
		curl_close($ch);
		$adresar[$senderId]['first_name'] = $ime = $result['first_name'];
		$adresar[$senderId]['last_name'] = $prezime = $result['last_name'];
		file_put_contents('adresar.json', json_encode($adresar));
		$introGuidelines = "Poštovanje $ime $prezime,\n";
		$korisnikUpravoDeklariran = true;
	}
	if (!empty($message['message'])) {
		$command = $message['message']['text'];

		if (!array_key_exists('address', $adresar[$senderId])) {
			$command = urlencode($command);
			$ch = curl_init();
			curl_setopt_array($ch, array(
				CURLOPT_URL => "https://maps.googleapis.com/maps/api/geocode/json?address=$command&key=" . API_KEY,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => 'GET',
				CURLOPT_HTTPHEADER => array(
					'content-type: application/json'
				)
			));
			$result = json_decode(curl_exec($ch), true);
			curl_close($ch);
			if ($result['status'] == 'OK') {
				if (count($result['results']) === 1) {
					foreach ($result['results'][0]['address_components'] as $comp) {
						if (in_array('street_number', $comp['types'])) {
							$streetNum = $comp['short_name'];
						}
						else if (in_array('route', $comp['types'])) {
							$route = $comp['long_name'];
						}
						else if (in_array('postal_code', $comp['types'])) {
							$postalCode = $comp['short_name'];
						}
					}
					if (isset($streetNum) && isset($route) && isset($postalCode)) {
						$adresar[$senderId]['address'] = ['street_number' => $streetNum, 'route' => $route, 'postal_code' => $postalCode];
						file_put_contents('adresar.json', json_encode($adresar));
						$introGuidelines .= "Uspješno ste registrirali adresu uz Vaš korisnički račun!\nNavedite Vašu e-mail adresu na koju ćete biti u mogućnosti kontaktirani";
					}
					else {
						if (isset($korisnikUpravoDeklariran)) {
							$introGuidelines .= 'Za korištenje aplikacije potrebno je proći kroz 3 koraka konfiguracije. Za početak, navedite Vašu adresu na koju će biti dopremljena roba.';
						}
						else {
							$introGuidelines = 'Molimo Vas da navedete sve komponente adrese koje su nam od značaja poput naziva ulice i kućnog broja te naziva poštanskog mjesta ili njegovog pripadajućeg broja';
						}
					}
				}
				else {
					if (isset($korisnikUpravoDeklariran)) {
						$introGuidelines .= 'Za korištenje aplikacije potrebno je proći kroz 3 koraka konfiguracije. Za početak, navedite Vašu adresu na koju će biti dopremljena roba.';
					}
				else {
						$introGuidelines = 'Molimo Vas da precizirate adresu! Naime, ne može se pouzdano otkriti o kojem je točno mjestu riječ';
					}
				}
			}
			else {
				if (isset($korisnikUpravoDeklariran)) {
					$introGuidelines .= 'Za korištenje aplikacije potrebno je proći kroz 3 koraka konfiguracije. Za početak, navedite Vašu adresu na koju će biti dopremljena roba.';
				}
				else {
					$introGuidelines = 'Molimo Vas da precizirate adresu! Naime, nije pronađeno nijedno mjesto koje odgovara na navedeni opis';
				}
			}
			replyBackWithSimpleText($introGuidelines);
		}
		else {
			if (!array_key_exists('email', $adresar[$senderId])) {
				if (preg_match('/^.*@.*\..*$/', $command)) {
					$adresar[$senderId]['email'] = $command;
					file_put_contents('adresar.json', json_encode($adresar));
					$introGuidelines = "Uspješno ste registrirali e-mail adresu uz Vaš korisnički račun!\nNavedite još Vaš kontaktni broj telefona";
				}
				else {
					$introGuidelines = 'E-mail adresa koju ste naveli nije važećeg formata! Molimo, napišite Vašu ispravnu e-mail adresu';
				}
				replyBackWithSimpleText($introGuidelines);
			}
			else {
				if (!array_key_exists('phone', $adresar[$senderId])) {
					if (preg_match('/^\+?\d+((\s|\/|\-)+\d+)*$/', $command)) {
						$adresar[$senderId]['phone'] = $command;
						file_put_contents('adresar.json', json_encode($adresar));
						$introGuidelines = "Uspješno ste registrirali telefonski broj uz Vaš korisnički račun!\nSada možete započeti s pretragom i naručivanjem artikala.";
					}
					else {
						$introGuidelines = 'Tekst koji ste unijeli ne predstavlja važeći telefonski broj! Molimo, napišite Vaš ispravni telefonski broj';
					}
					replyBackWithSimpleText($introGuidelines);
				}
			}
		}
		$userInfo = $adresar[$senderId];
	}
	// When bot receives button click from user
	else if (!empty($message['postback'])) {
		$command = $message['postback']['payload'];
		$userInfo = $adresar[$senderId];
		if (!array_key_exists('phone', $userInfo)) {	// if this attribute is not defined, then user still hasn't finished registration process
			if (!isset($introGuidelines)) {
				$introGuidelines = '';
			}
			$introGuidelines .= 'Za korištenje aplikacije potrebno je proći kroz 3 koraka konfiguracije. Za početak, navedite Vašu adresu na koju će biti dopremljena roba.';
			replyBackWithSimpleText($introGuidelines);
		}
		else {
			$commandParts = explode(' ', $command);
			if(strpos($command, '/hr/') === 0){
				if (count($commandParts) === 1) {	// pretpostavimo na putanja do stranice s artiklom nema razmaka
					$linkProizovada = $command;
					require './provjeraDostupnosti.php';
					$response = [
						'recipient' => [ 'id' => $senderId ],
						'message' => [ ($jestInteraktivan ? 'attachment' : 'text') => $answer ]
					];
					replyBackSpecificObject($response);
				}
				else {	// sadrži i dodatni parametar koji označava način otpremanja robe
					$linkProizovada = $commandParts[0];
					unset($commandParts[0]);
					$action = implode(' ', $commandParts);	// lokacije Zagreb Trešnjevka, Zagreb Dubrava i Slavonski Brod se sastoje od više riječi
					$delivery = ($action === 'dostava');
					$closestStore = $action;
					$desiredProducts = [ 'https://www.links.hr' . $linkProizovada => 1 ];
					require 'naruciRobu.php';

					if (!empty($ordererOutput)) {
						$ordererOutput = explode(PHP_EOL, $ordererOutput);
						$price = floatval(str_replace(',', '.', str_replace('.', '', explode(" ", $ordererOutput[0]))));
						$placeName = mb_convert_case($city, MB_CASE_TITLE);
						unset($ordererOutput[0]);
						$numOfOutputRows = count($ordererOutput);
						for ($i=0; $i<$numOfOutputRows; $i+=2) {
							$productName = $ordererOutput[$i];
							$productImageUrl = $ordererOutput[$i+1];
							$answer = [
								'type'=>'template',
								'payload'=>[
									'template_type'=>'receipt',
									'recipient_name'=>"$firstName $lastName",
									'order_number'=>'123456',
									'currency'=>'HRK',
									'payment_method'=>'Plaćanje pouzećem',
									'address'=>['street_1'=>$address,'city'=>$placeName,'postal_code'=>$postCode,'state'=>'Hrvatska','country'=>"CRO"],
									'summary'=>['subtotal'=>0,'shipping_cost'=>0,'total_tax'=>0,'total_cost'=>$price],
									'elements'=> [['title'=>$productName,'subtitle'=>$productName,'quantity'=>1,'price'=>$price,'currency'=>'HRK','image_url'=>$productImageUrl]]
								]
							];
	
							$response = [
								'recipient' => [ 'id' => $senderId ],
								'message' => [ 'attachment' => $answer ]
							];
							
							if($price === 0){
								replyBackSpecificObject(null);
							}else{
								replyBackSpecificObject($response);
							}
						}
					}
					else {
						replyBackWithSimpleText($answer);
					}
				}
			}
		}
	}
}

$input = prilagodiZahtjev(mb_strtoupper($command));
$translatedInput = translateInput($input, 'en');
if($translatedInput['status'] == 'OK'){
	$nlpText = NLPtext($translatedInput['translate']);
}else{
	replyBackWithSimpleText('Unesena je narudžba na krivom jeziku!');
}

$translatedOutput = translateInput($nlpText['tekst'], 'hr');

if($translatedOutput['status'] == "OK"){
	$translatedOutputText = $translatedOutput['translate'];
}else{
	replyBackWithSimpleText('Došlo je do pogreške!');
}

$trans = urediIzlaz($translatedOutputText);
$nlpText['tekst'] = $trans;
$translated = $nlpText;

require "./traziRobu.php";

if(!empty($obj)){
	$buttons = array();
	$itemsNum = min(10, count($obj));
	for($i=0; $i<$itemsNum; $i++){
		array_push($buttons, array('title'=>htmlentities($obj[$i]->naziv), 'image_url'=>$obj[$i]->slika, 'subtitle' => htmlentities($obj[$i]->naziv) . ", cijena: " . $obj[$i]->cijena, 'buttons' => array(array('type' => 'postback', 'payload' => $obj[$i]->link, 'title' => 'Naruči proizvod'))));
	}

	$answer = [
		'type'=>'template',
		'payload'=>[
			'template_type'=>'generic',
			'elements'=> $buttons
		]
	];

	$response = [
		'recipient' => [ 'id' => $senderId ],
		'message' => [ 'attachment' => $answer ]
	];
	replyBackSpecificObject($response);
}else{
	replyBackWithSimpleText('Nisu nađeni proizvodi koji odgovaraju zadanim kriterijima');
}


function replyBackSpecificObject($response) {
	global $command;
	$ch = curl_init('https://graph.facebook.com/v2.6/me/messages?access_token=' . ACCESS_TOKEN);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	if($command != ""){
		$result = curl_exec($ch);
	}
	curl_close($ch);
	exit();
}

function replyBackWithSimpleText($text) {
	global $command;
	global $senderId;
	$response = [
		'recipient' => [ 'id' => $senderId ],
		'message' => [ 'text' => $text ]
	];
	$ch = curl_init('https://graph.facebook.com/v2.6/me/messages?access_token=' . ACCESS_TOKEN);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	if($command != ""){
		$result = curl_exec($ch);
	}
	curl_close($ch);
	exit();
}