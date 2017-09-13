<?php

namespace Swoole\Core\Helper;

class Tool {
    /*
      从discuz获取标签
     */

    public static function get_tag_from_dz($subject, $message) {
        $charset = "utf-8";
        $subjectenc = rawurlencode(strip_tags($subject));
        $messageenc = rawurlencode(strip_tags(preg_replace("/\[.+?\]/U", '', $message)));

        $data = @implode('', file("http://keyword.discuz.com/related_kw.html?title=$subjectenc&content=$messageenc&ics={$charset}&ocs={$charset}"));

        if ($data) {
            $parser = xml_parser_create();
            xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
            xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
            xml_parse_into_struct($parser, $data, $values, $index);
            xml_parser_free($parser);

            $kws = array();

            foreach ($values as $valuearray) {
                if ($valuearray['tag'] == 'kw' || $valuearray['tag'] == 'ekw') {
                    if (PHP_VERSION > '5' && $charset != 'utf-8') {
                        $kws[] = iconv(trim($valuearray['value']), $charset, 'utf-8');
                    } else {
                        $kws[] = trim($valuearray['value']);
                    }
                }
            }

            $return = '';
            if ($kws) {
                foreach ($kws as $kw) {
                    $kw = htmlspecialchars($kw);
                    $return .= $kw . ' ';
                }
                $return = trim($return);
            }
            return $return;
        }
    }

    /*
      进行中文分词
     */

    public static function Scws($string) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_URL, "http://www.ftphp.com/scws/api.php");
        curl_setopt($ch, CURLOPT_POSTFIELDS, "data={$string}&respond=json");

        ob_start();
        curl_exec($ch);
        $content = ob_get_contents();
        curl_close($ch);
        ob_clean();
        $content = json_decode($content, true);
        return $content;
    }

    /**
     * 向URL发送请求
     * @param String $url | 请求的地址URL
     * @param mixed $data | 请求传参
     * @param int $http_header 额外的HTTP头设置
     * @return mixed
     */
    public static function send_curl($url = null, $method = 'GET', $data = [] , $http_header = [])
    {
        if (empty($url)) return ['status' => 5, 'data' => [], 'msg' => 'url为空'];

        //初始化curl
        $ch = curl_init();

        //设置参数
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if (!empty($http_header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        //post数据
        if($method == 'POST'){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 100); //设置超时时间
        $response = curl_exec($ch); //接收返回信息

        if (curl_errno($ch)) {//出错则显示错误信息
            return curl_error($ch);
        }

        curl_close($ch); //关闭curl链接

        return json_decode($response, true); //显示返回信息
    }

}
