<?php
$senderId = $_GET['senderid'];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://foi-konzultacije.info/curl.php?senderid=$senderId");
curl_setopt($ch, CURLOPT_HEADER, 0);
$output = curl_exec($ch);
curl_close($ch);
echo $output[0];