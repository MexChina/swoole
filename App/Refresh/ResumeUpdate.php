<?php
/**
 * Created by PhpStorm.
 * User: qing
 * Date: 16-12-27
 * Time: 下午4:30
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;
class ResumeUpdate extends \Swoole\Core\App\Controller{

    public function init(){
        // TODO: Implement init() method.
    }


    public function index(){

        $master_db = $this->db("master_icdc_".$this->swoole->worker_id);
        $slave_db = $this->db("slave_icdc_".$this->swoole->worker_id);
        $result = $slave_db->query("select count(1) as `total` from resumes")->fetch();
        $page_total = ceil($result['total']/1000);

        for($page=1;$page<=$page_total;$page++){

            $result = $slave_db->query("SELECT id,updated_at FROM `resumes` WHERE id >= (SELECT id FROM `resumes` where is_deleted='N' ORDER BY id asc LIMIT " . ($page - 1) * 1000 . ", 1) and is_deleted='N' ORDER BY id asc LIMIT 1000")->fetchall();
            if(empty($result)) continue;
            $ids=[];$ids2=[];
            $resumes=[];
            foreach($result as $row){
                $ids[]=$row['id'];
                $resumes[$row['id']] = $row['updated_at'];
            }

            $ids_str = implode(',',$ids);
            $result2 = $slave_db->query("select id from resumes_update where id in ($ids_str)")->fetchall();
            foreach($result2 as $r){
                $ids2[]=$r['id'];
            }

            $new_ids = array_diff($ids,$ids2);
            $values = '';
            foreach ($new_ids as $id){
                $values .= "('$id','".$resumes[$id]."'),";
            }
            $values = trim($values,',');
            if($values) $master_db->query("insert into resumes_update(`id`,`updated_at`) values $values");
            Log::write_log("page:$page ids_count:".count($new_ids));
            unset($result,$ids,$ids2,$new_ids,$resumes,$values);
        }
    }
}