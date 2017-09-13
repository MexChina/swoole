<?php

/** Request
 * 功能描述：根据用户id 获取简历id
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_user_resume",
        "m"=>"update_is_private",
        "p"=>array(
            "user_id"=>18,           //用户id
            "is_private"=>1     //是否隐藏简历，值域[0,1,2]， 0表示不隐藏，1表示投递的企业可见，2表示所有人都不可见
        )
    )
);
echo client($param);

/** Response
{
    "response": {
        "err_no": 0,
        "err_msg": "",
        "results": "4986981"
    }
}
 */
