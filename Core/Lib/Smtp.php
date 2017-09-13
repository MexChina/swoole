<?php
namespace Swoole\Core\Lib;
use Swoole\Core\Log;
class Smtp{

	private $smtp_port;
	private $time_out;
	private $host_name;
	private $relay_host;
	private $auth;
	private $user;
	private $pass;
	private $sock;

	public function __construct($user,$pass,$relay_host = "", $smtp_port = 25,$auth = false)
	{
		$this->smtp_port = $smtp_port;
		$this->relay_host = $relay_host;
		$this->time_out = 30;
		$this->auth = $auth;
		$this->user = $user;
		$this->pass = $pass;
		$this->host_name = "localhost";
		$this->sock = FALSE;
	}


	public function sendmail(string $to,string $from,string $subject = "",string $body = "", string $cc = "",string $bcc = "", string $additional_headers = ""):bool
	{
		$mail_from = $this->get_address($this->strip_comment($from));
		$body = ereg_replace("(^|(\r\n))(\.)", "\1.\3", $body);
		$header = "MIME-Version:1.0\r\n";

		$header .= "Content-Type:text/html\r\n";
		$header .= "To: ".$to."\r\n";
		if ($cc != "") {
			$header .= "Cc: ".$cc."\r\n";
		}
		$header .= "From: $from<".$from.">\r\n";
		$header .= "Subject: ".$subject."\r\n";
		$header .= $additional_headers;
		$header .= "Date: ".date("r")."\r\n";
		$header .= "X-Mailer:By Redhat (PHP/".phpversion().")\r\n";
		list($msec, $sec) = explode(" ", microtime());
		$header .= "Message-ID: <".date("YmdHis", $sec).".".($msec*1000000).".".$mail_from.">\r\n";
		$TO = explode(",", $this->strip_comment($to));
		if ($cc != "") {
			$TO = array_merge($TO, explode(",", $this->strip_comment($cc)));
		}

		if ($bcc != "") {
			$TO = array_merge($TO, explode(",", $this->strip_comment($bcc)));
		}

		$sent = TRUE;
		foreach ($TO as $rcpt_to) {
			$rcpt_to = $this->get_address($rcpt_to);
			if (!$this->smtp_sockopen($rcpt_to)) {
				Log::writelog("Error: Cannot send email to ".$rcpt_to);
				$sent = FALSE;
				continue;
			}
			if ($this->smtp_send($this->host_name, $mail_from, $rcpt_to, $header, $body)) {
				Log::writelog("E-mail has been sent to <".$rcpt_to.">");
			} else {
				Log::writelog("Error: Cannot send email to <".$rcpt_to.">");
				$sent = FALSE;
			}
			fclose($this->sock);
			Log::writelog("Disconnected from remote host");
		}
		return $sent;
	}

	private function smtp_send(string $helo,string $from, $to, $header, $body = ""):bool
	{
		if (!$this->smtp_putcmd("HELO", $helo)) {
			Log::writelog("sending HELO command error!");
			return false;
		}

		if($this->auth){
			if (!$this->smtp_putcmd("AUTH LOGIN", base64_encode($this->user))) {
				Log::writelog("sending HELO command error!");
				return false;
			}

			if (!$this->smtp_putcmd("", base64_encode($this->pass))) {
				Log::writelog("sending HELO command error!");
				return false;	
			}
		}

		if (!$this->smtp_putcmd("MAIL", "FROM:<".$from.">")) {
			Log::writelog("sending MAIL FROM command error!");
			return false;
		}

		if (!$this->smtp_putcmd("RCPT", "TO:<".$to.">")) {
			Log::writelog("sending RCPT TO command error!");
			return false;
		}

		if (!$this->smtp_putcmd("DATA")) {
			Log::writelog("sending DATA command error!");
			return false;
		}

		if (!$this->smtp_message($header, $body)) {
			Log::writelog("sending message error!");
			return false;
		}

		if (!$this->smtp_eom()) {
			Log::writelog("sending <CR><LF>.<CR><LF> [EOM] error!");
			return false;
		}

		if (!$this->smtp_putcmd("QUIT")) {
			Log::writelog("sending QUIT command error!");
			return false;
		}
		return TRUE;
	}

	private function smtp_sockopen(string $address):bool
	{
		if ($this->relay_host == "") {
			return $this->smtp_sockopen_mx($address);
		} else {
			return $this->smtp_sockopen_relay();
		}
	}

	private function smtp_sockopen_relay():bool
	{
		Log::writelog("Trying to ".$this->relay_host.":".$this->smtp_port);
		$this->sock = fsockopen($this->relay_host, $this->smtp_port, $errno, $errstr, $this->time_out);
		if (!($this->sock && $this->smtp_ok())) {
			Log::writelog("Error: Cannot connenct to relay host ".$this->relay_host."\nError: ".$errstr." (".$errno.")");
			return FALSE;
		}
		Log::writelog("Connected to relay host ".$this->relay_host);
		return TRUE;;
	}

	private function smtp_sockopen_mx(string $address):bool
	{
		$domain = ereg_replace("^.+@([^@]+)$", "\1", $address);
		if (!getmxrr($domain, $MXHOSTS)) {
			Log::writelog("Error: Cannot resolve MX ".$domain);
			return FALSE;
		}
		foreach ($MXHOSTS as $host) {
			Log::writelog("Trying to ".$host.":".$this->smtp_port);
			$this->sock = fsockopen($host, $this->smtp_port, $errno, $errstr, $this->time_out);

			if (!($this->sock && $this->smtp_ok())) {
				Log::writelog("Warning: Cannot connect to mx host ".$host."\nError: ".$errstr." (".$errno.")");
				continue;
			}
			Log::writelog("Connected to mx host ".$host);
			return TRUE;
		}
		Log::writelog("Error: Cannot connect to any mx hosts (".implode(", ", $MXHOSTS).")");
		return FALSE;
	}

	private function smtp_message(string $header,string $body):bool
	{
		fputs($this->sock, $header."\r\n".$body);
		return TRUE;
	}

	private function smtp_eom()
	{
		fputs($this->sock, "\r\n.\r\n");
		return $this->smtp_ok();
	}

	private function smtp_ok():bool
	{
		$response = str_replace("\r\n", "", fgets($this->sock, 512));
		if (!ereg("^[23]", $response)) {
			fputs($this->sock, "QUIT\r\n");
			fgets($this->sock, 512);
			Log::writelog("Error: Remote host returned \"".$response."\"\n");
			return FALSE;
		}
		return TRUE;
	}

	private function smtp_putcmd(string $cmd,string $arg = ""):bool
	{
		if ($arg != "") {
			$cmd = $cmd=="" ? $arg : $cmd." ".$arg;
		}
		fputs($this->sock, $cmd."\r\n");
		$this->smtp_debug("> ".$cmd."\n");
		return $this->smtp_ok();
	}

	private function strip_comment(string $address):string
	{
		$comment = "\([^()]*\)";
		while (ereg($comment, $address)) {
			$address = ereg_replace($comment, "", $address);
		}
		return $address;
	}

	private function get_address(string $address):string
	{
		$address = ereg_replace("([ \t\r\n])+", "", $address);
		$address = ereg_replace("^.*<(.+)>.*$", "\1", $address);
		return $address;
	}
}