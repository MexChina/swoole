<?php


require_once './public.php';

$param=array(
    "header"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_resume",
        "m"=>"get_multi_all",
        "p"=>array(
            "ids"=>[4999999],
            "selected"=>""
        )
    )
);
$res = client($param);
$res = json_decode($res,true);
$arr = $res['response']['results']['4999999'];
unset($arr['algorithm'],$arr['map'],$arr['education']);
$arr['work']=array();
$cv_content = json_encode($arr);
//$cv_content = json_encode($res['response']['results']['4999999']);
//echo $cv_content;exit;
echo "get resume info success....\n";
$param=array(
    "header"=>head(),
    "request"=>array(
        "w"=>'resign_prophet',
        "c"=>'resign_prediction',
        "m"=>'resign_computing',
        "p"=>array(
            "4999999"=>array(
                'cv_id'=>4999999,
                'last_intention'=>0.5,
                'cv_content'=>$cv_content,
                'behavior'=>array(
                    'times_deliver'=>3,
                    'times_update'=>5
                ),
	//'history'=>"[['2017-06-01-16-06-59', '2017-06-01-16-06-59', 3, 5, '0.906785276918']]"            
	'history'=>""            
),
        )
    )
);
echo client($param,'192.168.1.111','msgpack_unpack'),"\n";

