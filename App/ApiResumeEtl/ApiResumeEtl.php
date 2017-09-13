<?php
namespace Swoole\App\ApiResumeEtl;
use Swoole\Core\Lib\Gearman;
use Swoole\Core\Log;
use Swoole\Core\Helper\System;
use Swoole\Core\Helper\Strings;
class ApiResumeEtl extends \Swoole\Core\App\Controller{


    private $resume_data;

    private $salary_increase_ratio = 0.16; //默认年薪资涨幅
    private $avg_salary = 5600; //平均薪资
    private $birth; //生日共用部分
    private $salary;//薪资共用部分
    public function init(){}

    public function index(){
        $worker = new Gearman("192.168.1.247",4730,"api_resume_etl");
        $worker->worker(function($result){
            System::exec_time();
            $this->resume_data = $result;
            Log::write_info("request:".serialize($result),'resume');
            $return = array('basic'=>'','work'=>'','eduction'=>'','language'=>'','function'=>'','industry'=>'');
            if(empty($result) || !is_array($result)) return $return;
            if(isset($result['eduction'])) $return['eduction'] = $this->table_education();
            if(isset($result['basic'])) $return['basic'] = $this->table_common();
            if(isset($result['work'])) $return['work'] = $this->table_work();

            if(isset($result['language'])) $return['language'] = $this->table_language();
            if(isset($result['function'])) $return['function'] = $this->table_function();
            if(isset($result['industry'])) $return['industry'] = $this->table_industry();
            Log::write_info("response:".serialize($return),'resume');
            $this->resume_data = null;
            Log::write_log("查询完成，用时： " . System::exec_time() . " ms, 内存使用： " . System::get_used_memory());
            return array('results'=>$return);
        });
    }

    /**基本信息  base_common 表字段关系映射
     * @return bool
     */
    private function table_common(){
        $basic = $this->resume_data['basic'];
        $birth = $this->parser_birth($basic['birth']);
        $salary = $this->parser_salary($basic);
        $data['resume_id'] = $this->resume_data['resume_id'];                                                           //简历ID bigint
        $data['name'] = Strings::filter($basic['name']);                                                                //姓名    varchar
        $data['gender']=$basic['gender'] == 'M' ? 0 : 1;                                                                //性别(0:男，1:女）   tinyint
        $data['age']=$birth['age'];                                                                                     //年龄    tinyint
        $data['byear']=$birth['byear'];                                                                                 //出生年   mediumint
        $data['bmonth']=$birth['bmonth'];                                                                               //出生月   tinyint
        $data['marital']=isset($basic['marital']) ? $this->parser_ynu($basic['marital']) : 0;                           //是否已婚  tinyint
        $data['is_fertility']=isset($basic['is_fertility']) ? $this->parser_ynu($basic['is_fertility']) : 0;            //是否已育  tinyint
        $data['is_house']=isset($basic['is_house']) ? $this->parser_ynu($basic['is_house']) : 0;                        //居住地是否有房   tinyint
        $data['account_province']=isset($basic['account_province']) ? (int)$basic['account_province'] : 0;              //户籍所在省id   tinyint
        $data['account']=isset($basic['account']) ? (int)$basic['account'] : 0;                                         //户籍所在地id   mediumint
        $data['address_province']=isset($basic['address_province']) ? (int)$basic['address_province'] : 0;              //现居所在省id   tinyint
        $data['address']=isset($basic['address']) ? (int)$basic['address'] : 0;                                         //现居所在地id   mediumint
        $data['work_experience']=isset($basic['work_experience']) ? (int)$basic['work_experience'] : 0;                 //工作经验      smallint
        $data['current_status']=isset($basic['current_status']) ? (int)$basic['current_status'] : 0;                    //当前状态      tinyint
        $data['basic_salary']=$salary['basic_salary'];                                                                  //当前月薪      int
        $data['expect_basic_salary']=$salary['expect_basic_salary'];                                                    //期望月薪      int
        $data['salary_range_id']=$salary['salary_range_id'];                                                            //薪资范围      tinyint
        $data['degree']=isset($basic['degree']) ? (int)$basic['degree'] : 0;                                            //最高学历      tinyint
        $data['workex_range_id']=$this->parser_workex_range_id($basic['workex_range_id']);                             //工作年限范围    tinyint
        $data['update_time']=isset($basic['resume_updated_at']) ? (int)strtotime($basic['resume_updated_at']) : 0;      //用户刷新简历的时间 update_time int
        $data['is_new_worker']=isset($basic['is_new_worker']) ? (int)$basic['is_new_worker'] : 0;                       //是否是应届生
        $data['update_at']=date('Y-m-d H:i:s');                                                                         // update_at  datetime
        if($data) Log::write_log("table_common 处理完成...");
        return $data;
    }

