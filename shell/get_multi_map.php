<?php

/** Request *****************************************************************
 * 
 * 功能描述：简历操作 根据简历id或src或src_no查询maps
 * 
 * *************************************************************************
 */
require_once './public.php';
$param=array(
    "header"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_resume_map",
        "m"=>"get_multi",
        "p"=>array(
            "ids"=>58511795            //简历id               
        )
    )
);
echo client($param);

