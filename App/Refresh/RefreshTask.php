<?php

namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Lib\Worker;
use \RedisCluster;
use Swoole\Core\Helper\System;
use \mysqli;

/**
 * 统计相关脚本
 *
 * @author koly
 */
class RefreshTask extends \Swoole\Core\App\Controller {

    /**
     * redis配置
     * @var [type]
     */
    private $redis_config = [
        'dev' => ["192.168.1.201:7000", "192.168.1.201:7001", "192.168.1.201:7002", '192.168.1.201:7003', '192.168.1.201:7004', '192.168.1.201:7005'],
        'test' => ["10.9.10.6:7000", "10.9.10.6:7001", "10.9.10.6:7002", '10.9.10.6:7003', '10.9.10.6:7004', '10.9.10.6:7005'],
        'pro' => ['192.168.8.116:7105', '192.168.8.115:7105', '192.168.8.114:7105', '192.168.8.113:7105', '192.168.8.116:7106', '192.168.8.115:7106',
            '192.168.8.114:7106', '192.168.8.113:7106', '192.168.8.116:7107', '192.168.8.115:7107', '192.168.8.114:7107', '192.168.8.113:7107',
            '192.168.8.116:7108', '192.168.8.115:7108', '192.168.8.114:7108', '192.168.8.113:7108']
    ];
    private $refresh_field = array(//要刷库的字段
        //'cv_trade',
        'cv_tag',
            //'cv_entity',
            //'cv_education',     //学历识别
            //'cv_resign',      //离职率
    );
    private $refresh_servers = array(
        //'es_servers',
        //'fwdindex_service_online',
        'tag_predict' //cv_tag 的worker
    );
    //刷库白名单字典
    private $algorithm_field = [
        'cv_trade', 'cv_title', 'cv_tag', 'cv_entity', 'cv_education', 'cv_feature', 'cv_workyear', 'cv_quality', 'cv_language', 'cv_resign', 'cv_source'
    ];
    private $is_update_resume = false;          //是否更新主表
    private $is_update_resume_extra = false;    //是否更新压缩包表
    private $tag_predict = null;

    function __construct() {
        parent::__construct();
    }

    function init() {
        $this->redis = new RedisCluster(NULL, $this->redis_config['pro']);
        foreach ($this->refresh_servers as $worker_name) {
            $this->apiinit($worker_name);
        }

        if (empty($this->tag_predict)) {
            $this->apiinit("tag_predict");
        }
    }

    function algorithm($datas) {
        Log::write_log("【task_{$this->swoole->worker_id}】 ".count($datas['data'])." datas receive success");
        $extras = $datas['data'];
        $db_id = intval($datas['db_id']);
        $start_id = intval($datas['start_id']);
        $rs = true;
        if (empty($extras)) {
            return TRUE;
        }

        return true;

        $write_db_config = $this->config->get("db[master_icdc_{$db_id}]");
        $write_db = new mysqli($write_db_config['host'], $write_db_config['user'], $write_db_config['passwd'], $write_db_config['name'],$write_db_config['port']);
        $res = $write_db->query('BEGIN');
        //Log::write_log("【task_{$this->swoole->worker_id}】"."111111111111111111111__".($res ? "1":"0"));
        $res = $write_db->query("SET AUTOCOMMIT=0"); 
        //Log::write_log("【task_{$this->swoole->worker_id}】"."2222222222222222222222__".($res ? "1":"0"));
        //$write_db->autocommit(FALSE);
        //$write_db->tr_start();
        //Log::write_log("【task_{$this->swoole->worker_id}】"." write_db:icdc_{$db_id} connect success ...... ");
        $start_time = microtime(true);
        $worker_time = 0;
        $redis_time = 0;
        $mysql_time = 0;
        foreach ($extras as $extra) {
            $redis_data = [];
            $save_data = [];
            $is_update_resume_extra = false;

            if (empty($extra)) {
                Log::write_log("【task_{$this->swoole->worker_id}】resumes_extras 不存在");
                continue;
            }

            $resume_id = (int) $extra['id'];
            if ($resume_id <= 0) {
                Log::write_log("【task_{$this->swoole->worker_id}】resumes_extras 不存在");
                continue;
            }

            $compress = json_decode(gzuncompress($extra['compress']), true);
            if (empty($compress)) {
                Log::write_log("【task_{$this->swoole->worker_id}】$resume_id compress 数据损坏");
                continue;
            }

            if ($compress['basic']['is_deleted'] == 'Y') {
                continue;
            }

            if (empty($compress['basic']['id'])) {
                $compress['basic']['id'] = $resume_id;
                $this->is_update_resume_extra = true;
            }
          
            $time_start = System::exec_time();
            if (in_array('cv_tag', $this->refresh_field)) {
                $res = $this->cv_tag($compress);
                if ($res === null) {
                    continue;
                }
                $redis_data['cv_tag'] = $resume_algorithm['cv_tag'] = $save_data['cv_tag'] = $res;
            }
            $worker_time += System::exec_time();
            if (!empty($redis_data)) {
                $redis_data['updated_at'] = date('Y-m-d H:i:s');
                $this->redis->hMset($resume_id, $redis_data);
            }
            $redis_time += System::exec_time();
            //存储mysql数据
            if (!empty($save_data)) {
                $rs &= $this->save($write_db, $compress, $resume_info, $save_data);
                //Log::write_log("icdc_{$db_id}, resume_id:$resume_id  save " . ($rs ? 'success' : 'failed' ). "...");
            }
            $mysql_time += System::exec_time();
        }
        $rs &= $write_db->query("COMMIT");
        $mysql2_time += System::exec_time();
        $write_db->close();
        unset($datas,$extras,$compress);
        Log::write_log("【task_{$this->swoole->worker_id}】icdc_{$db_id}, start_id :$start_id   save " . ($rs ? 'success' : 'failed' ). ","
                ."worker use time {$worker_time}ms, redis use time {$redis_time}ms, mysql save use time {$mysql_time}ms, mysql commit {$mysql2_time}ms, "
                . "all use time【".(microtime(true)-$start_time )."】s , memory use 【" . System::get_used_memory() . "】...");
        return true;
    }

