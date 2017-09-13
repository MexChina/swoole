<?php

/** Request
 * 功能描述：简历操作 更新user_id和resume_id的关联关系
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_user_resume",
        "m"=>"save_relate",
        "p"=>array(
            "user_id"=>18,              //用户id
            "resume_id"=>88888          //简历id
        )
    )
);
echo client($param);

/** Response
{
    "response": {
        "err_no": 0,
        "err_msg": "",
        "results": true
    }
}
*
* 错误提示：
* 85072101  [参数错误]缺少user_id或resume_id
* 85072102  该简历不存在！
* 85072103  该简历id和其他用户已经绑定
* 85072104  该用户不存在！
* 85072105  该用户id和其他简历已经绑定
* 
* results:true      //更新成功
* 
 */