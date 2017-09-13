<?php

/** Request *****************************************************************
 * 
 * 功能描述：新增基础库公司信息+修改公司名
 * 
 * *************************************************************************
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"gsystem_basic_offline",
        "c"=>"Logic_corporation",
        "m"=>"save",
        "p"=>array(  
            "uid"=>1,                                 // 用户ID
            "uname"=>'jiqing.sun',                    // 用户名
            "type"=>3,								  // 1: bdlist   2: customer   3: merchant
            "name"=>"",							      // 公司名称
            "id"=>88,                                 // 公司ID，如果是修改则比传，添加不传
            "alias"=>[ '公司别名1', '公司别名2' ],    // 公司别名数组【必传字段】
            "industry"=> [1,13],                      // 公司行业ID数组【必传字段】
            "status"=> 0,                             // 公司状态，0表示不启用,1表示启用【必传字段】
            "ka_top"=>0,                              // 公司重要度，0表示普通，1表示KA，2表示TOP【必传字段】
            "industry_main"=>1,                       // 主营行业ID 为空不处理：如果所传值在industry字段中，那么对应该行业的status设为1
            "address"=>'上海市黄浦区新天地',          // 公司地址
            "priority_type"=> 88,                     // 优先级类型（适用于行业修改） 当为88时，该字段生效，可修改type_id=3的数据          
        )
    )
);
echo client($param);
