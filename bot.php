<?php
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

// parameters
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
}/*	 	$command = 'konzultacije Petar Šestak -'; $senderId = '1532028376807777';	//for debugging purposes */

/* "server is down" message */


if(strpos($command,'/hr/') === 0){
	
	$linkProizovada = $command;
	include "./provjeraDostupnosti.php";
	$response = [
		'recipient' => [ 'id' => $senderId ],
		'message' => [ 'text' => $answer ]
	];

}else{
	$input = prilagodiZahtjev(strtoupper($command));

	$translatedInput = translateInput($input, 'en');

	if($translatedInput['status'] == "OK"){
		$nlpText = NLPtext($translatedInput['translate']);
	}else{
		$answer = "Unesena je narudzba na krivom jeziku!";
		exit();
	}

	//var_dump($nlpText);

	$translatedOutput = translateInput($nlpText['tekst'], 'hr');

	if($translatedOutput['status'] == "OK"){
		$translatedOutputText = $translatedOutput['translate'];
	}else{
		$answer = "Doslo je do pogreske!";
		exit();
	}

	$trans = urediIzlaz($translatedOutputText);
	$nlpText['tekst'] = $trans;
	$translated = $nlpText;
	//echo "<br/>Kupac pretražuje: " . strtolower_cro($translated);

	include "./traziRobu.php";

	if($obj != null){
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
		$answer = "Ne postoji proizvod koji zadovoljava unesenim zahtjevima.";
		$response = [
			'recipient' => [ 'id' => $senderId ],
			'message' => [ 'text' => $answer ]
		];
	}
	
}
$ch = curl_init("https://graph.facebook.com/v2.6/me/messages?access_token=$accessToken");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
if($command != ""){
	$result = curl_exec($ch);
	var_dump($result);
}
curl_close($ch);
exit();