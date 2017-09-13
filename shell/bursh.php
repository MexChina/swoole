<?php
require_once './public.php';
$res = new SplFileObject('./resume_id.txt');
foreach($res as $r){
    $param=array(
        "header"=>head(),
        "request"=>array(
            "w"=>"icdc_basic",
            "c"=>"resumes/Logic_algorithm",
            "m"=>"process_resume",
            "p"=>array(
                "id"=>(int)$r,
            )
        )
    );
    echo client($param),"\n";
}