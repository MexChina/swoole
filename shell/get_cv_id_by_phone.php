<?php

/** Request
 * 功能描述：联系方式 根据手机号获取简历id
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_contact",
        "m"=>"get_cv_id_by_phone",
        "p"=>array(
            "phone"=>[18611615170],               //手机号码 支持批量
            
        )
    )
);
echo client($param);

/** Response

*/
