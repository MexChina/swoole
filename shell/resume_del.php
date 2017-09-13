<?php
/**
 * 功能描述：简历标记删除
 * 修改resumes主表和对应的cache
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_resume",
        "m"=>"del",
        "p"=>array(
            "ids"=>[4999353,4999354] //支持单个和多个
        )
    )
);
echo client($param);

/**
 * 返回
 */
