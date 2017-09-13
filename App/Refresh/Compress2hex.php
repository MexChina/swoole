<?php
/**
 * 将就的压缩包重构成新的压缩包
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;

class Compress2hex extends \Swoole\Core\App\Controller {
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

		if ($flag) {
			return;
		}

		$compress = bin2hex(gzcompress(json_encode($compress)));
		$time = date('Y-m-d H:i:s');
		$this->db->query("update resumes_extras set compress='$compress',updated_at='$time' where id=$id");
		Log::write_log($id . " ok...");
	}
}