    /**
     * 工作经历 base_work 表字段关系映射
     * @return array
     */
    private function table_work(){
        $i = 0;
        $data = array();
        foreach($this->resume_data['work'] as $workid=>$w){
            $work_time = $this->parser_work_time($i);
            if($work_time['continue']) continue;
            $data[$workid]['wid'] = $this->create_wid_eid($this->resume_data['resume_id'],$i+1);                                //wid   bigint  新的work_id，保证不重复
            $data[$workid]['sort_id'] = $i+1;                                                                                    //sort_id   tinyint 工作经历在当前简历中的序列ID,第一份为1，第二份为2，依次类推
            $data[$workid]['resume_id'] = $this->resume_data['resume_id'];                                                       //resume_id bigint  简历ID
            $data[$workid]['company_id'] = isset($w['corporation_id']) ? (int)$w['corporation_id'] : 0;                          //company_id    int 公司ID
            $data[$workid]['region_id'] = 0;                                                                                     //region_id     mediumint   当前工作所在城市ID
            $data[$workid]['level'] = 0;                                                                                         //level         tinyint     职级类别ID
            $data[$workid]['management_experience'] = isset($w['management_experience']) ? $w['management_experience'] : 0;      //management_experience tinyint 有无管理经验 0:无，1:有
            $data[$workid]['start_time'] = $work_time['start_time'];                                                             //start_time    bigint  开始时间(时间戳)
            $data[$workid]['end_time'] = $work_time['end_time'];                                                                 //end_time      bigint  结束时间(时间戳)
            $data[$workid]['work_time'] = $work_time['work_time'];                                                               //work_time     smallint    工作持续时间(单位:月)
            $data[$workid]['basic_salary'] = isset($w['basic_salary']) ? (int)$w['basic_salary'] : 0;                            //basic_salary  mediumint   月薪
            $data[$workid]['so_far'] = $w['so_far'] == 'Y' ? (isset($w['start_time']) ? date('Y')-(int)$w['start_time'] : 0) : 0;//so_far        smallint    最后一份工作距今的时间(单位:年) 0:未知   (是否至今  hbase)
            $data[$workid]['salary_range_id'] = isset($w['salary_range_id']) ? $this->salary_range_id($w['salary_range_id']) : 0;//salary_range_id   tinyint 薪资范围ID 1=>0-5,2=>5-8,3=>8-12,4=>12-15,5=>15-20,6=>20-30,7=>30-50,8=>50-9999999999
            $data[$workid]['scale_id'] = isset($w['scale']) ? $this->parser_scale($w['scale']) : 0;                             //scale_id      tinyint     1:1-49人,2:50-150人,3:150-499人,4:500-999人,5:1000-4999人,6:5000-9999人,7:10000人以上
            $data[$workid]['department'] = isset($w['architecture_id']) ? $w['architecture_id'] : 0;                             //department    tinyint     部门ID
            $data[$workid]['function_type'] = isset($w['cv_functions'][0]) ? $w['cv_functions'][0]['label']: 0;                  //function_type
            $data[$workid]['update_at'] = date('Y-m-d H:i:s');                                                                   //update_at
            $i++;
        }
        if($data) Log::write_log("table_work 处理完成...");
        return $data;
    }

