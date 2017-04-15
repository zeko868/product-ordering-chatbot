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


if($command == "konzultacije"){
     $answer = [
      'type'=>'template',
      'payload'=>[
        'template_type'=>'button',
        'text'=>'Kod kojeg profesora želite rezervirati konzultacije?',
        'buttons'=>[
          [
            'type'=>'postback',
            'title'=>'Profesor 1',
            'payload'=>'1'
          ],
		  [
            'type'=>'postback',
            'title'=>'Profesor 2',
            'payload'=>'2'
          ],
		  [
            'type'=>'postback',
            'title'=>'Profesor 3',
            'payload'=>'3'
          ]
        ]
      ]
      ];
     $response = [
    'recipient' => [ 'id' => $senderId ],
    'message' => [ 'attachment' => $answer ]
];

}else if($command == "1"){
	$answer = ['attachment'=>[
      'type'=>'template',
      'payload'=>[
        'template_type'=>'button',
        'text'=>'Termini konzultacija kod profesora 1: ',
        'buttons'=>[
          [
            'type'=>'postback',
            'title'=>'Termin 1',
            'payload'=>'Termin 1 profesor 1'
          ],
		  [
            'type'=>'postback',
            'title'=>'Termin 2',
            'payload'=>'Termin 2 profesor 1'
          ]
        ]
      ]
      ]];
     $response = [
    'recipient' => [ 'id' => $senderId ],
    'message' => $answer 
];
}else if($command == "2"){
	$answer = ['attachment'=>[
      'type'=>'template',
      'payload'=>[
        'template_type'=>'button',
        'text'=>'Termini konzultacija kod profesora 1: ',
        'buttons'=>[
          [
            'type'=>'postback',
            'title'=>'Termin 1',
            'payload'=>'Termin 1 profesor 2'
          ]
        ]
      ]
      ]];
     $response = [
    'recipient' => [ 'id' => $senderId ],
    'message' => $answer 
];
}else if($command == "3"){
	$answer = ['attachment'=>[
      'type'=>'template',
      'payload'=>[
        'template_type'=>'button',
        'text'=>'Termini konzultacija kod profesora 1: ',
        'buttons'=>[
          [
            'type'=>'postback',
            'title'=>'Termin 1',
            'payload'=>'Termin 1 profesor 3'
          ],
		  [
            'type'=>'postback',
            'title'=>'Termin 2',
            'payload'=>'Termin 2 profesor 3'
          ]
        ]
      ]
      ]];
     $response = [
    'recipient' => [ 'id' => $senderId ],
    'message' => $answer 
];
}else if($command == "Termin 1 profesor 1"){
	$answer = "Odabran je termin 1 kod profesora 1, kada profesor odgovori na zahtjev javit ćemo Vam profesorov odgovor.";
     $response = [
    'recipient' => [ 'id' => $senderId ],
    'message' => [ 'text' => $answer ]
];
}else if($command == "Termin 2 profesor 1"){
	$answer = "Odabran je termin 2 kod profesora 1, kada profesor odgovori na zahtjev javit ćemo Vam profesorov odgovor.";
     $response = [
    'recipient' => [ 'id' => $senderId ],
    'message' => [ 'text' => $answer ]
];
}else if($command == "Termin 1 profesor 2"){
	$answer = "Odabran je termin 1 kod profesora 2, kada profesor odgovori na zahtjev javit ćemo Vam profesorov odgovor.";
     $response = [
    'recipient' => [ 'id' => $senderId ],
    'message' => [ 'text' => $answer ]
];
}else if($command == "Termin 1 profesor 3"){
	$answer = "Odabran je termin 1 kod profesora 3, kada profesor odgovori na zahtjev javit ćemo Vam profesorov odgovor.";
     $response = [
    'recipient' => [ 'id' => $senderId ],
    'message' => [ 'text' => $answer ]
];
}else if($command == "Termin 2 profesor 3"){
	$answer = "Odabran je termin 1 kod profesora 1, kada profesor odgovori na zahtjev javit ćemo Vam profesorov odgovor.";
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



