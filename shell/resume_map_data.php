<?php

/** Request
 * 功能描述：简历查询 根据某个来源id查询tob简历id
 * 支持范围：c端简历  员工保留  猎头简历
 */
require_once './public.php';
$param=array(
    "header"=>head(),
    "request"=>array(
        "w"=>"icdc_map",                       
        "m"=>"register",
        "p"=>array(
            "id"=>123456,                       // 必选，简历在自己库中的id
            "src"=>89,                          // 必选，来源标识符 参考数据字典
            "is_merger"=>0,                     // 非必选，默认0，是否将此简历更新合并到b端的简历库中,如果b端没有则新建如果有则合并。0是不操作，1操作
            "data"=>array(                      // 必选，简历完整数据 array格式
                'basic'=>array(
                    'name'=>'zhang3'
                ),
                'work'=>array(
                    'work_idxxx'=>array(
                        'id'=>'work_idxxx',
                        'company_name'=>'xxxxx'
                    )
                ),
                'education'=>array(
                    'education_idxxx'=>array(
                        'id'=>'education_idxxx',
                        'school_name'=>'xxxx'
                    )
                ),
                'project'=>array(),
                'skill'=>array()
            )
        )
    )
);
echo client($param,'192.168.1.66');
