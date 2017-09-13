<?php

namespace Swoole\Core\Daemon;

/* * ***********************************************

  Snoopy的一些特点:
 * 方便抓取网页的内容 * 方便抓取网页的文本内容 (去除HTML标签)
 * 方便抓取网页的链接
 * 支持代理主机
 * 支持基本的用户名/密码验证
 * 支持设置 user_agent, referer(来路), cookies 和 header content(头文件)
 * 支持浏览器转向，并能控制转向深度
 * 能把网页中的链接扩展成高质量的url(默认)
 * 方便提交数据并且获取返回值
 * 支持跟踪HTML框架(v0.92增加)
 * 支持再转向的时候传递cookies (v0.92增加)


  类方法:
  　　fetch($URI)
  　　这是为了抓取网页的内容而使用的方法。$URI参数是被抓取网页的URL地址。抓取的结果被存储在 $this->results 中。如果你正在抓取的是一个框架，Snoopy将会将每个框架追踪后存入数组中，然后存入 $this->results。
  　　fetchtext($URI)
  　　本方法类似于fetch()，唯一不同的就是本方法会去除HTML标签和其他的无关数据，只返回网页中的文字内容。
  　　fetchform($URI)
  　　本方法类似于fetch()，唯一不同的就是本方法会去除HTML标签和其他的无关数据，只返回网页中表单内容(form)。
  　　fetchlinks($URI)
  　　本方法类似于fetch()，唯一不同的就是本方法会去除HTML标签和其他的无关数据，只返回网页中链接(link)。
  默认情况下，相对链接将自动补全，转换成完整的URL。
  　　submit($URI,$formvars)
  　　本方法向$URL指定的链接地址发送确认表单。$formvars是一个存储表单参数的数组。
  　　submittext($URI,$formvars)
  　　本方法类似于submit()，唯一不同的就是本方法会去除HTML标签和其他的无关数据，只返回登陆后网页中的文字内容。
  　　submitlinks($URI)
  　　本方法类似于submit()，唯一不同的就是本方法会去除HTML标签和其他的无关数据，只返回网页中链接(link)。
  默认情况下，相对链接将自动补全，转换成完整的URL。


  类属性: (缺省值在括号里)
  $host 连接的主机
  $port 连接的端口
  $proxy_host 使用的代理主机，如果有的话
  $proxy_port 使用的代理主机端口，如果有的话
  $agent 用户代理伪装 (Snoopy v0.1)
  $referer 来路信息，如果有的话
  $cookies cookies， 如果有的话
  $rawheaders 其他的头信息, 如果有的话
  $maxredirs 最大重定向次数， 0=不允许 (5)
  $offsiteok whether or not to allow redirects off-site. (true)
  $expandlinks 是否将链接都补全为完整地址 (true)
  $user 认证用户名, 如果有的话
  $pass 认证用户名, 如果有的话
  $accept http 接受类型 (image/gif, image/x-xbitmap, image/jpeg, image/pjpeg)
  $error 哪里报错, 如果有的话
  $response_code 从服务器返回的响应代码
  $headers 从服务器返回的头信息
  $maxlength 最长返回数据长度
  $read_timeout 读取操作超时 (requires PHP 4 Beta 4+)
  设置为0为没有超时
  $timed_out 如果一次读取操作超时了，本属性返回 true (requires PHP 4 Beta 4+)
  $maxframes 允许追踪的框架最大数量
  $status 抓取的http的状态
  $temp_dir 网页服务器能够写入的临时文件目录 (/tmp)
  $curl_path cURL binary 的目录, 如果没有cURL binary就设置为 false


  使用实例DEMO：
  1获取指定url内容
  PHP代码
  $url = “http://www.taoav.com”;
  include(“snoopy.php”);
  $snoopy = new Snoopy;
  $snoopy->fetch($url); //获取所有内容
  echo $snoopy->results; //显示结果
  //可选以下
  $snoopy->fetchtext //获取文本内容（去掉html代码）
  $snoopy->fetchlinks //获取链接
  $snoopy->fetchform //获取表单


  2 表单提交
  PHP代码
  $formvars["username"] = “admin”;
  $formvars["pwd"] = “admin”;
  $action = “http://www.taoav.com”;//表单提交地址
  $snoopy->submit($action,$formvars);//$formvars为提交的数组
  echo $snoopy->results; //获取表单提交后的 返回的结果
  //可选以下
  $snoopy->submittext; //提交后只返回 去除html的 文本
  $snoopy->submitlinks;//提交后只返回 链接
  既然已经提交的表单 那就可以做很多事情 接下来我们来伪装ip,伪装浏览器


  3 伪装
  PHP代码
  $formvars["username"] = “admin”;
  $formvars["pwd"] = “admin”;
  $action = “http://www.taoav.com”;
  include “snoopy.php”;
  $snoopy = new Snoopy;
  $snoopy->cookies["PHPSESSID"] = ‘fc106b1918bd522cc863f36890e6fff7′; //伪装sessionid
  $snoopy->agent = “(compatible; MSIE 4.01; MSN 2.5; AOL 4.0; Windows 98)”; //伪装浏览器
  $snoopy->referer = “http://www.only4.cn”; //伪装来源页地址 http_referer
  $snoopy->rawheaders["Pragma"] = “no-cache”; //cache 的http头信息
  $snoopy->rawheaders["X_FORWARDED_FOR"] = “127.0.0.101″; //伪装ip
  $snoopy->submit($action,$formvars);
  echo $snoopy->results;
  原来我们可以伪装session 伪装浏览器 ，伪装ip， haha 可以做很多事情了。
  例如 带验证码，验证ip 投票， 可以不停的投。
  ps:这里伪装ip ，其实是伪装http头, 所以一般的通过 REMOTE_ADDR 获取的ip是伪装不了，
  反而那些通过http头来获取ip的(可以防止代理的那种) 就可以自己来制造ip。
  关于如何验证码 ，简单说下：
  首先用普通的浏览器， 查看页面 ， 找到验证码所对应的sessionid，
  同时记下sessionid和验证码值，
  接下来就用snoopy去伪造 。
  原理:由于是同一个sessionid 所以取得的验证码和第一次输入的是一样的。


  4 有时我们可能需要伪造更多的东西,snoopy完全为我们想到了
  PHP代码
  $snoopy->proxy_host = “www.only4.cn”;
  $snoopy->proxy_port = “8080″; //使用代理
  $snoopy->maxredirs = 2; //重定向次数
  $snoopy->expandlinks = true; //是否补全链接 在采集的时候经常用到
  // 例如链接为 /images/logo,jpg 可改为它的全链接 http://www.rjkfw.com/images/logo.jpg，这个地方其实可以在最后输出的时候用ereg_replace函数自己替换
  $snoopy->maxframes = 5 //允许的最大框架数
  //注意抓取框架的时候 $snoopy->results 返回的是一个数组
  $snoopy->error //返回报错信息
  上面的基本用法了解了，下面我就实例演示一次：
  PHP代码
  <?
  //echo var_dump($_SERVER);
  include(“Snoopy.class.php”);
  $snoopy = new Snoopy;
  $snoopy->agent = “Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.0.5) Gecko/2008120122 Firefox/3.0.5 FirePHP/0.2.1″;//这项是浏览器信息，前面你用什么浏览器查看cookie，就用那个浏览器的信息(ps:$_SERVER可以查看到浏览器的信息)
  $snoopy->referer = “http://www.rjkfw.com/index.php”;
  $snoopy->expandlinks = true;
  $snoopy->rawheaders["COOKIE"]=”__utmz=17229162.1227682761.29.7.utmccn=(referral)|utmcsr=phpchina.com|utmcct=/html/index.html|utmcmd=referral; cdbphpchina_smile=1D2D0D1; cdbphpchina_cookietime=2592000; __utma=233700831.1562900865.1227113506.1229613449.1231233266.16; __utmz=233700831.1231233266.16.8.utmccn=(referral)|utmcsr=localhost:8080|utmcct=/test3.php|utmcmd=referral; __utma=17229162.1877703507.1227113568.1231228465.1231233160.58; uchome_loginuser=sinopf; xscdb_cookietime=2592000; __utmc=17229162; __utmb=17229162; cdbphpchina_sid=EX5w1V; __utmc=233700831; cdbphpchina_visitedfid=17; cdbphpchinaO766uPYGK6OWZaYlvHSuzJIP22VpwEMGnPQAuWCFL9Fd6CHp2e%2FKw0x4bKz0N9lGk; xscdb_auth=8106rAyhKpQL49eMs%2FyhLBf3C6ClZ%2B2idSk4bExJwbQr%2BHSZrVKgqPOttHVr%2B6KLPg3DtWpTMUI4ttqNNVpukUj6ElM; cdbphpchina_onlineusernum=3721″;
  $snoopy->fetch(“http://bbs.phpchina.com/forum-17-1.html”);
  $n=ereg_replace(“href=”",”href=”http://www.rjkfw.com/”,$snoopy->results );
  echo ereg_replace(“src=”",”src=”http://www.rjkfw.com/”,$n);
  ?>
  这是模拟登陆PHPCHINA论坛的过程，首先要查看自己浏览器的信息：echo var_dump($_SERVER);这句代码可以看到自己浏览器的信息，把 $_SERVER['HTTP_USER_AGENT']后边的内容复制下来，粘在$snoopy->agent的地方，然后就是要查看自己的COOKIE了，用自己在论坛的账号登陆论坛后，在浏览器地址栏里输入javascript:document.write(document.cookie)，回车，就可以看到自己的cookie信息，复制粘贴到$snoopy->rawheaders["COOKIE"]=的后边。（我的cookie信息为了安全起见已经删除了一段内容）
  然后再注意：
  # $n=ereg_replace(“href=”",”href=”http://www.rjkfw.com/”,$snoopy->results );
  # echo ereg_replace(“src=”",”src=”http://www.rjkfw.com/”,$n);
  这两句代码，因为采集到的内容所有的HTML源码地址都是相对链接，所以要替换成绝对链接，这样就可以引用论坛的图片和css样式了。


  5.取HTML内容
  <pre name=”code”>&lt;?php
  include ‘./Snoopy/Snoopy.class.php’;       // 根据本地路径 导入Snoopy类
  $snoopy = new Snoopy();                    // 实例化一个Snoopy对象
  $snoopy-&gt;fetch(“http://www.hao123.com/”);  // 想要抓取的网页地址，这里就抓取hao123为实例
  $line   = $snoopy-&gt;results;                // 通过results属性来获取内容
  print_r($line);                            // 输出</pre>


  6.取得纯文本内容
  <pre name=”code”>include ‘./Snoopy/Snoopy.class.php’;          // 根据本地路径 导入Snoopy类
  $snoopy = new Snoopy();                       // 实例化一个Snoopy对象
  $snoopy-&gt;fetchtext(“http://www.hao123.com/”); // 想要抓取的网页地址，这里就抓取hao123为实例
  $line   = $snoopy-&gt;results;                   // 通过results属性来获取内容
  print_r($line);                               // 输出</pre>


  7.取得表单字段内容
  <pre name=”code”>include ‘./Snoopy/Snoopy.class.php’;       // 根据本地路径 导入Snoopy类
  $snoopy = new Snoopy();                    // 实例化一个Snoopy对象
  $snoopy-&gt;fetch(“http://bbs.blueidea.com/logging.php?action=login”);  // 想要抓取的网页地址，这里就抓取blueidea的登录为实例
  $line   = $snoopy-&gt;results;                // 通过results属性来获取内容
  print_r($line);                            // 输出</pre>


  8.自动登录
  <pre name=”code”>$submit_url = “#”;
  $submit_vars['loginmode'] = ‘normal’;
  $submit_vars['styleid']   = ’1′;
  $submit_vars['name']      = ‘rjkfw.com’;
  $submit_vars['password']  = ‘*******’;
  $submit_vars['loginsubmit'] = “提&amp;nbsp;交”;
  $snoopy-&gt;submit($submit_url, $submit_vars);  // 用submit方法来实现登录
  print_r($snoopy-&gt;results);</pre> */

