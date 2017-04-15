<?php
$xml = simplexml_load_string("informacije.xml");
$dom = new DOMDocument();
$dom->loadXML($xml);
print_r($dom);

foreach($dom->getElementsByTagName('employee') as $currency)
{
    echo $currency->getAttribute('firstname'), "\n";
    echo "+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+\n";
}

