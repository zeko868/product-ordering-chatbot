<?php

const API_KEY = 'AIzaSyByjQCWlKAH_uKFlnN0fCUYduP8sXnjQLo';
const APP_ID = '156070991586244';
const APP_SECRET = '242b83d9eefedcf3e996e8c505e43366';
const ACCESS_TOKEN = 'EAACN8hwDY8QBAEcLkz9b9FZB2QXVgr92ZBduX8cEU1rfZBR7kOtzurRUtiWkZCan496HmhLyiWLnk86RAKsfMSiYKxZBdnIC6KftcZBy7EODHgPBERWpjFZCgqvPYWGUQyutGc76VccANwiCvrPxa9BCO7f3jnbTs2jXjZCzXk06OgZDZD';
include "./interpretirajZahtjev.php";
function get_regex_fullname_with_deviation($str) {
	global $substitutes;
	$str = localized_strtolower($str);
	$str = preg_replace('/(?:ij|(?!adeou)j|i)([aeiou])/', '(ij|i|j)$1', $str);	// furjan=furijan=furian ; mia=mija ; damjan=damian=damijan
	$str = preg_replace('/(\w)\1+/', '$1$1?', $str);	// schatten=schaten
	foreach ($substitutes as $spec => $alt) {
		$str = str_replace($spec, '(' . implode('|', $alt) . "|$spec)", $str);
	}
	$str = '(?=.*\b' . implode('\b)(?=.*\b', explode(' ', $str)) . '\b)(?!.*[^(' . implode(')|(', explode(' ', $str)) . ')| ])';	//  po navedenom bi naziv 'Martina Tomičić Furjan' dao sljedeći izraz (?=.*\bmartina\b)(?=.*\bfurjan\b)(?=.*\btomičić\b)(?!.*[^(martina)|(furjan)|(tomičić)| ])  - navedeno prihvaća 'Martina Tomičić Furjan', 'Martina Tomičić-Furjan', 'tomičić Furjan martina', 'tomičić martina furjan', ...
	return '/^' . $str . '.*$/u';
}

function localized_strtolower($str) {
	if ($str === false) {
		return null;
	}
	global $croatianLowercase;
	foreach ($croatianLowercase as $upper => $lower) {
		$str = str_replace($upper, $lower, $str);
	}
	return strtolower($str);
}

// handle bot's anwser
$input = json_decode(file_get_contents("php://input"), true, 512, JSON_BIGINT_AS_STRING);
$senderId = $input['entry'][0]['messaging'][0]['sender']['id'];
$response = null;
$command = "";

