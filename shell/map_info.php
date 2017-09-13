<?php

/** Request
 * 功能描述：简历查询 根据某个来源id查询tob简历id
 * 支持范围：c端简历  员工保留  猎头简历
 */
require_once './public.php';

//---------测试案例----------------------------

//设：其他来源src(89)的src_no(123456) 对应 b端简历cv_id(654123)

//----------根据其他简历id查询b端简历id

$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_map",                       
        "m"=>"get",
        "p"=>array(
            "src"=>89,                          // 来源标识符 参考数据字典
            "src_no"=>123456                    // 如果传入src_no  功能是：其他来源的简历id查询b端的简历id的映射关系
        )
    )
);
echo client($param);

/** Response
*{
*    "response": {
*        "err_no": 0,
*        "err_msg": "",
*        "results": {
*            "123456": 654123
*        }
*    }
*}
*/


//----------根据b端简历id查询其他简历id

$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_map",                       
        "m"=>"get",
        "p"=>array(
            "src"=>89,                          // 来源标识符 参考数据字典
            "cv_id"=>654123,                    // 如果传入cv_id   功能是：根据b端简历查询其他来源的简历id的映射关系
        )
    )
);
echo client($param);

/** Response
*{
*    "response": {
*        "err_no": 0,
*        "err_msg": "",
*        "results": {
*            "654123": 123456
*        }
*    }
*}
*/


 
 //-------------soruce数据字典---------------
 //87	员工保留
 //88	c端简历
 //89	猎头招聘