    /**
     * 存储数据
     * @return [type] [description]
     */
    private function save($write_db, $compress, $resumes, $algorithms) {
        $resume_id = $compress['basic']['id'];

        $algorithm = '';
        foreach ($algorithms as $key => $value) {
            $algorithm .= "'$key','" . addslashes($value) . "',";
        }
        $algorithm = rtrim($algorithm, ',');
        if (empty($algorithm))
            return;
        $time = date("Y-m-d H:i:s");
        
        $sql = "update algorithms set data=column_add(data,$algorithm),updated_at='$time' where id=$resume_id";
        
        $res = $write_db->query($sql);
        
        //$msg = empty($res) ? $resume_id." error:failed\n" : $resume_id." success\n";
        //error_log(date('Y-m-d H:i:s')."\t".$msg,3,'/opt/log/cv_tag_ids');
        //error_log(date("Y-m-d H:i:s")."\t".$sql."\n",3,'/opt/log/cv_tag_sql');
        
        //$this->cache($resume_id, "resumes/Model_resume_algorithm");
        return $res;
    }

    //=================================================================================================================================
    private function cv_tag($compress) {
        if (empty($compress['work']))
            return '';
        $work_list = array();
        foreach ($compress['work'] as $work) {
            $work_id = $work['id'];
            if (empty($work['position_name']) && empty($work['responsibilities']))
                continue;
            $work_list[$work_id] = array(
                'id' => $work_id,
                'type' => 0,
                'title' => empty($work['position_name']) ? '' : $work['position_name'],
                'desc' => empty($work['responsibilities']) ? '' : $work['responsibilities']
            );
        }

        if (empty($work_list))
            return '';

        //$success = @$this->tag_predict->ping('data testing');
        if (empty($this->tag_predict)) {
            $this->apiinit("tag_predict");
        }

        $param = array(
            'header' => $this->header(),
            'request' => array(
                'c' => 'cv_tag',
                'm' => 'get_cv_tags',
                'p' => array(
                    'cv_id' => $compress['basic']['id'],
                    'work_map' => $work_list
                )
            )
        );
        $this->access("tag_predict", $param);
        $start_time = microtime(true);
        $return = $this->tag_predict->doNormal("tag_predict", json_encode($param));
        // Log::write_log("code: ".$this->tag_predict->returnCode());
        $time = (microtime(TRUE) - $start_time) * 1000;
        $rs = msgpack_unpack($return);
        $this->access("tag_predict", $rs, "RSQ:", $time);

        if (isset($rs['response']['results'])) {
            return empty($rs['response']['results']) ? '' : json_encode($rs['response']['results'], JSON_UNESCAPED_UNICODE);
        } else {
            error_log($idss, 3, "/opt/log/cv_tag_timeout_ids");
            return null;
        }
    }

    /**
     * 初始化接口连接
     * @param  [type] $api [description]
     * @return [type]      [description]
     */
    private function apiinit($api) {
        $this->$api = new \GearmanClient();
        $config_text = json_decode(file_get_contents("/opt/wwwroot/conf/gm.conf"), true);
        if (isset($config_text[$api]) && $config_text[$api]['host']) {
            foreach ($config_text[$api]['host'] as $host) {
                $this->$api->addServers($host);
            }
        }
        Log::write_log("client $api connect success...");
    }

    /**
     * [header description]
     * @return [type] [description]
     */
    private function header() {
        return array(
            'product_name' => $param['product_name'] ?? 'BIService',
            'uid' => $param['uid'] ?? '9',
            'session_id' => $param['session_id'] ?? '0',
            'uname' => $param['uname'] ?? 'BIServer',
            'version' => $param['version'] ?? '0.1',
            'signid' => $param['signid'] ?? 0,
            'provider' => $param['provider'] ?? 'icdc',
            'ip' => $param['ip'] ?? '0.0.0.0',
            'user_ip' => $param['user_ip'] ?? '0.0.0.0',
            'local_ip' => $param['local_ip'] ?? '0.0.0.0',
            'log_id' => $param['log_id'] ?? uniqid('bi_'),
            'appid' => $param['appid'] ?? 999,
        );
    }

    private function access($api, $msg, $type = "RSP:", $time = '') {
        error_log(date('Y-m-d H:i:s') . "\t{$this->swoole->worker_id}\t$type\t" . json_encode($msg, JSON_UNESCAPED_UNICODE) . "\t$time\n", 3, "/opt/log/{$api}_access." . date('Y-m-d'));
    }

}
