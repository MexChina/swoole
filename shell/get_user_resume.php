<?php

/** Request
 * 功能描述：根据用户id 获取简历详情2
 */
require_once './public.php';
$param=array(
    "header"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_user_resume",
        "m"=>"get_resume_detail",
        //"m"=>"get_resume_id",
        "p"=>array(
            "id"=>11,               //用户id
            "selected"=>"basic"     //同获取简历详情使用方法
        )
    )
);
echo client($param);

