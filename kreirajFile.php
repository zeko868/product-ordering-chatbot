<?php

$DELETE = "https://www.links.hrhttps://www.links.hr/hr/procesor-intel-core-i3-8100-s-1151-3-6ghz-6mb-cache-gpu-quad-core-050600063";

$data = file("1155662414560805.txt");

$out = array();

foreach($data as $k) {
	if(trim($k) !== trim($DELETE)) {
		$out[] = $k;
	}
}
var_dump($out);

$fp = fopen("./1155662414560805.txt", "w+");
flock($fp, LOCK_EX);
foreach($out as $line) {
 fwrite($fp, $line);
}
flock($fp, LOCK_UN);
fclose($fp);  
?>