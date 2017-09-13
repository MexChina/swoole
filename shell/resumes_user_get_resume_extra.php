<?php

/** Request
 * 功能描述：根据uid获取简历id
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_user_resume",
        "m"=>"get_resume_extra",
        "p"=>array(
            "id"=>5000001,   //用户id
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
