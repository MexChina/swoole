<?php
/**
 * 处理存放于文件中的简历id，进行某个字段的刷库行为
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Lib\Cache\Mredis;
use Swoole\App\Algorithm\Api;
class TmpAlgorithm extends \Swoole\Core\App\Controller{
	private $db;
	private $page_size=5000;
    private $redis;
    private $api;
    private $field = 'cv_trade';

	public function init(){
        $this->redis = new Mredis(array(
            0 => ["master" => "192.168.1.201:7000",'slot' => '0-5460', 'slave' => '192.168.1.201:7003'],
            1 => ["master" => "192.168.1.201:7001",'slot' => '5461-10922', 'slave' => '192.168.1.201:7004'],
            2 => ["master" => "192.168.1.201:7002",'slot' => '10923-16383', 'slave' => '192.168.1.201:7005'],
        ));

        $this->redis->connect();
        $this->api = new Api;
        $this->db = $this->db("tob_icdc");
    }

	/**
	 * 24个库 主表进程
	 * @return [type] [description]
	 */
	public function index(){
		$result = new \SplFileObject('');
        $i=0;$j=0;
        $ids=[];
        foreach($result as $resume_id){
            $ids[] = (int)$resume_id;
            $i++;$j++;
            if($i==1000 || $j==29){
                $this->dispose($ids);
                $i=0;$ids=[];
            }
        }
        Log::writelog("complete...");
	}


    public function dispose($ids){
        $ids = implode(',',$ids);
        $resume_extras = $this->db->query("select * from resumes_extras where id in($ids)")->fetchall();
        $data=[];
        $sql=[];
        $field = $this->field;
        foreach($resume_extras as $k=>$resume_extra){
            $data = json_decode(gzuncompress($resume_extra['compress']), true);
            $result = $this->api->{$field($data)};
            if(!empty($result)){
                $value = json_encode($result,JSON_UNESCAPED_UNICODE);
                $data[]=[
                    'key'=>$resume_extra['id'],
                    'field'=>$field,
                    'value'=>$value
                ];
                $time=date('Y-m-d H:i:s');
                $sql[$resume_extra['id']]="update resumes_algorithms set $field='$value',updated_at='$time' where id=".$resume_extra['id'];
            }
        }
        if(!empty($data)) $this->redis->hmset($data);
        foreach($sql as $resume_id=>$s){
            $this->db->query($s);
            Log::writelog($resume_id." success....");
        }
    }
}
