<?php

namespace Swoole\App\ApiHbase;
use Swoole\Core\Lib\Hbase;
use Swoole\Core\Lib\Worker;
class ApiHbase extends \Swoole\Core\App\Controller{

    private $hbase;
    private $culumn_family = 'info:';
    public function init(){
        $this->hbase = new Hbase("221.228.230.200",19090);
    }

    public function index(){
        /**
         * p =>  [  resume_id=>array(),field=>'cv_entity',table=>'resume_label' ]
         * field   | *  全部字段    |  cv_entity   一个字段  |  cv_entity,cv_entity,....   多个字段用 “,” 隔开
         */
        $work = new Worker('api_hadoop');
        $work->worker(function($result){
            if(!is_array($result)) return array('err_no'=>1,'err_msg'=>'参数错误','results'=>array());
            $result = is_array($result['p']) ? $result['p'] : '';
            $table = $result['table'];
            if(empty($table)) return array('err_no'=>1,'err_msg'=>'查询hadoop的表明不能为空!','results'=>array());
            $field = $result['field'];
            if(empty($field)) return array('err_no'=>1,'err_msg'=>'查询字段格式错误','results'=>array());
            if(!is_array($result['resume_id'])) return array('err_no'=>1,'err_msg'=>'查询简历id格式错误','results'=>array());
            $result_data = array();
            $resume_ids = array_unique($result['resume_id']);   //简历ID将作为返回数据的key  避免重复
            foreach($resume_ids as $r){
                $resume_id = (int)$r;
                if($resume_id == 0) continue;
                $startrow = str_pad($resume_id, 10, "0", STR_PAD_LEFT);
                $result_data[$resume_id] = $this->select($table,$startrow,$field);
            }
            return array('results'=>$result_data);
        });
    }

    /**
     * 执行hadoop查询请求
     * @param $table
     * @param $startrow
     * @param $column
     * @return mixed
     */
    private function select($table,$startrow,$column){
        $method_name = strtolower($table);
        if($column == "*" || $method_name == 'resume_field_data'){
            $result = $this->hbase->search_table($table,$startrow);
        }else{
            $columns = explode(',',$column);
            foreach($columns as $k=>$c){
                $columns[$k] = $this->culumn_family.$c;
            }
            $result = $this->hbase->search_table($table,$startrow,null,null,$columns);
        }
        return $this->$method_name($result,$column);
    }

    /**
     * resume_label 表提取查询结果
     * @param $result  object
     * @return array
     */
    private function resume_label($result,$column=''){
        $return_data = array();
        foreach($result->columns as $column_name=>$column_obj){
            $key = str_replace($this->culumn_family,'',$column_name);
            $return_data[$key]=$this->pear_json($column_obj->value,array());
        }
        return $return_data;
    }

    /**resume_field_data 表
     * @param $result   object 查询结果集
     * @param $column   string 要返回的字段
     * @return array
     */
    private function resume_field_data($result,$column){
        $return_data = array();

        $flag = strpos($column,'#');

        if($column != '*'){
            $column = explode(',',$column);
        }

        $new_column = array();
        foreach($column as $c){
            $c_arr = explode("#",$c);   //0==>work   1==>field
            if(isset($new_column[$c_arr[0]])){
                array_push($new_column[$c_arr[0]],$c_arr[1]);
            }else{
                $new_column[$c_arr[0]][]=$c_arr[1];
            }
        }


        foreach($result->columns as $k=>$c){
            $field = str_replace($this->culumn_family,"", $k);
            $field_arr = explode("#",$field);

            if(is_array($column)){
                if(in_array($field_arr[0],$column)){
                    if(isset($field_arr[2])){
                        $return_data[$field_arr[0]][$field_arr[1]][$field_arr[2]]= $this->pear_json($c->value);
                    }else{
                        $return_data[$field_arr[0]][$field_arr[1]]=$this->pear_json($c->value);
                    }
                }elseif($flag !== false){
                    //field_arr  0-work  1-0  2-field
                    if(isset($field_arr[2])){
                        if(in_array($field_arr[2],$new_column[$field_arr[0]])){
                            $return_data[$field_arr[0]][$field_arr[1]][$field_arr[2]]= $this->pear_json($c->value);
                        }
                    }else{
                        if(in_array($field_arr[1],$new_column[$field_arr[0]])){
                            $return_data[$field_arr[0]][$field_arr[1]]=$this->pear_json($c->value);
                        }
                    }
                }
            }else{
                if(isset($field_arr[2])){
                    $return_data[$field_arr[0]][$field_arr[1]][$field_arr[2]]= $this->pear_json($c->value);
                }else{
                    $return_data[$field_arr[0]][$field_arr[1]]=$this->pear_json($c->value);
                }
            }
        }
        return $return_data;
    }

    private function pear_json($str,$return=null){
        $json_result = json_decode($str,true);
        if(is_array($json_result)){
            return $json_result;
        }else{
            return $return === null ? $str : $return;
        }
    }

}