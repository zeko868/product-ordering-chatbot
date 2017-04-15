<?php
// parameters
$hubVerifyToken = 'bot';
$accessToken =   "EAACN8hwDY8QBAEcLkz9b9FZB2QXVgr92ZBduX8cEU1rfZBR7kOtzurRUtiWkZCan496HmhLyiWLnk86RAKsfMSiYKxZBdnIC6KftcZBy7EODHgPBERWpjFZCgqvPYWGUQyutGc76VccANwiCvrPxa9BCO7f3jnbTs2jXjZCzXk06OgZDZD";

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
$cp = explode(" ", $command);

if($command == "konzultacije"){
	$button = array();
for($i=1;$i<=3;$i++){
	array_push($button, array('type'=>'postback', 'title'=>'Profesor ' . $i, 'payload' => $i));
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

}else if(strlen($command) > 12){
	
	$xml=simplexml_load_file('informacije.xml');
	$k = array();
foreach($xml->employee as $item)
{
	if(sizeof($cp) > 3){
		$answer = "Rezervirano: ". $command . '. Javiti ćemo Vam profesorov odgovor.';
     $response = [
    'recipient' => [ 'id' => $senderId ],
    'message' => [ 'text' => $answer ]
];
}else{
	$prof = substr($command, 13, strlen($command));	
	$p = explode(" ", $prof);
	$button = array();
	$broj = 1;
	if($item->firstname == $p[0] && $item->lastname == $p[1]){
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
	}
}
    
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



