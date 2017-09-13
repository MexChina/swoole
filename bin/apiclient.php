<?php
/**
 * gearmand -L 127.0.0.1 -p 4730 -u root -d
 */




//$str = '{
//    "header": {
//        "product_name": "api_department",
//        "uid": "",
//        "session_id": "",
//        "user_ip": "",
//        "local_ip": "127.0.0.1",
//        "log_id": "123456"
//    },
//    "request": {
//        "c": "department",
//        "m": "index",
//        "p": {
//            "cv_id": "222",
//            "work_map": {
//                "workid1": {
//                    "id": "11",
//                    "title": "运营部1"
//                },
//                "workid2": {
//                    "id": "12",
//                    "title": "运营部1"
//                }
//            }
//        }
//    }
//}';
//
//
//$str2 = '{
//    "header": {
//        "product_name": "api_department",
//        "uid": "",
//        "session_id": "",
//        "user_ip": "",
//        "local_ip": "127.0.0.1",
//        "log_id": "123456"
//    },
//    "response": {
//        "err_msg": "",
//        "err_no": 0,
//        "results": {
//            "11": {
//                "result_id":"1111"
//            },
//            "12": {
//				"result_id":"2222"
//            }
//        }
//    }
//}';
//
//
//$arr = json_decode($str2,true);
//var_dump($arr);
//
















echo "Starting\n";

$gmclient= new GearmanClient();
$gmclient->addServer("127.0.0.1",4730);

echo "Sending job\n";
$j = $i = 1;     //执行循环次数
$flag = true;

do {
    echo "开始第 ",substr(strval($j+10000),1,4)," 次发送......";
    $json_data = pack_data($i,$j);
    if($json_data == 1){
        echo "数据库连接超时\n";
        $i++;continue;
    }elseif($json_data == 2){
        echo "没有查询结果数据\n";
        continue;
    }elseif($json_data == 3){
        echo "接口数据为空\n";
        $j++;continue;
    }else{
        echo "获取数据成功......";
        $j++;
    }
    $json_result = $gmclient->doNormal("api_department", $json_data);
    $result = msgpack_unpack($json_result);

    if($result['response']['err_no'] == 0){
        echo "......发送成功!---",json_encode($result['response']['results']),"\n";
    }else{
        echo "......发送失败!",$result['response']['err_msg']."\n";
        $flag = false;
    }

    switch($gmclient->returnCode()) {
        case GEARMAN_WORK_DATA:
            echo "Data: $result\n";
            break;
        case GEARMAN_WORK_STATUS:
            list($numerator, $denominator)= $gmclient->doStatus();
            echo "Status: $numerator/$denominator complete\n";
            break;
        case GEARMAN_SUCCESS:
            break;
        default:
            echo "RET: " . $gmclient->returnCode() . "\n";
            exit;
    }
} while($j < 50000 && $flag);
//$gmclient->returnCode() != GEARMAN_SUCCESS



function pack_data($i,$j){
    $mysqli = new mysqli('221.228.230.200','user','user0526','icdc',8066);
//    $mysqli = new mysqli('127.0.0.1','root','dongqing','icdc',3306);
    if ($mysqli->connect_errno) {
        return 1;
    }

    $default_id = 82864896;
    $page = $default_id - ($j*500);
    $sql = "SELECT b.cv_trade,c.compress,a.id from resumes as a,resumes_algorithms as b,resumes_extras as c where a
    .id = b.id and a.id = c.id and a.is_deleted = 'N' and a.id < $page order by a.id desc limit 500;";

//    $sql = "select compress from resumes_extras order by id desc limit 500";

    $result = $mysqli->query($sql, MYSQLI_USE_RESULT);

    if($result == false){
        return 2;
    }
    $res = $result->fetch_all();
    $mysqli->close();
    $send_data['cv_id']=0;
    $kkk = 100;
    foreach($res as $r){
        $cv_trade = json_decode($r[0],true);
        $cv_trade = is_array($cv_trade) ? $cv_trade : array();
        $company_id = array();  //work_id => company_id  key=>value     //公司id数据集
        foreach($cv_trade as $k=>$t){
            $company_id[$t['work_id']]=$t['company_id'];
        }
        $compress = json_decode(gzuncompress($r[1]), true);
        $department = array();  //work_id => department key=>value      //部门数据集
        $compress['work'] = is_array($compress['work']) ? $compress['work'] : array();
        foreach($compress['work'] as $work_id=>$c){
            $department[$work_id]= isset($c['architecture_name']) ? $c['architecture_name'] : "";
        }
        foreach($company_id as $work_id=>$c){
            if(!empty($c) && !empty($department[$work_id])){
                $send_data['work_map'][$kkk]['id'] = $kkk;  //服务端不用此字段
                $send_data['work_map'][$kkk]['title'] = $department[$work_id];
            }
        }
        $kkk++;
    }

    if(empty($send_data)){
        return 3;
    }
    echo count($send_data)," 条数据";

    $param['header']['product_name'] = 'icdc';
    $param['header']['uid'] = '11';
    $param['header']['session_id'] = '22';
    $param['header']['user_ip'] = '127.0.0.1';
    $param['header']['local_ip'] = '127.0.0.1';
    $param['header']['log_id'] = '123456';
    $param['request']['c'] = '';
    $param['request']['m'] = '';
    $param['request']['p'] = $send_data;
    return msgpack_pack($param);
}



