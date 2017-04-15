<?php
$xml=simplexml_load_file('informacije.xml') or die("Error: Cannot create object");
echo $xml->employee[0]->firstname;

?>