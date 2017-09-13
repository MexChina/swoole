<?php
/**
 * 将压缩包中的城市id对应城市名称检查一般，将没有或错误的重新更正并清理对应的缓存
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;

class Cityname extends \Swoole\Core\App\Controller {
	private $db; //源库
	private $page_size = 1000;
	private $dictionaries;

	public function init() {}

	public function index() {
		$db = $this->db("master_icdc_" . $this->swoole->worker_id);
		$this->dictionaries = $this->cities();

		$cache = new \Memcached();
		$cache->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
		$cache->addServers([['192.168.8.36', '11211', '1', true], ['192.168.8.37', '11211', '1', true]]);

		$max = $db->query("select max(id) as id from resumes")->fetch();
		$min = $db->query("select min(id) as id from resumes")->fetch();

		for ($start_id = $min['id']; $start_id <= $max['id']; $start_id += $this->page_size) {
			$end_id = $start_id + $this->page_size;

			if ($end_id > $max['id']) {
				$end_id = $max['id'];
			}

			$result = $db->query("select id,compress from resumes_extras where id >= $start_id and id <= $end_id")->fetchall();
			foreach ($result as $row) {

				$sql = $this->check($row);
				if ($sql == false) {
					continue;
				}

				$key = base64_encode("resumes_extras_" . $row['id']);
				$cache->delete($key, 0);
				$db->query($sql);
			}

			$percent = sprintf("%1\$.2f", ($end_id / $max['id']) * 100);
			Log::write_log("icdc_" . $this->swoole->worker_id . " {$percent}%");
		}

		$db->close();
		Log::writelog("icdc_" . $this->swoole->worker_id . " complete...");
	}

	private function cities() {
		$worker = new \GearmanClient();
		$worker->addServers("192.168.8.70:4730");
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
				'c' => 'logic_region',
				'm' => 'search_all',
				'p' => array(),
			),
		);

		$res = $worker->doNormal('gsystem_basic', json_encode($param));

		$result = json_decode($res, true);

		$dictionary = [];
		foreach ($result['response']['results'] as $one) {
			$dictionary['cities'][$one['id']] = $one['name'];
		}
		return $dictionary;
	}

	public function check($row) {
		$flag = false;
		$resume_id = (int) $row['id'];
		if ($resume_id <= 0) {
			return false;
		}

		if (ctype_xdigit($row['compress'])) {
			$flag = true;
		}

		$compress = $flag ? json_decode(gzuncompress(hex2bin($row['compress'])), true) : json_decode(gzuncompress($row['compress']), true);

		if (!is_array($compress)) {
			error_log($resume_id . "\n", 3, "/opt/log/compress_error");
			return false;
		}

		//如果城市id为空，那么name必然为空
		if (empty($compress['basic']['expect_city_ids'])) {
			return false;
		}

		//重新获取name
		$expect_city_ids = explode(',', $compress['basic']['expect_city_ids']);
		foreach ($expect_city_ids as $idx => $id) {
			if (!$id) {
				continue;
			}

			$s = (0 == $idx) ? '' : ',';
			$nams = isset($this->dictionaries['cities'][$id]) ? $this->dictionaries['cities'][$id] : '';
			$expect_city_names .= $s . $nams;
		}

		//如果新查出来的名字和库中已有的名字相同，则退出
		if ($expect_city_names == $compress['basic']['expect_city_names']) {
			return false;
		}

		$compress['basic']['expect_city_names'] = $expect_city_names;
		$compress = addslashes(bin2hex(gzcompress(json_encode($compress))));
		$time = date('Y-m-d H:i:s');
		Log::write_log($resume_id . " ...");
		return "update resumes_extras set `compress`='$compress',`updated_at`='$time',`cv_source`='',`cv_trade`='',`cv_title`='',`cv_tag`='',`skill_tag`='',`personal_tag`='' where id='$resume_id'";
	}
}