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
        "m"=>"get_cv_by_phone",
        "p"=>array(
            "phone"=>15021231029,      
            "selected"=>"basic"          
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