class Snoopy {
    /*     * ** Public variables *** */

    /* user definable vars */

    var $host = "";  // host name we are connecting to
    var $port = 80;     // port we are connecting to
    public static $proxy_host = "";     // proxy host to use
    public static $proxy_port = "";     // proxy port to use
    public static $proxy_user = "";     // proxy user to use
    public static $proxy_pass = "";     // proxy password to use
    var $agent = "Mozilla/5.0"; // agent we masquerade as
    var $referer = "";     // referer info to pass
    var $cookies = array();   // array of cookies to pass
    // $cookies["username"]="joe";
    var $rawheaders = array();   // array of raw headers to send
    // $rawheaders["Content-type"]="text/html";
    var $maxredirs = 5;     // http redirection depth maximum. 0 = disallow
    var $lastredirectaddr = "";    // contains address of last redirected address
    var $offsiteok = true;    // allows redirection off-site
    var $maxframes = 0;     // frame content depth maximum. 0 = disallow
    var $expandlinks = true;    // expand links to fully qualified URLs.
    // this only applies to fetchlinks()
    // submitlinks(), and submittext()
    var $passcookies = true;    // pass set cookies back through redirects
    // NOTE: this currently does not respect
    // dates, domains or paths.
    var $user = "";     // user for http authentication
    var $pass = "";     // password for http authentication
    // http accept types
    var $accept = "application/json, text/javascript, */*; q=0.01";
    var $results = "";     // where the content is put
    var $error = "";     // error messages sent here
    var $response_code = "";     // response code returned from server
    var $headers = array();   // headers returned from server sent here
    var $maxlength = 500000;    // max return data length (body)
    var $read_timeout = 0;     // timeout on read operations, in seconds
    // supported only since PHP 4 Beta 4
    // set to 0 to disallow timeouts
    var $timed_out = false;    // if a read operation timed out
    var $status = 0;     // http request status
    var $temp_dir = "/tmp";    // temporary directory that the webserver
    // has permission to write to.
    // under Windows, this should be C:\temp
    var $curl_path = "/usr/local/bin/curl";
    // Snoopy will use cURL for fetching
    // SSL content if a full system path to
    // the cURL binary is supplied here.
    // set to false if you do not have
    // cURL installed. See http://curl.haxx.se
    // for details on installing cURL.
    // Snoopy does *not* use the cURL
    // library functions built into php,
    // as these functions are not stable
    // as of this Snoopy release.

