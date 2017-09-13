<?php

/** Request
 * 功能描述：获取简历详情
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"gsystem_basic",
        "c"=>"Logic_corporation_alias",
        "m"=>"get_multi",
        "p"=>array(
            "ids"=>[1869,1695,1959]      //支持单个和多个
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