if (!empty($input['entry'][0]['messaging'])) { 

	foreach ($input['entry'][0]['messaging'] as $message) {

		$adresar = json_decode(file_get_contents('adresar.json'), true);
        // When bot receive message from user
        if (!empty($message['message'])) {
			$command = $message['message']['text'];

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
				$ime = $result['first_name'];
				$prezime = $result['last_name'];
				$adresar[$senderId]['first_name'] = $ime;
				$adresar[$senderId]['last_name'] = $prezime;
				file_put_contents('adresar.json', json_encode($adresar));
				$introGuidelines = "Poštovanje  $ime $prezime,\n";
				$upravoDeklariran = true;
			}
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
							if (isset($upravoDeklariran)) {
								$introGuidelines .= 'Za korištenje aplikacije, potrebno je proći kroz 3 koraka konfiguracije. Za početak, navedite Vašu adresu na koju će biti dopremljena roba.';
							}
							else {
								$introGuidelines = 'Molimo Vas da navedete sve komponente adrese koje su nam od značaja poput naziva ulice i kućnog broja te naziva poštanskog mjesta ili njegovog pripadajućeg broja';
							}
						}
					}
					else {
						if (isset($upravoDeklariran)) {
							$introGuidelines .= 'Za korištenje aplikacije, potrebno je proći kroz 3 koraka konfiguracije. Za početak, navedite Vašu adresu na koju će biti dopremljena roba.';
						}
					else {
							$introGuidelines = 'Molimo Vas da precizirate adresu! Naime, ne može se pouzdano otkriti o kojem je točno mjestu riječ';
						}
					}
				}
				else {
					if (isset($upravoDeklariran)) {
						$introGuidelines .= 'Za korištenje aplikacije, potrebno je proći kroz 3 koraka konfiguracije. Za početak, navedite Vašu adresu na koju će biti dopremljena roba.';
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
						if (preg_match('/^\+?\d+(\s+\d+)*$/', $command)) {
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
         // When bot receive button click from user
         else if (!empty($message['postback'])) {
			 $command = $message['postback']['payload'];
			 
			 $commandParts = explode(' ', $command);
			 if(strpos($command,'/hr/') === 0){
				$userInfo = $adresar[$senderId];
				if (count($commandParts) === 1) {	// pretpostavimo na putanja do stranice s artiklom nema razmaka
					break;
				}
				else {	// sadrži i dodatni parametar koji označava način otpremanja robe
					$linkProizovada = $commandParts[0];
					$action = $commandParts[1];
					$delivery = ($action === 'dostava');
					$closestStore = $action;
					$desiredProducts = [ $linkProizovada => 1 ];
					require 'naruciRobu.php';
					replyBackWithSimpleText($answer);
				}
			 }
		}
		
    }
}/*	 	$command = 'konzultacije Petar Šestak -'; $senderId = '1532028376807777';	//for debugging purposes */

/* "server is down" message */

if(strpos($command,'/hr/') === 0){
	$linkProizovada = $command;
	require './provjeraDostupnosti.php';
	$response = [
		'recipient' => [ 'id' => $senderId ],
		'message' => [ ($jestInteraktivan ? 'attachment' : 'text') => $answer ]
	];
}else{
	$input = prilagodiZahtjev(strtoupper($command));

	$translatedInput = translateInput($input, 'en');

	if($translatedInput['status'] == "OK"){
		$nlpText = NLPtext($translatedInput['translate']);
	}else{
		replyBackWithSimpleText('Unesena je narudžba na krivom jeziku!');
	}

	//var_dump($nlpText);

	$translatedOutput = translateInput($nlpText['tekst'], 'hr');

	if($translatedOutput['status'] == "OK"){
		$translatedOutputText = $translatedOutput['translate'];
	}else{
		replyBackWithSimpleText('Došlo je do pogreške!');
	}

	$trans = urediIzlaz($translatedOutputText);
	$nlpText['tekst'] = $trans;
	$translated = $nlpText;
	//echo "<br/>Kupac pretražuje: " . strtolower_cro($translated);

	include "./traziRobu.php";


	if($obj[0] != null){
		$button = array();
		for($i=0;$i<10 && $i < count($obj);$i++){
			array_push($button, array('title'=>htmlentities($obj[$i]->naziv), 'image_url'=>$obj[$i]->slika, 'subtitle' => htmlentities($obj[$i]->naziv) . ", cijena: " . $obj[$i]->cijena, 'buttons' => array(array('type' => 'postback', 'payload' => $obj[$i]->link, 'title' => 'Naruči proizvod'))));
		}

		$answer = [
			'type'=>'template',
			'payload'=>[
				'template_type'=>'generic',
				'elements'=> $button
			]
		];

		$response = [
			'recipient' => [ 'id' => $senderId ],
			'message' => [ 'attachment' => $answer ]
		];
	}else{
		$response = [
			'recipient' => [ 'id' => $senderId ],
			'message' => [ 'text' => $answer ]
		];
	}
	
}

$ch = curl_init('https://graph.facebook.com/v2.6/me/messages?access_token=' . ACCESS_TOKEN);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
if($command != ""){
	$result = curl_exec($ch);
}
curl_close($ch);

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