    /*     * ** Private variables *** */

    var $_maxlinelen = 4096;    // max line length (headers)
    var $_httpmethod = "GET";    // default http request method
    var $_httpversion = "HTTP/1.0";   // default http request version
    var $_submit_method = "POST";    // default submit method
    var $_submit_type = "application/x-www-form-urlencoded"; // default submit type
    var $_mime_boundary = "";     // MIME boundary for multipart/form-data submit type
    var $_redirectaddr = true;    // will be set if page fetched is a redirect
    var $_redirectdepth = 0;     // increments on an http redirect
    var $_frameurls = array();   // frame src urls
    var $_framedepth = 0;     // increments on frame depth
    var $_isproxy = false;    // set if using a proxy server
    var $_fp_timeout = 30;     // timeout for socket connection

    /* ======================================================================*\
      Function:	fetch
      Purpose:	fetch the contents of a web page
      (and possibly other protocols in the
      future like ftp, nntp, gopher, etc.)
      Input:		$URI	the location of the page to fetch
      Output:		$this->results	the output text from the fetch
      \*====================================================================== */

    function __construct() {
        //选择代理
        $this->results = "";
    }

    function fetch($URI) {
        //preg_match("|^([^:]+)://([^:/]+)(:[\d]+)*(.*)|",$URI,$URI_PARTS);
        $URI_PARTS = parse_url($URI);
        if (!empty($URI_PARTS["user"]))
            $this->user = $URI_PARTS["user"];
        if (!empty($URI_PARTS["pass"]))
            $this->pass = $URI_PARTS["pass"];
        if (empty($URI_PARTS["query"]))
            $URI_PARTS["query"] = '';
        if (empty($URI_PARTS["path"]))
            $URI_PARTS["path"] = '';

        switch (strtolower($URI_PARTS["scheme"])) {
            case "http":
                $this->host = $URI_PARTS["host"];
                if (!empty($URI_PARTS["port"]))
                    $this->port = $URI_PARTS["port"];
                if ($this->_connect($fp)) {
                    if ($this->_isproxy) {
                        // using proxy, send entire URI
                        $this->_httprequest($URI, $fp, $URI, $this->_httpmethod);
                    } else {
                        $path = $URI_PARTS["path"] . ($URI_PARTS["query"] ? "?" . $URI_PARTS["query"] : "");
                        // no proxy, send only the path
                        $this->_httprequest($path, $fp, $URI, $this->_httpmethod);
                    }

                    $this->_disconnect($fp);

                    if ($this->_redirectaddr) {
                        /* url was redirected, check if we've hit the max depth */
                        if ($this->maxredirs > $this->_redirectdepth) {
                            // only follow redirect if it's on this site, or offsiteok is true
                            if (preg_match("|^http://" . preg_quote($this->host) . "|i", $this->_redirectaddr) || $this->offsiteok) {
                                /* follow the redirect */
                                $this->_redirectdepth++;
                                $this->lastredirectaddr = $this->_redirectaddr;
                                $this->fetch($this->_redirectaddr);
                            }
                        }
                    }

                    if ($this->_framedepth < $this->maxframes && count($this->_frameurls) > 0) {
                        $frameurls = $this->_frameurls;
                        $this->_frameurls = array();

                        while (list(, $frameurl) = each($frameurls)) {
                            if ($this->_framedepth < $this->maxframes) {
                                $this->fetch($frameurl);
                                $this->_framedepth++;
                            } else
                                break;
                        }
                    }
                }
                else {
                    return false;
                }
                return true;
                break;
            case "https":
                if (!function_exists('curl_init')) {
                    if (!$this->curl_path)
                        return false;
                    if (function_exists("is_executable"))
                        if (!is_executable($this->curl_path))
                            return false;
                }
                $this->host = $URI_PARTS["host"];
                if (!empty($URI_PARTS["port"]))
                    $this->port = $URI_PARTS["port"];
                if ($this->_isproxy) {
                    // using proxy, send entire URI
                    $this->_httpsrequest($URI, $URI, $this->_httpmethod);
                } else {
                    $path = $URI_PARTS["path"] . ($URI_PARTS["query"] ? "?" . $URI_PARTS["query"] : "");
                    // no proxy, send only the path
                    $this->_httpsrequest($path, $URI, $this->_httpmethod);
                }

                if ($this->_redirectaddr) {
                    /* url was redirected, check if we've hit the max depth */
                    if ($this->maxredirs > $this->_redirectdepth) {
                        // only follow redirect if it's on this site, or offsiteok is true
                        if (preg_match("|^https://" . preg_quote($this->host) . "|i", $this->_redirectaddr) || $this->offsiteok) {
                            /* follow the redirect */
                            $this->_redirectdepth++;
                            $this->lastredirectaddr = $this->_redirectaddr;
                            $this->fetch($this->_redirectaddr);
                        }
                    }
                }

                if ($this->_framedepth < $this->maxframes && count($this->_frameurls) > 0) {
                    $frameurls = $this->_frameurls;
                    $this->_frameurls = array();

                    while (list(, $frameurl) = each($frameurls)) {
                        if ($this->_framedepth < $this->maxframes) {
                            $this->fetch($frameurl);
                            $this->_framedepth++;
                        } else
                            break;
                    }
                }
                return true;
                break;
            default:
                // not a valid protocol
                $this->error = 'Invalid protocol "' . $URI_PARTS["scheme"] . '"\n';
                return false;
                break;
        }
        return true;
    }

