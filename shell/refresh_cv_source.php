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
        "m"=>"cv_source",
        "p"=>array(
            "resume_id"=>[5000994,5000995],     // [0,1000] list length
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
