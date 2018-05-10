<?php
$myfile = fopen("1155662414560805.txt", "r") or die("Unable to open file!");
$fileContent = fread($myfile,filesize("1155662414560805.txt"));
fclose($myfile);

$fileContentArray = explode("\n", $fileContent);

$desiredProducts = array();

foreach($fileContentArray as $link){
	$l["https://www.links.hr$link"] = 1;
	array_push($desiredProducts,$l);
}

var_dump($desiredProducts);
?>