    //教育信息
    private function table_education(){
        $i = 0;
        $data = array();
        foreach($this->resume_data['education'] as $k=>$e){
            $start_time = isset($e['start_time']) ? $e['start_time'] : 0;
            $end_time = isset($e['end_time']) ? $e['end_time'] : 0;
            $education = $this->parser_education($start_time,$end_time);

            $data[$k]['eid'] = $this->create_wid_eid($this->resume_data['resume_id'],$i+1);                             //eid       bigint      新的教育经历ID（不重复）
            $data[$k]['sort_id'] = $i+1;                                                                                //sort_id   bigint      教育经历在当前简历中的序列ID,第一份为1，第二份为2，依次类推
            $data[$k]['resume_id'] = $this->resume_data['resume_id'];                                                   //resume_id bigint      简历ID
            $data[$k]['school_id'] = isset($e['school_id']) ? (int)$e['school_id'] : 0;
            $data[$k]['discipline_id'] = isset($e['discipline_id']) ? (int)$e['discipline_id'] : 0;                     //discipline_id mediumint   专业ID
            $data[$k]['degree'] = isset($e['degree']) ? (int)$e['degree'] : 0;                                          //degree    tinyint         学历ID
            $data[$k]['start_time'] = $education['start_time'];                                                         //start_time    bigint  开始时间
            $data[$k]['end_time'] = $education['end_time'];                                                             //end_time  bigint  结束时间
            $data[$k]['school_length'] = $education['school_length'];                                                   //school_length tinyint 学制：三年/四年/五年/七年
            $data[$k]['graduation_year'] = $education['graduation_year'];                                               //graduation_year   mediumint   毕业时间（年）
            $data[$k]['update_at'] = date('Y-m-d H:i:s');                                                               // update_at  datetime
            $i++;
        }
        if($data) Log::write_log("table_education 处理完成...");
        return $data;
    }

    //语言技能
    private function table_language(){
        $i = 0;
        $data = array();
        foreach($this->resume_data['language'] as $k=>$l){
            $language = $this->parser_language($l);
            $data[$k]['resume_id'] = $this->resume_data['resume_id'];                                                   //简历ID  `resume_id` bigint(20)
            $data[$k]['sort_id'] = $k+1;                                                                                //序列ID  sort_id` bigint(20)
            $data[$k]['language_id'] = $language['language_id'];                                                        //语言名称ID `language_id` tinyint(3)
            $data[$k]['level_id'] = $language['level_id'];                                                              //语言等级ID `level_id` tinyint(3)
            $data[$k]['update_at'] = date('Y-m-d H:i:s');
            $i++;
        }
        if($data) Log::write_log("table_language 处理完成...");
        return $data;
    }

    private function table_function(){
        return '';
    }

    private function table_industry(){
        return '';
    }
#########################################################################################################################
    /** 获取 work_id 和 education_id
     * @param $resume_id
     * @param $sort_id
     * @return string
     */
    private function create_wid_eid($resume_id, $sort_id) {
        return $sort_id . str_pad($resume_id, 14, "0", STR_PAD_LEFT);
    }

