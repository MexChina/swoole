<?php
/**
 * Created by PhpStorm.
 * User: ifchangebisjq
 * Date: 2017/5/16
 * Time: 10:25
 */

namespace Swoole\App\Refresh;
use Swoole\Core\Lib\Excel;
use Swoole\Core\Log;
use Swoole\Core\Helper\System;
class RefreshCorporations extends \Swoole\Core\App\Controller{
    private $industry_list = [
        516 => '教育培训',
        535 => '学术科研'
    ];
    private $filename;
    private $insert_num = 0;
    private $update_num = 0; // 更新公司
    private $update_no_num = 0; // 更新但未更新公司数据
    private $no_num     = 0;
    private $total_num  = 0;

    public function init(){
        $this->db = $this->db("gsystem");
        $this->filename = SWOOLE_ROOT_DIR . 'shell/school0515.xlsx';
    }

    public function index(){
        $this->init();
        $excel = new Excel();
        $excel_data = $excel->getRows($this->filename);
        Log::write_log("refresh corporations start");

        System::exec_time();

        $this->total_num = count($excel_data);
        foreach($excel_data as $val){
            $name = $val[0]; // 公司名或大学名
            if(empty($name)) {
                Log::write_log('名称无，不处理');
                $this->no_num++;
                continue;
            }

            $corporation_detail = $this->db->query("select `id`,`status`,`is_deleted` from corporations where name = '$name'")->fetch();
            Log::write_log("corporation detail: " . json_encode($corporation_detail));
            $time = date('Y-m-d H:i:s', time());
            if($corporation_detail['id'] < 1){
                // 新增
                $this->db->query("insert into corporations (`name`,`uid`,`status`,`updated_at`) values ('$name',1,1,'$time')");
                $corporation_id = $this->db->insert_id();
                $this->insert_num++;
                Log::write_log("insert corporation : $corporation_id");
            }else{
                $corporation_id = $corporation_detail['id'];
                // 更新 如果status不为1，或者is_deleted不为N 则更新
                if($corporation_detail['status'] != 1 || $corporation_detail['is_deleted'] != 'N'){
                    $this->db->query("update corporations set `status`=1,`is_deleted`='N' where id=$corporation_id");
                    $this->update_num++;
                    Log::write_log("update corporation : $corporation_id");
                }else{
                    $this->update_no_num++;
                }
            }

            // 软删除公司行业数据
            $this->db->query("update corporations_industries set `is_deleted`='Y' where corporation_id=$corporation_id");

            Log::write_log("delete corporation industries : $corporation_id");

            // 新增公司行业数据
            $values = '';
            foreach ($this->industry_list as $key=>$val){
                $values .= "(1,$corporation_id,$key,'$val','$time'),";
            }
            $values = trim($values,',');
            $industry_sql = "insert into corporations_industries (`type_id`,`corporation_id`,`industry_id`,`industry_name`,`updated_at`) values $values";
            $this->db->query($industry_sql);
            Log::write_log("insert corporation industries : $corporation_id");
        }

        Log::write_log("总计：{$this->total_num}；新增：{$this->insert_num}；更新：{$this->update_num}；未更新：{$this->update_no_num}；名称无：{$this->no_num}");
        Log::write_log("refresh corporations 完成，用时： " . System::exec_time() . " ms, 内存使用： " . System::get_used_memory());
    }
}
