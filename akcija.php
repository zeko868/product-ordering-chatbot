<?php


const ACCESS_TOKEN = 'EAACN8hwDY8QBAEcLkz9b9FZB2QXVgr92ZBduX8cEU1rfZBR7kOtzurRUtiWkZCan496HmhLyiWLnk86RAKsfMSiYKxZBdnIC6KftcZBy7EODHgPBERWpjFZCgqvPYWGUQyutGc76VccANwiCvrPxa9BCO7f3jnbTs2jXjZCzXk06OgZDZD';


$url1 = "https://www.links.hr/hr/discounted-products?specFilters=1904";

$lines1 = file($url1);



for ($index = 0; $index < count($lines1); $index++) {
    $polje = array();
	
    if (strpos($lines1[$index], 'div class="product-item"')) {
        for ($i = $index; $i < $index + 54; $i++) {
            $polje[] = $lines1[$i];
        }
        $obj[] = parsiranjeProizvoda($polje);
    }
}

$today = new DateTime();

$to = $today->format("Y-m-d")." 23:59:59";
$week_before = $today->modify("-7 day");

$from = $week_before->format("Y-m-d")." 00:00:00";

$conn = pg_connect('postgres://gsnnkdcbycpcyq:ba69093c4619187587610e80e188d4f812627530798ef14d3133bd3541b00290@ec2-54-228-235-185.eu-west-1.compute.amazonaws.com:5432/dedt0mj008catq');
$result = pg_query("select distinct id_facebook, string_pretrage from pregledavanja where datum_pretrage <= '$to' and datum_pretrage >= '$from' and string_pretrage != '';");


while($discountInfo = pg_fetch_array($result, null, PGSQL_ASSOC)){
	$pretrage = explode(" ", $discountInfo["string_pretrage"]);
	//var_dump($pretrage);
	$buttons = array();
	foreach($pretrage as $p){
		
		foreach($obj as $o){
			if(strpos($o->naziv,$p ) !== false){
				//echo 'https://www.links.hr' . $o->link . '#quickTabs';
				array_push($buttons, array(
					'title' => htmlspecialchars_decode($o->naziv, ENT_QUOTES),
					'image_url' => $obj[$i]->slika,
					'subtitle' => htmlspecialchars_decode($o->naziv . ', cijena: ' . $obj[$i]->cijena, ENT_QUOTES),
					'default_action' => [
						'type' => 'web_url',
						'url' => 'https://www.links.hr/hr/tipkovnica-logitech-k375s-bluetooth-stalak-za-smartphone-tablet-unifying-receiver-bezicna-crna-101200320#quickTabs',
						'messenger_extensions' => true,
						'webview_height_ratio'=> 'TALL'
					],
					'buttons' => array(
						array(
							'type' => 'postback',
							'payload' => $o->link,
							'title' => 'Naruči proizvod'
							)
						)
					)
				);
			}
		}
		
		
	}
	
	$answer = [
		'type'=>'template',
		'payload'=>[
			'template_type'=>'generic',
			'elements'=> $buttons
		]
	];

	replyBackSpecificObject([ 'attachment' => $answer ], $discountInfo["id_facebook"]);
}

pg_free_result($result);

function replyBackSpecificObject($answer, $senderId) {
	pg_close();
	$response = [
		'messaging_type' => 'RESPONSE',
		'recipient' => [ 'id' => $senderId ],
		'message' => $answer
	];
	$ch = curl_init('https://graph.facebook.com/v2.6/me/messages?access_token=' . ACCESS_TOKEN);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	$result = curl_exec($ch);
	curl_close($ch);
	exit();
}


function parsiranjeProizvoda($polje) {
    for ($i = 0; $i < count($polje); $i++) {
        if (strpos($polje[$i], 'product-title')) {
            $naziv = parsirajIme($polje[$i + 1]);
            continue;
        }
        if (strpos($polje[$i], 'price actual-price')) {
            $cijena = parsirajCijenu($polje[$i]);
            continue;
        }
        if (strpos($polje[$i], 'a href')) {
            $link = parsirajLink($polje[$i]);
        }
        if(strpos($polje[$i], 'src=')){
            $slika= parsirajSliku($polje[$i]);
        }
    }
    $obj = (object) ['naziv' => $naziv, 'link' => $link, 'slika'=> $slika, 'cijena' => $cijena];
    return $obj;
}

function parsirajIme($linija) {
    preg_match('/title="(.*?)"/', $linija, $match);
    return $match[1];
}

function parsirajCijenu($linija) {
    preg_match_all('/(?<=\>).+?(?=\<)/', $linija, $match);
    $cijena = $match[0][0] . "," . $match[0][1];
    return $cijena;
}

function parsirajLink($linija) {
    preg_match('/"(.*?)"/', $linija, $match);
    return $match[1];
}

function parsirajProizvodace($linija) {
    preg_match('/"(.*)">(.*?)</', $linija, $match);
    $id = $match[1];
    $proizvodac = $match[2];
    $obj = (object) ['proizvodac' => $proizvodac, 'id' => $id];
    return $obj;
}

function parsirajSliku($linija){
    preg_match('/src="(.*?)"/', $linija, $match);
    $slika= $match[1];
    return $slika;
}
?>