<?php

$env=3;

if($env == 1){  //开发环境
    $db = new PDO('mysql:host=192.168.1.201;port:3310;', 'devuser', 'devuser');
}elseif($env == 2){ //测试环境
    $db = new PDO('mysql:host=10.9.10.6;port:3308;', 'bi', 'Vuf6m91PRGz8G.F*GJA0');
}else{
    $db = new PDO('mysql:host=192.168.8.105;port:3307;', 'biuser', '30iH541pSBCU');
}
$this->db->query('set names UTF8');