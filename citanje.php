<?php
$a = array();
$array = array('a'=>'1','b'=>'2');
array_push($a,$array);
array_push($a,array('a'=>'3','b'=>'4'));
$a2 =json_encode($a);
echo str_replace($a2,'{','[');
?>