<?php
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_map",                       
        "m"=>"query",
        "p"=>array(
            'de_id'=>'',
            'cv_id'=>''
        )
    )
);
echo client($param,'192.168.1.66');