<?php


$resume_id = count($argv) < 2 ? 0 : (int)$argv[1];
if($resume_id < 1){
	exit("请输入简历id\n");
}



$env=1;
if($env == 1){  //开发环境

	$suffix = floor($resume_id/5000000);
	$db_name = "icdc_".$suffix;
    $db = new PDO("mysql:dbname=$db_name;host=192.168.1.201;port:3310;", 'devuser', 'devuser');

}elseif($env == 2){ //测试环境

	$suffix = floor($resume_id/5000000);
	$db_name = "icdc_".$suffix;
    $db = new PDO("mysql:dbname=$db_name;host=10.9.10.6;port:3308;", 'bi', 'Vuf6m91PRGz8G.F*GJA0');

}else{

	$suffix = ($resume_id%8) + floor($resume_id/40000000) * 8;
	$db_name = "icdc_".$suffix;
    $db = new PDO("mysql:dbname=$db_name;host=192.168.8.105;port:3307;", 'biuser', '30iH541pSBCU');
}
$db->query('set names UTF8');



$resource = $db->query("select id,compress from resumes_extras where id=$resume_id");
if(empty($resource)) exit("该简历id的extra信息不存在\n");
$res = $resource->fetch(PDO::FETCH_ASSOC); //fetchAll

var_dump($res);

$compress = json_decode($res["compress"],true);
if(!is_array($compress)){
	$compress = json_decode(gzuncompress($res['compress']), true);
}

// foreach($compress['work'] as $k=>$w){

// 		$compress['work'][$k]['is_deleted']='N';
// 		$compress['work'][$k+1]=$compress['work'][$k];
	
// }

//var_dump($compress);exit;


// require_once './public.php';
// $param=array(
//     "hearder"=>array(
//     	"log"=>"xxxxxxxxxxxxxxxxxxxx"
//     ),
//     "request"=>array(
//         "c"=>"resumes/logic_resume_extra",
//         "m"=>"save",
//         "p"=>array(
//             "resume_extra"=>array(
//             	'id'=>$resume_id,
//             	'compress'=>gzcompress(json_encode($compress))
//             )
//         )
//     )
// );

// $param=array(
//     "header"=>array(
//     	"log"=>"xxxxxxxxxxxxxxxxxxxx"
//     ),
//     "request"=>array(
//         "c"=>"resumes/logic_resume_extra",
//         "m"=>"get",
//         "p"=>array(
//             "id"=>$resume_id,
//             "cache"=>true
//         )
//     )
// );



// $gmclient= new GearmanClient();
// $gmclient->addServer("192.168.1.108",4730);

// $json_str = $gmclient->doNormal("icdc_basic",msgpack_pack($param));
// $json_arr = msgpack_unpack($json_str);
// echo json_encode($json_arr,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";

// var_dump($param);


echo json_encode($compress,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),"\n";

// foreach($compress["work"] as $k=>$w){
// 	$compress["work"][$k]["is_deleted"]="Y";
// }

// $new_compress = addslashes(gzcompress(json_encode($compress)));
// $db->query("update resumes_extras set compress='$new_compress' where id=$resume_id");

// var_dump($compress);  1445286499
