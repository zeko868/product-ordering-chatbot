<?php
// parameters
$hubVerifyToken = 'bot';
$accessToken =   "EAACN8hwDY8QBAEcLkz9b9FZB2QXVgr92ZBduX8cEU1rfZBR7kOtzurRUtiWkZCan496HmhLyiWLnk86RAKsfMSiYKxZBdnIC6KftcZBy7EODHgPBERWpjFZCgqvPYWGUQyutGc76VccANwiCvrPxa9BCO7f3jnbTs2jXjZCzXk06OgZDZD";
// check token at setup
if ($_REQUEST['hub_verify_token'] === $hubVerifyToken) {
  echo $_REQUEST['hub_challenge'];
  exit;
}
// handle bot's anwser
$input = json_decode(file_get_contents("php://input"), true, 512, JSON_BIGINT_AS_STRING);
$command = "";
$ch = curl_init('https://graph.facebook.com/v2.6/me/messages?access_token='.$accessToken);
$answer = "1";
     $response = [
    'recipient' => [ 'id' => $senderId ],
    'message' => [ 'text' => $answer ]
];
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
if(!empty($input)){
$result = curl_exec($ch);
}

if (!empty($input['entry'][0]['messaging'])) { 

		$answer = "2";
     $response = [
    'recipient' => [ 'id' => $senderId ],
    'message' => [ 'text' => $answer ]
];
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
if(!empty($input)){
$result = curl_exec($ch);
}
		
        foreach ($input['entry'][0]['messaging'] as $message) { 

		$answer = "3";
     $response = [
    'recipient' => [ 'id' => $senderId ],
    'message' => [ 'text' => $answer ]
];
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
if(!empty($input)){
$result = curl_exec($ch);
}
        

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
$senderId = $input['entry'][0]['messaging'][0]['sender']['id'];
$response = null;
if($command == "hi"){
     $answer = ["attachment"=>[
      "type"=>"template",
      "payload"=>[
        "template_type"=>"generic",
        "elements"=>[
          [
            "title"=>"Dobro došli u aplikaciju za rezervaciju konzultacija",
            "subtitle"=>"Odaberite profesora kod kojeg želite rezervirati konzultacije:",
            "buttons"=>[
              [
                "type"=>"web_url",
                "url"=>"https://petersfancybrownhats.com",
                "title"=>"View Website"
              ],
              [
                "type"=>"postback",
                "title"=>"hi",
                "payload"=>"1"
              ],
				[
                "type"=>"postback",
                "title"=>"hi 2",
                "payload"=>"2"
              ]
            ]
          ]
        ]
      ]
    ]];
     $response = [
    'recipient' => [ 'id' => $senderId ],
    'message' => $answer 
];

}else if($command == "1"){
	$answer = "a";
 $response = [
'recipient' => [ 'id' => $senderId ],
'message' => [ 'text' => $answer ]
];
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
if(!empty($input)){
$result = curl_exec($ch);
}
}

curl_close($ch);



