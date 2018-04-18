<?php


$string = file_get_contents("./intents.json");
$json = json_decode($string, true);


foreach($json as $k => $v){
	echo "$k : $v<br/>";
}
