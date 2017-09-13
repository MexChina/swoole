<?php
/**
 * 定时管理日志的脚本  TimerManager
 */
$deltag = [
	'icdc.2017',
	'gsystem_buried',
	'gsystem.2017',
	'icdc_error',
	'icdc_gearman',
	'gsystem_gearman',
	'gsystem.log',
	'gsystem_table',
	'sql.log',
	'icdc_longdata',
	'icdc_mcache',
	'icdc_sql',
	'position_toh',
	'position-2017',
];

function L($msg) {
	$now = date('Y-m-d H:i:s');
	if (is_array($msg)) {
		$msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
	}
	echo "{$now}\t{$msg}\r\n";
	return error_log("{$now}\t{$msg}\r\n", 3, "/opt/log/TimerManager." . date('Y-m-d'));
}

function mails($msg) {
	$obj = new smtp("dongqing.shi@cheng95.com", "ifchange888", "service.cheng95.com");
	$obj->sendmail("bi@ifchange.com", "Server39", "TimerManager", $msg);
}


class smtp {
	private $smtp_port;
	private $time_out;
	private $host_name;
	private $relay_host;
	private $auth;
	private $user;
	private $pass;
	private $sock;
	public function __construct($user, $pass, $relay_host = "", $smtp_port = 25) {
		$this->smtp_port = $smtp_port;
		$this->relay_host = $relay_host;
		$this->time_out = 30;
		$this->user = $user;
		$this->pass = $pass;
		$this->host_name = "localhost";
		$this->sock = FALSE;
	}
	public function sendmail($to, $from, $subject = "", $body = "") {
		$mail_from = $from;
		$header = "MIME-Version:1.0\r\n";
		$header .= "Content-Type:text/html; charset=utf-8\r\n";
		$header .= "To: " . $to . "\r\n";
		$header .= "From: $from<" . $from . ">\r\n";
		$header .= "Subject: " . $subject . "\r\n";
		$header .= "Date: " . date("r") . "\r\n";
		$header .= "X-Mailer:By Redhat (PHP/" . phpversion() . ")\r\n";
		list($msec, $sec) = explode(" ", microtime());
		$header .= "Message-ID: <" . date("YmdHis", $sec) . "." . ($msec * 1000000) . "." . $mail_from . ">\r\n";
		$this->smtp_sockopen($to);
		$this->smtp_send($this->host_name, $mail_from, $to, $header, $body);
		fclose($this->sock);
	}
	private function smtp_send($helo, $from, $to, $header, $body = "") {
		$this->smtp_putcmd("HELO", $helo);
		$this->smtp_putcmd("MAIL", "FROM:<" . $from . ">");
		$this->smtp_putcmd("RCPT", "TO:<" . $to . ">");
		$this->smtp_putcmd("DATA");
		$this->smtp_message($header, $body);
		$this->smtp_eom();
		$this->smtp_putcmd("QUIT");
	}

	private function smtp_sockopen($address) {
		if ($this->relay_host == "") {
			return $this->smtp_sockopen_mx($address);
		} else {
			return $this->smtp_sockopen_relay();
		}
	}
	private function smtp_sockopen_relay() {
		$this->sock = fsockopen($this->relay_host, $this->smtp_port, $errno, $errstr, $this->time_out);
		if (!$this->sock) {
			return FALSE;
		}
		return TRUE;
	}
	private function smtp_sockopen_mx($address) {
		$domain = ereg_replace("^.+@([^@]+)$", "\1", $address);
		if (!getmxrr($domain, $MXHOSTS)) {
			return FALSE;
		}
		foreach ($MXHOSTS as $host) {
			$this->sock = fsockopen($host, $this->smtp_port, $errno, $errstr, $this->time_out);
			if (!$this->sock) {
				continue;
			}
			return TRUE;
		}
		return FALSE;
	}
	private function smtp_message($header, $body) {
		fputs($this->sock, $header . "\r\n" . $body);
		return TRUE;
	}

	private function smtp_eom() {
		fputs($this->sock, "\r\n.\r\n");
		return true;
	}
	private function smtp_putcmd($cmd, $arg = "") {
		if ($arg != "") {
			$cmd = $cmd == "" ? $arg : $cmd . " " . $arg;
		}
		fputs($this->sock, $cmd . "\r\n");
		return true;
	}
}

