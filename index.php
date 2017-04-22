<?php
$dbHandler = new mysqli('foi-konzultacije.info', 'admin', 'f1f2f3f4', 'konzultacije');
$dbHandler->set_charset("utf8");
$command = "SELECT count(*) FROM users WHERE id = ".$_GET['senderid'].";";
print_r($dbHandler);
$resultSet = $dbHandler->query($command);

if(intval($resultSet->fetch_assoc()['count(*)']) === 0){
	
}