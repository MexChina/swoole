<?php

/** Request *****************************************************************
 * 
 * 功能描述：简历操作 修改简历联系方式指向最新的简历的联系方式
 * 
 * 逻辑步骤：
 * 1、验证参数，验证简历ids
 * 2、如果简历id的src=99的结果集为空，则不用处理
 * 3、如果简历的src_no 和 resume_id 相同，则不用处理
 * 4、src_no 小于 1 ，则不用处理
 * 5、对满足条件的进行排序
 * 6、将新的contact_id 更新到简历中去
 * *************************************************************************
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_resume_map",
        "m"=>"update_map_contact",
        "p"=>array(
            "ids"=>[151328,270861],                //简历id
        )
    )
);
echo client($param);
