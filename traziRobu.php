<?php

ini_set('allow_url_fopen', 1);

$stranica = 'https://www.links.hr';
$trazilica = '/hr/search?q=';

//popunjavanje podacima koirsnika
$dodatak = '&adv=true&adv=false'; //??? vjerojatno treba za search
$pojamZaPretragu = $translated['tekst'];
//$pojamZaPretragu = "grafička";
if (isset($translated['ostalo']['cijenaOd'])) {
    $cMin = $translated['ostalo']['cijenaOd'];
} else {
    $cMin = '';
}
if (isset($translated['ostalo']['cijenaDo'])) {
    $cMax = $translated['ostalo']['cijenaDo'];
} else {
    $cMax = '';
}

//$cMin = "";
//$cMax = "";


$asc = '&orderby=10';
$desc = '&orderby=11';

$proizvodi = '&mid=';
$cijenaOd = '&pf=';
$cijenaDo = '&pt=';

$url = $stranica . $trazilica;
$lines = file($url);

$nadjeno = FALSE;

for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], '<label for="mid">Proizvođač:</label>')) {
        $i = $i + 2;
        while (strpos($lines[$i], '</select>') === FALSE) {
            $pro[] = parsirajProizvodace($lines[$i]);
            $i++;
        }
        break;
    }
}

//filteri -- proizvođač
//$proizvodac = 0;
$proizvodac = nadjiProizvodaca($translated['ostalo']['ostaliFilteri'], $pro); // 0 ako nije naveden

//url za pretragu

$url1 = $stranica . $trazilica . urlencode($pojamZaPretragu)
        . $dodatak
        . $proizvodi . $proizvodac        
        . $cijenaOd . $cMin
        . $cijenaDo . $cMax;

$lines1 = file($url1);

$obj = array();
for ($index = 0; $index < count($lines1); $index++) {
    $polje = array();
    if (strpos($lines1[$index], 'Nisu nađeni proizvodi koji odgovaraju zadanim kriterijima')) {
        break;
    }
    if (strpos($lines1[$index], 'div class="product-item"')) {
        for ($i = $index; $i < $index + 54; $i++) {
            $polje[] = $lines1[$i];
        }
        $obj[] = parsiranjeProizvoda($polje);
        if (!$nadjeno) {
            $nadjeno = TRUE;
        }
    }
}

 /*if ($nadjeno) {
  echo json_encode($obj, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }*/ 

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

function nadjiProizvodaca($naziv, $pro) {
    for ($i = 0; $i < count($naziv); $i++) {
        foreach ($pro as $object) {
            if (strtoupper($object->proizvodac) === $naziv[$i]) {
                return $object->id;
            }
        }
    }
    return 0;
}
?>