class access{

	public function __construct($file) {
		$this->file = $file;
	}

	private function css() {
		return '<style>.table{width:90%;margin-bottom:20px}.table-bordered{border:1px solid #ddd;border-collapse:separate;border-left:0;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}table{max-width:100%;background-color:transparent;border-collapse:collapse;border-spacing:0;display:table}thead{display:table-header-group;vertical-align:middle;border-color:inherit}tr{display:table-row;vertical-align:inherit;border-color:inherit}.table-bordered thead:first-child tr:first-child th:first-child,.table-bordered tbody:first-child tr:first-child td:first-child{-webkit-border-top-left-radius:4px;border-top-left-radius:4px;-moz-border-radius-topleft:4px}.table-bordered caption+thead tr:first-child th,.table-bordered caption+tbody tr:first-child th,.table-bordered caption+tbody tr:first-child td,.table-bordered colgroup+thead tr:first-child th,.table-bordered colgroup+tbody tr:first-child th,.table-bordered colgroup+tbody tr:first-child td,.table-bordered thead:first-child tr:first-child th,.table-bordered tbody:first-child tr:first-child th,.table-bordered tbody:first-child tr:first-child td{border-top:0}.table thead th{vertical-align:bottom}.table-bordered th,.table-bordered td{border-left:1px solid #ddd}.table th{font-weight:bold}.table th,.table td{padding:8px;line-height:20px;text-align:left;vertical-align:top;border-top:1px solid #ddd}td,th{display:table-cell;vertical-align:inherit}tbody{display:table-row-group;vertical-align:middle;border-color:inherit}tr{display:table-row;vertical-align:inherit;border-color:inherit}bordered td{border-left:1px solid #ddd}</style>';
	}

	private function read() {
		$tmp_arr = [];
		foreach (glob($this->file) as $file) {
			$log = new SplFileObject($file);
			foreach ($log as $line) {
				if (empty($line)) {
					continue;
				}

				$arr = explode("\t", $line);
				$w = $arr[1]; //icdc_online
				$c = $arr[2]; //resume
				$a = $arr[3]; //save
				$t = $arr[4]; //time
				if ($t < 1) {
					isset($tmp_arr[$w][$c][$a]['t1']) ? $tmp_arr[$w][$c][$a]['t1']++ : $tmp_arr[$w][$c][$a]['t1'] = 1;
				} elseif ($t > 1 && $t < 5) {
					isset($tmp_arr[$w][$c][$a]['t2']) ? $tmp_arr[$w][$c][$a]['t2']++ : $tmp_arr[$w][$c][$a]['t2'] = 1;
				} else {
					isset($tmp_arr[$w][$c][$a]['t3']) ? $tmp_arr[$w][$c][$a]['t3']++ : $tmp_arr[$w][$c][$a]['t3'] = 1;
				}
				isset($tmp_arr[$w][$c][$a]['t4']) ? $tmp_arr[$w][$c][$a]['t4'] += $t : $tmp_arr[$w][$c][$a]['t4'] = $t;
			}
			unset($log);
		}
		exec("/bin/cat {$this->file} > /opt/log/bi_access_back." . date("Y-m-d"));
		exec("/bin/rm -rf {$this->file}");
		return $tmp_arr;
	}

