<?php

function translateInput($inputText, $source, $target, $changeCase = true){
    if ($changeCase) {
        $inputText = mb_strtolower($inputText);
    }
    $curl = curl_init();
    $inputText = preg_replace('/(?:(\d)|\b)(kn|kuna|kune|hrk)\b/', '\1 hrvatskih kuna', $inputText);    // when translating from croatian language to english language, currency symbol is replaced with dollar sign - on the other hand, when translating from bosnian language to english, currency names/symbols are preserved (i have no idea why)

    curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://translation.googleapis.com/language/translate/v2?key=' . API_KEY,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode(
        [
            'q' => $inputText,
            'target' => $target,
            'source' => $source
        ],
        JSON_FORCE_OBJECT
    ),
    CURLOPT_HTTPHEADER => array(
        'cache-control: no-cache',
        'content-type: application/json'
        ),
    ));

    $response = curl_exec($curl);
    $json = json_decode($response, true); 

    curl_close($curl);

    $data = $json['data']['translations'][0];
    if($data){
        $returnValue['status'] = 'OK';
        if ($changeCase) {
            $returnValue['translate'] = mb_strtoupper(htmlspecialchars_decode($data['translatedText'], ENT_QUOTES));
        }
        else {
            $returnValue['translate'] = $data['translatedText'];
        }
    }else{
        $returnValue['status'] = 'ERROR';
    }
    
    return $returnValue;
}

