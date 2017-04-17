<?php
$command = $_GET['konzultacije'];
$command = preg_replace('/\s{2,}/', ' ', trim($command));
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
						$answer = "$origProfName";
						$response = [
							'recipient' => [ 'id' => $senderId ],
							'message' => [ 'text' => $answer ]
						];
						break;

					foreach($item->consultation->term as $i){
						//$i->day.' '.$i->time_from.' '.$i->time_to
						if($i->day != "utorak")
							array_push($button, array('type'=>'postback', 'title'=>substr($i->day, 0, 3).' '.$i->time_from.' - '.$i->time_to, 'payload' => "konzultacije $origProfName $i->day $i->time_from - $i->time_to"));
						else
							array_push($button, array('type'=>'postback', 'title'=>substr($i->day, 0, 2).' '.$i->time_from.' - '.$i->time_to, 'payload' => "konzultacije $origProfName $i->day $i->time_from - $i->time_to"));
					}
					array_push($button, array('type'=>'postback', 'title'=>'-', 'payload' => "konzultacije $origProfName -"));

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
					//print_r($response);
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
					foreach($item->consultation->term as $i){
						$answer = "l: '$item->firstname $item->lastname', r: '$origProfName'";
						$response = [
							'recipient' => [ 'id' => $senderId ],
							'message' => [ 'text' => $answer ]
						];
						break;

						if ($term === '-' || $term === "$i->day $i->time_from - $i->time_to") {
							if (send_email_and_get_success_state($senderId, 'Neko ime i prezime', 'eadresa@korisnika', $item->contact->email, $term)) {
								$answer = "Rezervirano: $origProfName $term. Javiti ćemo Vam profesorov odgovor.";
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