    /* ======================================================================*\
      Function:	submit
      Purpose:	submit an http form
      Input:		$URI	the location to post the data
      $formvars	the formvars to use.
      format: $formvars["var"] = "val";
      $formfiles  an array of files to submit
      format: $formfiles["var"] = "/dir/filename.ext";
      Output:		$this->results	the text output from the post
      \*====================================================================== */

    function submit($URI, $formvars = "", $formfiles = "") {
        unset($postdata);
        $postdata = $this->_prepare_post_body($formvars, $formfiles);
        $URI_PARTS = parse_url($URI);
        if (!empty($URI_PARTS["user"]))
            $this->user = $URI_PARTS["user"];
        if (!empty($URI_PARTS["pass"]))
            $this->pass = $URI_PARTS["pass"];
        if (empty($URI_PARTS["query"]))
            $URI_PARTS["query"] = '';
        if (empty($URI_PARTS["path"]))
            $URI_PARTS["path"] = '';

        switch (strtolower($URI_PARTS["scheme"])) {
            case "http":
                $this->host = $URI_PARTS["host"];
                if (!empty($URI_PARTS["port"]))
                    $this->port = $URI_PARTS["port"];
                if ($this->_connect($fp)) {
                    if ($this->_isproxy) {
                        // using proxy, send entire URI
                        $this->_httprequest($URI, $fp, $URI, $this->_submit_method, $this->_submit_type, $postdata);
                    } else {
                        $path = $URI_PARTS["path"] . ($URI_PARTS["query"] ? "?" . $URI_PARTS["query"] : "");
                        // no proxy, send only the path
                        $this->_httprequest($path, $fp, $URI, $this->_submit_method, $this->_submit_type, $postdata);
                    }

                    $this->_disconnect($fp);

                    if ($this->_redirectaddr) {
                        /* url was redirected, check if we've hit the max depth */
                        if ($this->maxredirs > $this->_redirectdepth) {
                            if (!preg_match("|^" . $URI_PARTS["scheme"] . "://|", $this->_redirectaddr))
                                $this->_redirectaddr = $this->_expandlinks($this->_redirectaddr, $URI_PARTS["scheme"] . "://" . $URI_PARTS["host"]);

                            // only follow redirect if it's on this site, or offsiteok is true
                            if (preg_match("|^http://" . preg_quote($this->host) . "|i", $this->_redirectaddr) || $this->offsiteok) {
                                /* follow the redirect */
                                $this->_redirectdepth++;
                                $this->lastredirectaddr = $this->_redirectaddr;
                                if (strpos($this->_redirectaddr, "?") > 0)
                                    $this->fetch($this->_redirectaddr); // the redirect has changed the request method from post to get
                                else
                                    $this->submit($this->_redirectaddr, $formvars, $formfiles);
                            }
                        }
                    }

                    if ($this->_framedepth < $this->maxframes && count($this->_frameurls) > 0) {
                        $frameurls = $this->_frameurls;
                        $this->_frameurls = array();

                        while (list(, $frameurl) = each($frameurls)) {
                            if ($this->_framedepth < $this->maxframes) {
                                $this->fetch($frameurl);
                                $this->_framedepth++;
                            } else
                                break;
                        }
                    }
                }
                else {
                    return false;
                }
                return true;
                break;
            case "https":
                if (!function_exists('curl_init')) {
                    if (!$this->curl_path)
                        return false;
                    if (function_exists("is_executable"))
                        if (!is_executable($this->curl_path))
                            return false;
                }
                $this->host = $URI_PARTS["host"];
                if (!empty($URI_PARTS["port"]))
                    $this->port = $URI_PARTS["port"];
                if ($this->_isproxy) {
                    // using proxy, send entire URI
                    $this->_httpsrequest($URI, $URI, $this->_submit_method, $this->_submit_type, $postdata);
                } else {
                    $path = $URI_PARTS["path"] . ($URI_PARTS["query"] ? "?" . $URI_PARTS["query"] : "");
                    // no proxy, send only the path
                    $this->_httpsrequest($path, $URI, $this->_submit_method, $this->_submit_type, $postdata);
                }

                if ($this->_redirectaddr) {
                    /* url was redirected, check if we've hit the max depth */
                    if ($this->maxredirs > $this->_redirectdepth) {
                        if (!preg_match("|^" . $URI_PARTS["scheme"] . "://|", $this->_redirectaddr))
                            $this->_redirectaddr = $this->_expandlinks($this->_redirectaddr, $URI_PARTS["scheme"] . "://" . $URI_PARTS["host"]);

                        // only follow redirect if it's on this site, or offsiteok is true
                        if (preg_match("|^https://" . preg_quote($this->host) . "|i", $this->_redirectaddr) || $this->offsiteok) {
                            /* follow the redirect */
                            $this->_redirectdepth++;
                            $this->lastredirectaddr = $this->_redirectaddr;
                            if (strpos($this->_redirectaddr, "?") > 0)
                                $this->fetch($this->_redirectaddr); // the redirect has changed the request method from post to get
                            else
                                $this->submit($this->_redirectaddr, $formvars, $formfiles);
                        }
                    }
                }

                if ($this->_framedepth < $this->maxframes && count($this->_frameurls) > 0) {
                    $frameurls = $this->_frameurls;
                    $this->_frameurls = array();

                    while (list(, $frameurl) = each($frameurls)) {
                        if ($this->_framedepth < $this->maxframes) {
                            $this->fetch($frameurl);
                            $this->_framedepth++;
                        } else
                            break;
                    }
                }
                return true;
                break;

            default:
                // not a valid protocol
                $this->error = 'Invalid protocol "' . $URI_PARTS["scheme"] . '"\n';
                return false;
                break;
        }
        return true;
    }

