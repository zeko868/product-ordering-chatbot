<?php
$xml = simplexml_load_string("informacije.xml");
$dom = new DOMDocument();
$dom->loadXML($xml);

foreach($dom->getElementsByTagName('response') as $currency)
{
    echo $currency->employees[0]->employee[0]->getAttribute('firstname'), "\n";
    echo "+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+\n";
}

