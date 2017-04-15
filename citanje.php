<?php
$xml=simplexml_load_file('informacije.xml') or die("Error: Cannot create object");
$item = $xml->employee[0];
foreach($item->consultation->term as $i){
			//$i->day.$i->time_from.$i->time_to
			echo $i->day;
		}

?>