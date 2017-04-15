<?php
$xml=simplexml_load_file('informacije.xml') or die("Error: Cannot create object");
print_r($xml->employees);

?>