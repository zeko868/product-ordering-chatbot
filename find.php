<?php


$string = file_get_contents("./components.json");
$json = json_decode($string, true);


foreach($json as $p){
	if(strpos("naručio bih nvidia grafička kartica" , strtolower($p))){
		echo $p;
	}
}
