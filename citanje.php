<?php
$xml=simplexml_load_file('informacije.xml') or die("Error: Cannot create object");
$item = $xml->employee[0];
$k = array();
foreach($item->consultation->term as $i){
			//$i->day.$i->time_from.$i->time_to
			array_push($k,$i->day.$i->time_from.$i->time_to);
		}
for($i=0;$i<sizeof($k);$i++)
	echo $k[$i];
?>