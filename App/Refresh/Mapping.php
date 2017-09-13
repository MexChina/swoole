<?php
/**
 * 作用  全库读数据，从里面筛选出src=89 并且没有被删除的   然后将简历id推送到hunter接口去。
 *
 * 
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Lib\Worker;
class Mapping extends \Swoole\Core\App\Controller{
    private $db;
    private $hunter;
    private $page_size=10;

	public function init(){
		$this->db = $this->db("icdc_map");
        $this->save_db = $this->db("icdc_map");
        $this->api = new Worker("cv_uniq_server_online");
	}

	public function index(){
        $this->init();

        //统计有多少条
        $start_time = number_format(microtime(true), 8, '.', '');
        $result = $this->db->query("select count(1) as `total` from tob_maps_data")->fetch();
        $page_total = ceil($result['total'] / $this->page_size);
		$runtime    = number_format(microtime(true), 8, '.', '') - $start_time;
        Log::write_log("icdc_{$this->swoole->worker_id} have {$page_total} to refresh used:$runtime");

     	//进行分页处理
        for($page=1;$page<=$page_total;$page++){
	        
	        $start_out_time = $start_time = number_format(microtime(true), 8, '.', '');
            $result = $this->db->query("select * from tob_maps_data",MYSQLI_USE_RESULT);
           
            while ($row=$result->fetch_assoc()){

                $src_no = (int)$row['src_no'];
                $src = (int)$row['src'];
                $cv_id = $this->algorithm_query($row['src_data']) ;
                $where = "src='$src' and src_no='$src_no'";

                //如果没有拿到，则下次再继续
                if(empty($cv_id)){
                    $this->save_db->query("update `tob_maps_data` set `counter` = `counter`+1 where $where");
                }else{
                    //先建立mapping
                    try{
                        $this->mapping_save(array('src'=>$src,'src_no'=>$src_no,'cv_id'=>$cv_id));
                    }catch(Exception $e){
                        Log::writelog("mapping faild...");
                    }
                    if($src == 89) $this->send_hunter(array($src_no=>$cv_id));      //猎头的需要回调通知
                    //再从队列中删除
                    $this->db->query("delete from `tob_maps_data` where $where");
                }

            }

            foreach ($result as $row){

                $src_no = (int)$row['src_no'];
                $src = (int)$row['src'];
                $cv_id = $this->algorithm_query($row['src_data']) ;
                $where = "src='$src' and src_no='$src_no'";

                //如果没有拿到，则下次再继续
                if(empty($cv_id)){
                    $this->save_db->query("update `tob_maps_data` set `counter` = `counter`+1 where $where");
                }else{
                    //先建立mapping
                    try{
                        $this->mapping_save(array('src'=>$src,'src_no'=>$src_no,'cv_id'=>$cv_id));
                    }catch(Exception $e){
                        Log::writelog("mapping faild...");
                    }
                    if($src == 89) $this->send_hunter(array($src_no=>$cv_id));      //猎头的需要回调通知
                    //再从队列中删除
                    $this->db->query("delete from `tob_maps_data` where $where");
                }


                $this->hunter->client(array(
		            'c'=>'apis/logic_resume_api',
		            'm'=>'resume_update_notice',
		            'p'=>array(
		            	'icdc_id'=>$r['resume_id'],
		            	'toh_id'=>$r['src_no']
		            )
		        ),false,false,true);
            }
 
	        $runtime    = number_format(microtime(true), 8, '.', '') - $start_out_time;
            Log::write_log("icdc_{$this->swoole->worker_id},{$page}/{$page_total} used:$runtime");
        }

        Log::write_log("icdc_{$this->swoole->worker_id} 刷库完成");
    }

    public function mapping_save(array $param):array
    {

        $src = (int)$param['src'];
        $cv_id = (int)$param['cv_id'];
        $src_no = (int)$param['src_no'];
        
        if($src_no > 0){
            $delete_key[] = $tox = $src.$src_no;
            $delete_key[] = $tob = '1'.$src.$cv_id;
            $this->save_db->query("replace into `tob_maps`(`2x`,`2b`) values($tox,$tob)");
        }else{
            $delete_key[] = $key = $src.abs($src_no);

            $res = $this->save_db->query("select `2b` from `tob_maps` where `2x`='$key'")->fetch();
            $delete_key[] = $res['2b'];
            $this->save_db->query("delete from `tob_maps` where `2x`='$key' limit 1");
        }
        $this->cache->deleteMulti($delete_key);
        return 'success';
    }

    private function algorithm_query($param):int
    {
        $res = $this->api->client(array(
            'c'=>'CVUniq',
            'm'=>'query',
            'p'=>array('-1'=>$param)
        ));
        Log::write_log(json_encode($res));
        $resume_id = 0;
        foreach ($res as $row) {
            $resume_id = empty($row[0]) ? 0 : $row[0]; break;
        }
        return $resume_id;
    }
}