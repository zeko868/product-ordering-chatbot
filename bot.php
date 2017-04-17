<?php
function get_regex_fullname_with_deviation($str) {
	global $substitutes;
	$str = localized_strtolower($str);
	foreach ($substitutes as $spec => $alt) {
		$str = str_replace($spec, '(' . implode('|', $alt) . "|$spec)", $str);
	}
	$str = '(.*?=\b' . implode('\b)(.*?=\b', explode(' ', $str)) . '\b)(?!.*[^(' . implode(')|(', explode(' ', $str)) . ')| ])';	//  po navedenom bi naziv 'Martina Tomičić Furjan' dao sljedeći izraz (?=.*\bmartina\b)(?=.*\bfurjan\b)(?=.*\btomičić\b)(?!.*[^(martina)|(furjan)|(tomičić)| ])  - navedeno prihvaća 'Martina Tomičić Furjan', 'Martina Tomičić-Furjan', 'tomičić Furjan martina', 'tomičić martina furjan', ...
	return '/^' . $str . '$/';
}

function localized_strtolower($str) {
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
		'nastavnik_email' => $recipientMail
	);
	if ($term !== '-') {
		$params['termin'] = $term;
	}
	$request = 'http://foi-konzultacije.info/sendmail.php?' . http_build_query($params);
	$ch = curl_init($request);
	//$result = curl_exec($ch);		// odkomentiranjem ove naredbe se šalju email poruke odabranom nastavniku
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
	'-' => ' '
];
$substitutes = [
	'č' => ['c'],
	'ć' => ['c'],
	'đ' => ['dj', 'dz', 'd'],
	'dž' => ['dj', 'dz', 'd'],
	'š' => ['s'],
	'ž' => ['z']
];
$daysAbbreviations = ['po', 'ut', 'sr', 'če', 'pe', 'su', 'ne'];
$termRegex = '/(' .implode('|', $daysAbbreviations) . ') \d{2} - \d{2}$/';

if (stripos($command, 'konzultacije') === 0) {
	$termArray = preg_grep($termRegex, $command);
	if (empty($termArray)) {
		$term = null;
	}
	else {
		$term = $termArray[0];
	}

	$prof = null;
	if ($term === null) {
		$prof = localized_strtolower(substr($command, strlen("konzultacije ")));
	}
	else {
		$termPosition = strpos($command, $term);
		$prof = localized_strtolower(substr($command, strlen("konzultacije "), $termPosition-1-strlen("konzultacije ")));
	}
	$xml = simplexml_load_file('informacije.xml');

	if ($prof === null) {
		foreach($xml->employee as $item) {
			array_push($button, array('type'=>'postback', 'title'=>"$item->firstname $item->lastname", 'payload' => "konzultacije $item->firstname $item->lastname"));
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
					foreach($item->consultation->term as $i){
						//$i->day.' '.$i->time_from.' '.$i->time_to
						if($i->day != "utorak")
							array_push($button, array('type'=>'postback', 'title'=>substr($i->day, 0, 3).' '.$i->time_from.' - '.$i->time_to, 'payload' => $prof.' '.$i->day.' '.$i->time_from.' - '.$i->time_to));
						else
							array_push($button, array('type'=>'postback', 'title'=>substr($i->day, 0, 2).' '.$i->time_from.' - '.$i->time_to, 'payload' => $prof.' '.$i->day.' '.$i->time_from.' - '.$i->time_to));
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
					$response = $suggestions[0];
					break;
				default:
					$button = array();
					$suggestions = array_keys($suggestions);
					for($i=0;$i<=count($suggestions);$i++){
						array_push($button, array('type'=>'postback', 'title'=>$suggestions[i], 'payload' => "konzultacije $suggestions[1]"));
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
					break;
			}
		} else {
			foreach($xml->employee as $item) {
				if ("$item->firstname $item->lastname" === $prof) {
					foreach($item->consultation->term as $i){
						if ($term === $prof.' '.$i->day.' '.$i->time_from.' - '.$i->time_to) {
							if (send_email_and_get_success_state($senderId, 'Neko ime i prezime', 'eadresa@korisnika', $item->contact->email, $term)) {
								$answer = "Rezervirano: $prof $term. Javiti ćemo Vam profesorov odgovor.";
							} else {
								$answer = "Pojavio se neuspjeh kod slanja e-mail poruke profesoru. Molimo Vas da pokušate kasnije.";
							}
							$response = [
								'recipient' => [ 'id' => $senderId ],
								'message' => [ 'text' => $answer ]
							];
						}
					}

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
}

if ($response === null) {
	$answer = "Za početak rezervacije termina konzultacija upišite sljedeću naredbu: konzultacije [naziv_nastavnika [termin]]";
	$response = [
		'recipient' => [ 'id' => $senderId ],
		'message' => [ 'text' => $answer ]
	];
}


$ch = curl_init('https://graph.facebook.com/v2.6/me/messages?access_token='.$accessToken);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
if(!empty($input)){
	$result = curl_exec($ch);
}
curl_close($ch);