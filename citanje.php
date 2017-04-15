<?php
$xml = simplexml_load_string("informacije.xml");
$dom = new DOMDocument();
$dom->loadXML($xml);
print_r($dom);

