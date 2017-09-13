<?php
namespace Swoole\App\Refresh;
use Swoole\Core\Lib\Worker;
use Swoole\Core\Log;

class Refresh extends \Swoole\Core\App\Controller {

	private $page_size = 1000;

	public function init() {
		$this->read_db = $this->db("slave_icdc_" . $this->swoole->worker_id); //读
		Log::write_log("从库连接成功...");
		$this->icdc_basic = new Worker("icdc_online");
	}

	public function index() {

		$db = $this->db("slave_icdc_" . $this->swoole->worker_id); //读
		$worker = new \GearmanClient();
		$worker->addServers("192.168.8.39:4731");

		$result = $db->query("select max(id) as id from resumes limit 1")->fetch(); //获取最大的id
		$result2 = $db->query("select min(id) as id from resumes limit 1")->fetch(); //获取最小的id
		$start_id = $result2['id'];
		$end_id = $result['id'];
		if (empty($result2['id']) && empty($end_id = $result['id'])) {
			Log::write_log("icdc_{$this->swoole->worker_id} 刷库完成");
			return;
		}
		Log::write_log("icdc_{$this->swoole->worker_id} [$start_id,$end_id] to refresh.......");

		while ($start_id <= $end_id) {
			$limit = $start_id + 100;
			$start_time = number_format(microtime(true), 8, '.', '');
			$resumes_extras = $db->query("select id from resumes where id >= $start_id and id < $limit")->fetchall();
			$ids = [];
			foreach ($resumes_extras as $r) {
				$ids[] = $r['id'];
			}

			$res = $worker->doNormal('icdc_refresh', json_encode(array(
				'header' => array(
					'product_name' => 'BIService',
					'uid' => '9',
					'uname' => 'BIServer',
					'provider' => 'icdc',
					'ip' => '192.168.8.43',
					'log_id' => uniqid('bi_'),
					'appid' => 999,
				),
				'request' => array(
					'c' => 'Logic_refresh',
					'm' => 'brushes',
					'p' => array(
						'resume_id' => $ids,
						'field' => ['cv_entity'],
					),
				),
			)));

			$runtime = number_format(microtime(true), 8, '.', '') - $start_time;
			$str = "{$runtime}s";
			Log::write_log("icdc_{$this->swoole->worker_id},{$start_id}-{$limit},$str");
			$start_id = $limit;
		}

		Log::write_log("icdc_{$this->swoole->worker_id} 刷库完成");

		$db->close();
	}
}