	private function worker_html($arr) {
		$body = "<h2>总的work统计</h2><table class=\"table table-hover table-bordered\">\n<thead><tr><th>worker名</th><th>总次数</th><th>[0,1)</th><th>[1,5)</th><th>[5,+]</th><th>平均时间</th></tr></thead><tbody>\n";
		$worker_arr = [];
		foreach ($arr as $worker_name => $worker_value) {
			$worker_arr[$worker_name] = array(
				'tt' => 0,
				't1' => 0,
				't2' => 0,
				't3' => 0,
				't4' => 0,
			);
			foreach ($worker_value as $controller) {
				foreach ($controller as $action) {
					if (isset($action['t1'])) {
						$worker_arr[$worker_name]['tt'] += $action['t1'];
						$worker_arr[$worker_name]['t1'] += $action['t1'];
					}

					if (isset($action['t2'])) {
						$worker_arr[$worker_name]['tt'] += $action['t2'];
						$worker_arr[$worker_name]['t2'] += $action['t2'];
					}

					if (isset($action['t3'])) {
						$worker_arr[$worker_name]['tt'] += $action['t3'];
						$worker_arr[$worker_name]['t3'] += $action['t3'];
					}
					$worker_arr[$worker_name]['t4'] > 0 ? $worker_arr[$worker_name]['t4'] += $action['t4'] : $worker_arr[$worker_name]['t4'] = $action['t4'];
				}
			}
		}
		$values = '';
		foreach ($worker_arr as $w => $r) {
			$t4 = @number_format($r['t4'] / $r['tt'], 5);
			$body .= "<tr><td>{$w}</td><td>{$r['tt']}</td><td>{$r['t1']}</td><td>{$r['t2']}</td><td>{$r['t3']}</td><td>{$t4}</td></tr>\n";
			$values .= "('{$this->host}','{$w}','{$r['tt']}','{$r['t1']}','{$r['t2']}','{$r['t3']}','{$t4}'),";
		}
		$values = rtrim($values, ',');
		return $body . "</tbody></table>\n";
	}

	private function info_html($arr) {
		$body = "<h2>详细的work统计</h2><table class=\"table table-hover table-bordered\">\n<thead><tr><th>worker名</th><th>控制器名</th><th>方法名</th><th>总次数</th><th>[0,1)</th><th>[1,5)</th><th>[5,+]</th><th>平均时间</th></tr></thead><tbody>\n";
		$values = '';
		foreach ($arr as $w => $worker_value) {
			foreach ($worker_value as $c => $controller) {
				foreach ($controller as $m => $action) {
					$t1 = isset($action['t1']) ? $action['t1'] : 0;
					$t2 = isset($action['t2']) ? $action['t2'] : 0;
					$t3 = isset($action['t3']) ? $action['t3'] : 0;
					$tt = $t1 + $t2 + $t3;
					$t4 = @number_format($action['t4'] / $tt, 5);
					$body .= "<tr><td>{$w}</td><td>{$c}</td><td>{$m}</td><td>{$tt}</td><td>{$t1}</td><td>{$t2}</td><td>{$t3}</td><td>{$t4}</td></tr>\n";
					$values .= "('{$this->host}','{$w}','{$c}','{$m}','{$tt}','{$t1}','{$t2}','{$t3}','{$t4}'),";
				}
			}
		}
		$values = rtrim($values, ',');
		return $body . "</tbody></table>\n";
	}

	public function start() {
		L("start to read file");
		$result = $this->read();
		L("start to load css");
		$body = $this->css();
		L("start to count worker data");
		$body .= $this->worker_html($result);
		$body .= "<hr/ style='border-color:inherit'>";
		L("start to count worker detail data");
		$body .= $this->info_html($result);
		L("start to send email");
		mails($body);
		L("mail success");
	}
}


swoole_set_process_name('TimerManager');
swoole_process::daemon(true, false);
swoole_timer_tick(1000, function () use ($deltag) {

	$flag = false;
	exec("df -h | awk '{print $5\",\"$6}'", $out);
	$msg = '<h3>服务器磁盘使用情况</h3><hr/>';
	foreach ($out as $r) {
		$msg .= $r . "<br/>";
		$tt = explode(',', $r);
		if ($tt[1] == '/opt' && (int) $tt[0] > 80) {
			$flag = true;
		}
	}

	if ($flag) {
		$msg .= '<br/><br/><br/><h3>被清理的日志</h3><hr/>';
		foreach ($deltag as $v) {
			exec("ls /opt/log | grep $v", $k);
			exec("/bin/rm -rf /opt/log/" . $k[0]);
			L("/opt/log/{$k[0]} del");
			$msg .= date('Y-m-d H:i:s') . "&nbsp;&nbsp;&nbsp;&nbsp;/opt/log/{$k[0]} del<br/>";
			unset($k);
		}
		mails($msg);
	}

	//如果是周五了该怎么办  写周报发周报
	if (date('w') == '5' && date('H:i:s') == '16:00:00') {
		$obj = new access("/opt/log/*_access.2017*");
		$obj->start();
	}
});
