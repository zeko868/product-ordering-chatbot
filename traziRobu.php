<?php
ob_start();
header('Content-Type: text/html; charset=utf-8');
ini_set("allow_url_fopen", 1);

$stranica = "https://www.links.hr";
$trazilica = "/hr/search?q=";
$pojamZaPretragu = "procesor"; //Trenutno hardcoded
$dodatak = "&adv=true&adv=false"; //??? vjerojatno treba za search
$proizvodac = 658; // AMD --- 0 ako nije navedeno
$cMin = 1000;
$cMax = 20000;

$asc = "&orderby=10";
$desc = "&orderby=11";

$proizvodi = "&mid=";
$cijenaOd = "&pf=";
$cijenaDo = "&pt=";

$url = $stranica . $trazilica;
$lines = file($url);

//filteri
$filterProizvodac = "";
$filterCijenaMin = "";
$filterCijenaMax = "";


$nadjeno = FALSE;

for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], "<label for=\"mid\">Proizvođač:</label>")) {
        $i = $i + 2;
        while (strpos($lines[$i], "</select>") === FALSE) {
            $pro[] = parsirajProizvodace($lines[$i]);
            $i++;
        }
        break;
    }
}
//echo json_encode($pro, JSON_UNESCAPED_UNICODE);


//url za pretragu
if ($filterProizvodac !== "") {
    $rez = nadjiProizvodaca($filterProizvodac, $pro);
    if($rez === FALSE){
        
    }
    $proizvodac = $filterProizvodac;
}
if ($filterCijenaMin !== "") {
    $cMin = $filterCijenaMin;
}
if ($filterCijenaMax !== "") {
    $cMax = $filterCijenaMax;
}

$url1 = $stranica . $trazilica . urlencode($pojamZaPretragu)
        . $proizvodi . $proizvodac
        . $dodatak
        . $cijenaOd . $cMin
        . $cijenaDo . $cMax
        . $asc;
echo $url1 . "<br/>";

$lines1 = file($url1);

for ($index = 0; $index < count($lines1); $index++) {
    $polje = array();
    if (strpos($lines1[$index], "Nisu nađeni proizvodi koji odgovaraju zadanim kriterijima")) {
        echo "Nisu nađeni proizvodi koji odgovaraju zadanim kriterijima <br/>";
        break;
    }
    if (strpos($lines1[$index], "div class=\"product-item\"")) {
        for ($i = $index; $i < $index + 54; $i++) {
            $polje[] = $lines1[$i];
        }
        $obj[] = parsiranjeProizvoda($polje);
        if (!$nadjeno) {
            $nadjeno = TRUE;
        }
        unset($polje);
    }
}

if ($nadjeno) {
    echo json_encode($obj, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function parsiranjeProizvoda($polje) {
    $naziv = "";
    $cijena = "";
    $link = "";
    for ($i = 0; $i < count($polje); $i++) {
        if (strpos($polje[$i], "product-title")) {
            $naziv = parsirajIme($polje[$i + 1]);
            continue;
            //echo "Naziv: " . $naziv . "<br>";
        }
        if (strpos($polje[$i], "price actual-price")) {
            $cijena = parsirajCijenu($polje[$i]);
            continue;
            //echo "Cijena: " . $cijena . "<br>";
        }
        if (strpos($polje[$i], "a href")) {
            $link = parsirajLink($polje[$i]);
        }
    }
    $obj = (object) ['naziv' => $naziv, 'link' => $link, 'cijena' => $cijena];
    return $obj;
}

function parsirajIme($linija) {
    preg_match("/title=\"(.*?)\"/", $linija, $match);
    return $match[1];
}

function parsirajCijenu($linija) {
    preg_match_all("/(?<=\>).+?(?=\<)/", $linija, $match);
    $cijena = $match[0][0] . "," . $match[0][1];
    return $cijena;
}

function parsirajLink($linija) {
    preg_match("/\"(.*?)\"/", $linija, $match);
    return $match[1];
}

function parsirajProizvodace($linija) {
    preg_match("/\"(.*)\">(.*?)</", $linija, $match);
    $id = $match[1];
    $proizvodac = $match[2];
    $obj = (object) ['proizvodac' => $proizvodac, 'id' => $id];
    return $obj;
}

function nadjiProizvodaca($naziv, $pro) {
    foreach ($pro as $object) {
        if ($object->proizvodac === $naziv) {
            return $object->id;
        }
    }
    return FALSE;
}

nadjiProizvodaca("AMD", $pro);
?>
<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title></title>
    </head>
    <body>
        <?php
        ob_flush();
        ?>
    </body>
</html>
