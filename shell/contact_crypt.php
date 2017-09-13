<?php

/** Request
 * 功能描述：简历标记删除
 * 修改resumes主表和对应的cache
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_contact",
        "m"=>"contact_crypt",
        "p"=>array(
            "type"=>1,      //0 加密   1 解密
            "data"=>[       //批量操作  子数组为标识符=>值
                [           
                    'phone'=>'24A7102456BE1F24C1B02F26',
                    'mail'=>'24A7102456BE1F24C1B02F26'
                ],
            ]
            
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
