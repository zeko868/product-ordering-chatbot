<?php

const API_KEY = 'AIzaSyByjQCWlKAH_uKFlnN0fCUYduP8sXnjQLo';
const APP_ID = '156070991586244';
const APP_SECRET = '242b83d9eefedcf3e996e8c505e43366';
const ACCESS_TOKEN = 'EAACN8hwDY8QBAEcLkz9b9FZB2QXVgr92ZBduX8cEU1rfZBR7kOtzurRUtiWkZCan496HmhLyiWLnk86RAKsfMSiYKxZBdnIC6KftcZBy7EODHgPBERWpjFZCgqvPYWGUQyutGc76VccANwiCvrPxa9BCO7f3jnbTs2jXjZCzXk06OgZDZD';
require './interpretirajZahtjev.php';

// handle bot's anwser
$input = json_decode(file_get_contents('php://input'), true, 512, JSON_BIGINT_AS_STRING);
$senderId = $input['entry'][0]['messaging'][0]['sender']['id'];
$response = null;
$command = '';

if (!empty($input['entry'][0]['messaging'])) {
	$message = $input['entry'][0]['messaging'][0];
	$conn = pg_connect(getenv('DATABASE_URL'));
	$result = pg_query("INSERT INTO user_account VALUES ('$senderId');");	// this is performed whether the user id is already in database or not - all the other table attributes are nullable, so their values don't need to be explicitly set
	$introGuidelines = '';
	if (pg_affected_rows($result) === 1) {
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
		pg_query("UPDATE user_account SET first_name='$ime', last_name='$prezime' WHERE id='$senderId';");
		$introGuidelines = "Poštovanje $ime $prezime,\n";
		$korisnikUpravoDeklariran = true;
	}
	$result = pg_query("SELECT * FROM user_account u LEFT JOIN address a ON u.address=a.id WHERE u.id='$senderId' LIMIT 1;");
	$userInfo = pg_fetch_array($result, null, PGSQL_ASSOC);
	pg_free_result($result);
	if (!empty($message['message']['quick_reply']['payload'])) {
		if (empty($userInfo['phone'])) {	// if this attribute is not defined, then user still hasn't finished registration process
			if (!isset($introGuidelines)) {
				$introGuidelines = '';
			}
			$introGuidelines .= 'Za korištenje aplikacije potrebno je proći kroz 3 koraka konfiguracije. Za početak, navedite Vašu adresu na koju će biti dopremljena roba.';
			replyBackWithSimpleText($introGuidelines);
		}
		else {
			$command = $message['message']['quick_reply']['payload'];
			$commandParts = explode(' ', $command);
			$linkProizovada = $commandParts[0];
			unset($commandParts[0]);
			$action = implode(' ', $commandParts);	// lokacije Zagreb Trešnjevka, Zagreb Dubrava i Slavonski Brod se sastoje od više riječi
			$delivery = ($action === 'dostava');
			$closestStore = $action;
			$desiredProducts = [ 'https://www.links.hr' . $linkProizovada => 1 ];
			changeTypingIndicator(true);
			require 'naruciRobu.php';

			if (!empty($ordererOutput)) {
				$ordererOutput = explode("\n", $ordererOutput);
				$price = floatval(str_replace(array('.', ','), array('', '.'), explode(' ', $ordererOutput[0])[0]));
				$placeName = mb_convert_case($city, MB_CASE_TITLE);
				$numOfOutputRows = count($ordererOutput);
				$orderedItems = [];
				for ($i=2; $i<$numOfOutputRows; $i+=2) {
					$productName = $ordererOutput[$i-1];
					$productImageUrl = $ordererOutput[$i];
					$orderedItems[] = ['title'=>substr($productName, 0, 80), 'subtitle'=>substr($productName, 0, 80),'quantity'=>1,'price'=>$price,'currency'=>'HRK','image_url'=>$productImageUrl];
				}
				$answer = [
					'type'=>'template',
					'payload'=>[
						'template_type'=>'receipt',
						'recipient_name'=>"$firstName $lastName",
						'order_number'=>'123456',
						'currency'=>'HRK',
						'payment_method'=>'Plaćanje pouzećem',
						'address'=>['street_1'=>$address,'city'=>$placeName,'postal_code'=>$postCode,'state'=>'Hrvatska','country'=>'CRO'],
						'summary'=>['subtotal'=>0,'shipping_cost'=>0,'total_tax'=>0,'total_cost'=>$price],
						'elements'=> $orderedItems
					]
				];
				
				changeTypingIndicator(false);
				replyBackSpecificObject([ 'attachment' => $answer ]);
			}
			else {
				changeTypingIndicator(false);
				replyBackWithSimpleText($answer);
			}
		}
	}
	else if (!empty($message['message']['text'])) {
		$command = $message['message']['text'];

		if (empty($userInfo['address'])) {
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
						pg_query("UPDATE user_account SET address=get_address_id('$streetNum', '$route', '$postalCode') WHERE id='$senderId';");
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
			if (empty($userInfo['email'])) {
				if (preg_match('/\S*@\S*\.\S*/', $command, $matches)) {
					$email = trim($matches[0], ':.,-;?!');
					pg_query_params("UPDATE user_account SET email=$1 WHERE id='$senderId';", array($email));	// protection against potential attacks like sql-injection
					$introGuidelines = "Uspješno ste registrirali e-mail adresu uz Vaš korisnički račun!\nNavedite još Vaš kontaktni broj telefona";
				}
				else {
					$introGuidelines = 'Niste naveli e-mail adresu ili ona koju ste naveli nije važećeg formata! Molimo, napišite Vašu ispravnu e-mail adresu';
				}
				replyBackWithSimpleText($introGuidelines);
			}
			else {
				if (empty($userInfo['phone'])) {
					if (preg_match_all('/(?:\+\s*)?\d+(?:(?:\s|\/|\-)+\d+)*/', $command, $matches)) {
						foreach ($matches[0] as $numWithSeparators) {
							$number = preg_replace('(-|/|\s)', '', $numWithSeparators);
							if (strlen($number) > 7) {
								pg_query("UPDATE user_account SET phone='$number' WHERE id='$senderId';");
								$introGuidelines = "Uspješno ste registrirali telefonski broj uz Vaš korisnički račun!\nSada možete započeti s pretragom i naručivanjem artikala.";
							}
						}
					}
					else {
						$introGuidelines = 'Niste naveli važeći telefonski broj! Molimo, napišite Vaš ispravni telefonski broj';
					}
					replyBackWithSimpleText($introGuidelines);
				}
			}
		}
	}
	// When bot receives button click from user
	else if (!empty($message['postback'])) {
		if (empty($userInfo['phone'])) {	// if this attribute is not defined, then user still hasn't finished registration process
			if (!isset($introGuidelines)) {
				$introGuidelines = '';
			}
			$introGuidelines .= 'Za korištenje aplikacije potrebno je proći kroz 3 koraka konfiguracije. Za početak, navedite Vašu adresu na koju će biti dopremljena roba.';
			replyBackWithSimpleText($introGuidelines);
		}
		else {
			$command = $message['postback']['payload'];
			if(strpos($command, '/hr/') === 0){
				$linkProizovada = $command;
				require './provjeraDostupnosti.php';
				$answer = [ 'text' => $replyContent ];
				if (!empty($quickReplies)) {
					$answer['quick_replies'] = $quickReplies;
				}
				replyBackSpecificObject($answer);
			}
		}
	}
}

