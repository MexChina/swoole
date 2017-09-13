<?php

/**
 * gearman 客户端
 */
function client($id,$host='192.168.1.108'){
    $gmclient= new GearmanClient();
    $gmclient->addServer($host,4730);
    $param = array(
    		"header"=>array(
		        "product_name"=>"icdc",                     //产品名
		        "uid"=>"99",                                //用户id
		        "session_id"=>"99",                         //session_id
		        "uname"=>"dongqing.shi",                    //用户名 基本是用户的邮箱地址
		        "developer"=>"dongqing.shi@ifchange.com",   //开发者  开发者邮箱 
		        "ip"=>"192.168.1.66",                       //外网ip？
		        "user_ip"=>"192.168.1.66",                  //用户ip
		        "local_ip"=>"192.168.1.66",                 //客户端服务器ip
		        "log_id"=>uniqid(),                         //日志id全局唯一标识符
		        "appid"=>"99"                               //app编号
		    ),
		    "request"=>array(
		    	"c"=>"resumes/Logic_resume",
		        "m"=>"del",
		        "p"=>array(
		            "ids"=>[$id] //支持单个和多个
		        )
		    )
    	);
    $json_str = $gmclient->doNormal("icdc_refresh",json_encode($param));
    $json_arr = json_decode($json_str);
    unset($json_arr['header']);
    error_log(date('Y-m-d H:i:s')."\t".json_encode($json_arr,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n",3,'./resume_del.log');
    echo json_encode($json_arr,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
}

$arr = new SplFileObject("./resumes_del_ids");
foreach($arr as $row){
	$row = (int)$row;
	if(empty($row)) continue;
	client($row);
}