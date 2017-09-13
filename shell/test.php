<?php
$arr = array(
		'basic'=>array('name'=>'zhangsan'),
		'work'=>array()
	);

$json = json_encode($arr);
$str = gzcompress($json);
$json['aa'] = array();
$str1 = gzcompress($json);

var_dump($str);
echo "\n";
var_dump($str1);
echo "\n";