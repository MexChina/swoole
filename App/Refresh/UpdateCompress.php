<?php
/**
 * 将就的压缩包重构成新的压缩包
 *
 * 
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;
class UpdateCompress extends \Swoole\Core\App\Controller{
    private $db;            //源库
    private $new_db;         //目标库
    private $worker;
    private $db_name;

    public function init(){}

    public function index(){
        $this->db = $this->db("slave_icdc_".$this->swoole->worker_id);
        $this->worker = new \GearmanClient();
        $this->worker->addServer("192.168.8.39",4731);

        $result = $this->db->query("select id,compress from resumes_extras where id=11187855",MYSQLI_USE_RESULT);
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

        if(isset($compress['work'])){
            $i=$j1=0;
            $this->uniquess($compress['work'],'corporation_name',$i,$j1);
            if($j1){
                Log::write_log($id." work total:{$i} -{$j1}");
            }
        }

        if(isset($compress['education'])){
            $i=$j2=0;
            $this->uniquess($compress['education'],'school_name',$i,$j2);
            if($j2){
                Log::write_log($id." education total:{$i} -{$j2}");
            }
        }

        if(isset($compress['project'])){
            $i=$j3=0;
            $this->uniquess($compress['project'],'name',$i,$j3);
            if($j3){
                Log::write_log($id." project total:{$i} -{$j3}");
            }
        }

        if($j1 || $j2 || $j3){
            $this->icdc_basic($id,$compress);
        }
    }

    public function icdc_basic($id,$compress){
        $uniq = uniqid('bi_');
        $param=array(
            'header'=>array(
                'product_name'  =>  'BIService',
                'uid'           =>  '9',
                'uname'         =>  'BIServer',
                'provider'      =>  'icdc',
                'ip'            =>  '192.168.8.43',
                'log_id'        =>   $uniq,
                'appid'         =>   999
            ),
            'request'=>array(
                'c' => 'resumes/Logic_resume_extra',
                'm' => 'save',
                'p' => array(
                    'resume_extra'=>array(
                        'id'=>$id,
                        'compress'=>json_encode($compress)
                    )
                )
            )
        );
        $send_data = json_encode($param);
        error_log(date('Y-m-d H:i:s')."\t$uniq\t".$send_data."\n",3,"/opt/log/update_compress_result");
        $return = $this->worker->doNormal("icdc_basic",$send_data,$uniq);
        error_log(date('Y-m-d H:i:s')."\t$uniq\t".$return."\n",3,"/opt/log/update_compress_result");
    }

    /**
     * 工作经历 教育经历  项目经历去重
     * @param  [type] $list     [description]
     * @param  [type] $name_key [description]
     * @return [type]           [description]
     */
    private function uniquess(&$list,$name_key,&$i,&$j){
        $new_data = array();
        foreach($list as $k=>$row){
            if(isset($row['is_deleted']) && $row['is_deleted'] == 'Y') continue;
            $start_time = isset($row['start_time']) ? $this->format_date($row['start_time']) : 0;
            $year = date('Y',$start_time);
            $name = isset($row[$name_key]) ? strtolower($row[$name_key]) : '';
            $i++;
            $str = $year.$name;
            if(in_array($str,$new_data)){
                $list[$k]['is_deleted'] = 'Y';
                $list[$k]['deleted_at'] = date('Y-m-d H:i:s');
                $j++;
            }else{
                $new_data[]=$year.$name;
            }
        }
        unset($new_data);
    }

    public function format_date($date_txt) {
        $format_date = "";
        if ($date_txt) {
            if (preg_match("/(\d{2,4})[^\d]+?(\d{1,2})[^\d]+?(\d{1,2})|(\d{4})(\d{2})(\d{2})|(\d{2,4})[^\d]+?(\d{1,2})|(\d{2,4})/", $date_txt, $match)) {
                $year = $match[1] ? $match[1] : ($match[4] ? $match[4] : ($match[7] ? $match[7] : $match[9]));
                if ($year) {
                    $format_date .= (strlen($year) == 2 ? ( $year <= intval(date("y", time())) ? intval("20" . $year) : intval("19" . $year) ) : intval($year));
                }
                $month = $match[2] ? $match[2] : ($match[5] ? $match[5] : $match[8]);
                if ($month) {
                    $format_date .= "-" . (substr($month, 0, 1) == 0 ? intval(substr($month, 1, 1)) : intval($month));
                } else {
                    $format_date .= "-01";
                }
                $day = $match[3] ? $match[3] : $match[6];
                if ($day) {
                    $format_date .= "-" . (substr($day, 0, 1) == 0 ? intval(substr($day, 1, 1)) : intval($day));
                } else {
                    $format_date .= "-01";
                }
            }
        }
        return strtotime($format_date);
    }
    
 
    public function __destruct(){
        $this->db->close();
    }
}