<?php
$xml=simplexml_load_file('informacije.xml') or die("Error: Cannot create object");
$item = $xml->employee[0];
foreach($item->consultation as $i){
			//$i->day.$i->time_from.$i->time_to
			echo $i->term;
		}
echo $xml->employee[0]->firstname;

?>