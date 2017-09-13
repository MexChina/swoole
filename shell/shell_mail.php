<?php

/** Request
 * 功能描述：获取简历详情
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"shell/Logic_mail",
        "m"=>"index",
        "p"=>array(
            "subject"=>"测试邮件".date("Y-m-d H:i:s"),      //支持单个和多个
	    "body"=>"<h1>text</h1>"
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
