<?php

/** Request
 * 功能描述：简历操作 判断是否存在工作，教育，项目，语言
 * 
 * 业务逻辑：
 * 1、验证用户是否存在
 * 2、获取简历
 * 3、获取联系方式
 * 4、逻辑处理 当姓名不为空&&性别不为U&&生日不为空&&手机号不为空  则basic=Y  其他都是根据字段值是否为空
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_user_resume",
        "m"=>"is_exist_basic_info",
        "p"=>array(
            "id"=>[4999353] //用户id
        )
    )
);
echo client($param);

//错误提示
//85072101    用户id不能为空！
//85072102    该用户id不存在！

//返回信息
//{
//   "response": {
//       "err_no": 0,
//       "err_msg": "",
//       "results": {
//            "is_basic":"Y",         //是否存在基本信息
//            "is_work":"Y",          //是否存在工作经历
//            "is_education":"Y",     //是否存在教育经历
//            "is_project":"Y",       //是否存在项目经历
//            "is_language":"Y",      //是否存在语言经历
//            "is_other_info":"N"     //是否存在其他信息
//         }
//   }
//}