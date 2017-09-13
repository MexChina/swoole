<?php
require_once './public.php';
$param=array(
    "header"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"logic_heart",                
        "m"=>"test",
        "p"=>array(
		"func"=>'fetch',                          // 来源标识符 参考数据字典
        )
    )
);
echo client($param);