    /**获取工作开始时间  结束时间
     * @param $start 工作开始时间
     * @param $end      工作结束时间
     * @return mixed    处理后的时间
     *
     * 处理工作时间相关,这里的规则如下:
     * 1、第一份工作经历如果没有开始时间则用最后一份教育经历的结束时间作为开始时间
     * 2、每份工作经历的结束时间如果没有则取下一份工作经历的开始时间
     * 3、如果下一份工作经历仍然没有开始时间，则抛弃，
     * 4、非第一份工作经历缺失开始时间时，取上一份工作经历的结束时间，如果上一份工作经历没有结束时间则抛弃
     * 5、当前工作经历开始时间小于等于上一份工作经历的开始时间则抛弃
     * 6、当前工作经历的开始时间小于上一份工作经历的结束时间一个月则抛弃
     * 7、当前工作经历的结束时间小于开始时间一个月则抛弃
     * 8、当前工作经历既没有开始时间，也没有结束时间则抛弃
     * 9、只有最新一份工作经历的才能算工作至今，其结束时间为当前的时间戳
     *
     */
    private function parser_work_time($key){
        $current_work_start_time = $current_work_end_time = $next_work_start_time = 'undefined';
        $work_time['continue'] = false;
        $work_time['key'] = "第".($key+1)."份工作经历";
        $i=0;

        foreach($this->resume_data['work'] as $w){
            if($i == $key){  //当前份工作
                $current_work_start_time = isset($w['start_time']) ? $w['start_time'] : 'undefined';
                $current_work_end_time = isset($w['end_time']) ? $w['end_time'] : 'undefined';
                if($w['so_far'] == 'Y') $current_work_end_time = date('Y年m月d日');
            }

            if($i == $key+1){ //下一份工作
                $next_work_start_time = isset($w['start_time']) ? $w['start_time'] : 'undefined';
                if($w['so_far'] == 'Y') $next_work_start_time = date('Y年m月d日');
            }

            $i++;
        }

        foreach($this->resume_data['education'] as $e){
            if(isset($e['end_time']) && $current_work_start_time == 'undefined'){
                $current_work_start_time = $e['end_time'];
            }
        }

        //1、第一份工作经历如果没有开始时间则用最后一份教育经历的结束时间作为开始时间
        if($key==0 && $current_work_start_time == 'undefined'){
            $edu_count = count($this->resume_data['education']);
            $current_work_start_time = empty($this->resume_data['education'][$edu_count-1]['end_time']) ? 'undefined' : $this->resume_data['education'][$edu_count-1]['end_time'];
        }


        //每份工作经历的结束时间如果没有则取下一份工作经历的开始时间
        //如果下一份工作经历仍然没有开始时间，则抛弃，
        if($current_work_end_time == 'undefined' && $next_work_start_time != 'undefined'){
            $current_work_end_time = $next_work_start_time;
        }


//        if($key > 0){
//            //上一份工作的结束时间
//            $previous_work_end_time = isset($resume_data['work'][$key-1]['end_time']) ? $resume_data['work'][$key-1]['end_time'] : 'undefined';
//
//            //4、非第一份工作经历缺失开始时间时，取上一份工作经历的结束时间，如果上一份工作经历没有结束时间则抛弃
//            if($current_work_start_time === 'undefined' && $previous_work_end_time !== 'undefined'){
//                $current_work_start_time = $previous_work_end_time;
//            }else{
//                $work_time['continue'] = true;
//            }
//            //上一份工作的开始时间
//            $previous_work_start_time = isset($resume_data['work'][$key-1]['start_time']) ? $resume_data['work'][$key-1]['start_time'] : 'undefined';
//
//            //5、当前工作经历开始时间小于等于上一份工作经历的开始时间则抛弃(前提是两个都正常存在然后在比较)
//            if($current_work_start_time !== 'undefined' && $previous_work_start_time !== 'undefined'){
//                if (preg_match("/((19|20)\d{2}).+?(\d+).*/", trim($current_work_start_time), $match_start)) {
//                    $start_month = $match_start[1] * 12 + $match_start[3];
//                } else {
//                    $start_month = 0;
//                }
//                if (preg_match("/((19|20)\d{2}).+?(\d+).*/", trim($previous_work_start_time), $match_start2)) {
//                    $start2_month = $match_start2[1] * 12 + $match_start2[3];
//                } else {
//                    $start2_month = 0;
//                }
//                if($start_month <= $start2_month){
//                    $work_time['continue'] = true;
//                }
//                unset($match_start,$match_start2,$start_month,$start2_month);
//            }
//
//            //6、当前工作经历的开始时间小于上一份工作经历的结束时间一个月则抛弃(先同时正常存在) x<y+1
//            if($current_work_start_time !== 'undefined' && $previous_work_end_time !== 'undefined'){
//                if (preg_match("/((19|20)\d{2}).+?(\d+).*/", trim($current_work_start_time), $match_start)) {
//                    $start_month = $match_start[1] * 12 + $match_start[3];
//                } else {
//                    $start_month = 0;
//                }
//                if (preg_match("/((19|20)\d{2}).+?(\d+).*/", trim($previous_work_end_time), $match_start2)) {
//                    $start2_month = $match_start2[1] * 12 + $match_start2[3];
//                } else {
//                    $start2_month = 0;
//                }
//
//                if($start_month - $start2_month < 1){
//                    $work_time['continue'] = true;
//                }
//                unset($match_start,$match_start2,$start_month,$start2_month);
//            }
//
//        }

        //8、当前工作经历既没有开始时间，也没有结束时间则抛弃
        if($current_work_start_time === 'undefined' && $current_work_end_time ==='undefined'){
            $work_time['continue'] = true;
        }else{
            $start_month=$end_month=0;
            if (preg_match("/((19|20)\d{2}).+?(\d+).*/", trim($current_work_start_time), $match_start)) {
                $work_time['start_time'] = strtotime($match_start[1] . "-" . $match_start[3]);
                $start_month = $match_start[1] * 12 + $match_start[3];
            } else {
                $work_time['start_time'] = 0;
            }
            if (preg_match("/((19|20)\d{2}).+?(\d+).*/", trim($current_work_end_time), $match_end)) {
                $work_time['end_time'] = strtotime($match_end[1] . "-" . $match_end[3]);
                $end_month = $match_end[1] * 12 + $match_end[3];
            } else {
                $work_time['end_time'] = 0;
            }
            unset($match_start,$match_end);

            //7、当前工作经历的结束时间小于开始时间一个月则抛弃x<y+1
            if($end_month - $start_month < 1){
                $work_time['continue'] = true;
            }

            $byear = (int)$this->birth['byear'];
            //如果工作时间小于出生时间，过滤
            if ($byear > 0 && $work_time['start_time'] > 0 && date("Y", $work_time['start_time']) < $byear + 15) {
                $work_time['start_time'] = 0;
            }
            if ($byear > 0 && $work_time['end_time'] > 0 && date("Y", $work_time['end_time']) < $byear + 15) {
                $work_time['end_time'] = 0;
            }

            $work_time['work_time'] = $end_month > $start_month ? $end_month - $start_month : 0;
        }
        return $work_time;
    }

