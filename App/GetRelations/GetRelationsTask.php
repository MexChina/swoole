<?php

namespace Swoole\App\GetRelations;

use Swoole\Core\Log;
use Swoole\App\GetRelations\Logic\DumpDataTask;
use Swoole\App\GetRelations\Logic\RelationsTask;
use Swoole\App\GetRelations\Logic\IncRelationsTask;

/**
 * 统计异步任务脚本
 *
 * @author xuelin.zhou
 */
class GetRelationsTask extends \Swoole\Core\App\Controller {

    private $dump_data;
    private $get_relations;

    public function init() {
        $this->get_relations = new RelationsTask($this);
        Log::writelog("task[{$this->swoole->worker_id}] object init ......");
    }

    public function init_config($params) {
        $this->dump_data = new DumpDataTask($params, $this);
    }

    //获取人脉
    public function get_relations_from_redis($params) {
        return $this->get_relations->get_relations_from_redis($params);
    }

    //获取人脉
    public function get_relations_from_redis2($params) {
        return $this->get_relations->get_relations_from_redis2($params);
    }

    public function del_duplicate_data($params) {
        return $this->get_relations->del_duplicate_data($params);
    }

    /*
     * 写入数据到mysql、redis
     */

    public function dump_to_mysql($params) {
        return $this->dump_data->dump_to_mysql($params);
    }

    /*
     * 利用gpfidst导出gp数据到文件
     */

    function gpfdist($params) {
        $path = $this->dump_data->gpfdist($params);
        return $path;
    }

    function gpfdist_inc($params) {
        $path = $this->dump_data_inc->gpfdist($params);
        return $path;
    }

}
