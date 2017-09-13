<?php

/** Request
 * 功能描述：根据用户id 获取联系方式
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_resume",
        "m"=>"set_contact_status",
        "p"=>array(
            "resume_id"=>18,                // 简历的内网ID.
            "is_validate"=>"1"               // 简历的联系方式是否有效,允许的值:-1(未知),0(无效),1(有效).
        )
    )
);
echo client($param);

/** Response

 */