    /**获取修正后的薪资
     * @param $data
     */
    private function parser_salary($data){
        $basic_salary = $expect_basic_salary = 0;   //初始化 当前月薪、期望月薪
        //当前月薪
        if (isset($data['basic_salary']) && $data['basic_salary']) {
            $basic_salary = strpos($data['basic_salary'], "年") !== false ? $data['basic_salary'] / 12 : $data['basic_salary'];
        } elseif (isset($data['annual_salary']) && $data['annual_salary']) {
            $basic_salary = round(($data['basic_salary']) / 12, 2);
        } elseif (isset($data['basic_salary_from']) && isset($data['basic_salary_to']) && $data['basic_salary_to'] >= $data['basic_salary_from']) {
            $basic_salary = round($data['basic_salary_from'] + (($data['basic_salary_to'] - $data['basic_salary_from']) * $this->salary_increase_ratio * 2 ), 2);
        } elseif (isset($data['basic_salary_from']) && $data['basic_salary_from']) {
            $basic_salary = round($data['basic_salary_from'] * ($this->salary_increase_ratio * 2 + 1), 2);
        } elseif (isset($data['basic_salary_to']) && $data['basic_salary_to']) {
            $basic_salary = round($data['basic_salary_to'] / ($this->salary_increase_ratio * 2 + 1), 2);
        } elseif (isset($data['annual_salary_from']) && isset($data['annual_salary_to']) && $data['annual_salary_to'] >= $data['annual_salary_from']) {
            $basic_salary = round(($data['annual_salary_from'] + (($data['annual_salary_to'] - $data['annual_salary_from']) * $this->salary_increase_ratio * 2)) / 12, 2);
        } elseif (isset($data['annual_salary_from'])) {
            $basic_salary = round($data['annual_salary_from'] * ($this->salary_increase_ratio * 2 + 1) / 12, 2);
        } elseif (isset($data['annual_salary_to'])) {
            $basic_salary = round($data['annual_salary_to'] / 12 * ($this->salary_increase_ratio * 2 + 1), 2);
        }
        //期望月薪
        if (isset($data['expect_basic_salary'])){
            $expect_basic_salary = $data['expect_basic_salary'];
        } elseif (isset($data['expect_annual_salary'])){
            $expect_basic_salary = $data['expect_annual_salary'] / 12;
        } elseif (isset($data['expect_salary_from']) && isset($data['expect_salary_to']) && $data['expect_salary_to'] >= $data['expect_salary_from']){
            $expect_basic_salary = round($data['expect_salary_from'] + (($data['expect_salary_to'] - $data['expect_salary_from']) * $this->salary_increase_ratio * 2), 2);
        } elseif (isset($data['expect_salary_from'])){
            $expect_basic_salary = round($data['expect_salary_from'] * ($this->salary_increase_ratio * 2 + 1), 2);
        } elseif (isset($data['expect_salary_to'])){
            $expect_basic_salary = round($data['expect_salary_to'] / ($this->salary_increase_ratio * 2 + 1), 2);
        } elseif (isset($data['expect_annual_salary_from']) && isset($data['expect_annual_salary_to']) && $data['expect_annual_salary_to'] >= $data['expect_annual_salary_from']) {
            $expect_basic_salary = round(($data['expect_annual_salary_from'] + (($data['expect_annual_salary_to'] - $data['expect_annual_salary_from']) * $this->salary_increase_ratio * 2)) / 12, 2);
        } elseif (isset($data['expect_annual_salary_from'])) {
            $expect_basic_salary = round($data['expect_annual_salary_from'] * ($this->salary_increase_ratio * 2 + 1) / 12, 2);
        } elseif (isset($data['expect_annual_salary_to'])) {
            $expect_basic_salary = round($data['expect_annual_salary_to'] / 12 * ($this->salary_increase_ratio * 2 + 1), 2);
        }
        //过滤 当前月薪 太离谱的数据
        if ($basic_salary >= 999999){
            $basic_salary = 0;
        } elseif ($basic_salary < 1000) {
            $basic_salary = $basic_salary * 1000;
        }
        //过滤 期望月薪 太离谱的数据
        if ($expect_basic_salary >= 999999){
            $expect_basic_salary = 0;
        } elseif ($expect_basic_salary < 1000) {
            $expect_basic_salary = $expect_basic_salary * 1000;
        }
        //修正薪资
        if ($expect_basic_salary && $basic_salary) {
            if ($expect_basic_salary / $basic_salary >= 2 || $expect_basic_salary / $basic_salary < 1) {
                $salary_value = $this->compare_salary([$expect_basic_salary, $basic_salary]);
                if ($basic_salary = $salary_value) {
                    $expect_basic_salary = $basic_salary * ($this->salary_increase_ratio * 2 + 1);
                } else {
                    $basic_salary = $expect_basic_salary / ($this->salary_increase_ratio * 2 + 1);
                }
            }
        } elseif (!$basic_salary && $expect_basic_salary) {
            $basic_salary = $expect_basic_salary / ($this->salary_increase_ratio * 2 + 1);
        } elseif ($basic_salary && !$expect_basic_salary) {
            $expect_basic_salary = $basic_salary * ($this->salary_increase_ratio * 2 + 1);
        }

        $salary_range_id = isset($data['salary_range_id']) ? $data['salary_range_id'] : 0;
        $this->salary = array(
            'basic_salary'=>$basic_salary,
            'expect_basic_salary'=>$expect_basic_salary,
            'salary_range_id'=>$this->salary_range_id($salary_range_id)
        );
        return $this->salary;
    }

