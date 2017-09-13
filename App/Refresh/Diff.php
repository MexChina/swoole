<?php
/**
 * 比较旧的库和新的库里面的数据的一致性
 * 如果旧库中存在而新库中未有，则将就的数据库中的数据写到新的库中
 * 
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Helper\System;
class Diff extends \Swoole\Core\App\Controller{
    private $old_db;    //目标库
    private $new_db;    //新的主库
    private $page_size = 100;
    //要同步的表
    private $tables=['contacts','resumes','resumes_algorithms','resumes_extras','resumes_flags','resumes_maps','resumes_update','users_contacts','users_resumes'];

    public function init(){}

    public function index(){
        $this->old_db = $this->db("old_icdc_".$this->swoole->worker_id);
        $this->new_db = $this->db("new_icdc_".$this->swoole->worker_id);
        if(empty($this->old_db) || empty($this->new_db)) exit("?_icdc_".$this->swoole->worker_id." no connection");
        foreach($this->tables as $table){
            Log::write_log("start to process $table");
            $this->table($table);
            Log::write_log("end process $table");
        }
    }

    /**
     * 2、 table  根据传入的table进行读写数据
     * @return [type] [description]
     */
    public function table(string $table){
        $result = $this->old_db->query("select count(1) as `total` from $table")->fetch();
        $page_total = ceil($result['total'] / $this->page_size);unset($result);
        Log::write_log("icdc_{$this->swoole->worker_id}.{$table} have {$page_total} .......");


        for($page=1;$page<=$page_total;$page++){
            $start_time = number_format(microtime(true), 8, '.', '');
            $start_memory = memory_get_usage();           
            $resume_ids=[];
            $result = $this->old_db->query("SELECT id FROM `{$table}` WHERE id >= (SELECT id FROM `{$table}` ORDER BY id asc LIMIT " . ($page - 1) * $this->page_size . ", 1) ORDER BY id asc LIMIT $this->page_size")->fetchall();

            foreach ($result as $r) {
                $resume_ids[]=$r['id'];
            }
            
            if (empty($resume_ids)){
                $this->show($table,$page,$page_total);
                continue;
            } 
            $res = $this->diff($resume_ids,$table);
            if(empty($res)){
                $this->show($table,$page,$page_total);
                continue;
            } 
            
            $ids = implode(',',$res);
            $this->data($ids,$table);
            $this->show($table,$page,$page_total);
        }
    
    }

    /**
     * [diff description]
     * @param  array  $id_arr [description]
     * @param  string $table  [description]
     * @return [type]         [description]
     */
    private function diff(array $id_arr,string $table):array
    {
        $ids = implode(',',$id_arr);
        $result = $this->new_db->query("SELECT id FROM `{$table}` WHERE id IN($ids)")->fetchall();
        $new_id_arr=[];
        foreach($result as $row){
            if($row['id']) $new_id_arr[]=$row['id'];
        }
        return array_diff($id_arr,$new_id_arr);
    }

    /**
     * [show description]
     * @param  string $table [description]
     * @return [type]        [description]
     */
    private function show(string $table,string $page,string $page_total)
    {
        $runtime    = number_format(microtime(true), 8, '.', '') - $start_time;
        $str   = "{$runtime}s,";
        $memory_use = number_format((memory_get_usage() - $start_memory) / 1024, 2);
        $str .= "{$memory_use}kb";     
        Log::write_log("icdc_{$this->swoole->worker_id}.{$table},{$page}/{$page_total},$str");
    }

    private function data(string $ids,string $table)
    {
        $result = $this->old_db->query("select * from {$table} where id in($ids)")->fetchall();
        $values = '';
        foreach($result as $r){
            $values .= "(";
            foreach($r as $field=>$v){
                $values .= "'".addslashes($v)."',";
            }
            $values = rtrim($values,',');
            $values .= "),";
        }
        $values = rtrim($values,',');
        $sql = "insert into $table values $values";
        $this->new_db->query($sql);
        error_log(date('Y-m-d H:i:s')."\t".$table."\t".$sql."\n",3,SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/diff_sql.log");
    }
}
