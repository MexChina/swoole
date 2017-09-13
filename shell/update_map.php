<?php

/** Request *****************************************************************
 * 
 * 功能描述：简历操作 修改内网简历的来源信息
 * 
 * 逻辑步骤：
 * 1、验证参数，验证简历id
 * 2、更新map数据
 * *************************************************************************
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_resume_map",
        "m"=>"update",
        "p"=>array(
            "resume_id"=>100002,                //简历id
            "src"=>98,                          //简历来源，不传默认为99
            "src_no"=>100002,                   //来源的简历id，默认为和resume_id相同
            "show_src"=>99,                     //展示的来源，默认为99
            "show_src_no"=>"100002",            //展示的来源id，默认为和resume_id相同
            "is_deleted"=>"N",                  //默认为N
            "updated_at"=>"2017-2-9 15:15:15"   //系统当前时间
        )
    )
);
echo client($param);
