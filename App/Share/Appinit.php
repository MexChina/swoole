<?php
namespace Swoole\App\Share;
use Swoole\Core\App;
use Swoole\Core\App\AppinitInterface;
use Swoole\Core\AppServer;
use Swoole\Core\Log;
use Swoole\Core\Helper\System;
use \swoole_table;
class Appinit implements AppinitInterface {

    function init_cache() {
        $db = AppServer::db('shares');
        $db->query("truncate table shares");
        $db->query("truncate table shares_finance");
        $db->query("truncate table shares_manager");
        $db->query("truncate table shares_place_dic");
        $db->query("truncate table shares_trade_dic");

        $place_table = new swoole_table(2000);
        $place_table->column('id',swoole_table::TYPE_INT, 4);
        $place_table->create();
        $trade_table = new swoole_table(2000);
        $trade_table->column('id',swoole_table::TYPE_INT, 4);
        $trade_table->create();
        $company_table = new swoole_table(100000);
        $company_table->column('id',swoole_table::TYPE_INT,4);
        $company_table->create();
        AppServer::$tables['trade_dic'] = $trade_table;
        AppServer::$tables['place_dic'] = $place_table;
        AppServer::$tables['company_table'] = $company_table;
        $db->close();
    }

    function worker_init(){
        Log::write_log("开始全量更新 ......");
        System::exec_time();
        App::$app->index();
        Log::write_log("更新完成，用时： " . System::exec_time() . " ms, 内存使用： " . System::get_used_memory());
        AppServer::$instances->swoole->shutdown();
    }
    function get_worker_number() {}
    function timer_init() {}
    function tasker_init() {}
    function worker_stop() {}
    function server_close() {}
}
