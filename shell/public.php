<?php
/**
 * gearman 客户端
 */
function client($param,$host='192.168.1.108',$func="json_decode"){
    $gmclient= new GearmanClient();
    $gmclient->addServer($host,4730);
    $worker_name = $param['request']['w'];
    unset($param['request']['w']);
    $json_str = $gmclient->doNormal($worker_name,json_encode($param));
    $json_arr = $func($json_str);
    unset($json_arr->header);
    return json_encode($json_arr,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
}
/**
 * 头部标准信息
 */
function head(){
    return array(
        "product_name"=>"icdc",                     //产品名
        "uid"=>"99",                                //用户id
        "session_id"=>"99",                         //session_id
        "uname"=>"dongqing.shi",                    //用户名 基本是用户的邮箱地址
        "developer"=>"dongqing.shi@ifchange.com",   //开发者  开发者邮箱 
        "version"=>"v1.1",
        "signid"=>"99",
        "provider"=>"icdc",
        "ip"=>"192.168.1.66",                       //外网ip？
        "user_ip"=>"192.168.1.66",                  //用户ip
        "local_ip"=>"192.168.1.66",                 //客户端服务器ip
        "log_id"=>uniqid(),                         //日志id全局唯一标识符
        "appid"=>"99"                               //app编号
    );
}
