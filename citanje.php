<?php
$xml = simplexml_load_string("informacije.xml");
$dom = new DOMDocument();
$dom->loadXML($str);

foreach($dom->getElementsByTagName('employee') as $currency)
{
    echo $currency->getAttribute('firstname'), "\n";
    echo "+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+\n";
}