    /* ======================================================================*\
      Function:	fetchlinks
      Purpose:	fetch the links from a web page
      Input:		$URI	where you are fetching from
      Output:		$this->results	an array of the URLs
      \*====================================================================== */

    function fetchlinks($URI) {
        if ($this->fetch($URI)) {
            if ($this->lastredirectaddr)
                $URI = $this->lastredirectaddr;
            if (is_array($this->results)) {
                for ($x = 0; $x < count($this->results); $x++)
                    $this->results[$x] = $this->_striplinks($this->results[$x]);
            } else
                $this->results = $this->_striplinks($this->results);

            if ($this->expandlinks)
                $this->results = $this->_expandlinks($this->results, $URI);
            return true;
        } else
            return false;
    }

    /* ======================================================================*\
      Function:	fetchform
      Purpose:	fetch the form elements from a web page
      Input:		$URI	where you are fetching from
      Output:		$this->results	the resulting html form
      \*====================================================================== */

    function fetchform($URI) {

        if ($this->fetch($URI)) {

            if (is_array($this->results)) {
                for ($x = 0; $x < count($this->results); $x++)
                    $this->results[$x] = $this->_stripform($this->results[$x]);
            } else
                $this->results = $this->_stripform($this->results);

            return true;
        } else
            return false;
    }

    /* ======================================================================*\
      Function:	fetchtext
      Purpose:	fetch the text from a web page, stripping the links
      Input:		$URI	where you are fetching from
      Output:		$this->results	the text from the web page
      \*====================================================================== */

    function fetchtext($URI) {
        if ($this->fetch($URI)) {
            if (is_array($this->results)) {
                for ($x = 0; $x < count($this->results); $x++)
                    $this->results[$x] = $this->_striptext($this->results[$x]);
            } else
                $this->results = $this->_striptext($this->results);
            return true;
        } else
            return false;
    }

    /* ======================================================================*\
      Function:	submitlinks
      Purpose:	grab links from a form submission
      Input:		$URI	where you are submitting from
      Output:		$this->results	an array of the links from the post
      \*====================================================================== */

    function submitlinks($URI, $formvars = "", $formfiles = "") {
        if ($this->submit($URI, $formvars, $formfiles)) {
            if ($this->lastredirectaddr)
                $URI = $this->lastredirectaddr;
            if (is_array($this->results)) {
                for ($x = 0; $x < count($this->results); $x++) {
                    $this->results[$x] = $this->_striplinks($this->results[$x]);
                    if ($this->expandlinks)
                        $this->results[$x] = $this->_expandlinks($this->results[$x], $URI);
                }
            }
            else {
                $this->results = $this->_striplinks($this->results);
                if ($this->expandlinks)
                    $this->results = $this->_expandlinks($this->results, $URI);
            }
            return true;
        } else
            return false;
    }

    /* ======================================================================*\
      Function:	submittext
      Purpose:	grab text from a form submission
      Input:		$URI	where you are submitting from
      Output:		$this->results	the text from the web page
      \*====================================================================== */

    function submittext($URI, $formvars = "", $formfiles = "") {
        if ($this->submit($URI, $formvars, $formfiles)) {
            if ($this->lastredirectaddr)
                $URI = $this->lastredirectaddr;
            if (is_array($this->results)) {
                for ($x = 0; $x < count($this->results); $x++) {
                    $this->results[$x] = $this->_striptext($this->results[$x]);
                    if ($this->expandlinks)
                        $this->results[$x] = $this->_expandlinks($this->results[$x], $URI);
                }
            }
            else {
                $this->results = $this->_striptext($this->results);
                if ($this->expandlinks)
                    $this->results = $this->_expandlinks($this->results, $URI);
            }
            return true;
        } else
            return false;
    }

    /* ======================================================================*\
      Function:	set_submit_multipart
      Purpose:	Set the form submission content type to
      multipart/form-data
      \*====================================================================== */

    function set_submit_multipart() {
        $this->_submit_type = "multipart/form-data";
    }

    /* ======================================================================*\
      Function:	set_submit_normal
      Purpose:	Set the form submission content type to
      application/x-www-form-urlencoded
      \*====================================================================== */

    function set_submit_normal() {
        $this->_submit_type = "application/x-www-form-urlencoded";
    }

    /* ======================================================================*\
      Private functions
      \*====================================================================== */


    /* ======================================================================*\
      Function:	_striplinks
      Purpose:	strip the hyperlinks from an html document
      Input:		$document	document to strip.
      Output:		$match		an array of the links
      \*====================================================================== */

