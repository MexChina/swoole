#!/opt/app/php/bin/php
<?php

/** Request
 * 功能描述：简历操作 算法字段更新
 *
 * model 可传参数：
 * "model/Model_"
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_online",
        "c"=>"Logic_refresh",
        "m"=>"cache",
        "p"=>array(
            "id"=>5000004,
            "model"=>""
        )
    )
);
echo client($param);