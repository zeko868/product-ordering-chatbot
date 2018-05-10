<?php
$myfile = fopen("1155662414560805.txt", "r") or die("Unable to open file!");
echo fread($myfile,filesize("1155662414560805.txt"));
fclose($myfile);
?>