function NLPtext($translatedText){
	
	
    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://language.googleapis.com/v1beta2/documents:analyzeEntities?key=' . API_KEY,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode(
        [
            'document' => [
                'type' => 'plain_text',
                'language' => 'en',
                'content' => $translatedText
            ],
            'encodingType' => 'UTF8'
        ],
        JSON_FORCE_OBJECT
    ),
    CURLOPT_HTTPHEADER => array(
        'cache-control: no-cache',
        'content-type: application/json'
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    
    $json = json_decode($response, true);
    $data = $json['entities'];

    foreach($data as $value){
        switch($value['type']){
            case 'ORGANIZATION':
                $nlp['proizvodac'] = $value['name'];
                break;
            case 'CONSUMER_GOOD':
                $nlp['proizvod'] = $value['name'];
                break;
        }
    }
	
	$json = json_decode( file_get_contents('./producers.json') , true);


	foreach($json as $p){
		if(mb_stripos($translatedText , $p) !== false){
			$nlp['proizvodac'] = $p;
			break;
		}
	}
	
	$json = json_decode( file_get_contents('./components.json') , true);


	foreach($json as $p){
		if(mb_stripos($translatedText , $p) !== false){
			$nlp['proizvod'] = $p;
			break;
		}
	}
    
    $string = '';

    if(isset($nlp['proizvodac']))
        $string .= $nlp['proizvodac'] . ' ';
    if(isset($nlp['proizvod']))
        $string .= $nlp['proizvod'];
    

    if(!isset($nlp['proizvodac']) && !isset($nlp['proizvod'])){
        echo 'Doslo je do pogreske!';
        exit();
    }

    $curl = curl_init();
    
    $ostalo = array();

    curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://language.googleapis.com/v1/documents:analyzeSyntax?key=' . API_KEY,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode(
        [
            'document' => [
                'type' => 'plain_text',
                'language' => 'en',
                'content' => $translatedText
            ],
            'encodingType' => 'UTF8'
        ],
        JSON_FORCE_OBJECT
    ),
    CURLOPT_HTTPHEADER => array(
        'cache-control: no-cache',
        'content-type: application/json'
        ),
    ));
    
    $response = curl_exec($curl);
    
    $json = json_decode($response,true);
    $data = $json['tokens'];

    $ost = array();

    for($i = 0; $i < sizeof($data); $i++){
        if(strpos($string, $data[$i]['text']['content'])===false && $data[$i]['partOfSpeech']['tag'] === 'NOUN' && !in_array($data[$i]['text']['content'], ['THOUSANDS', 'HUNDREDS', 'TENS'])){
            array_push($ost,translateInput($data[$i]['text']['content'],'en','hr')['translate']);
        }
        if($data[$i]['partOfSpeech']['tag'] === 'ADP' && $data[$i]['text']['content'] === 'BETWEEN'){
            $cijenaOd = '';
            for ($j = $i; isset($data[$j]) && $data[$j]['partOfSpeech']['tag'] !== 'NUM'; $j++) {
                true;   // dođi do prvog sljedećeg broja
            }
            $pocetakBroja = $j;
            for (; isset($data[$j]) && $data[$j]['partOfSpeech']['tag'] === 'NUM'; $j++) {
                $cijenaOd .= $data[$j]['text']['content'] . ' ';
            }
            $zavrsetakBroja = $j-1;
            $ostalo['cijenaOd'] = dajNumerickuReprezentacijuBroja($cijenaOd);
            $ostalo['valutaOd'] = dajSpomenutuValutu($data, $pocetakBroja, $zavrsetakBroja);
            for (; isset($data[$j]) && $data[$j]['partOfSpeech']['tag'] !== 'NUM'; $j++) {
                true;   // dođi do prvog sljedećeg broja
            }
            $pocetakBroja = $j;
            $cijenaDo = '';
            for (; isset($data[$j]) && $data[$j]['partOfSpeech']['tag'] === 'NUM'; $j++) {
                $cijenaDo .= $data[$j]['text']['content'] . ' ';
            }
            $zavrsetakBroja = $j-1;
            $ostalo['cijenaDo'] = dajNumerickuReprezentacijuBroja($cijenaDo);
            $ostalo['valutaDo'] = dajSpomenutuValutu($data, $pocetakBroja, $zavrsetakBroja);
            if ($ostalo['valutaOd'] !== null && $ostalo['valutaOd'] !== 'HRK') {
                $tecaj = dohvatiTecaj($ostalo['valutaOd']);
                $ostalo['cijenaOd'] = $tecaj * $ostalo['cijenaOd'];
            }
            if ($ostalo['valutaDo'] !== null && $ostalo['valutaDo'] !== 'HRK') {
                if ($ostalo['valutaDo'] === $ostalo['valutaOd']) {  // avoid fetching exchange rate that was already fetched
                    $ostalo['cijenaDo'] = $tecaj * $ostalo['cijenaDo'];
                }
                else {
                    $tecaj = dohvatiTecaj($ostalo['valutaDo']);
                    $ostalo['cijenaDo'] = $tecaj * $ostalo['cijenaDo'];
                    if ($ostalo['valutaOd'] === null) {
                        $ostalo['cijenaOd'] = $tecaj * $ostalo['cijenaOd'];
                    }
                }
            }
        }
        else if (strpos($string, $data[$i]['text']['content'])===false && !isset($ostalo['cijenaOd']) && !isset($ostalo['cijenaDo'])) {
            $j = $i;
            $cijena = '';
            if ($data[$i]['partOfSpeech']['tag'] === 'NUM') {
                for (; isset($data[$i]) && $data[$i]['partOfSpeech']['tag'] === 'NUM'; $i++) {  // preskakanje ponovne interpretacije ostalih brojeva (ako više riječi predstavlja jedan broj) u sljedećoj iteraciji vanjske petlje
                    $cijena .= $data[$i]['text']['content'] . ' ';
                }
                $i--;
            }
            else if (in_array($data[$i]['text']['content'], ['THOUSANDS', 'HUNDREDS', 'TENS'])) {   // Google NLP riječi poput thousands, hundreds i tens u kontekstu "couple of/few/several hundreds" tretira kao imenice, a ne brojeve
                $cijena = $data[$i]['lemma'];
            }
            else {  // ako trenutna riječ nema veze s brojevima
                continue;
            }
            $pocetakBroja = $j;
            $zavrsetakBroja = $i;

            $iznos = dajNumerickuReprezentacijuBroja($cijena);

            for (; $j >= 0; $j--){
                if ($data[$j]['partOfSpeech']['tag'] === 'ADJ' || $data[$j]['partOfSpeech']['tag'] === 'ADP') {
                    switch ($data[$j]['text']['content']) {
                        case 'OVER':
                        case 'MORE':
                            $ostalo['cijenaOd'] = $iznos;
                            break 2;
                        case 'LESS':
                            $ostalo['cijenaDo'] = $iznos;
                            break 2;
                        case 'AROUND':
                        case 'ABOUT':
                        case 'CCA.':
                        case 'CIRCA':
                        case 'APPROXIMATELY':
                            $ostalo['cijenaOd'] = 0.7 * $iznos;
                            $ostalo['cijenaDo'] = 1.3 * $iznos;
                            break 2;
                        case 'FEW':
                        case 'SEVERAL':
                            $ostalo['cijenaOd'] = 2 * $iznos;
                            $ostalo['cijenaDo'] = 10 * $iznos;
                            break 2;
                        case 'TENS':    // obuhvaća slučajeve poput 'tens of thousands'
                            $ostalo['cijenaOd'] = 10 * $iznos;
                            $ostalo['cijenaDo'] = 100 * $iznos;
                            break 2;
                        case 'HUNDREDS':
                            $ostalo['cijenaOd'] = 100 * $iznos;
                            $ostalo['cijenaDo'] = 1000 * $iznos;
                            break 2;
                        case 'THOUSANDS':
                            $ostalo['cijenaOd'] = 1000 * $iznos;
                            $ostalo['cijenaDo'] = 10000 * $iznos;
                            break 2;
                    }
                }
                if ($data[$j]['text']['content'] === 'COUPLE') {
                    $ostalo['cijenaOd'] = 2 * $iznos;
                    $ostalo['cijenaDo'] = 6 * $iznos;
                    break;
                }
            }
            $ostalo['valuta'] = dajSpomenutuValutu($data, $pocetakBroja, $zavrsetakBroja);
            if ($ostalo['valuta'] !== null && $ostalo['valuta'] !== 'HRK') {
                $tecaj = dohvatiTecaj($ostalo['valuta']);
                if (isset($ostalo['cijenaOd'])) {
                    $ostalo['cijenaOd'] = $tecaj * $ostalo['cijenaOd'];
                }
                if (isset($ostalo['cijenaDo'])) {
                    $ostalo['cijenaDo'] = $tecaj * $ostalo['cijenaDo'];
                }
            }
        }
    }
    if (isset($ostalo['cijenaOd'])) {
        $ostalo['cijenaOd'] = intval($ostalo['cijenaOd']);
    }
    if (isset($ostalo['cijenaDo'])) {
        $ostalo['cijenaDo'] = intval($ostalo['cijenaDo']);
    }
    $ostalo['ostaliFilteri'] = $ost;

    curl_close($curl);
    $s['tekst'] = $string;
    $s['ostalo'] = $ostalo;
    return $s;
}

