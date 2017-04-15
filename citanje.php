<?php
$answer = [
      'type'=>'template',
      'payload'=>[
        'template_type'=>'button',
        'text'=>'Kod kojeg profesora želite rezervirati konzultacije?',
        'buttons'=>[
		['type'=>'postback','title'=>'Profesor 1','payload'=>'1'],
		['type'=>'postback','title'=>'Profesor 1','payload'=>'1'],
		['type'=>'postback','title'=>'Profesor 1','payload'=>'1']
		]
      ]
      ];
     $response = [
    'recipient' => [ 'id' => $senderId ],
    'message' => [ 'attachment' => $answer ]
];
 
echo json_encode($answer);
 
?>