<?php


$string = file_get_contents("./producers.json");
$json = json_decode($string, true);


foreach($json as $p){
	if(strpos("Želim kupiti amd grafičku karticu", strtolower($p))){
		echo $p . "<br/>";
	}
}
