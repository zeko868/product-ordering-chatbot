<?php
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

function send_email_and_get_success_state($senderId, $senderName, $senderMail, $recipientMail, $term) {
	$params = array(
		'student_id' => $senderId,
		'student_naziv' => $senderName,
		'student_email' => $senderMail,
		'nastavnik_email' => (string) $recipientMail 	// ovo je xml object pa ga treba castati - inace se uzrokuje da elementu s kljucem 'nastavnik_email' bude dodijeljeno polje, a ne vrijednost (string)
	);
	if ($term !== '-') {
		$params['termin'] = $term;
	}
	$request = 'http://foi-konzultacije.info/sendmail.php?' . http_build_query($params);
	$ch = curl_init($request);
	curl_setopt($ch, CURLOPT_VERBOSE, true);	// za potrebe pregleda stanja izvođenja curl naredbe u error logu
	if ($recipientMail=='petar.sestak3@foi.hr' || // nije dozvoljena uporaba operatora identičnosti jer je $recipientMail tipa object, a ne string (zbog xml-a)
		$recipientMail=='marmihajl@foi.hr' ||
		$recipientMail=='petloncar2@foi.hr' ||
		$recipientMail=='tommarkul@foi.hr') {
		$result = curl_exec($ch)=='true'?true:false;		// odkomentiranjem ove naredbe se šalju email poruke odabranom nastavniku
	}
	else {
		$result = true;
	}
	curl_close($ch);
	return $result;
}

// parameters
$hubVerifyToken = 'bot';
$accessToken = 'EAACN8hwDY8QBAEcLkz9b9FZB2QXVgr92ZBduX8cEU1rfZBR7kOtzurRUtiWkZCan496HmhLyiWLnk86RAKsfMSiYKxZBdnIC6KftcZBy7EODHgPBERWpjFZCgqvPYWGUQyutGc76VccANwiCvrPxa9BCO7f3jnbTs2jXjZCzXk06OgZDZD';

// handle bot's anwser
$input = json_decode(file_get_contents("php://input"), true, 512, JSON_BIGINT_AS_STRING);
$senderId = $input['entry'][0]['messaging'][0]['sender']['id'];
$response = null;
$command = "";

if (!empty($input['entry'][0]['messaging'])) { 

	foreach ($input['entry'][0]['messaging'] as $message) { 

        // When bot receive message from user
        if (!empty($message['message'])) {
             $command = $message['message']['text'];           
        }
         // When bot receive button click from user
         else if (!empty($message['postback'])) {
             $command = $message['postback']['payload'];
        }
    }
}

