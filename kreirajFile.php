<?php
$myfile = fopen("1155662414560805.txt", "r") or die("Unable to open file!");
$fileContent = fread($myfile,filesize("1155662414560805.txt"));
fclose($myfile);

$fileContentArray = explode("\n", $fileContent);

$desiredProducts = [];

foreach($fileContentArray as $link){
	if(!empty($link)){
		$desiredProducts["https://www.links.hr$link"] = 1;
	}
	
}

var_dump($desiredProducts);
?>