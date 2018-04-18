<?php


$string = file_get_contents("./intents.json");
$json = json_decode($string, true);


foreach($json as $k => $v){
	if(strpos(strtolower("Koje je radno vrijeme Links trgovina?"),strtolower($k)))
		echo $v;
}
