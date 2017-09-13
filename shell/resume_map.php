<?php

/** Request
 * 功能描述：简历查询 根据某个来源id查询tob简历id
 * 支持范围：c端简历  员工保留  猎头简历
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_map",
        "m"=>"get",
        "p"=>array(
            "src"=>89,                          // 来源标识符 参考数据字典
            "cv_id"=>654123,                    // 如果传入cv_id   功能是：根据b端简历查询其他来源的简历id的映射关系
           // "src_no"=>123456                 // 如果传入src_no  功能是：其他来源的简历id查询b端的简历id的映射关系
        )
    )
);
echo client($param,'192.168.1.66');

/** Response
 *{
 *   "response": {
 *       "err_no": 0,
 *       "err_msg": "",
 *       "results": "Refresh success....."
 *   }
 *}
 */
 
 //-------------soruce数据字典---------------
 //87	员工保留
 //88	c端简历
 //89	猎头招聘
