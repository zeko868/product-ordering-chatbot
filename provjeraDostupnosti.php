<?php

ob_start();
header('Content-Type: text/html; charset=utf-8');
//hardcoded za sad
$linkProizovada = "/hr/graficka-kartica-used-pci-e-gainward-geforce-gtx-970-phoenix-4gb-ddr5-dvi-hdmi-mdp-8100001048";
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
    echo json_encode($obj, JSON_UNESCAPED_UNICODE);
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
        }
        else if (strpos($polje[$i], "class=\"circle active\"")) {
            $dostupnost = parsirajDostupnost($polje[$i]);
            $d = TRUE;
        }
        else if ($d && $n) {
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