    function _striplinks($document) {
        preg_match_all("'<\s*a\s.*?href\s*=\s*			# find <a href=
						([\"\'])?					# find single or double quote
						(?(1) (.*?)\\1 | ([^\s\>]+))		# if quote found, match up to next matching
													# quote, otherwise match up to next space
						'isx", $document, $links);


        // catenate the non-empty matches from the conditional subpattern

        while (list($key, $val) = each($links[2])) {
            if (!empty($val))
                $match[] = $val;
        }

        while (list($key, $val) = each($links[3])) {
            if (!empty($val))
                $match[] = $val;
        }

        // return the links
        return $match;
    }

    /* ======================================================================*\
      Function:	_stripform
      Purpose:	strip the form elements from an html document
      Input:		$document	document to strip.
      Output:		$match		an array of the links
      \*====================================================================== */

    function _stripform($document) {
        preg_match_all("'<\/?(FORM|INPUT|SELECT|TEXTAREA|(OPTION))[^<>]*>(?(2)(.*(?=<\/?(option|select)[^<>]*>[\r\n]*)|(?=[\r\n]*))|(?=[\r\n]*))'Usi", $document, $elements);

        // catenate the matches
        $match = implode("\r\n", $elements[0]);

        // return the links
        return $match;
    }

    /* ======================================================================*\
      Function:	_striptext
      Purpose:	strip the text from an html document
      Input:		$document	document to strip.
      Output:		$text		the resulting text
      \*====================================================================== */

    function _striptext($document) {

        // I didn't use preg eval (//e) since that is only available in PHP 4.0.
        // so, list your entities one by one here. I included some of the
        // more common ones.

        $search = array("'<script[^>]*?>.*?</script>'si", // strip out javascript
            "'<[\/\!]*?[^<>]*?>'si", // strip out html tags
            "'([\r\n])[\s]+'", // strip out white space
            "'&(quot|#34|#034|#x22);'i", // replace html entities
            "'&(amp|#38|#038|#x26);'i", // added hexadecimal values
            "'&(lt|#60|#060|#x3c);'i",
            "'&(gt|#62|#062|#x3e);'i",
            "'&(nbsp|#160|#xa0);'i",
            "'&(iexcl|#161);'i",
            "'&(cent|#162);'i",
            "'&(pound|#163);'i",
            "'&(copy|#169);'i",
            "'&(reg|#174);'i",
            "'&(deg|#176);'i",
            "'&(#39|#039|#x27);'",
            "'&(euro|#8364);'i", // europe
            "'&a(uml|UML);'", // german
            "'&o(uml|UML);'",
            "'&u(uml|UML);'",
            "'&A(uml|UML);'",
            "'&O(uml|UML);'",
            "'&U(uml|UML);'",
            "'&szlig;'i",
        );
        $replace = array("",
            "",
            "\\1",
            "\"",
            "&",
            "<",
            ">",
            " ",
            chr(161),
            chr(162),
            chr(163),
            chr(169),
            chr(174),
            chr(176),
            chr(39),
            chr(128),
            "�",
            "�",
            "�",
            "�",
            "�",
            "�",
            "�",
        );

        $text = preg_replace($search, $replace, $document);

        return $text;
    }

    /* ======================================================================*\
      Function:	_expandlinks
      Purpose:	expand each link into a fully qualified URL
      Input:		$links			the links to qualify
      $URI			the full URI to get the base from
      Output:		$expandedLinks	the expanded links
      \*====================================================================== */

    function _expandlinks($links, $URI) {

        preg_match("/^[^\?]+/", $URI, $match);

        $match = preg_replace("|/[^\/\.]+\.[^\/\.]+$|", "", $match[0]);
        $match = preg_replace("|/$|", "", $match);
        $match_part = parse_url($match);
        $match_root = $match_part["scheme"] . "://" . $match_part["host"];

        $search = array("|^http://" . preg_quote($this->host) . "|i",
            "|^(\/)|i",
            "|^(?!http://)(?!mailto:)|i",
            "|/\./|",
            "|/[^\/]+/\.\./|"
        );

        $replace = array("",
            $match_root . "/",
            $match . "/",
            "/",
            "/"
        );

        $expandedLinks = preg_replace($search, $replace, $links);

        return $expandedLinks;
    }

    /* ======================================================================*\
      Function:	_httprequest
      Purpose:	go get the http data from the server
      Input:		$url		the url to fetch
      $fp			the current open file pointer
      $URI		the full URI
      $body		body contents to send if any (POST)
      Output:
      \*====================================================================== */

    function _httprequest($url, $fp, $URI, $http_method, $content_type = "", $body = "") {
        $cookie_headers = '';
        if ($this->passcookies)
            $this->setcookies();

        $URI_PARTS = parse_url($URI);
        if (empty($url))
            $url = "/";
        $headers = $http_method . " " . $url . " " . $this->_httpversion . "\r\n";
        if (!empty($this->agent))
            $headers .= "User-Agent: " . $this->agent . "\r\n";
        if (!empty($this->host) && !isset($this->rawheaders['Host'])) {
            $headers .= "Host: " . $this->host;
            if (!empty($this->port) && $this->port != 80)
                $headers .= ":" . $this->port;
            $headers .= "\r\n";
        }
        if (!empty($this->accept))
            $headers .= "Accept: " . $this->accept . "\r\n";
        if (!empty($this->referer))
            $headers .= "Referer: " . $this->referer . "\r\n";
        if (!empty($this->cookies)) {
            if (!is_array($this->cookies))
                $this->cookies = (array) $this->cookies;

            reset($this->cookies);
            if (count($this->cookies) > 0) {
                $cookie_headers .= 'Cookie: ';
                foreach ($this->cookies as $cookieKey => $cookieVal) {
                    $cookie_headers .= $cookieKey . "=" . $cookieVal . "; ";
                }
                $headers .= substr($cookie_headers, 0, -2) . "\r\n";
            }
        }
        if (!empty($this->rawheaders)) {
            if (!is_array($this->rawheaders))
                $this->rawheaders = (array) $this->rawheaders;
            while (list($headerKey, $headerVal) = each($this->rawheaders))
                $headers .= $headerKey . ": " . $headerVal . "\r\n";
        }
        if (!empty($content_type)) {
            $headers .= "Content-type: $content_type";
            if ($content_type == "multipart/form-data")
                $headers .= "; boundary=" . $this->_mime_boundary;
            $headers .= "\r\n";
        }
        if (!empty($body))
            $headers .= "Content-length: " . strlen($body) . "\r\n";
        if (!empty($this->user) || !empty($this->pass))
            $headers .= "Authorization: Basic " . base64_encode($this->user . ":" . $this->pass) . "\r\n";

        //add proxy auth headers
        if (!empty(self::$proxy_user))
            $headers .= 'Proxy-Authorization: ' . 'Basic ' . base64_encode(self::$proxy_user . ':' . self::$proxy_pass) . "\r\n";


        $headers .= "\r\n";

        // set the read timeout if needed
        if ($this->read_timeout > 0)
            socket_set_timeout($fp, $this->read_timeout);
        $this->timed_out = false;

        fwrite($fp, $headers . $body, strlen($headers . $body));

        $this->_redirectaddr = false;
        unset($this->headers);

        while ($currentHeader = fgets($fp, $this->_maxlinelen)) {
            if ($this->read_timeout > 0 && $this->_check_timeout($fp)) {
                $this->status = -100;
                return false;
            }

            if ($currentHeader == "\r\n")
                break;

            // if a header begins with Location: or URI:, set the redirect
            if (preg_match("/^(Location:|URI:)/i", $currentHeader)) {
                // get URL portion of the redirect
                preg_match("/^(Location:|URI:)[ ]+(.*)/i", chop($currentHeader), $matches);
                // look for :// in the Location header to see if hostname is included
                if (!empty($matches)) {
                    if (!preg_match("|\:\/\/|", $matches[2])) {
                        // no host in the path, so prepend
                        $this->_redirectaddr = $URI_PARTS["scheme"] . "://" . $this->host . ":" . $this->port;
                        // eliminate double slash
                        if (!preg_match("|^/|", $matches[2]))
                            $this->_redirectaddr .= "/" . $matches[2];
                        else
                            $this->_redirectaddr .= $matches[2];
                    } else
                        $this->_redirectaddr = $matches[2];
                }
            }

            if (preg_match("|^HTTP/|", $currentHeader)) {
                if (preg_match("|^HTTP/[^\s]*\s(.*?)\s|", $currentHeader, $status)) {
                    $this->status = $status[1];
                }
                $this->response_code = $currentHeader;
            }

            $this->headers[] = $currentHeader;
        }

        $results = '';
        do {
            $_data = fread($fp, $this->maxlength);
            if (strlen($_data) == 0) {
                break;
            }
            $results .= $_data;
        } while (true);

        if ($this->read_timeout > 0 && $this->_check_timeout($fp)) {
            $this->status = -100;
            return false;
        }

        // check if there is a a redirect meta tag

        if (preg_match("'<meta[\s]*http-equiv[^>]*?content[\s]*=[\s]*[\"\']?\d+;[\s]*URL[\s]*=[\s]*([^\"\']*?)[\"\']?>'i", $results, $match)) {
            $this->_redirectaddr = $this->_expandlinks($match[1], $URI);
        }

        // have we hit our frame depth and is there frame src to fetch?
        if (($this->_framedepth < $this->maxframes) && preg_match_all("'<frame\s+.*src[\s]*=[\'\"]?([^\'\"\>]+)'i", $results, $match)) {
            $this->results[] = $results;
            for ($x = 0; $x < count($match[1]); $x++)
                $this->_frameurls[] = $this->_expandlinks($match[1][$x], $URI_PARTS["scheme"] . "://" . $this->host);
        }
        // have we already fetched framed content?
        elseif (is_array($this->results))
            $this->results[] = $results;
        // no framed content
        else
            $this->results = $results;
        //保存cookies
        if ($this->passcookies)
            $this->setcookies();
        return true;
    }

    /* ======================================================================*\
      Function:	_httpsrequest
      Purpose:	go get the https data from the server using curl
      Input:		$url		the url to fetch
      $URI		the full URI
      $body		body contents to send if any (POST)
      Output:
      \*====================================================================== */

    function _httpsrequest($url, $URI, $http_method, $content_type = "", $body = "") {
        $headers = array();

        $URI_PARTS = parse_url($URI);
        if (empty($url))
            $url = "/";
        // GET ... header not needed for curl
        //$headers[] = $http_method." ".$url." ".$this->_httpversion;
        if (!empty($this->agent))
            $headers[] = "User-Agent: " . $this->agent;
        if (!empty($this->host))
            if (!empty($this->port) && $this->port != 80)
                $headers[] = "Host: " . $this->host . ":" . $this->port;
            else
                $headers[] = "Host: " . $this->host;
        if (!empty($this->accept))
            $headers[] = "Accept: " . $this->accept;
        if (!empty($this->referer))
            $headers[] = "Referer: " . $this->referer;
        if (!empty($this->cookies)) {
            if (!is_array($this->cookies))
                $this->cookies = (array) $this->cookies;

            reset($this->cookies);
            if (count($this->cookies) > 0) {
                $cookie_str = 'Cookie: ';
                foreach ($this->cookies as $cookieKey => $cookieVal) {
                    $cookie_str .= $cookieKey . "=" . $cookieVal . "; ";
                }
                $headers[] = substr($cookie_str, 0, -2);
            }
        }
        if (!empty($this->rawheaders)) {
            if (!is_array($this->rawheaders))
                $this->rawheaders = (array) $this->rawheaders;
            while (list($headerKey, $headerVal) = each($this->rawheaders))
                $headers[] = $headerKey . ": " . $headerVal;
        }
        if (!empty($content_type)) {
            if ($content_type == "multipart/form-data")
                $headers[] = "Content-type: $content_type; boundary=" . $this->_mime_boundary;
            else
                $headers[] = "Content-type: $content_type";
        }
        if (!empty($body))
            $headers[] = "Content-length: " . strlen($body);
        if (!empty($this->user) || !empty($this->pass))
            $headers[] = "Authorization: BASIC " . base64_encode($this->user . ":" . $this->pass);
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $URI);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->read_timeout);
            if (self::$proxy_host && self::$proxy_port) {
                curl_setopt($ch, CURLOPT_PROXY, self::$proxy_host);
                curl_setopt($ch, CURLOPT_PROXYPORT, self::$proxy_port);
                if (self::$proxy_user && self::$proxy_pass) {
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, "[" . self::$proxy_user . "]:[" . self::$proxy_pass . "]");
                }
            }
            if (!empty($body)) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            $data = curl_exec($ch);
            if ($data === false) {
                $this->error = "Error: Curl error  " . curl_error($ch);
                return false;
            }
            if (self::$proxy_host && self::$proxy_port) {
                $parts = explode("\r\n\r\n", $data, 3);
                $result_headers = explode("\r\n", $parts[1]);
                $results = $parts[2];
            } else {
                $parts = explode("\r\n\r\n", $data, 2);
                $result_headers = explode("\r\n", $parts[0]);
                $results = $parts[1];
            }
            unset($parts);
        } else {
            for ($curr_header = 0; $curr_header < count($headers); $curr_header++) {
                $safer_header = strtr($headers[$curr_header], "\"", " ");
                $cmdline_params .= " -H \"" . $safer_header . "\"";
            }

            if (!empty($body))
                $cmdline_params .= " -d \"$body\"";

            if ($this->read_timeout > 0)
                $cmdline_params .= " -m " . $this->read_timeout;

            $headerfile = tempnam($temp_dir, "sno");

            exec($this->curl_path . " -k -D \"$headerfile\"" . $cmdline_params . " \"" . escapeshellcmd($URI) . "\"", $results, $return);

            if ($return) {
                $this->error = "Error: cURL could not retrieve the document, error $return.";
                return false;
            }


            $results = implode("\r\n", $results);

            $result_headers = file("$headerfile");
        }
        $this->_redirectaddr = false;
        unset($this->headers);