function dajNumerickuReprezentacijuBroja($brojRazlicitogFormata) {   // broj riječima ili miješano (brojevi i slova)
    $brojRazlicitogFormata = str_replace(',', '', $brojRazlicitogFormata);
    $brojRazlicitogFormata = mb_strtolower($brojRazlicitogFormata);
    if (preg_match_all('/(\d+)(?:\.\d*)?/', $brojRazlicitogFormata, $matches, PREG_OFFSET_CAPTURE)) {
        $nf = new NumberFormatter('en', NumberFormatter::SPELLOUT);
        foreach (array_reverse($matches[1]) as $index => $m) {
            $brojWord = $nf->format($m[0]);
            $brojRazlicitogFormata = substr_replace($brojRazlicitogFormata, $brojWord, $m[1], strlen($matches[0][$index][0]));
        }
    }
    require_once 'vendor/words-to-number.php';
    return intval(wordsToNumber($brojRazlicitogFormata));
}

function dajSpomenutuValutu($rijeci, $pocetakBroja, $zavrsetakBroja) {
    $moguceValutneOznake = [];
    $postojiOfIzaBroja = false;
    foreach (json_decode(file_get_contents('currencies.json'), true) as $currencyInfo) {
        foreach ($currencyInfo['alternative_names'] as $altNameInfo) {
            $currencySymbol = $altNameInfo['name'];
            $brojRijeciSimbola = substr_count($currencySymbol, ' ') + 1;
            foreach ($altNameInfo['location'] as $currencySymbolLocation) {
                $pozicijaMoguceValutneOznake = $brojRijeciSimbola * ($currencySymbolLocation === 'prev' ? -1 : 1);
                if (!isset($moguceValutneOznake[$pozicijaMoguceValutneOznake])) {
                    if ($pozicijaMoguceValutneOznake > 0) {
                        for ($i = $pozicijaMoguceValutneOznake; $i > 0; $i--) {
                            if (isset($moguceValutneOznake[$i])) {
                                break;
                            }
                        }
                        if ($i === 0) {
                            if (isset($rijeci[$zavrsetakBroja+1])) {
                                if ($rijeci[$zavrsetakBroja+1]['text']['content'] === 'OF') {  // npr. thousands of dollars
                                    $postojiOfIzaBroja = true;
                                    if (isset($rijeci[$zavrsetakBroja+2])) {
                                        $moguceValutneOznake[1] = mb_strtolower($rijeci[$zavrsetakBroja+2]['text']['content']);
                                    }
                                    else {
                                        $moguceValutneOznake[1] = '';
                                    }
                                }
                                else {
                                    $moguceValutneOznake[1] = mb_strtolower($rijeci[$zavrsetakBroja+1]['text']['content']);
                                }
                            }
                            else {
                                $moguceValutneOznake[1] = '';
                            }
                            $i++;
                        }
                        $offset = $postojiOfIzaBroja ? 1 : 0;
                        $pozicijaZadnjeRijeciZaProvjeriti = $zavrsetakBroja + $pozicijaMoguceValutneOznake + $offset;
                        for ($j = $zavrsetakBroja+1+$i+$offset, $k=$i; $j <= $pozicijaZadnjeRijeciZaProvjeriti; $j++, $k++) {
                            if (isset($rijeci[$j])) {
                                $moguceValutneOznake[$k+1] = $moguceValutneOznake[$k] . ' ' . mb_strtolower($rijeci[$j]['text']['content']);
                            }
                            else {
                                $moguceValutneOznake[$k+1] = '';
                            }
                        }
                    }
                    else {
                        for ($i = $pozicijaMoguceValutneOznake; $i < 0; $i++) {
                            if (isset($moguceValutneOznake[$i])) {
                                break;
                            }
                        }
                        if ($i === 0) {
                            if (isset($rijeci[$pocetakBroja-1])) {
                                $moguceValutneOznake[-1] = mb_strtolower($rijeci[$pocetakBroja-1]['text']['content']);
                            }
                            else {
                                $moguceValutneOznake[-1] = '';
                            }
                            $i--;
                        }
                        $pozicijaZadnjeRijeciZaProvjeriti = $pocetakBroja + $pozicijaMoguceValutneOznake;
                        for ($j = $pocetakBroja-1+$i, $k=$i; $j >= $pozicijaZadnjeRijeciZaProvjeriti; $j--, $k--) {
                            if (isset($rijeci[$j])) {
                                $moguceValutneOznake[$k-1] = mb_strtolower($rijeci[$j]['text']['content']) . ' ' . $moguceValutneOznake[$k];
                            }
                            else {
                                $moguceValutneOznake[$k-1] = '';
                            }
                        }
                    }
                }
                $mogucaValutnaOznaka = $moguceValutneOznake[$pozicijaMoguceValutneOznake];
                if (!empty($mogucaValutnaOznaka)) {
                    if ($currencySymbol === $mogucaValutnaOznaka) {
                        return $currencyInfo['currency'];
                    }
                }
            }
        }
    }
    return null;
}

