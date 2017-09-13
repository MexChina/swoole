<?php

/** Request
 * 功能描述：联系方式 根据contact_id批量获取
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_contact",
        "m"=>"get_multi",
        "p"=>array(
            "ids"=>[5000000,5000001,5000002],               //contact_id
            "selected"=>'*'
        )
    )
);
echo client($param);

/** Response
{
    "response": {
        "err_no": 0,
        "err_msg": "",
        "results": {
            "2": {
                "name": "Burluti",
                "tel": "(0) 10 6505 7923",
                "phone": "(0) 10 6505 7923"
            }
        }
    }
}
*/
