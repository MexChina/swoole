<?php

/** Request
 * 功能描述：简历操作 算法字段更新
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"Logic_refresh",
        "m"=>"brushes",
        "p"=>array(
            "resume_id"=>[4999353,4999354],     // [0,1000]
            "field"=>['trade'],                 //要刷的字段 trade|title|tag|education|feature|workyear|language
            "refresh_time"=>"1"                 //是否更新 resume_flag 时间  布尔  1 | 0
        )
    )
);
echo client($param);

/** Response
 *{
 *   "response": {
 *       "err_no": 0,
 *       "err_msg": "",
 *       "results": "Refresh success....."
 *   }
 *}
 */