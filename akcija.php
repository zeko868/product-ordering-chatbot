<?php





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

//var_dump($obj);

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