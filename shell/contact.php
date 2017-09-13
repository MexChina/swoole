<?php

$key='!@#$%^&*()_+{":}';

if(empty($argv[1])){
	$arr=[17087843358,18665003150,13581759695,13581611772];
	foreach($arr as $a){
        	echo $a,'=>',xxtea_encrypt($a,$key),"\n";
	}

}else{
	echo $argv[1],'=>',contact_decrypt($argv[1],$key),"\n";
}
