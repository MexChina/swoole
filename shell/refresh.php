#!/opt/app/php/bin/php
<?php

/** Request
 * 功能描述：简历操作 算法字段更新
 */
require_once './public.php';
$ids = [17];
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_online",
        "c"=>"Logic_refresh",
        "m"=>"brushes",
        //"m"=>"save",
        "p"=>array(
            "resume_id"=>5000004,
            "field"=>['cv_resign'],
            //"field"=>['cv_title'], 
        )
    )
);
echo client($param);