function dohvatiTecaj($valutaIz, $valutaPrema='HRK') {
    $param = "${valutaIz}_$valutaPrema";
    return json_decode(file_get_contents("https://free.currencyconverterapi.com/api/v5/convert?q=$param&compact=y"), true)[$param]['val'];

}

function prilagodiZahtjev($inputText){
    $json_params = file_get_contents('./promjeneUZahtjevu.json');

    $json = json_decode($json_params, true);

    
    foreach($json as $val){
        $inputText = str_replace($val['original'], $val['promjena'], $inputText);
    }    
    
    if(($pozicijaProcesora = strpos($inputText, 'PROCESOR'))!==false && strpos($inputText, 'RAČUNALNI')===false){
        $input = substr($inputText, 0, $pozicijaProcesora) . 'RAČUNALNI ' . substr($inputText, $pozicijaProcesora, strlen($inputText));
        return $input;
    }
    
    return $inputText;
}

function urediIzlaz($inputText){
    $inputText = mb_strtolower($inputText);
    if(($pozicijaRacunalnog = strpos($inputText, 'računalni')) !== false){
        $input = substr($inputText, 0, $pozicijaRacunalnog) . substr($inputText, $pozicijaRacunalnog + 11, strlen($inputText));
        return $input;
    }
    return $inputText;
}

?>