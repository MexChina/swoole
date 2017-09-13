<?php
/**
 * 将就的压缩包重构成新的压缩包
 *
 * 
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;
class ImportCompress extends \Swoole\Core\App\Controller{
    private $db;            //源库
    private $new_db;         //目标库
    private $worker;
    private $db_name;

    public function init(){}

    public function index(){
        $this->db = $this->db("slave_icdc_".$this->swoole->worker_id);

        $result = $this->db->query("select id,compress from resumes_extras",MYSQLI_USE_RESULT);
        while ($row=$result->fetch_assoc()){
            $this->check($row);
        }
        $result->close();

        Log::writelog("icdc_".$this->swoole->worker_id." complete...");
    }

    public function check($row){
        $id = (int)$row['id'];
        if($id <= 0) return;

        if(empty($row['compress'])){
            return;
        }

        $compress = json_decode(gzuncompress($row['compress']), true);
        
        if(!is_array($compress)){
            return;
        }

        $name = isset($compress['basic']['name']) ? $compress['basic']['name'] : '';
        $birth = isset($compress['basic']['birth']) ? $compress['basic']['birth'] : '';
        $corporation_name = isset($compress['basic']['corporation_name']) ? $compress['basic']['corporation_name'] : '';
        $basic_salary = isset($compress['basic']['basic_salary']) ? $compress['basic']['basic_salary'] : '';
        error_log($id."\t".$name."\t".$birth."\t".$corporation_name."\t".$basic_salary."\n",3,"/opt/log/importcompress.result");
    }


 
 
    public function __destruct(){
        $this->db->close();
    }
}