<?php

ob_start();
header('Content-Type: text/html; charset=utf-8');
//hardcoded za sad
//$linkProizovada = "/hr/graficka-kartica-used-pci-e-gainward-geforce-gtx-970-phoenix-4gb-ddr5-dvi-hdmi-mdp-8100001048";
//nedostupno
//$linkNedostupno = "/hr/graficka-kartica-used-pci-e-gainward-geforce-gtx-960-phantom-glh-2gb-ddr5-dualdvi-hdmi-dp-810000861";
$nedostupno = FALSE;

$stranica = "https://www.links.hr";

$lines = file($stranica . $linkProizovada);

/* for ($i = 0; $i < count($lines); $i++) {
  echo "Linija: " . $i . "-->" . htmlentities($lines[$i]) . "<br/>";
  } */

for ($index = 0; $index < count($lines); $index++) {
    $polje = array();
    if (strpos($lines[$index], "class=\"label\">Dostupnost")) {
        $vrijednost = preg_match("/\>(.*?)\</", $lines[$index + 1], $match);
        if ($match[1] === "Nedostupno") {
            echo json_encode("Nedostupno");
            $nedostupno = TRUE;
            break;
        }
    }
    if (strpos($lines[$index], "class=\"warehouseInventory\"")) {
        for ($i = $index; $i < $index + 111; $i++) {
            $polje[] = $lines[$i];
        }
        $obj[] = parsiranjeSkladista($polje);
        break;
        //unset($polje);
    }
}

if (!$nedostupno) {
    $dostupnosti = $obj;
    require 'pronadjiNajblizeSkladiste.php';
    $buttons = array();
    switch ($najblizeIshodiste) {
        case null:
            $replyContent = 'Pojavio se neuspjeh kod pokušaja pronalaska obližnje poslovnice u kojoj je dostupan traženi artikl. Želite li ga naručiti dostavom?';
            break;
        case false:
            $replyContent = 'Traženi artikl je dostupan u centralnom skladištu te ga je moguće samo dostaviti. Želite li ga naručiti dostavom?';
            break;
        default:
            $replyContent = "$najblizeIshodiste Vam je najbliže mjesto s našom poslovnicom u kojoj je dostupan traženi artikl.";
            array_push($buttons, array('type'=>'postback', 'title'=>'Pokupit ću sam', 'payload' => "$linkProizovada $najblizeIshodiste"));
    }
    array_push($buttons, array('type'=>'postback', 'title'=>'Želim dostavu', 'payload' => "$linkProizovada dostava"));
    $answer = [
        'type'=>'template',
        'payload'=>[
            'template_type'=>'button',
            'text'=>$replyContent,
            'buttons'=> $buttons
        ]
    ];
}else{
    $answer = 'Ispričavamo se, traženi artikl trenutno nije dostupan!';
}

function parsiranjeSkladista($polje) {
    $naziv = "";
    $dostupnost = "";
    $n = FALSE;
    $d = FALSE;
    for ($i = 0; $i < count($polje); $i++) {
        if (strpos($polje[$i], "class=\"warehouse\"")) {
            $naziv = parsirajIme($polje[$i]);
            if ($naziv) {
                $n = TRUE;
            }
            else {
                $n = FALSE;
            }
        }
        else if ($n && strpos($polje[$i], "class=\"circle active\"")) {
            $dostupnost = parsirajDostupnost($polje[$i]);
            $d = TRUE;
            $obj[] = (object) ["naziv" => $naziv, "dostupnost" => $dostupnost];
            $n = FALSE;
            $d = FALSE;
        }
    }
    return $obj;
}

function parsirajIme($linija) {
    if (preg_match('/<a.*?>(.*?)<\/a>/', $linija, $match)) {
        return $match[1];
    }
    else {
        return FALSE;
    }
}

function parsirajDostupnost($linija) {
    preg_match("/td class=\"warehouse(.*?)\"/", $linija, $match);
    return $match[1];
}

ob_flush();
?>

