<?php
namespace Swoole\App\Algorithm;
class Algorithm extends \Swoole\Core\App\Controller {
	private $db;
	private $page_size = 10;
	private $task_num = 30;

	public function init() {
	}

	public function start() {
		$db = $this->db("allot");

		swoole_timer_tick(1000, function () use ($db) {
			$time = date('Y-m-d H:i:s');
			$this->read($time, $db);
		});

	}

	/** 执行算法处理job
	 * @return bool
	 */
	public function read($time, $db) {
		$res = $db->query("select min(resume_id) as min_id,max(resume_id) as max_id from algorithm_jobs where created_at<='$time'")->fetch();
		if (empty($res)) {
			return;
		}

		for ($start_id = $res['min_id']; $start_id <= $res['max_id'];) {
			$result = $db->query("select resume_id from algorithm_jobs where created_at<='$time' and resume_id >='$start_id' order by resume_id asc limit {$this->page_size}")->fetchall();
			if (empty($result)) {
				continue;
			}
			$data = [];
			foreach ($result as $row) {
				$data[] = (int) $row['resume_id'];
			}

			$this->send($data);
			$start_id += array_pop($data);
		}
	}

	public function send($job_ids) {
		if ($this->task_num > 0) {
			$task_id = $this->task(array(
				'job_ids' => $job_ids,
			), function ($response, $request) {
				$this->task_num++;
			});
			if ($task_id) {
				$this->task_num--;
			}
		} else {
			sleep(1);
			$this->send($job_ids);
		}
	}
}