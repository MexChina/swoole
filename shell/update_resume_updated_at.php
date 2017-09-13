<?php

/** Request *****************************************************************
 * 
 * 功能描述：简历操作 修改简历的刷新时间
 * 
 * 逻辑步骤：
 * 1、验证参数，支持单个数字以及多个list
 * 2、验证参数ids是否存在
 * 3、更新时间为系统当前时间
 * *************************************************************************
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_resume",
        "m"=>"update_resume_updated_at",
        "p"=>array(
            "ids"=>[4999350]            //简历id               
        )
    )
);
echo client($param);
 
//返回结果：
//{
//    "response": {
//        "err_no": 0,
//        "err_msg": "",
//        "results": 1
//    }
//}


//错误提示：
//100001   ids 不能为空！