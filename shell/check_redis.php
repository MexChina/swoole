<?php
require_once './public.php';
$param=array(
    "header"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"logic_heart",                
        "m"=>"check_redis",
        "p"=>array(
			'key'=>108275059,
			'value'=>array(
				'cv_title'=>'sssssssssssssssssss',
				'cv_tag'=>'tttttttttttttttttttt',
				'updated_at'=>date('Y-m-d H:i:s')
				)
        )
    )
);
echo client($param);
