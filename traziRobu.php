<?php
ob_start();
header('Content-Type: text/html; charset=utf-8');
ini_set("allow_url_fopen", 1);
$stranica = "https://www.links.hr";
$trazilica= "/hr/search?q=";
$pojamZaPretragu = "grafička"; //Trenutno hardcoded 
$url = $stranica. $trazilica . urlencode($pojamZaPretragu);

$lines = file($url);

$nadjeno = TRUE;


for ($index = 0; $index < count($lines); $index++) {
    $polje = array();
    if (strpos($lines[$index], "Nisu nađeni proizvodi koji odgovaraju zadanim kriterijima")) {
        echo "Nisu nađeni proizvodi koji odgovaraju zadanim kriterijima <br/>";
        $nadjeno = FALSE;
        break;
    }
    if (strpos($lines[$index], "div class=\"product-item\"")) {
        for ($i = $index; $i < $index + 54; $i++) {
            $polje[] = $lines[$i];
        }
        $obj[] = parsiranjeProizvoda($polje);
        //break;
        unset($polje);
    }
}

if ($nadjeno) {
    echo json_encode($obj, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

function parsiranjeProizvoda($polje) {
    $naziv = "";
    $cijena = "";
    $link= "";
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
        if(strpos($polje[$i], "a href")){
            $link= parsirajLink($polje[$i]);
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

function parsirajLink($linija){
    preg_match("/\"(.*?)\"/", $linija, $match);
    return $match[1];
}
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
