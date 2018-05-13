<?php

const API_KEY = 'AIzaSyByjQCWlKAH_uKFlnN0fCUYduP8sXnjQLo';
const APP_ID = '156070991586244';
const APP_SECRET = '242b83d9eefedcf3e996e8c505e43366';
const ACCESS_TOKEN = 'EAACN8hwDY8QBAEcLkz9b9FZB2QXVgr92ZBduX8cEU1rfZBR7kOtzurRUtiWkZCan496HmhLyiWLnk86RAKsfMSiYKxZBdnIC6KftcZBy7EODHgPBERWpjFZCgqvPYWGUQyutGc76VccANwiCvrPxa9BCO7f3jnbTs2jXjZCzXk06OgZDZD';
require './interpretirajZahtjev.php';

// handle user message
//	$input = ['entry'=>[['messaging'=>[['sender'=>['id'=>'1532028376807777'], 'message'=>['text'=>'Zainteresiran sam za kupnju Logitechovog miša G203']]]]]];	// for debugging purposes
$input = json_decode(file_get_contents('php://input'), true, 512, JSON_BIGINT_AS_STRING);
$senderId = $input['entry'][0]['messaging'][0]['sender']['id'];
$response = null;
$command = '';

if ($messageInfo = $input['entry'][0]['messaging'][0]) {
	$conn = pg_connect('postgres://gsnnkdcbycpcyq:ba69093c4619187587610e80e188d4f812627530798ef14d3133bd3541b00290@ec2-54-228-235-185.eu-west-1.compute.amazonaws.com:5432/dedt0mj008catq');
	$result = pg_query("INSERT INTO user_account VALUES ('$senderId');");	// this is performed whether the user id is already in database or not - all the other table attributes are nullable, so their values don't need to be explicitly set
	if ($result && pg_affected_rows($result) === 1) {
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
		pg_query_params("UPDATE user_account SET first_name=$1, last_name=$2 WHERE id=$3;", array($ime, $prezime, $senderId));
		replyBackWithSimpleText("Poštovanje $ime $prezime,\nRazgovarate s virtualnim asistentom koji će Vas voditi kroz kupovinu. Dovoljno je navesti u slobodnom formatu (po mogućnosti u službenom hrvatskom jeziku) što tražite, bilo naziv proizvođača, marke, vrste komponente i/ili cjenovni raspon traženog proizvoda. Kao rezultat se vraćaju stavke dostupne iz Linskovog web-shopa koje je onda moguće jednostavno naručiti.", false);
		$korisnikUpravoDeklariran = true;
		$mysqlDbHandler = new mysqli('chatbot-ordering.com', 'heroku', getenv('MYSQL_PW_ON_MAILSERVER'), 'vmail');
		$mysqlDbHandler->multi_query("INSERT INTO alias (address, domain, active) VALUES ('$senderId@chatbot-ordering.com', 'chatbot-ordering.com', 1);INSERT INTO forwardings (address, forwarding, domain, dest_domain, is_list, active) VALUES ('$senderId@chatbot-ordering.com', 'postmaster@chatbot-ordering.com', 'chatbot-ordering.com', 'chatbot-ordering.com', 1, 0), ('$senderId@chatbot-ordering.com', '', 'chatbot-ordering.com', '', 1, 0);");
		$mysqlDbHandler->close();
	}
	$result = pg_query("SELECT * FROM user_account u LEFT JOIN address a ON u.address=a.id WHERE u.id='$senderId' LIMIT 1;");
	$userInfo = pg_fetch_array($result, null, PGSQL_ASSOC);
	pg_free_result($result);
	$userAlreadyRegistered = !empty($userInfo['phone']);
	$expectedValueType = $userInfo['currently_edited_attribute'];
	if (!empty($messageInfo['message']['attachments'])) {
		if ($messageInfo['message']['attachments'][0]['type'] === 'location') {
			$coordinates = $messageInfo['message']['attachments'][0]['payload']['coordinates'];
			if (!empty($coordinates)) {
				$ch = curl_init();
				curl_setopt_array($ch, array(
					CURLOPT_URL => "https://maps.googleapis.com/maps/api/geocode/json?latlng=$coordinates[lat],$coordinates[long]&key=" . API_KEY,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_CUSTOMREQUEST => 'GET',
					CURLOPT_HTTPHEADER => array(
						'content-type: application/json'
					)
				));
				$result = json_decode(curl_exec($ch), true);
				curl_close($ch);
				if ($result['status'] === 'OK') {
					if (count($result['results']) !== 0) {
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
					}
					else {
						replyBackWithSimpleText('address', true, 'Za definiranu lokaciju nije moguće odrediti ulicu, kućni i poštanski broj. Pokušajte navesti te geografske podatke ili pak odabrati neku drugu lokaciju');
					}
				}
				$q = 'UPDATE user_account SET address=get_address_id($1, $2, $3), currently_edited_attribute=$4 WHERE id=$5;';
				$params = [$streetNum, $route, $postalCode, 'subscribe', $senderId];
				if ($userAlreadyRegistered) {
					$params[3] = null;
					pg_query_params($q, $params);
					replyBackWithSimpleText("Uspješno ste uz Vaš korisnički račun povezali adresu '$route $streetNum' s poštanskim brojem $postalCode.");
				}
				else {
					pg_query_params($q, $params);
					posaljiZahtjevZaOdabirom($params[3], false, "Uspješno ste uz Vaš korisnički račun registrirali adresu '$route $streetNum' s poštanskim brojem $postalCode.");
				}
			}
		}
		else {
			replyBackWithSimpleText('Privitke poput slika, video-zapisa i audio-sadržaja nije moguće analizirati! Molimo Vas da se izrazite tekstualno.');
		}
	}
	else if (!empty($messageInfo['notification'])) {	// vendor's notification related to completed orders
		$notification = $messageInfo['notification'];
		replyBackWithSimpleText(<<<EOS
*Naslov:* $notification[subject]
*Pošiljatelj:* $notification[mail]
*Sadržaj:*
$notification[content]
EOS
		);
	}
	else if (!empty($messageInfo['message']['quick_reply']['payload'])) {
		$command = $messageInfo['message']['quick_reply']['payload'];
		if (empty($expectedValueType)) {
			/*$commandParts = explode(' ', $command);
			$linkProizovada = $commandParts[0];
			unset($commandParts[0]);
			$action = implode(' ', $commandParts);	// lokacije Zagreb Trešnjevka, Zagreb Dubrava i Slavonski Brod se sastoje od više riječi
			$delivery = ($action === 'dostava');
			$closestStore = $action;
			$desiredProducts = [ 'https://www.links.hr' . $linkProizovada => 1 ];
			changeTypingIndicator(true);
			require 'naruciRobu.php';

			if (!empty($ordererOutput)) {
				addItemInBasket("$senderId.txt","links.hr\n");
				$ordererOutput = explode("\n", $ordererOutput);
				$price = floatval(str_replace(array('.', ','), array('', '.'), explode(' ', $ordererOutput[0])[0]));
				$placeName = mb_convert_case($city, MB_CASE_TITLE);
				$numOfOutputRows = count($ordererOutput);
				$orderedItems = [];
				for ($i=2; $i<$numOfOutputRows; $i+=2) {
					$productName = $ordererOutput[$i-1];
					$productImageUrl = $ordererOutput[$i];
					extractTitleAndSubtitle($productName, $title, $subtitle);
					$orderedItems[] = ['title'=>$title, 'subtitle'=>$subtitle,'quantity'=>1,'price'=>$price,'currency'=>'HRK','image_url'=>$productImageUrl];
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
			}*/
			
			$myfile = fopen("$senderId.txt", "r") or die("Unable to open file!");
			$fileContent = fread($myfile,filesize("$senderId.txt"));
			fclose($myfile);

			$fileContentArray = explode("\n", $fileContent);

			$desiredProducts = [];

			foreach($fileContentArray as $link){
				if(!empty($link)){
					$desiredProducts["https://www.links.hr$link"] = 1;
				}
			}
			
			$action = $command;	// lokacije Zagreb Trešnjevka, Zagreb Dubrava i Slavonski Brod se sastoje od više riječi
			$delivery = ($action === 'dostava');
			$closestStore = $action;
			changeTypingIndicator(true);
			require 'naruciRobu.php';

			if (!empty($ordererOutput)) {
				$id = rand (10000, 99999);
				addItemInBasket("$senderId.txt","links.hr\n");
				$ordererOutput = explode("\n", $ordererOutput);
				$price = floatval(str_replace(array('.', ','), array('', '.'), explode(' ', $ordererOutput[0])[0]));
				$placeName = mb_convert_case($city, MB_CASE_TITLE);
				$numOfOutputRows = count($ordererOutput);
				$orderedItems = [];
				for ($i=3; $i<$numOfOutputRows; $i+=3) {
					$productName = $ordererOutput[$i-2];
					$productImageUrl = $ordererOutput[$i-1];
					extractTitleAndSubtitle($productName, $title, $subtitle);
					$orderedItems[] = ['title'=>$title, 'subtitle'=>$subtitle,'quantity'=>1,'price'=>$price,'currency'=>'HRK','image_url'=>$productImageUrl];
				}
				$answer = [
					'type'=>'template',
					'payload'=>[
						'template_type'=>'receipt',
						'recipient_name'=>"$firstName $lastName",
						'order_number'=>(string)$id,
						'currency'=>'HRK',
						'payment_method'=>'Plaćanje pouzećem',
						'address'=>['street_1'=>$address,'city'=>$placeName,'postal_code'=>$postCode,'state'=>'Hrvatska','country'=>'CRO'],
						'summary'=>['subtotal'=>0,'shipping_cost'=>0,'total_tax'=>0,'total_cost'=>$price],
						'elements'=> $orderedItems
					]
				];
				
				changeTypingIndicator(false);
				//open file to write
				$fp = fopen("$senderId.txt", "r+");
				// clear content to 0 bits
				ftruncate($fp, 0);
				//close file
				fclose($fp);
				replyBackSpecificObject([ 'attachment' => $answer ]);
			}
		}
		else {
			switch ($command) {
				case 'preserve last_name':
					pg_query("UPDATE user_account SET currently_edited_attribute='first_name' WHERE id='$senderId';");
					posaljiZahtjevZaOdabirom('first_name');
					break;
				case 'preserve first_name':
					if ($userAlreadyRegistered) {
						pg_query("UPDATE user_account SET currently_edited_attribute=NULL WHERE id='$senderId';");
						replyBackWithSimpleText('Uspješno ste ažurirali svoje stvarno puno ime!');
					}
					else {
						pg_query("UPDATE user_account SET currently_edited_attribute='address' WHERE id='$senderId';");
						posaljiZahtjevZaOdabirom('address', false, 'Uspješno ste registrirali svoje puno ime!');
					}
					break;
				case 'preserve address':
					if ($userAlreadyRegistered) {
						pg_query("UPDATE user_account SET currently_edited_attribute=NULL WHERE id='$senderId';");
						replyBackWithSimpleText('Uspješno ste ažurirali svoju geografsku adresu!');
					}
					else {
						pg_query("UPDATE user_account SET currently_edited_attribute='subscribe' WHERE id='$senderId';");
						posaljiZahtjevZaOdabirom('subscribe');
					}
					break;
				case 'preserve subscription':
					switch ($userInfo['subscribe']) {
						case 'email':
						case 'both':
							$nextStep = 'email';
							break;
						default:
							if ($userAlreadyRegistered) {
								$nextStep = null;
							}
							else {
								$nextStep = 'phone';
							}
					}
					pg_query_params('UPDATE user_account SET currently_edited_attribute=$1 WHERE id=$2;', array($nextStep, $senderId));
					if ($nextStep !== 'email') {
						replyBackWithSimpleText('Uspješno je pohranjen odabir vezan uz način primanja obavijesti o narudžbama!');
					}
					else {
						posaljiZahtjevZaOdabirom('email');
					}
					break;
				case 'subscribe email':
					$params = ['email', 'email', $senderId];
					pg_query_params('UPDATE user_account SET currently_edited_attribute=$1, subscribe=$2 WHERE id=$3;', $params);
					$mysqlDbHandler = new mysqli('chatbot-ordering.com', 'heroku', getenv('MYSQL_PW_ON_MAILSERVER'), 'vmail');
					$mysqlDbHandler->multi_query("UPDATE forwardings SET active=0 WHERE address='$senderId@chatbot-ordering.com' AND forwarding='postmaster@chatbot-ordering.com';UPDATE forwardings SET active=1 WHERE address='$senderId@chatbot-ordering.com' AND forwarding<>'postmaster@chatbot-ordering.com';");
					$mysqlDbHandler->close();
					posaljiZahtjevZaOdabirom($params[0]);
					break;
				case 'subscribe messenger':
					$params = [$userAlreadyRegistered ? null : 'phone', 'messenger', $senderId];
					pg_query_params('UPDATE user_account SET currently_edited_attribute=$1, subscribe=$2 WHERE id=$3;', $params);
					$mysqlDbHandler = new mysqli('chatbot-ordering.com', 'heroku', getenv('MYSQL_PW_ON_MAILSERVER'), 'vmail');
					$mysqlDbHandler->multi_query("UPDATE forwardings SET active=1 WHERE address='$senderId@chatbot-ordering.com' AND forwarding='postmaster@chatbot-ordering.com';UPDATE forwardings SET active=0 WHERE address='$senderId@chatbot-ordering.com' AND forwarding<>'postmaster@chatbot-ordering.com';");					$mysqlDbHandler->close();
					if ($userAlreadyRegistered) {
						replyBackWithSimpleText('Uspješno je pohranjen odabir vezan uz način primanja obavijesti o narudžbama!');
					}
					else {
						posaljiZahtjevZaOdabirom($params[0]);
					}
					break;
				case 'subscribe both':
					$params = ['email', 'both', $senderId];
					pg_query_params('UPDATE user_account SET currently_edited_attribute=$1, subscribe=$2 WHERE id=$3;', $params);
					$mysqlDbHandler = new mysqli('chatbot-ordering.com', 'heroku', getenv('MYSQL_PW_ON_MAILSERVER'), 'vmail');
					$mysqlDbHandler->query("UPDATE forwardings SET active=1 WHERE address='$senderId@chatbot-ordering.com';");
					$mysqlDbHandler->close();
					posaljiZahtjevZaOdabirom($params[0]);
					break;
				case 'preserve email':
					if ($userAlreadyRegistered) {
						pg_query("UPDATE user_account SET currently_edited_attribute=NULL WHERE id='$senderId';");
						replyBackWithSimpleText('Uspješno ste ažurirali svoju e-mail adresu!');
					}
					else {
						pg_query("UPDATE user_account SET currently_edited_attribute='phone' WHERE id='$senderId';");
						posaljiZahtjevZaOdabirom('phone');
					}
					break;
				case 'preserve phone':
					pg_query("UPDATE user_account SET currently_edited_attribute=NULL WHERE id='$senderId';");
					if ($userAlreadyRegistered) {
						replyBackWithSimpleText('Uspješno ste ažurirali svoj telefonski broj!');
					}
					else {
						replyBackWithSimpleText('Uspješno ste registraciju priveli kraju. Možete dalje nastaviti normalno koristiti pogodnosti chatbota!');
					}
					break;
				case 'change full_name':
					pg_query("UPDATE user_account SET currently_edited_attribute='last_name' WHERE id='$senderId';");
					posaljiZahtjevZaOdabirom('last_name');
					break;
				case 'preserve full_name':
					pg_query("UPDATE user_account SET currently_edited_attribute='address' WHERE id='$senderId';");
					posaljiZahtjevZaOdabirom('address');
					break;
				default:	// for handling payload data from selected special quick reply controls that read user's e-mail address or phone number from user's profile
					$params = [null, $command, $senderId];
					if (strpos($command, '@') !== false) {
						notifyMXServerAboutChangedEmailAddress($command);
						$q = 'UPDATE user_account SET currently_edited_attribute=$1, email=$2 WHERE id=$3;';
						if ($userAlreadyRegistered) {
							pg_query_params($q, $params);
							replyBackWithSimpleText('Uspješno ste ažurirali svoju e-mail adresu!');
						}
						else {
							$params[0] = 'phone';
							pg_query_params($q, $params);
							posaljiZahtjevZaOdabirom('phone');
						}
					}
					else {
						$q = 'UPDATE user_account SET currently_edited_attribute=$1, phone=$2 WHERE id=$3;';
						if ($userAlreadyRegistered) {
							pg_query_params($q, $params);
							replyBackWithSimpleText('Uspješno ste ažurirali svoj telefonski broj!');
						}
						else {
							pg_query_params($q, $params);
							replyBackWithSimpleText('Uspješno ste registraciju priveli kraju. Možete dalje nastaviti normalno koristiti pogodnosti chatbota!');
						}
					}
			}
		}
	}else if (!empty($messageInfo['message']['text'])) {
		$command = $messageInfo['message']['text'];

		if (!empty($expectedValueType)) {
			switch ($expectedValueType) {
				case 'last_name':
					$lastName = trim($command, ". \t\n\r\0\x0B");
					if (!empty($lastName)) {
						$params = [$lastName, 'first_name', $senderId];
						pg_query_params('UPDATE user_account SET last_name=$1, currently_edited_attribute=$2 WHERE id=$3;', $params);
						posaljiZahtjevZaOdabirom($params[1]);
					}
					else {
						posaljiZahtjevZaOdabirom($expectedValueType, true, 'Prezime ne može biti neprazno jer je očito nestvarno!');
					}
					break;
				case 'first_name':
					$firstName = trim($command, ". \t\n\r\0\x0B");
					if (!empty($firstName)) {
						$q = 'UPDATE user_account SET first_name=$1, currently_edited_attribute=$2 WHERE id=$3;';
						$params = [$firstName, 'address', $senderId];
						if ($userAlreadyRegistered) {
							$params[1] = null;
							pg_query_params($q, $params);
							replyBackWithSimpleText('Uspješno ste ažurirali svoje stvarno puno ime!');
						}
						else {
							pg_query_params($q, $params);
							posaljiZahtjevZaOdabirom($params[1], false, 'Uspješno ste registrirali svoje stvarno puno ime!');
						}
					}
					else {
						posaljiZahtjevZaOdabirom($expectedValueType, true, 'Ime ne može biti neprazno jer je očito nestvarno!');
					}
					break;
				case 'address':
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
					if ($result['status'] === 'OK') {
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
								$q = 'UPDATE user_account SET address=get_address_id($1, $2, $3), currently_edited_attribute=$4 WHERE id=$5;';
								$params = [$streetNum, $route, $postalCode, 'subscribe', $senderId];
								if ($userAlreadyRegistered) {
									$params[3] = null;
									pg_query_params($q, $params);
									replyBackWithSimpleText('Uspješno ste ažurirali Vašu adresu');
								}
								else {
									pg_query_params($q, $params);
									posaljiZahtjevZaOdabirom($params[3], false, "Uspješno ste uz Vaš korisnički račun registrirali adresu '$route $streetNum' s poštanskim brojem $postalCode.");
								}
							}
							else {
								posaljiZahtjevZaOdabirom($expectedValueType, true, 'Molimo Vas da navedete sve komponente adrese koje su nam od značaja poput naziva ulice i kućnog broja te naziva poštanskog mjesta ili njegovog pripadajućeg broja.');
							}
						}
						else {
							posaljiZahtjevZaOdabirom($expectedValueType, true, 'Molimo Vas da precizirate adresu! Naime, ne može se pouzdano otkriti o kojem je točno mjestu riječ.');
						}
					}
					else {
						posaljiZahtjevZaOdabirom($expectedValueType, true, 'Molimo Vas da precizirate adresu! Naime, nije pronađeno nijedno mjesto koje odgovara na navedeni opis.');
					}
					break;
				case 'email':
					if (preg_match('/\S*@\S*\.\S*/', $command, $matches)) {
						$email = trim($matches[0], ':.,-;?!');
						$q = 'UPDATE user_account SET email=$1, currently_edited_attribute=$2 WHERE id=$3;';
						notifyMXServerAboutChangedEmailAddress($email);
						$params = [$email, 'phone', $senderId];
						if ($userAlreadyRegistered) {
							$params[1] = null;
							pg_query_params($q, $params);	// protection against potential attacks like sql-injection
							replyBackWithSimpleText('Uspješno ste ažurirali e-mail adresu Vašeg korisničkog računa');
						}
						else {
							pg_query_params($q, $params);	// protection against potential attacks like sql-injection
							posaljiZahtjevZaOdabirom($params[1], false, 'Uspješno ste registrirali e-mail adresu uz Vaš korisnički račun!');
						}
					}
					else {
						posaljiZahtjevZaOdabirom($expectedValueType, true, 'Niste naveli e-mail adresu ili ona koju ste naveli nije važećeg formata!');
					}
					break;
				case 'phone':
					if (preg_match_all('/(?:\+\s*)?\d+(?:(?:\s|\/|\-)+\d+)*/', $command, $matches)) {
						foreach ($matches[0] as $numWithSeparators) {
							$number = preg_replace('(-|/|\s)', '', $numWithSeparators);
							if (strlen($number) > 7) {
								pg_query_params('UPDATE user_account SET phone=$1, currently_edited_attribute=$2 WHERE id=$3;', array($number, null, $senderId));
								if ($userAlreadyRegistered) {
									replyBackWithSimpleText('Uspješno ste ažurirali telefonski broj Vašeg korisničkog računa!');
								}
								else {
									replyBackWithSimpleText("Uspješno ste registrirali telefonski broj uz Vaš korisnički račun!\nSada možete započeti s pretragom i naručivanjem artikala.");
								}
							}
							else {
								posaljiZahtjevZaOdabirom($expectedValueType, true, 'Niste naveli važeći telefonski broj!');
							}
						}
					}
					else {
						posaljiZahtjevZaOdabirom($expectedValueType, true, 'Niste naveli važeći telefonski broj!');
					}
					break;
				default:
					posaljiZahtjevZaOdabirom($expectedValueType, true);
			}
		}
	}
	// When bot receives button click from user
	else if (!empty($messageInfo['postback'])) {
		if (!empty($expectedValueType)) {
			posaljiZahtjevZaOdabirom($expectedValueType, true);
		}
		else if (!$userAlreadyRegistered) {
			pg_query("UPDATE user_account SET currently_edited_attribute='full_name' WHERE id='$senderId';");
			posaljiZahtjevZaOdabirom('full_name');
		}
		else {
			$command = $messageInfo['postback']['payload'];
			if(strpos($command, '/hr/') === 0){
				$linkProizovada = $command;
				
				
				require './provjeraDostupnosti.php';
				if($replyContent != 'Ispričavamo se, traženi artikl trenutno nije dostupan!'){
					addItemInBasket("$senderId.txt","$linkProizovada\n");
					
					replyBackWithSimpleText("Artikl je uspješno dodan u košaricu, možete nastaviti s kupnjom ili odabrati jednu od akcija vezanih za košaricu koje se nalaze u izborniku.");
				}else{
					replyBackWithSimpleText($replyContent);
				}
				
				/*if (!empty($quickReplies)) {
					$answer['quick_replies'] = $quickReplies;
				}*/
				//replyBackSpecificObject($answer);
			}else if(strpos($command, 'obrisi') === 0){
				
				
				$x = explode(" ", $command);
				
				$DELETE = $x[1];

				$data = file("$senderId.txt");

				$out = array();
				

				foreach($data as $k) {
					if(trim("https://www.links.hr".$k) !== trim($DELETE)) {
						$out[] = $k;
					}
				}

				$fp = fopen("$senderId.txt", "w+");
				flock($fp, LOCK_EX);
				foreach($out as $line) {
				 fwrite($fp, $line);
				}
				flock($fp, LOCK_UN);
				fclose($fp);  
				
				replyBackWithSimpleText("Uspješno uklonjen artikl iz košarice");
			}
		}
	}
	else if (!empty($messageInfo['main_menu'])) {
		$command = $messageInfo['main_menu']['payload'];
		switch ($command) {
			case 'zavrsi_kupovinu':
			
				$myfile = fopen("$senderId.txt", "r");
				$fileContent = fread($myfile,filesize("$senderId.txt"));
				fclose($myfile);

				$fileContentArray = [];
				if($fileContent != null){
					$fileContentArray = explode("\n", $fileContent);
				}
				
				$postojiItem = false;
				foreach($fileContentArray as $line){
					if(!empty($line)) $postojiItem = true;
				}
				
				if(sizeof($fileContentArray) > 0 && $postojiItem){
					$quickReplies = [];
					/*array_push($quickReplies, array('content_type'=>'text', 'title'=>'Pokupit ću tamo', 'payload' => "$linkProizovada Rijeka"));
					array_push($quickReplies, array('content_type'=>'text', 'title'=>'Želim dostavu', 'payload' => "$linkProizovada dostava"));
					array_push($quickReplies, array('content_type'=>'text', 'title'=>'Odustajem od kupnje', 'payload' => ''));*/
					
					array_push($quickReplies, array('content_type'=>'text', 'title'=>'Pokupit ću tamo', 'payload' => "Rijeka"));
					array_push($quickReplies, array('content_type'=>'text', 'title'=>'Želim dostavu', 'payload' => "dostava"));
					
					$answer = [ 'text' => "Odaberite način preuzimanja narudžbe" ];
					
					$answer['quick_replies'] = $quickReplies;
					replyBackSpecificObject($answer);
					exit();
				}else{
					replyBackWithSimpleText("U košarici nemate nikakvih artikala.");
				}
				
				break;
			case 'isprazni_kosaricu':
				
				//open file to write
				$fp = fopen("$senderId.txt", "r+");
				// clear content to 0 bits
				ftruncate($fp, 0);
				//close file
				fclose($fp);
				
				replyBackWithSimpleText("Uspješno je obrisana košarica!");
				
				break;
			case 'prikazi_sadrzaj_kosarice':
			
				$myfile = fopen("$senderId.txt", "r");
				$fileContent = fread($myfile,filesize("$senderId.txt"));
				fclose($myfile);

				$fileContentArray = [];
				if($fileContent != null){
					$fileContentArray = explode("\n", $fileContent);
				}
				
				$postojiItem = false;
				foreach($fileContentArray as $line){
					if(!empty($line)) $postojiItem = true;
				}
				
				if(sizeof($fileContentArray) > 0 && $postojiItem){
					

					$desiredProducts = [];

					foreach($fileContentArray as $link){
						if(!empty($link)){
							$desiredProducts["https://www.links.hr$link"] = 1;
						}
						
					}
					
					$action = "pregled";	// lokacije Zagreb Trešnjevka, Zagreb Dubrava i Slavonski Brod se sastoje od više riječi
					$delivery = false;
					$closestStore = $action;
					changeTypingIndicator(true);
					require 'naruciRobu.php';

					$buttons = array();
					$item = 0;
					if (!empty($ordererOutput)) {
						$ordererOutput = explode("\n", $ordererOutput);
						$price = floatval(str_replace(array('.', ','), array('', '.'), explode(' ', $ordererOutput[0])[0]));
						$placeName = mb_convert_case($city, MB_CASE_TITLE);
						$numOfOutputRows = count($ordererOutput);
						$orderedItems = [];
						for ($i=3; $i<$numOfOutputRows; $i+=3) {
							$productName = $ordererOutput[$i-2];
							$productImageUrl = $ordererOutput[$i-1];
							$itemPrice = $ordererOutput[$i];
							extractTitleAndSubtitle($productName, $title, $subtitle);
							
							array_push($buttons, array(
								'title' => $title,
								'image_url' => $productImageUrl,
								'subtitle' => 
									$itemPrice . " HRK",
								'default_action' => [
									'type' => 'web_url',
									'url' => 'https://www.links.hr' . $fileContentArray[$item] . '#quickTabs',
									'messenger_extensions' => true,
									'webview_height_ratio'=> 'TALL'
								],
								'buttons' => array(
									array(
										'type' => 'postback',
										'payload' => "obrisi https://www.links.hr" . $fileContentArray[$item++],
										'title' => 'Obriši iz košarice'
										)
									)
								)
							);
							
							//$orderedItems[] = ['title'=>$title, 'subtitle'=>$subtitle,'quantity'=>1,'price'=>$price,'currency'=>'HRK','image_url'=>$productImageUrl];
					}
					
					$answer = [
						'type'=>'template',
						'payload'=>[
							'template_type'=>'generic',
							'elements'=> $buttons
						]
					];
					
					changeTypingIndicator(false);
					replyBackSpecificObject([ 'attachment' => $answer ], false);
					
					replyBackWithSimpleText("Za kupovinu košarice Košarica -> Završi kupovinu");
					}
				}else{
					replyBackWithSimpleText("U košarici nemate nikakvih artikala.");
				}
			
				
			
				break;
			case 'full_name':
				$command = 'last_name';	// to avoid asking user if their current name is valid if they chose to change it
			case 'address':
			case 'phone':
			case 'subscribe':
				pg_query_params('UPDATE user_account SET currently_edited_attribute=$1 WHERE id=$2', array($command, $senderId));
				posaljiZahtjevZaOdabirom($command);
				break;
			case 'pregled_osobnih_podataka':
				if (!empty($userInfo['address'])) {
					$userInfo['address'] = "$userInfo[route] $userInfo[street_number], $userInfo[postal_code]";
				}
				foreach ($userInfo as $key => $val) {
					if (empty($val)) {
						$userInfo[$key] = "_nedefinirano_";
					}
				}
				replyBackWithSimpleText(<<<EOS
Vaši trenutni osobni podaci su sljedeći:
*Prezime:* $userInfo[last_name]
*Ime:* $userInfo[first_name]
*Adresa:* $userInfo[address]
*E-mail:* $userInfo[email]
*Telefon:* $userInfo[phone]
EOS
				);
				break;
			case 'radna_vremena':
			
				require 'radnoVrijeme.php';
				if ($exactOpeningHours) {
					$v = $exactOpeningHours;
				}
				replyBackWithSimpleText($v);
				break;
			case 'lokacije':
				break;
			case 'gaming':
				$prijedlog = "https://www.links.hr/hr/laptopi-za-igranje-0150";
				
				$buttons = array();				
				require './traziRobu.php';
				
				$itemsNum = min(10, count($obj));
				for($i=0; $i<$itemsNum; $i++){
					extractTitleAndSubtitle($obj[$i]->naziv, $title, $subtitle, $obj[$i]->cijena);
					array_push($buttons, array(
						'title' => htmlspecialchars_decode($title, ENT_QUOTES),
						'image_url' => $obj[$i]->slika,
						'subtitle' => 
							htmlspecialchars_decode($subtitle, ENT_QUOTES),
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
								'title' => 'Dodaj u košaricu'
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

				break;
			case 'business':
			
				$prijedlog = "https://www.links.hr/hr/laptopi-za-posao-0154";
				
				$buttons = array();				
				require './traziRobu.php';
				
				$itemsNum = min(10, count($obj));
				for($i=0; $i<$itemsNum; $i++){
					extractTitleAndSubtitle($obj[$i]->naziv, $title, $subtitle, $obj[$i]->cijena);
					array_push($buttons, array(
						'title' => htmlspecialchars_decode($title, ENT_QUOTES),
						'image_url' => $obj[$i]->slika,
						'subtitle' => 
							htmlspecialchars_decode($subtitle, ENT_QUOTES),
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
								'title' => 'Dodaj u košaricu'
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
				
				break;
			case 'cheap':
			
				$prijedlog = "https://www.links.hr/hr/laptopi-za-internet-i-office-0142";
				
				$buttons = array();				
				require './traziRobu.php';
				
				$itemsNum = min(10, count($obj));
				for($i=0; $i<$itemsNum; $i++){
					extractTitleAndSubtitle($obj[$i]->naziv, $title, $subtitle, $obj[$i]->cijena);
					array_push($buttons, array(
						'title' => htmlspecialchars_decode($title, ENT_QUOTES),
						'image_url' => $obj[$i]->slika,
						'subtitle' => 
							htmlspecialchars_decode($subtitle, ENT_QUOTES),
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
								'title' => 'Dodaj u košaricu'
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
				
				break;
			case 'help':
				$v = json_decode(file_get_contents('./intents.json'),true)['pomoć'];
				break;
			default:
				$v = 'Poruka za developera da nije del switch za novo-dodanu/promijenjenu tipku menija';
		}
		replyBackWithSimpleText($v);	
	}
}
	
foreach( json_decode(file_get_contents('./intents.json'),true) as $k => $v){
	if (mb_stripos($command, $k) !== false) {
		if ($k === 'radno vrijeme') {
			require 'radnoVrijeme.php';
			if ($exactOpeningHours) {
				$v = $exactOpeningHours;
			}
		}
		replyBackWithSimpleText($v);
	}
}
//replyBackWithSimpleText($command);
$translatedInput = translateInput(prilagodiZahtjev(mb_strtoupper($command)), 'hr', 'en');	// when translating from bosnian language to english (instead from croatian), currency names/symbols are preserved (i have no idea why)
if($translatedInput['status'] === 'OK'){
	$nlpText = NLPtext($translatedInput['translate']);
}else{
	replyBackWithSimpleText('Unesena je narudžba na krivom jeziku!');
}

$translatedOutput = translateInput($nlpText['tekst'], 'en', 'hr');

if($translatedOutput['status'] === 'OK'){
	$translatedOutputText = $translatedOutput['translate'];
}else{
	replyBackWithSimpleText('Došlo je do pogreške!');
}

$nlpText['tekst'] = urediIzlaz($translatedOutputText);

$datum = new DateTime();
$datumString = $datum->format('Y-m-d H:i:s');


require './traziRobu.php';

if(!empty($obj)){
	$buttons = array();
	$itemsNum = min(10, count($obj));
	for($i=0; $i<$itemsNum; $i++){
		extractTitleAndSubtitle($obj[$i]->naziv, $title, $subtitle, $obj[$i]->cijena);
		array_push($buttons, array(
			'title' => htmlspecialchars_decode($title, ENT_QUOTES),
			'image_url' => $obj[$i]->slika,
			'subtitle' => 
				htmlspecialchars_decode($subtitle, ENT_QUOTES),
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
					'title' => 'Dodaj u košaricu'
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
	if (!isset($korisnikUpravoDeklariran)) {
		replyBackWithSimpleText('Nisu nađeni proizvodi koji odgovaraju zadanim kriterijima');
	}
	else {
		pg_close();
	}
}

function replyBackSpecificObject($answer, $zavrsi=true) {
	global $senderId;
	if ($zavrsi) {
		pg_close();
	}
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
	if ($zavrsi) {
		exit();
	}
}

function replyBackWithSimpleText($text, $zavrsi=true) {
	replyBackSpecificObject([ 'text' => $text ], $zavrsi);
	if ($zavrsi) {
		exit();
	}
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

function posaljiZahtjevZaOdabirom($atribut, $ponavljanje=false, $prefiks='') {
	global $userInfo;
	global $userAlreadyRegistered;
	if (!empty($prefiks)) {
		$replyContent = $prefiks . "\n";
	}
	else {
		$replyContent = '';
	}
	$quickReplies = [];
	switch ($atribut) {
		case 'full_name':
			if ($ponavljanje) {
				$replyContent .= "Potrebno je odabrati jednu od ponuđenih opcija! Ponavljamo, da li je '$userInfo[first_name] $userInfo[last_name]' Vaše pravo ime?";
			}
			else {
				if (!$userAlreadyRegistered) {
					$replyContent .= "Za daljnje korištenje aplikacije potrebno se je registrirati. Za početak, odgovorite da li je '$userInfo[first_name] $userInfo[last_name]' Vaše puno ime.";
				}
				else {
					$replyContent .= "Da li je '$userInfo[first_name] $userInfo[last_name]' Vaše puno ime?";
				}
			}
			array_push($quickReplies, array('content_type'=>'text', 'title'=>'Da', 'payload' => 'preserve full_name'));
			array_push($quickReplies, array('content_type'=>'text', 'title'=>'Ne', 'payload' => 'change full_name'));
			break;
		case 'first_name':
			if ($ponavljanje) {
				$replyContent .= 'Ponavljamo, napišite Vaše ime ili odaberite da se zadrži dosadašnje.';
			}
			else {
				$replyContent .= 'Navedite Vaše ime:';
			}
			if (!empty($userInfo['first_name'])) {
				array_push($quickReplies, array('content_type'=>'text', 'title'=>"zadrži '$userInfo[first_name]'", 'payload' => 'preserve first_name'));
			}
			break;
		case 'last_name':
			if ($ponavljanje) {
				$replyContent .= 'Ponavljamo, napišite Vaše prezime ili odaberite da se zadrži dosadašnje.';
			}
			else {
				$replyContent .= 'Navedite Vaše prezime:';
			}
			if (!empty($userInfo['last_name'])) {
				array_push($quickReplies, array('content_type'=>'text', 'title'=>"zadrži '$userInfo[last_name]'", 'payload' => 'preserve last_name'));
			}
			break;
		case 'address':
			if ($ponavljanje) {
				if (empty($userInfo['address'])) {
					$replyContent .= 'Ponavljamo, navedite Vašu adresu stanovanja ili dostavljanja.';
				}
				else {
					$replyContent .= "Ponavljamo, navedite Vašu adresu stanovanja ili dostavljanja, ili pak odaberite da se zadrži dosadašnja ($userInfo[route] $userInfo[street_number], $userInfo[postal_code]).";
				}
			}
			else {
				if (empty($userInfo['address'])) {
					$replyContent .= 'Navedite Vašu adresu stanovanja ili adresu na koju želite da Vam se dostavi roba:';
				}
				else {
					$replyContent .= "Navedite Vašu adresu stanovanja ili adresu na koju želite da Vam se dostavi roba, ili pak odaberite da se zadrži dosadašnja ($userInfo[route] $userInfo[street_number], $userInfo[postal_code]).";
				}
			}
			if (!empty($userInfo['address'])) {
				array_push($quickReplies, array('content_type'=>'text', 'title'=>'zadrži dosadašnju', 'payload' => 'preserve address'));
			}
			array_push($quickReplies, array('content_type' => 'location'));
			break;
		case 'subscribe':
			switch ($userInfo['subscribe']) {
				case 'email':
					$subscribeName = 'e-mail';
					break;
				case 'messenger':
					$subscribeName = 'messenger';
					break;
				case 'both':
					$subscribeName = 'oboje';
					break;
			}
			if ($ponavljanje) {
				if (empty($userInfo['subscribe'])) {
					$replyContent .= 'Ponavljamo, odaberite način na koji ćete biti informirani o statusu narudžbe.';
				}
				else {
					$replyContent .= "Ponavljamo, navedite način na koji ćete biti informirani o statusu narudžbe ili pak odaberite da se zadrži dosadašnji ($subscribeName).";
				}
			}
			else {
				if (empty($userInfo['subscribe'])) {
					$replyContent .= 'Odaberite način na koji ćete biti informirani o statusu narudžbe.';
				}
				else {
					$replyContent .= "Odaberite način na koji ćete biti informirani o statusu narudžbe ili pak odaberite da se zadrži dosadašnji ($subscribeName).";
				}
			}
			array_push($quickReplies, array('content_type'=>'text', 'title'=>'e-mail', 'payload' => 'subscribe email'));
			array_push($quickReplies, array('content_type'=>'text', 'title'=>'messenger', 'payload' => 'subscribe messenger'));
			array_push($quickReplies, array('content_type'=>'text', 'title'=>'oboje', 'payload' => 'subscribe both'));
			if (!empty($userInfo['subscribe'])) {
				array_push($quickReplies, array('content_type'=>'text', 'title'=>'zadrži dosadašnji', 'payload' => 'preserve subscription'));
			}
			break;
		case 'email':
			if ($ponavljanje) {
				if (empty($userInfo['email'])) {
					$replyContent .= 'Ponavljamo, navedite Vašu e-mail adresu.';
				}
				else {
					$replyContent .= "Ponavljamo, navedite Vašu e-mail adresu ili odaberite da se zadrži dosadašnja ($userInfo[email]).";
				}
			}
			else {
				if (empty($userInfo['email'])) {
					$replyContent .= "Navedite Vašu e-mail adresu na koju ćete biti u mogućnosti kontaktirani:";
				}
				else {
					$replyContent .= "Navedite Vašu e-mail adresu na koju ćete biti u mogućnosti kontaktirani ili odaberite dosadašnju ($userInfo[email]).";
				}
			}
			if (!empty($userInfo['email'])) {
				array_push($quickReplies, array('content_type'=>'text', 'title'=>'zadrži dosadašnju', 'payload' => 'preserve email'));
			}
			array_push($quickReplies, array('content_type' => 'user_email'));
			break;
		case 'phone':
			if ($ponavljanje) {
				if (empty($userInfo['phone'])) {
					$replyContent .= 'Ponavljamo, navedite Vaš telefonski broj.';
				}
				else {
					$replyContent .= "Ponavljamo, navedite Vaš telefonski broj ili odaberite da se zadrži dosadašnji ($userInfo[phone]).";
				}
			}
			else {
				if (empty($userInfo['phone'])) {
					$replyContent .= 'Navedite Vaš telefonski broj na koji ćete biti u mogućnosti kontaktirani:';
				}
				else {
					$replyContent .= "Navedite Vaš telefonski broj na koji ćete biti u mogućnosti kontaktirani ili odaberite da se zadrži dosadašnji ($userInfo[phone]).";
				}
			}
			if (!empty($userInfo['phone'])) {
				array_push($quickReplies, array('content_type'=>'text', 'title'=>'zadrži dosadašnji', 'payload' => 'preserve phone'));
			}
			array_push($quickReplies, array('content_type' => 'user_phone_number'));
			break;

	}
	$answer = [ 'text' => $replyContent ];
	if (!empty($quickReplies)) {
		$answer['quick_replies'] = $quickReplies;
	}
	replyBackSpecificObject($answer);
}

function extractTitleAndSubtitle($productName, &$title, &$subtitle, $price=null) {
	if ($price === null) {
		$title = $productName;
		$subtitle = '';
	}
	else {
		$title = preg_replace('/,\s*cijena:.+?(,|$)/i', '\1', $productName, 1);
		$subtitle = 'Cijena: ' . $price . " kn\n";
	}
	$titleLength = strlen($title);
	if ($titleLength > 80) {
		if ( ($titleLimit = strrpos($title, ',', 80-$titleLength))!==false || ($titleLimit = strrpos($title, ' ', 80-$titleLength))!==false ) {
			$subtitle .= substr($title, $titleLimit+1);
			$title = substr($title, 0, $titleLimit);
		}
	}
}

function addItemInBasket($file,$link){
	if (file_exists($file)) {
		$fh = fopen($file, 'a');
		fwrite($fh, $link."\n");
	} else {
		$fh = fopen($file, 'wb');
		fwrite($fh, $link."\n");
	}

	fclose($fh);
	chmod($file, 0777);
}

function notifyMXServerAboutChangedEmailAddress($emailAddress) {
	global $senderId;
	$mysqlDbHandler = new mysqli('chatbot-ordering.com', 'heroku', getenv('MYSQL_PW_ON_MAILSERVER'), 'vmail');
	$stmt = $mysqlDbHandler->prepare("UPDATE forwardings SET forwarding=?, dest_domain=? WHERE address=? AND forwarding<>'postmaster@chatbot-ordering.com';");
	$stmt->bind_param('sss', $emailAddress, $emailDomain, $forwardingAddress);
	$emailDomain = substr($emailAddress, strpos($emailAddress, '@')+1);
	$forwardingAddress = "$senderId@chatbot-ordering.com";
	$stmt->execute();
	$stmt->close();
	$mysqlDbHandler->close();
}