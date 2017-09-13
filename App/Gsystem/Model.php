<?php
/**
 * Created by PhpStorm.
 * User: qing
 * Date: 16-12-15
 * Time: 下午9:04
 */
namespace Swoole\App\Gsystem;
abstract class Model extends \Swoole\Core\App\Controller{
    protected $gsystem_db;
    public function init(){
        $this->gsystem_db = $this->db("gsystem");
    }

    public function __construct(){
        $this->init();
    }


    /** 只继承不实例
     * @param $sql string sql语句
     * @param bool $is_select 是否是查询
     * @param bool $is_insert_id 是否需要返回插入id
     * @return mixed 如果是查询返回数组，如果是插入返回插入id，如果是update什么不返回
     * @throws \Exception
     */
    protected function parse_sql($sql,$is_select=true,$is_insert_id=false){
        try{
            if($is_select){
                return $this->gsystem_db->query($sql)->fetchall();
            }else{
                $this->gsystem_db->query($sql);
                if($is_insert_id){
                    return $this->gsystem_db->insert_id();
                }
            }
        }catch (\Exception $e){
            throw $e;
        }
    }
}