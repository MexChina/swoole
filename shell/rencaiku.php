<?php

require_once './public.php';
$json = '{
    "request": {
        "c": "resumes/logic_resume",
        "m": "save",
        "p": {
	    "basic":{
               "id":"4963088"
            },
            "contact": {
                "name": "保健",
                "tel": "",
                "phone": "",
                "email": ""
            },
            "source": {
                "src": 100
            },
            "is_check_contact":true
        }
    },
    "header": {
        "client_ip": "10.9.10.2",
        "local_ip": "10.9.10.2",
        "log_id": "2274168573",
        "user_ip": "116.247.109.58",
        "ip": "116.247.109.58",
        "version": 1,
        "signid": "2274168655",
        "provider": "2b",
        "auth": "2b",
        "product_name": "tob_web",
        "session_id": "jd7u05iv6atdhhro34hilep2s1",
        "uid": "92672",
        "uname": "123321@qq.com",
        "appid": "5"
    }
}';
$param = json_decode($json,true);
//var_dump($param);die;
$param['request']['w']='icdc_basic';
echo client($param);

