<?php
$button = array();
for($i=0;$i<3;$i++){
	array_push($button, array('type'=>'postback', 'title'=>'Profesor 1', 'payload' => '1'));
}
$button =json_encode($button);
$button = str_replace(":","=>",$button);
$button = str_replace("{","[",$button);
$button = str_replace("}","]",$button);
$button = str_replace("\"","'",$button);
echo $button;
?>