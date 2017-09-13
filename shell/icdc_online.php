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
        "w"=>"icdc_online",
        "c"=>"resumes/Logic_resume",
        "m"=>"get_multi_all",
        "p"=>array(
            "ids"=>[6270829],
		 "selected"  => '',               
        )
    )
);
echo client($param);

