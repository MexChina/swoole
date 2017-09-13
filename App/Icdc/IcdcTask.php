<?php

namespace Swoole\App\Icdc;
use Swoole\Core\Log;
class IcdcTask extends \Swoole\Core\App\Controller{

    private $read_db;
    private $write_db;
    private $table;
    private $db_name;
    public function init(){}
    public function index($param){
        $type = (int)$param['type'];
        $this->table = $param['table'];

        if($type == 1){
            $this->db_name = "icdc_".$param['db_num'];
            $this->read_db = $this->db("icdc_".$param['db_num']);
            $this->write_db = $this->db("new_icdc");
            $this->import();
        }elseif($type == 2){
            $this->db_name = "icdc_".$param['db_num'];
            $this->read_db = $this->db("icdc_".$param['db_num']);
            $this->write_db = $this->db("algorithm_".$param['db_num']);
            $this->algorithm();
        }elseif($type == 3){
            $this->read_db = $this->db("icdc_".$param['db_num']);
            $this->write_db = $this->db("icdc_map");
            $this->map_data();
        }else{
            $this->db_name = "icdc_allot";
            $this->read_db = $this->db("icdc_allot");
            $this->write_db = $this->db("new_icdc_allot");
            $this->import();
        }

        
        // $this->read_db->close();
        $this->write_db->close();
    }

    public function map_data(){
        if($this->table == 'resumes_extras'){
            $resource = $this->read_db->query("select * from {$this->table}",MYSQLI_USE_RESULT);
            $values='';
            $i=1;
            while ($row = $resource->fetch_assoc()){

                $compress = addslashes(gzuncompress($row['compress']));

                $values .= "(87,$i,'$compress'),";
                $name = $this->db_name.".".$this->table;

                $i++;
                if($i == 1000 || ($j==$page && $i==$ip)){
                    $values = rtrim($values,',');
                    $this->write_db->query( "insert into tob_maps_data(src,src_no,src_data) values $values");
                    Log::writelog("{$name} {$j}/{$page} {$i} write success...");
                    break;
                    //$i=0;$values='';
                    //$j++;
                }
            }
        }
    }

    public function test(){
        $this->write_db->query("TRUNCATE TABLE resumes_algorithms");
        $data = '{"diff": "[]", "cv_tag": "", "cv_title": "", "cv_trade": [{"work_id": "1415861103", "company_id": 726123, "company_info": {"region": ["上海"], "keyword": ["丝芙兰", "sephora", "丝芙兰（上海）化妆品销售有限公司"], "internal_id": 726123, "company_type": ["化妆品", "销售"], "internal_name": "丝芙兰（上海）化妆品销售有限公司"}, "company_name": "丝芙兰（上海）化妆品销售有限公司", "corp_cluster": "white", "norm_corp_id": 85, "first_trade_list": [20], "second_trade_list": [], "norm_parent_corp_id": 196}], "cv_degree": "99", "cv_entity": "", "cv_source": [{"src": "98", "src_no": "4", "show_src": "99", "show_src_no": "4"}], "skill_tag": "", "cv_feature": "", "cv_quality": "", "cv_language": "", "cv_education": "", "personal_tag": {"ts": 1423190505, "tag": ["196"]}}';
        $values = '';
        $j = 0;
        for($i=1;$i<10000000000;$i++){
            $values .= "($i,'$data'),";
            if($j== 1000){
                $values = rtrim($values,',');
                $page = $i/100000000;
                $this->write_db->query("insert into resumes_algorithms(`id`,`data`) values $values");
                Log::writelog("algorithm_6.resumes_algorithms {$page}% write success...");
                $values='';$j=0;
            }
            $j++;
        }
    }

    public function algorithm(){
        $json_feild = array('cv_source','cv_trade','cv_title','cv_tag','cv_entity','cv_education','cv_feature','skill_tag','personal_tag','cv_language');
 
        
        // $this->write_db->query("TRUNCATE TABLE {$this->table}");
        $result = $this->read_db->query("select count(1) as `total` from {$this->table}")->fetch();
        $str = "$table have ".$result['total']." to transport";
        $page = ceil($result['total']/1000);

        $ip = $result['total'] - ($page-1)*1000;    //求最后一页的余数
        $str .= " page $page";
        $resource = $this->read_db->query("select * from {$this->table}",MYSQLI_USE_RESULT);

        $i=0;$j=1;$t=0;
        $values='';
        $field = 'id,data';
        while ($row = $resource->fetch_assoc()){
            $value=array();

            $value['cv_source'] = json_decode($row['cv_source'],true) == null ? '' : json_decode($row['cv_source'],true);
            $value['cv_trade'] = json_decode($row['cv_trade'],true) == null ? '' : json_decode($row['cv_trade'],true);
            $value['cv_title'] = json_decode($row['cv_title'],true) == null ? '' : json_decode($row['cv_title'],true);
            $value['cv_tag'] = json_decode($row['cv_tag'],true) == null ? '' : json_decode($row['cv_tag'],true);
            $value['cv_entity'] = json_decode($row['cv_entity'],true) == null ? '' : json_decode($row['cv_entity'],true);
            $value['cv_education'] = json_decode($row['cv_education'],true) == null ? '' : json_decode($row['cv_education'],true);
            $value['cv_feature'] = json_decode($row['cv_feature'],true) == null ? '' : json_decode($row['cv_feature'],true);
            $value['skill_tag'] = json_decode($row['skill_tag'],true) == null ? '' : json_decode($row['skill_tag'],true);
            $value['personal_tag'] = json_decode($row['personal_tag'],true) == null ? '' : json_decode($row['personal_tag'],true);
            $value['cv_language'] = json_decode($row['cv_language'],true) == null ? '' : json_decode($row['cv_language'],true);
            $value['cv_degree'] = $row['cv_degree'];
            $value['diff'] = $row['diff'];
            $value['cv_quality'] = $row['cv_quality'];

            $values .= '('.$row['id'].",'".addslashes(json_encode($value,JSON_UNESCAPED_UNICODE))."'),";
            
           
            $i++;
            if($i == 1000 || ($j==$page && $i==$ip)){
                $values = rtrim($values,',');
                $this->write_db->query( "insert into resumes_algorithms(`id`,`data`) values $values");
                Log::writelog("{$name} {$j}/{$page} {$i} write success...");
                $i=0;$values='';
                $j++;
            }
            $t++;
        }
        $resource->free();
        $str .= ' complete total:'.$t;
        Log::writelog($str);
    }



