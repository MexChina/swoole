<?php

/** Request
 * 功能描述：联系方式 根据contact_id批量获取
 */
require_once './public.php';
$param=array(
    "header"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_contact",
        "m"=>"get_multi",
        "p"=>array(
            "ids"=>[5000000,5000001,5000002],               //contact_id
            
        )
    )
);
echo client($param,'192.168.1.66');

/** Response
{
    "response": {
        "err_no": 0,
        "err_msg": "",
        "results": {
            "5000000": {
                "id": "5000000",
                "name": "",
                "tel": "",
                "phone": "18611615170",
                "email": "",
                "qq": "",
                "msn": "",
                "sina": "",
                "ten": "",
                "wechat": "",
                "phone_area": "1",
                "is_deleted": "N",
                "updated_at": "2014-11-18 17:46:03",
                "created_at": "2014-11-18 17:46:03"
            },
            "5000001": {
                "id": "5000001",
                "name": "",
                "tel": "",
                "phone": "18611615170",
                "email": "",
                "qq": "",
                "msn": "",
                "sina": "",
                "ten": "",
                "wechat": "",
                "phone_area": "1",
                "is_deleted": "N",
                "updated_at": "2014-11-18 17:49:17",
                "created_at": "2014-11-18 17:49:17"
            },
            "5000002": {
                "id": "5000002",
                "name": "",
                "tel": "",
                "phone": "18611615170",
                "email": "",
                "qq": "",
                "msn": "",
                "sina": "",
                "ten": "",
                "wechat": "",
                "phone_area": "1",
                "is_deleted": "N",
                "updated_at": "2014-11-18 17:50:18",
                "created_at": "2014-11-18 17:50:18"
            }
        }
    }
}
*/