    /**修正薪资
     * @param array $salarys
     * @param int $compare_value
     * @return int|mixed
     */
    private function compare_salary($salarys = array(), $compare_value = 0) {
        $compare_value = $compare_value ? $compare_value : $this->avg_salary;
        $salary1 = $salary2 = 0;
        foreach ($salarys as $salary) {
            if (!$salary1) {
                $salary1 = $salary;
                continue;
            } else {
                $salary2 = $salary;
                $sm1 = $salary1 > $compare_value ? ($salary1 / $compare_value) : ($compare_value / $salary1);
                $sm2 = $salary2 > $compare_value ? ($salary2 / $compare_value) : ($compare_value / $salary2);
                if ($sm1 > $sm2) {
                    $salary1 = $salary2;
                }
            }
        }
        return $salary1;
    }

    /**工作年限范围ID 1=>0-1,2=>1-3,3=>3-5,4=>5-8,5=>8-15,6=>15-999
     * @param $id
     * @return int
     */
    private function parser_workex_range_id($id){
        $id = (int)$id;
        if($id <=1){
            return 1;
        }elseif($id >1 && $id <= 3){
            return 2;
        }elseif($id > 3 && $id <= 5){
            return 3;
        }elseif($id > 5 && $id <= 8){
            return 4;
        }elseif($id > 8 && $id <= 15){
            return 5;
        }else{
            return 6;
        }
    }

