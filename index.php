<?php
$dbHandler = new mysqli('207.154.255.63', 'admin', 'f1f2f3f4', 'konzultacije');
$dbHandler->set_charset("utf8");
$command = "SELECT count(*) FROM users WHERE id = ".$_GET['senderid'].";";
print_r($dbHandler);
$resultSet = $dbHandler->query($command);

if(intval($resultSet->fetch_assoc()['count(*)']) === 0){
	
}