<?php
/**
 * gearman 客户端
 */
function client($param,$host='192.168.1.111'){
    $gmclient= new GearmanClient();
    $gmclient->addServer($host,4730);
    $worker_name = $param['request']['w'];
    unset($param['request']['w']);
    $json_str = $gmclient->doNormal($worker_name,msgpack_pack($param));
    $json_arr = msgpack_unpack($json_str);
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

$param['header'] = head();
$param['request'] = array (
'w'=>'tag_predict',
  'c' => 'cv_tag',
  'm' => 'get_cv_tags',
  'p' => array (
    'cv_id' => '98206194',
    'work_map' => array (
      1482285194 => array (
        'id' => 1482285194,
        'type' => 0,
        'title' => '总行审批部门副总经理',
        'desc' => '工作描述：
负责全行授信业务的管理，全行授信业务的贷前审查、调查及组织审贷会并上会汇报',
      ),
      1482285195 => array (
        'id' => 1482285195,
        'type' => 0,
        'title' => '支行行长',
        'desc' => '工作描述：
负责支行全面工作',
      ),
      1482285196 => array (
        'id' => 1482285196,
        'type' => 0,
        'title' => '进出口/信用证结算',
        'desc' => '工作描述：
负责国际业务、公司业务的产品营销推广',
      ),
    ),
  ),
);

echo client($param);
