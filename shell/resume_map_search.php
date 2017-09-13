<?php

$body=<<<STR

STR;

require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"grab_basic",
        "c"=>"resume/logic_parse",
        "m"=>"parse_import_resume",
        "p"=>array(
            "type"=>'resume',
            "site_id"=>'2',
            "is_user"=>'0',
            "is_contact"=>'0',
            "is_test"=>'0',
            "body"=>$body,
        )
    )
);
echo client($param);
