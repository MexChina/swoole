<?php

/** Request
 * 功能描述：根据用户id 获取简历详情2
 */
require_once './public.php';
$str='
{
    "request": {
	"w":"icdc_basic",
        "p": {
      
        "source": {
            "src": 100
        }, 
         
        "basic": {
            "id": "27468050"
        }, 
        "education": {
            "1": {
                "so_far": "N", 
                "discipline_desc": "", 
                "degree": "1", 
                "start_time": "1998年09月01日", 
                "school_name": "北京国际商务学院", 
                "end_time": "2002年07月01日", 
                "discipline_name": "工商管理"
            }, 
            "0": {
                "so_far": "N", 
                "discipline_desc": "技能特长", 
                "degree": "4", 
                "start_time": "1988年09月01日", 
                "school_name": "陕西师范大学", 
                "end_time": "1990年07月01日", 
                "discipline_name": "教育学"
            }
        }
    }, 
    "c": "resumes/logic_resume", 
    "m": "save"
    },
    "header": {
        "ip": "116.247.109.58",
        "version": 1,
        "signid": "2592026113",
        "provider": "2b",
        "auth": "2b",
        "product_name": "tob_web",
        "session_id": "",
        "uid": "1",
        "uname": "root",
        "appid": "5",
        "unique": "2592049554"
    }
}

';

$arr = json_decode($str,true);
//var_dump($arr);exit;
echo client($arr);

