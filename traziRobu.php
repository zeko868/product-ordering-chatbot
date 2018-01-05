<?php
ob_start();
header('Content-Type: text/html; charset=utf-8');
ini_set("allow_url_fopen", 1);
$trazilica = "https://www.links.hr/hr/search?q=";
$pojamZaPretragu = "i7"; //Trenutno hardcoded
$url = $trazilica . $pojamZaPretragu;

$lines = file($url);

$nadjeno = TRUE;
echo $url . "<br/>";

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
    echo json_encode($obj, JSON_UNESCAPED_UNICODE);
}

function parsiranjeProizvoda($polje) {
    $naziv = "";
    $cijena = "";
    for ($i = 0; $i < count($polje); $i++) {
        if (strpos($polje[$i], "product-title")) {
            $naziv = parsirajIme($polje[$i + 1]);
            //echo "Naziv: " . $naziv . "<br>";
        }
        if (strpos($polje[$i], "price actual-price")) {
            $cijena = parsirajCijenu($polje[$i]);
            //echo "Cijena: " . $cijena . "<br>";
        }
    }
    $obj = (object) ['naziv' => $naziv, 'cijena' => $cijena];
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