    /**薪资范围ID  1=>0-5,2=>5-8,3=>8-12,4=>12-15,5=>15-20,6=>20-30,7=>30-50,8=>50-9999999999
     * @param $id
     * @return int
     */
    private function salary_range_id($id){
        $id = (int)$id;
        if($id < 5){
            return 1;
        }elseif($id < 8){
            return 2;
        }elseif($id < 12){
            return 3;
        }elseif($id < 15){
            return 4;
        }elseif($id < 20){
            return 5;
        }elseif($id < 30){
            return 6;
        }elseif($id < 50){
            return 7;
        }else{
            return 8;
        }
    }

    /** 根据YNU获取对应的值
     * @param $key
     * @return mixed
     */
    private function parser_ynu($key){
        $arr = array(
            'Y' => 1,   //已 （已婚，已有住房....）
            'N' => 0,   //未
            'U' => 2    //未知
        );
        return $arr[$key];
    }

    /** 获取 年龄 出生年 出生月
     * @param $birth
     * @return array
     */
    private function parser_birth($birth){
        $age = $byear = $bmonth = 0;
        $birth = empty($birth) ? false : trim($birth);
        if ($birth) {
            if (preg_match("/(\d{4}).*?(\d{2}|\d{1})|(\d{4})|(\d{2})年(\d+)月/", $birth, $match)) {
                if ($match[1] && $match[2]) {
                    $byear = intval($match[1]);
                    $bmonth = intval($match[2]);
                } elseif ($match[3]) {
                    $byear = intval($match[3]);
                } elseif ($match[4] && $match[5]) {
                    $byear = intval("19" . $match[4]);
                    $bmonth = intval($match[5]);
                }
                $this_year = intval(date("Y", time()));
                if ($byear >= 1960 && $byear <= $this_year - 16) {
                    $age = intval($this_year - $byear);
                } else {
                    $byear = 0;
                }
                if ($bmonth > 12) {
                    $bmonth = 0;
                }
            }
        }
        $this->birth = array('age'=>$age,'byear'=>$byear,'bmonth'=>$bmonth);
        return $this->birth;
    }

    /** 公司规模处理
     * @param $str_scale
     * @return int
     */
    private function parser_scale($str_scale){
        $str_scale = trim($str_scale);
        $scale_patter = array(
            1 => "/.*(人以下|少于|1\-49|1\-50).*/",
            2 => "/.*(50\-99|20\-99|50\-150).*/",
            3 => "/.*(100\-499|150\-500|150\-499).*/",
            4 => "/.*(500\-1000|500\-999).*/",
            5 => "/.*(1000\-9999|1000\-5000|1000\-2000|2000\-5000|1000\-4999).*/",
            6 => "/.*(5000\-10000|5000\-9999).*/",
            7 => "/.*(10000人以上).*/"
        );
        $array_scale_patter = array_values($scale_patter);
        $array_scale_replacement = array_keys($scale_patter);
        $scale = intval(preg_replace($array_scale_patter, $array_scale_replacement, $str_scale));
        if (!in_array($scale, $array_scale_replacement)) {
            $scale = 0;
        }
        return $scale;
    }

