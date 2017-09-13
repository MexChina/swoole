<?php
/**
 * 将就的压缩包重构成新的压缩包
 *
 *
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;

class Esserver extends \Swoole\Core\App\Controller {
	private $db; //源库
	private $worker;

	public function init() {}

	public function index() {
		$this->db = $this->db("slave_icdc_" . $this->swoole->worker_id);
		$this->dbs = $this->db("master_icdc_" . $this->swoole->worker_id);

		$this->worker = new \GearmanClient();
		$this->worker->addServers("192.168.8.39:4731");

		$result = $this->db->query("select id,is_processing,updated_at from resumes", MYSQLI_USE_RESULT);
		while ($row = $result->fetch_assoc()) {
			$this->check($row);
		}
		$result->close();

		Log::writelog("icdc_" . $this->swoole->worker_id . " complete...");
	}

	public function check($row) {

		$id = (int) $row['id'];
		if ($id <= 0) {
			return;
		}

		if ($row['is_processing'] == 0) {
			return;
		}

		$time = strtotime($row['updated_at']);
		$time2 = strtotime('2017-08-20');
		if ($time > $time2) {
			return;
		}

		// $this->dbs->query("replace into algorithm_jobs(`resume_id`) values('$id')");
		$this->dbs->query("update resumes set is_processing='0' where id='$id'");

		$param = array(
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
				'c' => 'resumes/Logic_algorithm',
				'm' => 'process_resume',
				'p' => array(
					'id' => $id,
				),
			),
		);
		$this->worker->doBackground('icdc_refresh', json_encode($param));
		Log::write_log($id . " ok");
	}

	public function __destruct() {
		$this->db->close();
	}
}