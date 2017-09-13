<?php

/** Request
 * 功能描述：获取简历详情
 */
require_once './public.php';
$param=array(
    "header"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_resume",
        "m"=>"get_multi_all",
        "p"=>array(
            "ids"=>[13],      //支持单个和多个
            //"selected"=>"algorithm"          //不写获取完整简历信息，简历块如basic,work 详细如：basic{name}
        )
    )
);
echo client($param);

/** Response
 *{
 *   "response": {
 *       "err_no": 0,
 *       "err_msg": "",
 *       "results": 1
 *   }
 *}
 */
