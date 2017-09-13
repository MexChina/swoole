<?php
/**
 * Created by PhpStorm.
 * User: qing
 * Date: 16-12-26
 * Time: 上午11:03
 */
namespace Swoole\App\IcdcQueue;
use Swoole\Core\App\Controller;
use Swoole\Core\Lib\Worker;

class IcdcQueue extends Controller{
    private $db;
    private $woker;
    public function init(){
        $this->db = $this->db("allot_dev");
        $this->woker = new Worker('icdc_refresh');
    }

    public function index(){
        while (1){
            $result = $this->db->query("select resume_id  from algorithm_jobs order by created_at asc limit 100")->fetchall();
            if(empty($result)) sleep(10);
            foreach($result as $r){
                $r['client'] = $this->woker;
                $this->task($r);
            }
        }
    }
}