    /**
     * 从一张表复制数据导入到另一张表
     */
    public function import(){
        $json_feild = array('cv_source','cv_trade','cv_title','cv_tag','cv_entity','cv_education','cv_feature','skill_tag','personal_tag','cv_language');
        $time_field = array('updated_at','created_at','resume_updated_at','refresh_at','scheduled_at','executed_at','finished_at');
        $yn_field = array('has_phone','is_deleted');
        $ynu_field = array('is_validate','is_increased');
        
        Log::writelog("start {$this->table} ...");

        // $this->write_db->query("TRUNCATE TABLE {$this->table}");
        $result = $this->read_db->query("select count(1) as `total` from {$this->table}")->fetch();
        $str = "{$this->db_name}.{$this->table} have ".$result['total']." to transport";
        Log::writelog($str);
        $page = ceil($result['total']/1000);

        $ip = $result['total'] - ($page-1)*1000;    //求最后一页的余数
        $str .= " page $page";
        $resource = $this->read_db->query("select * from {$this->table}",MYSQLI_USE_RESULT);

        $i=0;$j=1;$t=0;
        $values='';
        $field = '';$is_field=true;
        while ($row = $resource->fetch_assoc()){
            $value='';
            $algorithm=array();
            foreach($row as $k=>$r){
                if($is_field) $field .= "`$k`,";
                //处理时间字段
                if(in_array($k,$time_field)){
                    if($r == '0000-00-00 00:00:00'){
                        $value .= "'".date('Y-m-d H:i:s')."',";
                    }else{
                        $date=date_create($r);
                        $value .= "'".date_format($date,"Y-m-d H:i:s")."',";
                    }
                //处理YN枚举字段    
                }elseif(in_array($k,$yn_field)){
                    $value .= $r == 'Y' ? "'Y'," : "'N',";
                //处理YNU枚举字段
                }elseif(in_array($k,$ynu_field)){
                    $value .=  $r == 'Y' ? "'Y'," : $r == 'N' ? "'N'," : "'U',";
                //处理个别表个别人恶意添加的字段
                }elseif($this->table == 'resumes_extras'){

                    if($k == 'id'){
                        $value .= (int)$r.",";
                    }elseif($k == "compress"){
                        $value .= "'".addslashes($r)."',";
                    }else{
                        echo "no field : ",$k;
                    }
                //处理json字段                    
                // }elseif($this->table == 'resumes_algorithms'){
                //     if($k == 'id' || $k == 'cv_degree') $value .= (int)$r.",";
                //     if(in_array($k,$json_feild)){
                //         if(empty($r)){
                //             $value .= "null,";
                //         }else{
                //             $arr = json_decode($r,true);
                //             if(empty($arr)){
                //                 $value .= "null,";
                //             }else{
                //                 $value .= json_encode($r,JSON_UNESCAPED_UNICODE).",";
                //             }
                //         }
                //     }
                //     if($k == 'diff' || $k == 'cv_quality'){
                //         $value .= "'{$r}',";
                //     }
                }else{
                    $value .= "'".addslashes($r)."',";
                }                
            }
            if($is_field){
                $field = rtrim($field,',');
                $field = "($field)";
                $is_field = false;
            } 
            


            $value = rtrim($value,',');
            $values .= "($value),";
            $name = $this->db_name.".".$this->table;
            // error_log("insert into $name $field values $values",3,"/opt/wwwroot/swoole/sql.sql");
            // exit;
            $i++;
            if($i == 1000 || ($j==$page && $i==$ip)){
                $values = rtrim($values,',');
                $this->write_db->query( "insert into $name $field values $values");
                Log::writelog("{$name} {$j}/{$page} {$i} write success...");
                $i=0;$values='';
                $j++;
            }
            $t++;
        }
        $resource->free();
        $str .= ' complete total:'.$t;
        Log::writelog($str);
    }
}