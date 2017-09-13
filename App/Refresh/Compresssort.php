<?php
/**
 * 将就的压缩包重构成新的压缩包
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;

class Compresssort extends \Swoole\Core\App\Controller {
	private $db; //源库
	private $page_size = 1000;

	public function init() {}

	public function index() {
		// $this->db = $this->db("master_icdc_" . $this->swoole->worker_id);
		$this->db = new \mysqli("192.168.1.201", 'devuser', 'devuser', 'icdc_' . $this->swoole->worker_id, 3310);
		$max = $this->db->query("select max(id) as id from resumes")->fetch_assoc();
		$min = $this->db->query("select min(id) as id from resumes")->fetch_assoc();

		for ($start_id = $min['id']; $start_id <= $max['id']; $start_id += $this->page_size) {
			$end_id = $start_id + $this->page_size;

			if ($end_id > $max['id']) {
				$end_id = $max['id'];
			}

			$result = $this->db->query("select id,compress from resumes_extras where id >= $start_id and id <= $end_id")->fetch_all(MYSQLI_ASSOC);
			foreach ($result as $row) {
				$this->check($row);
			}

			$percent = sprintf("%1\$.2f", ($end_id / $max['id']) * 100);
			Log::write_log("icdc_" . $this->swoole->worker_id . " {$percent}%");
		}

		$this->db->close();
		Log::writelog("icdc_" . $this->swoole->worker_id . " complete...");
	}

	public function check($row) {
		$flag = false;
		$id = (int) $row['id'];
		if ($id <= 0) {
			return;
		}

		if (ctype_xdigit($row['compress'])) {
			$flag = true;
		}

		$compress = $flag ? json_decode(gzuncompress(hex2bin($row['compress'])), true) : json_decode(gzuncompress($row['compress']), true);

		if (!is_array($compress)) {
			error_log($id . "\n", 3, "/opt/log/compress_error");
			return;
		}

		$work = isset($compress['work']) ? count($compress['work']) : 0;
		$education = isset($compress['education']) ? count($compress['education']) : 0;
		if ($work > 20 || $education > 20) {
			error_log($id . "\n", 3, "/opt/log/compress_long");
		}

		$compress['work'] = $this->my_work_sort($compress['work']);
		uasort($compress['education'], array($this, 'education_sort'));
		uasort($compress['project'], array($this, 'project_sort'));
		uasort($compress['training'], array($this, 'training_sort'));
		uasort($compress['certificate'], array($this, 'certificate_sort'));

		// $compress = bin2hex(gzcompress(json_encode($compress)));
		// $time = date('Y-m-d H:i:s');
		// $this->db->query("update resumes_extras set compress='$compress',updated_at='$time' where id=$id");
		// Log::write_log($id . " ok...");
	}

	public function education_sort() {
		preg_match_all('/(\d+)/', $e1['start_time'], $matches);
		if (2 == count($matches[1])) {
			foreach ($matches[1] as $key => $value) {
				$matches[1][$key] = (int) $value;
			}
			$e1_start_time = vsprintf('%04d%02d', $matches[1]);
		} else {
			$e1_start_time = '';
		}
		preg_match_all('/(\d+)/', $e2['start_time'], $matches);
		if (2 == count($matches[1])) {
			foreach ($matches[1] as $key => $value) {
				$matches[1][$key] = (int) $value;
			}
			$e2_start_time = vsprintf('%04d%02d', $matches[1]);
		} else {
			$e2_start_time = '';
		}
		if (strcasecmp($e1_start_time, $e2_start_time) == 0) {
			preg_match_all('/(\d+)/', $e1['end_time'], $matches);
			if (2 == count($matches[1])) {
				foreach ($matches[1] as $key => $value) {
					$matches[1][$key] = (int) $value;
				}
				$e1_end_time = vsprintf('%04d%02d', $matches[1]);
			} else {
				$e1_end_time = date('Ym');
			}
			preg_match_all('/(\d+)/', $e2['end_time'], $matches);
			if (2 == count($matches[1])) {
				foreach ($matches[1] as $key => $value) {
					$matches[1][$key] = (int) $value;
				}
				$e2_end_time = vsprintf('%04d%02d', $matches[1]);
			} else {
				$e2_end_time = date('Ym');
			}
			if (strcasecmp($e1_end_time, $e2_end_time) == 0) {
				if (strcasecmp($e1['school_name'], $e2['school_name']) == 0) {
					return 0;
				} else {
					return strcasecmp($e1['school_name'], $e2['school_name']) < 0 ? 1 : -1;
				}
			} else {
				return strcasecmp($e1_end_time, $e2_end_time) < 0 ? 1 : -1;
			}
		} else {
			return strcasecmp($e1_start_time, $e2_start_time) < 0 ? 1 : -1;
		}
	}

	public function project_sort() {
		preg_match_all('/(\d+)/', $e1['start_time'], $matches);
		if (2 == count($matches[1])) {
			foreach ($matches[1] as $key => $value) {
				$matches[1][$key] = (int) $value;
			}
			$e1_start_time = vsprintf('%04d%02d', $matches[1]);
		} else {
			$e1_start_time = '';
		}
		preg_match_all('/(\d+)/', $e2['start_time'], $matches);
		if (2 == count($matches[1])) {
			foreach ($matches[1] as $key => $value) {
				$matches[1][$key] = (int) $value;
			}
			$e2_start_time = vsprintf('%04d%02d', $matches[1]);
		} else {
			$e2_start_time = '';
		}
		if (strcasecmp($e1_start_time, $e2_start_time) == 0) {
			preg_match_all('/(\d+)/', $e1['end_time'], $matches);
			if (2 == count($matches[1])) {
				foreach ($matches[1] as $key => $value) {
					$matches[1][$key] = (int) $value;
				}
				$e1_end_time = vsprintf('%04d%02d', $matches[1]);
			} else {
				$e1_end_time = date('Ym');
			}
			preg_match_all('/(\d+)/', $e2['end_time'], $matches);
			if (2 == count($matches[1])) {
				foreach ($matches[1] as $key => $value) {
					$matches[1][$key] = (int) $value;
				}
				$e2_end_time = vsprintf('%04d%02d', $matches[1]);
			} else {
				$e2_end_time = date('Ym');
			}
			if (strcasecmp($e1_end_time, $e2_end_time) == 0) {
				if (strcasecmp($e1['name'], $e2['name']) == 0) {
					return 0;
				} else {
					return strcasecmp($e1['name'], $e2['name']) < 0 ? 1 : -1;
				}
			} else {
				return strcasecmp($e1_end_time, $e2_end_time) < 0 ? 1 : -1;
			}
		} else {
			return strcasecmp($e1_start_time, $e2_start_time) < 0 ? 1 : -1;
		}
	}

	public function training_sort() {
		preg_match_all('/(\d+)/', $e1['start_time'], $matches);
		if (2 == count($matches[1])) {
			foreach ($matches[1] as $key => $value) {
				$matches[1][$key] = (int) $value;
			}
			$e1_start_time = vsprintf('%04d%02d', $matches[1]);
		} else {
			$e1_start_time = '';
		}
		preg_match_all('/(\d+)/', $e2['start_time'], $matches);
		if (2 == count($matches[1])) {
			foreach ($matches[1] as $key => $value) {
				$matches[1][$key] = (int) $value;
			}
			$e2_start_time = vsprintf('%04d%02d', $matches[1]);
		} else {
			$e2_start_time = '';
		}
		return strcasecmp($e1_start_time, $e2_start_time) < 0 ? 1 : -1;
	}

	public function certificate_sort() {
		$e1['start_time'] = isset($e1['start_time']) ? $e1['start_time'] : '';
		$e2['start_time'] = isset($e2['start_time']) ? $e2['start_time'] : '';
		preg_match_all('/(\d+)/', $e1['start_time'], $matches);
		if (2 == count($matches[1])) {
			foreach ($matches[1] as $key => $value) {
				$matches[1][$key] = (int) $value;
			}
			$e1_start_time = vsprintf('%04d%02d', $matches[1]);
		} else {
			$e1_start_time = '';
		}
		preg_match_all('/(\d+)/', $e2['start_time'], $matches);
		if (2 == count($matches[1])) {
			foreach ($matches[1] as $key => $value) {
				$matches[1][$key] = (int) $value;
			}
			$e2_start_time = vsprintf('%04d%02d', $matches[1]);
		} else {
			$e2_start_time = '';
		}
		return strcasecmp($e1_start_time, $e2_start_time) < 0 ? 1 : -1;
	}

	public function my_work_sort($works) {
		$years = [];
		$months = [];
		$new_work = []; //排序后的数据
		foreach ($works as $k => $work) {

			if (empty($work['start_time'])) {
				return $works;
			}

			if (empty($work['end_time']) && $work['so_far'] == 'N') {
				return $works;
			}
			if (strpos($work['start_time'], '年') == false) {
				return $works;
			}
			if (strlen($work['start_time']) < 5) {
				return $works;
			}
			$arr = explode('年', rtrim($work['start_time'], '月'));
			if (empty($arr[1])) {
				$arr[1] = 0;
			}
			array_push($years, $arr[0]); //年 组
			$months[$arr[0]][$k] = (int) $arr[1]; //月 组
		}

		$years = array_unique($years);
		rsort($years);
		foreach ($years as $year) {
			$year_month = $months[$year];
			arsort($year_month);
			$i = 0;
			$new_month = [];
			foreach ($year_month as $k => $month) {
				if ($month == 0) {
					$before_month = $new_month[$i - 1];
					if ($k < $before_month['k'] && $before_month['m'] > 0) {
						$new_month[$i - 1] = array('k' => $k, 'm' => $month);
						$new_month[$i] = $before_month;
						continue;
					}
				}
				$new_month[$i] = array('k' => $k, 'm' => $month);
				$i++;
			}

			foreach ($new_month as $month) {
				$key = $month['k'];
				$new_key = empty($works[$key]['id']) ? $key : $works[$key]['id'];
				$new_work["$new_key"] = $works[$key];
			}
		}
		return $new_work;
	}
}