        for ($currentHeader = 0; $currentHeader < count($result_headers); $currentHeader++) {

            // if a header begins with Location: or URI:, set the redirect
            if (preg_match("/^(Location: |URI: )/i", $result_headers[$currentHeader])) {
                // get URL portion of the redirect
                preg_match("/^(Location: |URI:)\s+(.*)/", chop($result_headers[$currentHeader]), $matches);
                // look for :// in the Location header to see if hostname is included
                if (!empty($matches)) {
                    if (!preg_match("|\:\/\/|", $matches[2])) {
                        // no host in the path, so prepend
                        $this->_redirectaddr = $URI_PARTS["scheme"] . "://" . $this->host;
                        // eliminate double slash
                        if (!preg_match("|^/|", $matches[2]))
                            $this->_redirectaddr .= "/" . $matches[2];
                        else
                            $this->_redirectaddr .= $matches[2];
                    } else
                        $this->_redirectaddr = $matches[2];
                }
            }

            if (preg_match("|^HTTP/|", $result_headers[$currentHeader]))
                $this->response_code = $result_headers[$currentHeader];

            $this->headers[] = $result_headers[$currentHeader];
        }

        // check if there is a a redirect meta tag

        if (preg_match("'<meta[\s]*http-equiv[^>]*?content[\s]*=[\s]*[\"\']?\d+;[\s]*URL[\s]*=[\s]*([^\"\']*?)[\"\']?>'i", $results, $match)) {
            $this->_redirectaddr = $this->_expandlinks($match[1], $URI);
        }

