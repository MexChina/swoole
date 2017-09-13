<?php
/**
 * 刷库脚本
 * cv_education 已经开发好
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Lib\Worker;
use Swoole\App\Algorithm\Api;
use \RedisCluster;
class AlgorithmCount extends \Swoole\Core\App\Controller{
    private $db;        //全量读取数据的db
    private $page_size = 1000;
    
    public function init(){
        // $this->db = $this->db("new_icdc_".$this->swoole->worker_id);
        $this->db = $this->db("icdc_".$this->swoole->worker_id);
        // $this->db2 = $this->db("icdc_".$this->swoole->worker_id);
        $this->redis = new RedisCluster(NULL,array(
            '192.168.8.116:7105', '192.168.8.115:7205',
            '192.168.8.115:7105', '192.168.8.116:7205',
            '192.168.8.114:7105', '192.168.8.113:7205',
            '192.168.8.113:7105', '192.168.8.114:7205',
            '192.168.8.116:7106', '192.168.8.115:7206',
            '192.168.8.115:7106', '192.168.8.116:7206',
            '192.168.8.114:7106', '192.168.8.113:7206',
            '192.168.8.113:7106', '192.168.8.114:7206',
            '192.168.8.116:7107', '192.168.8.115:7207',
            '192.168.8.115:7107', '192.168.8.116:7207',
            '192.168.8.114:7107', '192.168.8.113:7207',
            '192.168.8.113:7107', '192.168.8.114:7207',
            '192.168.8.116:7108', '192.168.8.115:7208',
            '192.168.8.115:7108', '192.168.8.116:7208',
            '192.168.8.114:7108', '192.168.8.113:7208',
            '192.168.8.113:7108', '192.168.8.114:7208'
        ));
        $this->api = new Api();
    }


    public function index(){
        $this->init();

        $allow_key = array(
            'cv_source',    //简历的来源
            'cv_trade',     //公司的识别
            'cv_title',     //职级
            'cv_tag',       //职能(标签)
            'cv_entity',    //职能技能标签
            'cv_education', //学校和专业
            'cv_feature',   //特征
            'skill_tag',    //技能(标签)
            'personal_tag', //算法团队用的标签
            'diff',         //diff标识字段
            'cv_quality',   //简历质量
            'cv_language',  //语言识别
            'cv_degree',    //学历识别
            'cv_resign'     //离职意愿度
        );
        $field = 'id,';
        foreach($allow_key as $s){
            $field .= "column_get(data,'".$s."' as char) as ".$s.",";
        }
        $field = rtrim($field,',');

        $result = $this->db->query("select $field from algorithms",MYSQLI_USE_RESULT);
        while ($row=$result->fetch_assoc()){
             if(!empty($row)) $this->rediss($row);   //将数据库中的数据同步到redis
            // if(!empty($row)) $this->check($row);    //验证数据库中的数据和redis中的数据的差异
            //if(!empty($row)) $this->trans($row);    //验证数据库中的数据和redis中的数据的差异
        }
        $result->close();
        Log::write_log("icdc_{$this->swoole->worker_id} 刷库完成");
    }

    /**
     * 将算法数据全部和redis同步一遍
     * @param  [type] $algorithm [description]
     * @return [type]            [description]
     */
    private function rediss($algorithm){
        $key = (string)$algorithm['id'];
        unset($algorithm['id']);
        $algorithm['updated_at'] = date('Y-m-d H:i:s');
        $res = $this->redis->hMset($key,$algorithm);
        if($res === true){
            Log::write_log("$key success...");
        }else{
            Log::write_log("$key failed...");
        }
    }

    /**
     * 验证db中的在redis中是否存在
     * @param  [type] $algorithm [description]
     * @return [type]            [description]
     */
    private function check($algorithm){
        $key = $algorithm['id'];
        unset($algorithm['id']);
        $redis_data = $this->redis->hmgetall([$key]);
        foreach($algorithm as $field=>$value){
            if(!empty($value)){
                if(empty($redis_data[0][$field])){
                    Log::writelog($key."\t".$field." empty");
                }
                if(md5($value) !== md5($redis_data[0][$field])){
                    Log::writelog($key."\t".$field." diff");
                }
            }
        }
    }

    /**
     * 将旧的算法表数据传输到新的算法表中
     * @param  [type] $algorithm [description]
     * @return [type]            [description]
     */
    private function trans($algorithm){
        $id = $algorithm['id'];
        $u_time = $algorithm['updated_at'];
        $c_time = $algorithm['created_at'];
        unset($algorithm['id'],$algorithm['updated_at'],$algorithm['created_at']);

        $field = '';
        foreach($algorithm as $k=>$v){
            $field .= "'".$k."','".addslashes($v)."',";
        }
        $field = rtrim($field,',');
        if(empty($field)) return;
        Log::writelog($id."\tsuccess");
        $this->db2->query("insert into algorithms values($id,COLUMN_CREATE($field),'$u_time','$c_time')");
    }




  



    public function __destruct(){
        $this->db->close();
        // $this->db2->close();
    }
}