$command = preg_replace('/\s{2,}/', ' ', trim($command));	// brisanje viška razmaka ispred i iza naredbe te zamjena (najčešće slučajno napisanih) višestrukih razmaka s jednostrukim
$croatianLowercase = [
	'Č' => 'č',
	'Ć' => 'ć',
	'Đ' => 'đ',
	'Š' => 'š',
	'Ž' => 'ž',
	'Ä' => 'ä',
	'Ö' => 'ö',
	'Ü' => 'ü',
	'-' => ' '
];
$substitutes = [
	'č' => ['c', 'ć'],
	'ć' => ['c', 'č'],
	'š' => ['s'],
	'ž' => ['z'],
	'đ' => ['dj', 'dz', 'd', 'dž'],
	'dž' => ['dj', 'dz', 'd', 'đ'],
	'ä' => ['a', 'ae'],
	'ö' => ['o', 'oe'],
	'ü' => ['u', 'ue'],
	'ß' => ['ss', 's']
];
$dayNames = ['ponedjeljak', 'utorak', 'srijeda', 'četvrtak', 'petak', 'subota', 'nedjelja'];
$termRegex = '/(-|(' .implode('|', $dayNames) . ') \d{2}:\d{2} - \d{2}:\d{2})$/u';
if (preg_match('/^autenti(fi)?kacija$/', $command) === 1){
	$answer = "Potrebna je autentikacija za rad u sustavu. Za autentikaciju pristupite linku: http://foi-konzultacije.info/prijava.php?senderid=".$senderId.". Nakon autentikacije upišite konzultacije [naziv_nastavnika [termin]]";
	$response = [
		'recipient' => [ 'id' => $senderId ],
		'message' => [ 'text' => $answer ]
	];
}
else if (stripos($command, 'konzultacije') === 0) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'http://foi-konzultacije.info/curl.php?' . http_build_query(array('senderid' => $senderId)));
	curl_setopt($ch, CURLOPT_HTTPGET, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	// bez ovoga se vraća true ako je dohvaćanje uspjela, a inače false - sada vraća dobavljenu vrijednost
	curl_setopt($ch, CURLOPT_HEADER, 0);
	$output = curl_exec($ch);
	curl_close($ch);
	if ($output === '1') {
		preg_match($termRegex, $command, $termArray);
		if (empty($termArray)) {
			$term = null;
		}
		else {
			$term = $termArray[0];
		}

		$prof = null;
		if ($term === null) {
			$origProfName = substr($command, strlen("konzultacije "));
		}
		else {
			$termPosition = strpos($command, $term);
			$origProfName = substr($command, strlen("konzultacije "), $termPosition-1-strlen("konzultacije "));
		}
		$prof = localized_strtolower($origProfName);
		$xml = simplexml_load_file('informacije.xml');

		if ($prof === null) {
			$button = array();
			$i = 0;
			foreach($xml->employee as $item) {
				if ($i === 3) {
					break;
				}
				array_push($button, array('type'=>'postback', 'title'=>"$item->firstname $item->lastname", 'payload' => "konzultacije $item->firstname $item->lastname"));
				$i++;
			}
			$answer = [
				'type'=>'template',
				'payload'=>[
					'template_type'=>'button',
					'text'=>'Kod kojeg profesora želite rezervirati konzultacije?',
					'buttons'=> $button
				]
			];
			$response = [
				'recipient' => [ 'id' => $senderId ],
				'message' => [ 'attachment' => $answer ]
			];

		} else {
			if ($term === null) {
				$suggestions = array();
				foreach($xml->employee as $item) {
					if (preg_match(get_regex_fullname_with_deviation("$item->firstname $item->lastname"), $prof)===1) {
						
						$button = array();

						foreach($item->consultation->term as $i){
							//$i->day.' '.$i->time_from.' '.$i->time_to
							if($i->day == 'utorak')
								array_push($button, array('type'=>'postback', 'title'=>substr($i->day, 0, 2).' '.$i->time_from.' - '.$i->time_to, 'payload' => "konzultacije $item->firstname $item->lastname $i->day $i->time_from - $i->time_to"));
							else if ($i->day != '')
								array_push($button, array('type'=>'postback', 'title'=>substr($i->day, 0, 3).' '.$i->time_from.' - '.$i->time_to, 'payload' => "konzultacije $item->firstname $item->lastname $i->day $i->time_from - $i->time_to"));
							else
								continue;
						}
						array_push($button, array('type'=>'postback', 'title'=>'-', 'payload' => "konzultacije $item->firstname $item->lastname -"));

						$answer = [
							'type'=>'template',
							'payload'=>[
								'template_type'=>'button',
								'text'=>'U kojem od navedenih termina želite rezervirati konzultacije?',
								'buttons'=> $button
							]
						];
						$response = [
							'recipient' => [ 'id' => $senderId ],
							'message' => [ 'attachment' => $answer ]
						];

						$suggestions["$item->firstname $item->lastname"] = $response;
					}
				}
				switch (count($suggestions)) {
					case 0:
						$answer = 'Ne postoji nastavnik u bazi podataka s navedenim imenom';
						$response = [
							'recipient' => [ 'id' => $senderId ],
							'message' => [ 'text' => $answer ]
						];
						break;
					case 1:
						$response = array_values($suggestions)[0];
						break;
					default:
						$button = array();
						$suggestions = array_keys($suggestions);
						for($i=0;$i<=count($suggestions);$i++){
							array_push($button, array('type'=>'postback', 'title'=>$suggestions[$i], 'payload' => "konzultacije $suggestions[$i]"));
						}
											
						$answer = [
							'type'=>'template',
							'payload'=>[
								'template_type'=>'button',
								'text'=>'Neuspjeh kod prepoznavanja. Kod kojeg profesora želite rezervirati konzultacije?',
								'buttons'=> $button
							]
						];
						$response = [
							'recipient' => [ 'id' => $senderId ],
							'message' => [ 'attachment' => $answer ]
						];
						break;
				}
			} else {
				foreach($xml->employee as $item) {
					if ("$item->firstname $item->lastname" === $origProfName) {
						if ($term === '-') {
							$ch = curl_init();
							curl_setopt($ch, CURLOPT_URL, 'http://foi-konzultacije.info/dohvati_ime.php?id=' . http_build_query(array('senderid' => $senderId)));
							curl_setopt($ch, CURLOPT_HTTPGET, 1);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	
							curl_setopt($ch, CURLOPT_HEADER, 0);
							$output = curl_exec($ch);
							curl_close($ch);
							$o = json_decode($output);
							$name = $o->fullName;
							if (send_email_and_get_success_state($senderId, $name, 'email', $item->contact->email, $term)) {
								$answer = "Vaš zahtjev za dodatnim terminom konzultacija je poslan nastavniku $origProfName. Javiti ćemo Vam profesorov odgovor.";
							} else {
								$answer = "Pojavio se neuspjeh kod slanja e-mail poruke profesoru. Molimo Vas da pokušate kasnije.";
							}
						}
						foreach($item->consultation->term as $i){
							if ($term === "$i->day $i->time_from - $i->time_to") {
								$ch = curl_init();
								curl_setopt($ch, CURLOPT_URL, 'http://foi-konzultacije.info/curl.php?' . http_build_query(array('senderid' => $senderId)));
								curl_setopt($ch, CURLOPT_HTTPGET, 1);
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	
								curl_setopt($ch, CURLOPT_HEADER, 0);
								$output = curl_exec($ch);
								curl_close($ch);
								if (send_email_and_get_success_state($senderId, 'Neko ime i prezime', 'eadresa@korisnika', $item->contact->email, $term)) {
									$answer = "Rezervirano: $origProfName $term. Javiti ćemo Vam profesorov odgovor.";
								} else {
									$answer = "Pojavio se neuspjeh kod slanja e-mail poruke profesoru. Molimo Vas da pokušate kasnije.";
								}
								break;
							}
						}
						$response = [
							'recipient' => [ 'id' => $senderId ],
							'message' => [ 'text' => $answer ]
						];

						break;
					}
				}
			}
		}

		if ($response === null) {
			$answer = "Pojavila se pogreška kod pokušaja izvršavanja Vaše naredbe. Ispravan format naredbe je sljedeći: konzultacije [naziv_nastavnika [termin]]";
			$response = [
				'recipient' => [ 'id' => $senderId ],
				'message' => [ 'text' => $answer ]
			];
		}
	}else{
		$answer = "Niste se autenticirali za rad u sustavu. Za autentikaciju pristupite linku: http://foi-konzultacije.info/prijava.php?senderid=".$senderId.". Nakon autentikacije upišite konzultacije [naziv_nastavnika [termin]]";
		$response = [
			'recipient' => [ 'id' => $senderId ],
			'message' => [ 'text' => $answer ]
		];
	}
	
	
}


$ch = curl_init('https://graph.facebook.com/v2.6/me/messages?access_token='.$accessToken);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
if(!empty($input)){
	$result = curl_exec($ch);
}
curl_close($ch);