        // have we hit our frame depth and is there frame src to fetch?
        if (($this->_framedepth < $this->maxframes) && preg_match_all("'<frame\s+.*src[\s]*=[\'\"]?([^\'\"\>]+)'i", $results, $match)) {
            $this->results[] = $results;
            for ($x = 0; $x < count($match[1]); $x++)
                $this->_frameurls[] = $this->_expandlinks($match[1][$x], $URI_PARTS["scheme"] . "://" . $this->host);
        }
        // have we already fetched framed content?
        elseif (is_array($this->results))
            $this->results[] = $results;
        // no framed content
        else
            $this->results = $results;
        if (isset($headerfile) && file_exists($headerfile))
            unlink($headerfile);
        //保存cookies
        if ($this->passcookies)
            $this->setcookies();
        return true;
    }

    /* ======================================================================*\
      Function:	setcookies()
      Purpose:	set cookies for a redirection
      \*====================================================================== */

    function setcookies() {
        for ($x = 0; $x < count($this->headers); $x++) {
            if (preg_match('/^set-cookie:[\s]+([^=]+)=([^;]+)/i', $this->headers[$x], $match))
                $this->cookies[$match[1]] = urldecode($match[2]);
        }
    }

    /* ======================================================================*\
      Function:	_check_timeout
      Purpose:	checks whether timeout has occurred
      Input:		$fp	file pointer
      \*====================================================================== */

    function _check_timeout($fp) {
        if ($this->read_timeout > 0) {
            $fp_status = socket_get_status($fp);
            if ($fp_status["timed_out"]) {
                $this->timed_out = true;
                return true;
            }
        }
        return false;
    }

    /* ======================================================================*\
      Function:	_connect
      Purpose:	make a socket connection
      Input:		$fp	file pointer
      \*====================================================================== */

    function _connect(&$fp) {
        if (!empty(self::$proxy_host) && !empty(self::$proxy_port)) {
            $this->_isproxy = true;

            $host = self::$proxy_host;
            $port = self::$proxy_port;
        } else {
            $host = $this->host;
            $port = $this->port;
        }

        $this->status = 0;

        if ($fp = fsockopen($host, $port, $errno, $errstr, $this->_fp_timeout)) {
            // socket connection succeeded
            return true;
        } else {
            // socket connection failed
            $this->status = $errno;
            switch ($errno) {
                case -3:
                    $this->error = "socket creation failed (-3)";
                case -4:
                    $this->error = "dns lookup failure (-4)";
                case -5:
                    $this->error = "connection refused or timed out (-5)";
                default:
                    $this->error = "connection failed (" . $errno . ")";
            }
            return false;
        }
    }

    /* ======================================================================*\
      Function:	_disconnect
      Purpose:	disconnect a socket connection
      Input:		$fp	file pointer
      \*====================================================================== */

    function _disconnect($fp) {
        return(fclose($fp));
    }

    /* ======================================================================*\
      Function:	_prepare_post_body
      Purpose:	Prepare post body according to encoding type
      Input:		$formvars  - form variables
      $formfiles - form upload files
      Output:		post body
      \*====================================================================== */

    function _prepare_post_body($formvars, $formfiles) {
        settype($formvars, "array");
        settype($formfiles, "array");
        $postdata = '';

        if (count($formvars) == 0 && count($formfiles) == 0)
            return;
        if (is_string($formvars))
            return $formvars;
        if (count($formvars) == 1)
            return $formvars[0];
        switch ($this->_submit_type) {
            case "application/x-www-form-urlencoded":
                reset($formvars);
                while (list($key, $val) = each($formvars)) {
                    if (is_array($val) || is_object($val)) {
                        while (list($cur_key, $cur_val) = each($val)) {
                            $postdata .= urlencode($key) . "[]=" . urlencode($cur_val) . "&";
                        }
                    } else
                        $postdata .= urlencode($key) . "=" . urlencode($val) . "&";
                }
                break;

            case "multipart/form-data":
                $this->_mime_boundary = "--------" . md5(uniqid(microtime()));

                reset($formvars);
                while (list($key, $val) = each($formvars)) {
                    if (is_array($val) || is_object($val)) {
                        while (list($cur_key, $cur_val) = each($val)) {
                            $postdata .= "--" . $this->_mime_boundary . "\r\n";
                            $postdata .= "Content-Disposition: form-data; name=\"$key\[\]\"\r\n\r\n";
                            $postdata .= "$cur_val\r\n";
                        }
                    } else {
                        $postdata .= "--" . $this->_mime_boundary . "\r\n";
                        $postdata .= "Content-Disposition: form-data; name=\"$key\"\r\n\r\n";
                        $postdata .= "$val\r\n";
                    }
                }

                reset($formfiles);
                while (list($field_name, $file_names) = each($formfiles)) {
                    settype($file_names, "array");
                    while (list(, $file_name) = each($file_names)) {
                        $file_content = file_get_contents($file_name);
                        if (!$file_content)
                            continue;

                        $base_name = basename($file_name);

                        $postdata .= "--" . $this->_mime_boundary . "\r\n";
                        $postdata .= "Content-Disposition: form-data; name=\"$field_name\"; filename=\"$base_name\"\r\nContent-Type: application/octet-stream\r\n\r\n";
                        $postdata .= "$file_content\r\n";
                    }
                }
                $postdata .= "--" . $this->_mime_boundary . "--\r\n";
                break;
        }

        return $postdata;
    }

    function setUserAgent($string) {
        $this->agent = $string;
    }

}
