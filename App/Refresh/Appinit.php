<?php
/**
 * 刷库模块
 * 1、公司公交信息刷库   traffic
 * 2、 icdc 算法表
 */
namespace Swoole\App\Refresh;
use Swoole\Core\App;
use Swoole\Core\App\AppinitInterface;

class Appinit implements AppinitInterface {

	public function init_cache() {}

	public function worker_init() {
		// $work_id = AppServer::instance()->workerid;

		/**
		 * 刷公司公交信息
		 */
//        $traffic = new Traffic();
		//        $traffic->start();

		/**
		 * 刷icdc算法
		 */
//        App::$app->start();

		/**
		 * 刷职能信息
		 */
		/*if($work_id < 1){
			            $functions = new RefreshFunctions();
			            $functions->start(SWOOLE_ROOT_DIR . 'functions_20161114.xlsx');
		*/
		//echo "ssss\n";

		// $obj=new ImportSchoolScore();
		// $obj=new Refresh();
		$obj = new Compress2hex();
		$obj->index();
		/*
			         * 新trade测试刷库
		*/
		//$refresh_name = new Refreshyingcai;
		//$refresh_name->index();
		//$contacts = new Contacts();
		//$contacts->start();
	}

	public function timer_init() {}
	public function tasker_init() {}
	public function get_worker_number() {}
	public function worker_stop() {}
	public function server_close() {}
}
