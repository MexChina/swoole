<?php

/** Request
 * 功能描述：简历操作 简历入库后更新一系列算法相关参数的队列服务
 * 逻辑步骤：
 * 1、验证是否存在于队列中
 * 2、获取简历扩展信息
 * 3、获取简历算法信息
 * 4、更新算法字段到库中
 * 5、存储cv_workyear
 * 6、存储算法表字段值失败
 * 7、存储cv_source
 * 8、通知算法es服务
 * 9、通知算法正排服务
 * 10、清除队列
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_algorithm",
        "m"=>"process_resume",
        "p"=>array(
            "id"=>4999353,
        )
    )
);
echo client($param);

// Response
// {
//    "response": {
//        "err_no": 0,
//        "err_msg": "",
//        "results": {
//            "get_resume_extra":"",                  //获取简历扩展信息耗时
//            "get_resume_algorithm":"",              //获取简历算法信息耗时
//            "get_algorithm_value":"",               //获取cv_trade,cv_title,cv_tag,cv_entity,cv_enducation,cv_feature,cv_workyear,cv_quality,cv_language耗时
//            "save_workyear":"",                     //存储cv_workyear耗时
//            "update_resume_algorithm_fields":"",    //存储算法表字段值耗时
//            "update_resume_source":"",              //存储cv_source耗时
//            "notify_es":"",                         //通知es服务耗时
//            "notify_fwdindex":""                    //通知正排服务耗时
//            "delete_job":""                         //清除队列失败耗时
//        }
//    }
// }
 
 
 //错误提示：
 //100001   简历id不能为空！
 //100001   该简历id：? 已经不在队列中了
 //100002   获取简历扩展信息失败！
 //100003   获取简历算法信息失败！
 //100004   获取cv_trade,cv_title,cv_tag,cv_entity,cv_enducation,cv_feature,cv_workyear,cv_quality,cv_language接口失败！
 //100005   存储cv_workyear
 //100006   存储算法表字段值失败！
 //100007   存储cv_source失败！
 //100008   通知es服务失败:?
 //100009   通知正排服务失败:?
 //100010   清除队列失败:?
 