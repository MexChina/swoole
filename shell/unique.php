<?php

/** Request
 * 功能描述：简历人工去重 将人工识别出多个简历id
 * 认为是同一份简历的一批ids进行去重合并
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"Logic_unique",
        "m"=>"index",
        "p"=>array(
            "ids"=>[4999353,4999354] //支持单个和多个
        )
    )
);
echo client($param);

/** Response
 *{
 *   "response": {
 *       "err_no": 0,
 *       "err_msg": "",
 *       "results": 1
 *   }
 *}
 */
