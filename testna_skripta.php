<?php
$ans = [
    'type'=>'template',
    'payload'=>[
        'template_type'=>'receipt',
        'recipient_name'=>"Marin Mihajlović",
        'order_number'=>'123456',
        'currency'=>'HRK',
        'payment_method'=>'Preuzeće',
        'address'=>['street_1'=>"Prudnjaci 15",'city'=>"Bestovje",'postal_code'=>"10437",'state'=>'Hrvatska','country'=>"CRO"],
        'summary'=>['subtotal'=>0,'shipping_cost'=>0,'total_tax'=>0,'total_cost'=>floatval(100.55)],
        'elements'=> [['title'=>'Proizvod','subtitle'=>'proizvod','quantity'=>1,'price'=>floatval(100.55),'currency'=>'HRK','image_url'=>'https://www.links.hr/hr/procesor-intel-pentium-g4400-box-s-1151-3-3ghz-3mb-cache-gpu-dualcore-050606315']]
    ]
];

$response = [
    'recipient' => [ 'id' => "1155662414560805" ],
    'message' => [ 'attachment' => $ans ]
];

$ch = curl_init('https://graph.facebook.com/v2.6/me/messages?access_token=EAACN8hwDY8QBAEcLkz9b9FZB2QXVgr92ZBduX8cEU1rfZBR7kOtzurRUtiWkZCan496HmhLyiWLnk86RAKsfMSiYKxZBdnIC6KftcZBy7EODHgPBERWpjFZCgqvPYWGUQyutGc76VccANwiCvrPxa9BCO7f3jnbTs2jXjZCzXk06OgZDZD');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$command = "posalji";
if($command != ""){
    $result = curl_exec($ch);
}
curl_close($ch);
exit();