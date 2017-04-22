<?php
$dbHandler = new mysqli('foi-konzultacije.info', 'admin', 'f1f2f3f4', 'konzultacije');
$dbHandler->set_charset("utf8");
$command = "SELECT count(*) FROM users WHERE id = ".$_POST['senderid'].";";

$resultSet = $dbHandler->query($command);
print_r($resultSet);
if(intval($resultSet->fetch_assoc()['count(*)']) === 0){
	
}