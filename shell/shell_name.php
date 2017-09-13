<?php

/** Request
 * 功能描述：获取简历详情
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"shell/Logic_name",
        "m"=>"index",
        "p"=>array(
            "ids"=>[4964048,13209214],      //支持单个和多个
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