    /** 获取教育信息时间
     * @param $start_time
     * @param $end_time
     * @return mixed
     */
    private function parser_education($start_time,$end_time){

        if (preg_match("/((19|20)\d{2}).+?(\d+).*/", trim($start_time), $match_start)) {
            $education['start_time'] = $start_time ? strtotime($match_start[1] . "-" . $match_start[3]) : 0;
        } else {
            $education['start_time'] = 0;
        }
        if (preg_match("/((19|20)\d{2}).+?(\d+).*/", trim($end_time), $match_end)) {
            $education['end_time'] = $end_time ? strtotime($match_end[1] . "-" . $match_end[3]) : 0;
        } else {
            $education['end_time'] = 0;
        }
        unset($match_start);
        unset($match_end);
        if (($education['end_time'] > 0 && $education['end_time'] < $education['start_time']) || $education['end_time'] >= time()) {
            $education['end_time'] = 0;
        }

        //学制
        $education['school_length'] = 0;
        if ($education['start_time'] && $education['end_time']) {
            $education['school_length'] = (int)date("Y", $education['end_time']) - (int)date("Y", $education['start_time']);
            if ($education['school_length'] > 9) {
                $education['school_length'] = 0;
            }
        }
        //毕业时间（年）
        $education['graduation_year'] = 0;
        if ($education['end_time']) {
            $education['graduation_year'] = (int)date("Y", $education['end_time']);
        }

        return $education;
    }

    /** 获取语言等级
     * @param $language [level] => 熟练 [name] => 日语
     * @return mixed
     */
    private function parser_language($language){
        $level = array(
            1 => "/.*一般.*/",
            2 => "/.*熟练.*/",
            3 => "/.*良好.*/",
            4 => "/.*精通.*/",
            5 => "/.*一级.*/",
            6 => "/.*二级.*/",
            7 => "/.*三级.*/",
            8 => "/.*四级.*/",
            9 => "/.*六级.*/",
            10 => "/.*八级.*/"
        );
        $language_name = array(
            1 => "/.*IELTS.*/i",
            2 => "/.*TOEIC.*/i",
            3 => "/.*TOFEL.*/i",
            4 => "/.*上海话.*/",
            5 => "/.*俄语.*/",
            6 => "/.*德语.*/",
            7 => "/.*意大利语.*/",
            8 => "/.*日语.*/",
            9 => "/.*普通话.*/",
            10 => "/.*法语.*/",
            11 => "/.*粤语.*/",
            12 => "/.*(英语|english).*/i",
            13 => "/.*葡萄牙语.*/",
            14 => "/.*西班牙语.*/",
            15 => "/.*闽南语.*/",
            16 => "/.*阿拉伯语.*/",
            17 => "/.*韩语.*/"
        );
        $array_level_patter = array_values($level);
        $array_level_replacement = array_keys($level);
        $array_langu_patter = array_values($language_name);
        $array_langu_replacement = array_keys($language_name);
        $name = isset($language['name']) ? trim($language['name']) : '';
        $level = isset($language['level']) ? trim($language['level']) : '';

        $language_name = preg_replace($array_langu_patter, $array_langu_replacement, $name);
        $language_level = preg_replace($array_level_patter, $array_level_replacement, $level);

        if (in_array($language_level, $array_level_replacement) && in_array($language_name, $array_langu_replacement)) {
            $language_data['language_id'] = $language_name;
            $language_data['level_id'] = $language_level;
        }else{
            $language_data['language_id'] = 0;
            $language_data['level_id'] = 0;
        }

        return $language_data;
    }

    public function __destruct(){
        unset($this->resume_data);
    }
}