<?php
namespace Swoole\App\Algorithm;
use Swoole\Core\Lib\Worker;
use Swoole\Core\Log;

class AlgorithmTask extends \Swoole\Core\App\Controller {
	public function init() {
	}

	public function index($params) {
		$job_ids = $params['job_ids'];
		if (empty($job_ids)) {
			return array('return' => 1);
		}

		$work = new Worker("icdc_basic");
		foreach ($job_ids as $job_id) {
			$start_time = number_format(microtime(true), 8, '.', '');

			$gearman_return = $work->client(array(
				'c' => 'resumes/Logic_algorithm',
				'm' => 'process_resume',
				'p' => array(
					'id' => $job_id,
				),
			));
			$gearman_return = !is_string($gearman_return['results']) ? json_encode($gearman_return['results']) : $gearman_return['results'];

			$runtime = number_format(microtime(true), 8, '.', '') - $start_time;
			$str = " runtime：{$runtime}s";
			Log::write_log("algorithm_jobs $job_id $gearman_return $str...");
		}
		return array('return' => 1);
	}
}
