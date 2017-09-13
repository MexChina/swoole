<?php

/** Request *****************************************************************
 * 
 * 功能描述：简历操作 存储简历算法数据(新的全量刷库模块使用)
 * 
 * 逻辑步骤：
 * 1、验证参数，简历id不能为空
 * 2、验证参数id是否存在
 * 3、存储算法数据
 * 4、更新refresh_at时间
 * *************************************************************************
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_algorithm",
        "m"=>"save",
        "p"=>array(
            "id"=>4999350,            //简历id  
            "data"=>array(
                "cv_source"=>'[{"src":"98","src_no":"3","show_src":"99","show_src_no":"3"}]',
                "cv_trade"=>'[{"first_trade_list":[],"company_info":{"region":[],"company_type":[],"internal_id":0,"keyword":["burluti"]},"company_id":0,"work_id":1415861102,"second_trade_list":[]}]',
                "cv_title"=>'{"1415861104":{"phrase":"null","id":0,"level":1}}',
                "cv_tag"=>'{"1415861102":{"no_tag":""}}',
                "cv_entity"=>'{"1415861102":{"no_tag":""}}',
                "cv_education"=>'{"1415861102":{"school_id":0,"discipline_id":0}}',
                "cv_feature"=>'{"ts":1423209507,"tag":["193"]}',
                "skill_tag"=>'{"ts":1423209507,"tag":["193"]}',
                "personal_tag"=>'{"ts":1423209507,"tag":["193"]}',
                "diff"=>'{"ts":1423209507,"tag":["193"]}',
                "cv_quality"=>'0.2458455',
                "cv_language"=>'[{"level":"\u4e00\u822c","name":"\u6cd5\u8bed","detail":""},{"level":"\u4e00\u822c","name":"\u6c49\u8bed","detail":""},{"level":"\u4e00\u822c","name":"\u82f1\u8bed","detail":""}]',
                "cv_degree"=>"99",
            )             
        )
    )
);
echo client($param);
 
//测试方法  1、先根据简历id获取简历详情，详情如下：
//{
//    "response": {
//        "err_no": 0,
//        "err_msg": "",
//        "results": {
//            "4999350": {
//                "basic": {
//                    "updated_at": "2017-01-22 16:05:22"
//                },
//                "setting": {
//                    "salary_is_visible": "1"
//                },
//                "is_toc": "0"
//            }
//        }
//    }
//}



//错误提示：
//100001   ids 不能为空！
//100002   该简历不存在！
