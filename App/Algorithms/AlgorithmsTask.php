<?php

namespace Swoole\App\Algorithms;
use Swoole\Core\Log;
class AlgorithmsTask extends \Swoole\Core\App\Controller{
    
    private $master_db;
    private $slave_db;
    
    public function init(){}
    public function index($params){
        unset($params['from_worker_id']);
        
        $this->master_db = $this->db("master_icdc_".$params['db_id']);
        $this->slave_db = $this->db("slave_icdc_".$params['db_id']);
        
        //echo $params['db_id'];
        
    
        
        $this->extra($params['ids']);
    }
    
    /**
     * 处理简历压缩包  2级嵌套
     */
    public function extra($ids){
        $ids = implode(',',$ids);
        $result = $this->slave_db->query("select * from resumes_extras where id in($ids)")->fetchall();
        foreach($result as $row){
            $resume_id = $row['id'];
            $api = new Apis();
            //$cv_trade = $api->cv_trade();
            $compress = json_decode(gzuncompress($row['compress']), true);
            $cv_title = $api->cv_title($resume_id,$compress);
            //echo $resume_id,"\n";
            
            //$cv_trade = $this->cv_title($resume_id,$compress);
        }
    }
    
    public function save($data){
        
    }
    
    
}