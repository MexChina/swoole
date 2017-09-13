<?php

/** Request
 * 功能描述：联系方式 在线加密解密
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_contact",
        //"c"=>"resumes/Logic_resume",
        "m"=>"get_multi_contacts_by_ids",
        "p"=>array(
            "ids"=>[5000009,5000010],               //简历id
            "selected"=>'name,tel'    //联系方式字段 为空查全部
            
        )
    )
);
echo client($param);

/** Response
{
    "response": {
        "err_no": 0,
        "err_msg": "",
        "results": [
            {
                "phone": "F38DBB59FE5EAFCAF6A75E51",
                "mail": "8C69357417E63958ADF4678664A59DC8"
            },
            {
                "qq": "452A9AFDCFAACDAF40EE5195"
            }
        ]
    }
}
 */
