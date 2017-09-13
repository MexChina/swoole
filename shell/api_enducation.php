<?php

/** Request
 * 功能描述：算法接口 获取cv_enducation cv_degree
 * 提 供 方：赵付涛<futao.zhao@ifchange.com>
 * 平台地址：192.168.1.204 || 10.9.10.5 || 192.168.8.56
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"cv_education_service_online",
        "c"=>"CVEducation",
        "m"=>"query",
        "p"=>array(
            "basic_degree"=>5,     //简历basic信息中的最高学历信息
            "educations"=>array(
                "1478476588"=>array(
                    "school"=>"xxx大学",
                    "major"=>"",
                    "degree"=>"4"
                ),
                "1478476589"=>array(
                    "school"=>"xxx大学",
                    "major"=>"",
                    "degree"=>"1"
                ),
            )
        )
    )
);
echo client($param);

// Response
//{
//    "response": {
//        "err_no": 0,
//        "err_msg": "",
//        "results": {
//            "units":{
//                "1419572496":{
//                    "major":"",             //识别出的专业名
//                    "major_id":"2",         //识别出的专业id
//                    "major_explain":"",     //对专业识别结果的解释，调试用
//                    "school":"",            //识别出的学校名
//                    "school_explain":"",    //对学校识别结果的解释,调试用
//                    "school_id":"2334"      //识别出的学校id
//                }
//            },
//            "features":{
//                "degree":"4"                //识别出的最高学历
//            }
//        }
//    }
//}
