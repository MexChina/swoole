<?php

/** Request *****************************************************************
 * 
 * 功能描述：简历操作 修改联系方式
 * 
 * 逻辑步骤：
 * 1、字段验证
 * 2、简历验证
 * 3、验证并存储联系方式
 * 4、新增来源
 * 5、更新简历主表的名字和联系方式
 * 6、更新压缩包的名字和phone_id和mail_id
 * 7、如果有去重则进行去重服务
 * 
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_resume",
        "m"=>"update_resume_contact",
        "p"=>array(
            "resume_id"=>4999350,               //简历id
            "contact"=>[                        //允许修改的字段   name|tel|phone|email|qq|msn|sina|ten|wechat|phone_area
                'name'=>'赵娜1号',
                'phone'=>'13651277950',
                'email'=>'zhaonaeastfair@hotmail.com',
            ],                 
        )
    )
);
echo client($param);
