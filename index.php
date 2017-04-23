<?php
$vars="{ 'channels': 
	[ 'SMS' ], 
	'destinations': 
		[ 
			{ 'phoneNumber': '0955541310' } 
		], 
	'sms': { 
		'sender': 'smssender', 
		'text': 'Sms text' 
	} 
}";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,"https://omni1.mobile-gw.com:9010/v1/omni/message");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,$vars);  
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$headers = [
	'Host: omni1.mobile-gw.com:9010',
	'Authorization: Basic aGVsaXhuZWJ1bGE6K0t7YEc9LWNgNEJ0bjtVWA==',
	'Content-Type: application/json',
	'Accept: application/json'
];

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

//$server_output = curl_exec ($ch);

curl_close ($ch);
echo $ch;
//print  $server_output ;