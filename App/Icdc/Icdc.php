<?php
/**
 * Created by PhpStorm.
 * User: dongqing.shi
 * Date: 2016/10/11 0011
 * Time: 上午 11:39
 */
namespace Swoole\App\Icdc;
use Swoole\Core\Log;
class Icdc extends \Swoole\Core\App\Controller{

    private $icdc_int = array('attachments','contacts','resumes','resumes_flags','resumes_maps','resumes_update','users_contacts','users_resumes','resumes_algorithms','resumes_extras');

    private $icdc_allot = array('algorithm_jobs','allots_attachments','allots_contacts','allots_corporations','allots_resumes','allots_resumes_maps','records_maps','sim_pairs','sources','sources_maps');

    public function init(){
        // TODO: Implement init() method.
    }

    //work_id   0-23    
    public function index(){
        $db_num = $this->swoole->worker_id;
        
        //for($type=0;$type<1;$type++){
            //$tables = $type == 1 ? $this->icdc_int : $this->icdc_allot;
            $tables = $this->icdc_int;
            foreach($tables as $table){
                Log::writelog($table." 已被分发");
                $this->task(array('db_num'=>$this->swoole->worker_id,'table'=>$table,'type'=>3));
            }
        //}
    }

}