$string = file_get_contents("./intents.json");
$json = json_decode($string, true);


foreach($json as $k => $v){
	if(strpos(strtolower($command),strtolower($k))) {
		if ($k === 'radno vrijeme') {
			require 'radnoVrijeme.php';
			if ($exactOpeningHours) {
				$v = $exactOpeningHours;
			}
		}
		replyBackWithSimpleText($v);
	}
}

$input = prilagodiZahtjev(mb_strtoupper($command));
$translatedInput = translateInput($input, 'hr', 'en');
if($translatedInput['status'] == 'OK'){
	$nlpText = NLPtext($translatedInput['translate']);
}else{
	replyBackWithSimpleText('Unesena je narudžba na krivom jeziku!');
}

$translatedOutput = translateInput($nlpText['tekst'], 'en', 'hr');

if($translatedOutput['status'] == 'OK'){
	$translatedOutputText = $translatedOutput['translate'];
}else{
	replyBackWithSimpleText('Došlo je do pogreške!');
}

$trans = urediIzlaz($translatedOutputText);
$nlpText['tekst'] = $trans;
$translated = $nlpText;

require './traziRobu.php';

if(!empty($obj)){
	$buttons = array();
	$itemsNum = min(10, count($obj));
	for($i=0; $i<$itemsNum; $i++){
		array_push($buttons, array(
			'title' => htmlspecialchars_decode($obj[$i]->naziv, ENT_QUOTES),
			'image_url' => $obj[$i]->slika,
			'subtitle' => htmlspecialchars_decode($obj[$i]->naziv . ', cijena: ' . $obj[$i]->cijena, ENT_QUOTES),
			'default_action' => [
				'type' => 'web_url',
				'url' => 'https://www.links.hr' . $obj[$i]->link . '#quickTabs',
				'messenger_extensions' => true,
				'webview_height_ratio'=> 'TALL'
			],
			'buttons' => array(
				array(
					'type' => 'postback',
					'payload' => $obj[$i]->link,
					'title' => 'Naruči proizvod'
					)
				)
			)
		);
	}

	$answer = [
		'type'=>'template',
		'payload'=>[
			'template_type'=>'generic',
			'elements'=> $buttons
		]
	];

	replyBackSpecificObject([ 'attachment' => $answer ]);
}else{
	replyBackWithSimpleText('Nisu nađeni proizvodi koji odgovaraju zadanim kriterijima');
}


function replyBackSpecificObject($answer) {
	global $senderId;
	pg_close();
	$response = [
		'messaging_type' => 'RESPONSE',
		'recipient' => [ 'id' => $senderId ],
		'message' => $answer
	];
	$ch = curl_init('https://graph.facebook.com/v2.6/me/messages?access_token=' . ACCESS_TOKEN);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	$result = curl_exec($ch);
	curl_close($ch);
	exit();
}

function replyBackWithSimpleText($text) {
	replyBackSpecificObject([ 'text' => $text ]);
}

function changeTypingIndicator($turnOn) {
	global $senderId;
	$ch = curl_init('https://graph.facebook.com/v2.6/me/messages?access_token=' . ACCESS_TOKEN);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(
		[
			'recipient' => [ 'id' => $senderId ],
			'sender_action' => $turnOn ? 'typing_on' : 'typing_off'
		]
	));
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	$result = curl_exec($ch);
	curl_close($ch);
}