<?php

/** Request
 * 功能描述：简历更新头像
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_resume",
        "m"=>"update_photo",
        "p"=>array(
            "resume_id"=>4999353,   //简历id
            "is_rewrite"=>1,        //是否强制更新 1 或者 0
            "photo"=>''             //图片二进制流
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