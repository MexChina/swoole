#!/opt/app/php/bin/php
<?php

/** Request *****************************************************************
 * 
 * 功能描述：简历操作 存储简历算法数据(新的全量刷库模块使用)
 * 
 * 逻辑步骤：
 * 1、验证参数，简历id不能为空
 * 2、验证参数id是否存在
 * 3、存储算法数据
 * 4、更新refresh_at时间
 * *************************************************************************
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_algorithm",
        "m"=>"test",
        "p"=>array(
            "id"=>4999350,            //简历id  
        )
    )
);
echo client($param);
 



//错误提示：
//100001   ids 不能为空！
//100002   该简历不存在！
