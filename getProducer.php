<?php

ini_set('allow_url_fopen', 1);

$url = 'https://en.wikipedia.org/wiki/List_of_computer_system_manufacturers';

$lines = file($url);

//var_dump($lines1);

//<span class="mw-headline" id="Current">Current</span>
$pocetak = 0;
$kraj = 0;
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], '<span class="mw-headline" id="Current">Current</span>')) {
        //var_dump($lines[$i+3]);
		
		$i += 3;
		$pocetak = $i;
		$end = false;
		for ($j = $i; $j < count($lines) && !$end; $j++) {
		
			
			//var_dump(!strpos($lines[$j], '<span class="mw-headline" id="Companies_that_have_ceased_production">Companies that have ceased production</span>') === FALSE);
			if(!strpos($lines[$j], '<span class="mw-headline" id="Companies_that_have_ceased_production">Companies that have ceased production</span>') === FALSE){
				
				$end = true;
				$kraj = $j;
				
			}
			
		}
    }
	
}

$proizvodaci = [];

for($i=$pocetak; $i < $kraj; $i++){
	//echo $lines[$i];
	
	preg_match('/title="(.*?)"/', $lines[$i], $match);
	
	if(isset($match[1])){
		
		$proizvodac = $match[1];
		
		if(strpos($proizvodac, ' (')){
			$p = explode(" (",$proizvodac);
			
			$proizvodac = $p[0];
			
			
		}
		
		$p = explode(" ",$proizvodac);
			
		$proizvodac = $p[0];
		
		$p = explode(",",$proizvodac);
			
		$proizvodac = $p[0];
		
		array_push($proizvodaci, $proizvodac);
	} 
}

$myfile = fopen("producers.json", "w") or die("Unable to open file!");

fwrite($myfile, json_encode($proizvodaci));

fclose($myfile);

echo json_encode($proizvodaci);
?>