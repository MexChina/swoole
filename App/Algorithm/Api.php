<?php
namespace Swoole\App\Algorithm;
use Swoole\Core\Log;
use Swoole\Core\Lib\Worker;
class Api{


    /*****************************************************************
     * cv_education、cv_degree  
     * @param  array $compress resumes_extras.compress
     * @author 赵付涛<futao.zhao@ifchange.com>
     * @return array => [
     *            'cv_education' string json
     *            'cv_degree'   int
     * ]
     * 2017-5-27 检
     ****************************************************************/
    public function cv_education($compress){
        $educations = $compress['education'];
        if(empty($educations)) return array('cv_education'=>'','cv_degree'=>99);
        $p=array();
        foreach($educations as $education){
            $education_id = $education['id'];
            $school_name = isset($education['school_name']) ? $education['school_name'] : '';
            $discipline_name = isset($education['discipline_name']) ? $education['discipline_name'] : '';
            $degree = isset($education['degree']) ? $education['degree'] : 99;

            if(!empty($school_name) || !empty($discipline_name)){
                $p['educations'][$education_id] = json_encode(array('school'=>$school_name, 'major'=>$discipline_name, 'degree'=>$degree));
            }
        }
        $p['basic_degree'] = $compress['basic']['degree'];
        if(empty($p)) return array('cv_education'=>'','cv_degree'=>99);
        $work = new Worker("cv_education_service_online");
        $gearman_return = $work->client(array(
            'c'=>'CVEducation',
            'm'=>'query',
            'p'=>$p
        ),true);

        $results = $gearman_return['results'];
        $cv_degree = $results['features']['degree'];
        $units = (array)$results['units'];
        $cv_education = empty($units) ? '' : json_encode($units,JSON_UNESCAPED_UNICODE);

        return array(
            "cv_education"=>$cv_education,
            "cv_degree"=>$cv_degree
        );
    }


    /**************************************************************
     * 简历职级标签
     * @param  array $compress resumes_extras.compress
     * @author 刘治<zhi.liu@ifchange.com>
     * @return string
     * 2017-5-17 检
     **************************************************************/
    public function cv_title($compress){
        if(empty($compress['work'])) return '';

        $position_names = array();
        foreach ($compress['work'] as $work) {
            if (empty($work['position_name'])) continue;
            $work_id = (string)$work['id'];
            $position_names["$work_id"] = $work['position_name'];
        }

        if (empty($position_names)) return '';
        $work = new Worker("title_recognize_server_new_format");
        $rs = $work->client(array(
            'c'=>'title_recognition',
            'm'=>'get_title_recognition',
            'p'=>$position_names
        ),true);
        return json_encode($rs['results'],JSON_UNESCAPED_UNICODE);
    }
    /**************************************************************
     * 语言识别
     * @param  array $compress resumes_extras.compress
     * @author 刘治<zhi.liu@ifchange.com>
     * @return string
     * 2017-5-17 检
     **************************************************************/
    public function cv_language($compress){
        if (empty($compress['language'])) return '';
        $cvs = array(
            "id" => $compress['basic']['id'],
            "language" => array()
        );
        foreach ($compress['language'] as $language) {
            $cvs["language"][$language["id"]] = array(
                "certificate" => $language["certificate"],
                "name"        => $language["name"],
                "level"       => $language["level"]
            );
        }
        $work = new Worker("cv_lang");
        $rs = $work->client(array(
            'c'=>'cv_lang',
            'm'=>'get_cv_lang',
            'p'=>array(
                    'cvs' => array($cvs)
                )
        ),true);
        return json_encode($rs['results']["cvs"][0]["language"],JSON_UNESCAPED_UNICODE);
    }
    
    /**************************************************************
     * 工作年限(单位:月)
     * @param  array $compress resumes_extras.compress
     * @author 刘治<zhi.liu@ifchange.com>
     * @return int 存放resumes表
     * 2017-5-17 检
     **************************************************************/
    public function cv_workyear($compress){
        if (empty($compress['work'])) return 0;
        $work = new Worker("cv_wkep");
        $gearman_return = $work->client(array(
            'p'=>json_encode(array(
                    'work'=>$compress['work'],
                    'education'=>$compress['education'],
                ))
        ),true);
        return (int)$gearman_return['results'];
    }

    /*************************************************************
     * [cv_feature description]
     * @param  [type] $compress [description]
     * @author 姚程<cheng.yao@ifchange.com>
     * @return [type]           [description]
     *************************************************************/
    public function cv_feature($compress){
        if (empty($compress['work']) && empty($compress['project'])) {
            return '';
        }
        $work = new Worker("rs_feature_svr_online_new_format");
        $rs = $work->client(array(
            'c' => 'rs_feature',
            'm' => 'get_all_feature',
            'p' => array(
                'cv_id'   => $compress['basic']['id'],
                'cv_json' => json_encode($compress)
                )
        ),true);//json暂未支持
        return json_encode($rs['results'],JSON_UNESCAPED_UNICODE);
    }

    /**
     * 学校识别接口，根据学校名获取学校id
     * @param  [type]  $school [description]
     * @param  string  $major  [description]
     * @param  integer $degree [description]
     * @return [type]          [description]
     */
    public function school($school, $major='', $degree=1){
        if(empty($school)) return 0;
        $work = new Worker("cv_education_service_online");
        $results = $work->client(array(
            'c'=>'CVEducation',
            'm'=>'query',
            'p'=>json_encode(array('school'=>$school, 'major'=>$major, 'degree'=>$degree))
        ),true);

        $schoolid=0;
        foreach($results["units"] as $key => $schoolid_item) {
            if($key == 1 && isset($schoolid_item['school_id'])) {
                $schoolid = (int)$schoolid_item['school_id'];
            }
        }
        return $schoolid;
    }

    /*****************************************************************
     * [cv_quality description]
     * @param  [type] $compress [description]
     * @author 战思南<sinan.zhan@ifchange.com>
     * @return [type]           [description]
     ******************************************************************/
    public function cv_quality($compress,$cv_tag=array(),$cv_title=array(),$cv_education=array(),$cv_workyear=0){
        if(empty($cv_tag)) $cv_tag = $this->cv_tag($compress);
        if(empty($cv_title)) $cv_title = $this->cv_title($compress);
        if(empty($cv_education)) $cv_education = $this->cv_education($compress);
        if(empty($cv_workyear)) $cv_workyear = $this->cv_workyear($compress);
        $cv_tag = is_array($cv_tag) ? $cv_tag : json_decode($cv_tag,true);
        $cv_title = is_array($cv_title) ? $cv_title : json_decode($cv_title,true);
        $cv_education = is_array($cv_education) ? $cv_education : json_decode($cv_education,true);


        $works = $compress['work'];
        $company_name = '';
        $funcids = '';

        // 取出相关用到的数据
        $cv_tag_json = '';
        $cv_title_json = '';
        $cv_education_json = '';
        if($resume_algorithm_info) {
            $cv_tag_json = $resume_algorithm_info['cv_tag'];
            $cv_title_json = $resume_algorithm_info['cv_title'];
            $cv_education_json = $resume_algorithm_info['cv_education'];
        }

        // 获取公司名字、传入最近两段工作经历的所有职能ID， 顺序不分先后
        if($works) {
            $funcid_count = 0;
            foreach($works as $work) {
                $work_id = $work['id'];
                $company_name .= empty($work['corporation_name']) ? '' : $work['corporation_name'].',';
                if($cv_tag && $cv_tag[$work_id] && $funcid_count < 2) {
                    $cv_tag_must = isset($cv_tag[$work_id]['must']) ? $cv_tag[$work_id]['must'] : '';
                    if($cv_tag_must) {
                        foreach($cv_tag_must as $item) {
                            if(strpos($item, ':') !== false) {
                                $item_info = explode(':', $item);
                                $funcids = $item_info[0].',';

                            }
                        }
                    }
               }

               $funcid_count++;
            }
        }

        // 去掉上面每次在最后加的逗号分隔符
        if(!empty($company_name)) {
            $company_name = rtrim($company_name, ',');
        }

        // 去掉上面每次在最后加的逗号分隔符
        if(!empty($funcids)) {
            $funcids = rtrim($funcids, ',');
        }

        //职级 1普通职员，2经理，4总监，8VP，16实习生
        $ranktitleid = 1;
        if($cv_title) {
            $cv_title_algorithm_config = array('普通职员' => 1, '经理' => 2, '总监' => 4, 'VP' => 8, '实习生' => 16);
            foreach($cv_title as $title_item) {
                if($title_item['phrase'] && $title_item['phrase'] != 'null') {
                    if(isset($cv_title_algorithm_config[$title_item['phrase']])) {
                        $ranktitleid = $cv_title_algorithm_config[$title_item['phrase']];
                    }

                    break;
                }
            }
        }
        

        // 传入最高学历所在学校名
        $school_name = empty($compress['basic']['school_name']) ? '' : $compress['basic']['school_name'];

        // 传入最高学历所在学校类型，1为211,2为985,0为其他
        $schoolid = 0;
        $school_key = '';
        if($school_name) {
            if($compress['education'] && $cv_education) {
                uasort($compress['education'], 'education_sort');
                foreach($compress['education'] as $education_key => $education_item) {
                    if($education_item['school_name'] == $school_name) {
                        if(isset($cv_enducation[$education_key]['school_id']) && $cv_enducation[$education_key]['school_id']) {
                            $schoolid = (int)$cv_enducation[$education_key]['school_id'];
                            break;
                        }
                    }
                }
            }

            // 如果没有取到school id,重新单独去取
            if(empty($schoolid)) {
                $schoolid = $this->school($school_name);
            }
        }

        // 传入工作年限(向下取整，如1.8年算1年)
        $work_experience = 0;
        $work_experience = intval($cv_workyear/12);
        

        // 传入最高学历对应的学历ID， 1为本科，2为硕士，3为博士，4为大专，6为MBA，0为其他
        $degree = empty($compress['basic']['degree']) ? 0 : intval($compress['basic']['degree']);
        // 算法那边需要学历组合
        $degree_algorithm_config = array(1, 2, 3, 4, 6);
        if(!in_array($degree, $degree_algorithm_config)) {
            $degree = 0;
        }

        $work = new Worker("edps");
        $rs = $work->client(array(
            'p'=>array(
                    'm' => 'get_cv_quality',
                    'handle' => 'cvcompetence',
                    'cvid'         => (int)$compress['basic']['id'],
                    'corpnames'    => $company_name,
                    'funcids'      => $funcids,
                    'ranktitleid'  => $ranktitleid,
                    'schoolname'   => $school_name,
                    'schoolid'     => (int)$schoolid,
                    'workexpyears' => $work_experience,
                    'degreeid'     => $degree
                )
        ),true);

        return isset($rs['score']) ? $rs['score'] : 0.1;
    }

    /*************************************************************
     * 简历职能标签
     * @param  array $compress resumes_extras.compress
     * @author 刘贝<bei.liu@ifchange.com>
     * @return string  智能标签数据
     * 2017-5-17 检
     *************************************************************/
    public function cv_tag($compress){
        if(empty($compress['work'])) return '';
        $work_list = array();
        foreach ($compress['work'] as $work){
            $work_id = $work['id'];
            if (empty($work['position_name']) && empty($work['responsibilities'])) continue;
            $work_list[$work_id] = array(
                'id'    => $work_id,
                'type'  => 0,
                'title' => empty($work['position_name']) ? '' : $work['position_name'],
                'desc'  => empty($work['responsibilities']) ? '' : $work['responsibilities']
            );
        }

        if(empty($work_list)) return '';

        $work = new Worker("tag_predict");
        $rs = $work->client(array(
            'c' => 'cv_tag',
            'm' => 'get_cv_tags',
            'p' => array(
                'cv_id' => $compress['basic']['id'],
                'work_map' => $work_list
            ),
        ),true);

        if(isset($rs['err_no']) && $rs['err_no']==0){
            foreach ($rs['results'] as $work_id => $row) {
                $cv_tag[$work_id] = $row;
            }
            return empty($cv_tag) ? '' : json_encode($cv_tag,JSON_UNESCAPED_UNICODE);
        }else{
            Log::writelog($compress['basic']['id']." cv_tag 失败！");
        }
    }

    /*************************************************************
     * 职能技能标签
     * @param  array $compress resumes_extras.compress
     * @author 姚程<cheng.yao@ifchange.com>
     * @return string  智能标签数据
     * 2017-5-17 检
     *************************************************************/
    public function cv_entity($compress){
        if(empty($compress['work'])) return '';
        
        $work_list = array();
        $cv_entity = array();
        foreach ($compress['work'] as $work) {
            $work_id = $work['id'];
            if (empty($work['position_name']) && empty($work['responsibilities'])) {
                continue;
            }
            $work_list[$work_id] = array(
                'id'    => $work_id,
                'type'  => 0,
                'title' => empty($work['position_name']) ? '' : $work['position_name'],
                'desc'  => empty($work['responsibilities']) ? '' : $work['responsibilities']
            );
        }
        if(empty($work_list)) return '';
        $work = new Worker("cv_entity_new_format");
        $rs = $work->client(array(
                'c' => 'cv_entity',
                'm' => 'get_cv_entitys',
                'p' => array(
                    'cv_id' => $compress['basic']['id'],
                    'work_map' => $work_list
                ),
            ),true);

        if(isset($rs['err_no']) && $rs['err_no']==0){
            foreach ($rs['results'] as $work_id => $row) {
                $cv_entity[$work_id] = $row;
            }
            return empty($cv_entity) ? '' : json_encode($cv_entity,JSON_UNESCAPED_UNICODE);
        }else{
            Log::writelog($compress['basic']['id']." cv_entity 失败！");
        }
    }

    /*************************************************************
     * 公司识别接口 
     * @param  array $compress  resumes_extras.compress
     * @author 战思南<sinan.zhan@ifchange.com>
     * @return string  公司识别接口 
     * 2017-5-17 检
     *************************************************************/
    public function cv_trade($compress){
        if(empty($compress['work'])) return '';
        $input['cv_id'] = uniqid($compress['basic']['id'].'_');
        $input['work_list'] = array();
        foreach($compress['work'] as $work) {
            $work_id = $work['id'];
            if(empty($work['corporation_name'])) {
                continue;
            }
            $input['work_list'][] = array(
                'position' => empty($work['position_name']) ? '' : $work['position_name'],
                'company_name' => empty($work['corporation_name']) ? '' : $work['corporation_name'],
                'work_id' => intval($work_id),
                'desc' => empty($work['corporation_desc']) ? (empty($work['responsibilities']) ? '' : $work['responsibilities']) : $work['corporation_desc'],
                'industry_name' => empty($work['industry_name']) ? '' : $work['industry_name'],
            );
        }
        if(empty($input['work_list'])) return '';
        $work = new Worker("corp_tag");
        $rs = $work->client($input,true);
        if(isset($rs['status']) && $rs['status']==0){
            return json_encode($rs['result'],JSON_UNESCAPED_UNICODE);
        }else{
            Log::writelog($compress['basic']['id']." corp_tag 失败！");
        }
    }

    /**
     * 新增简历的算法离职率 离线的
     * @return [type] [description]
     */
    public function cv_resign($compress,$deliver=array(),$history=''){
        $deliver = empty($deliver) ? array('days7_deliver_num'=>0,'days7_update_num'=>0) : $deliver;
        $resume_id = $compress['basic']['id'];
        $work = new Worker("resign_prophet");
        $rs = $work->client(array(
            'c'=>'resign_prediction',
            'm'=>'resign_computing',
            'p'=>array(
                    $resume_id=>array(
                            'cv_id'=>$resume_id,
                            'last_intention'=>0,
                            'cv_content'=>json_encode($compress,JSON_UNESCAPED_UNICODE),
                            'behavior'=>array(
                                'times_deliver'=>$deliver['days7_deliver_num'],
                                'times_update'=>$deliver['days7_update_num']
                            ),
                            'history'=>$history
                        )
                )
            ),true);
        $rs['results'] = (array)$rs['results'];
        return empty($rs['results'][$resume_id]) ? '' : json_encode($rs['results'][$resume_id],JSON_UNESCAPED_UNICODE);
    }

    /**
     * [CVUniq description]
     * @author 赵付涛<futao.zhao@ifchange.com>
     * @param [type] $compress [description]
     */
    public function CVUniq($compress){
        $work = new Worker("cv_uniq_server_online");
        $res = $work->client(array(
            'c'=>'CVUniq',
            'm'=>'query',
            'p'=>array('-1'=>json_encode($compress))
        ),true,true);
        return $res["results"]["-1"];
    }

    /**
     * 异步通知正牌服务
     * @author 赵付涛<futao.zhao@ifchange.com>
     * @param array $compress    简历扩展表压缩包内容
     * @param array $algorighm   简历算发表数据
     * @param int $cv_workyear   简历主表工作年限
     */
    public function CVFwdIndex($compress,$algorighm,$cv_workyear){
        $resume_id = (int)$compress['basic']['id'];
        $work = new Worker("fwdindex_service_online");
        $work->client(array(
            'c' => 'CVFwdIndex',
            'm' => 'add',
            'p' => array(
                $resume_id => array(
                    'cv_id'           => $resume_id,
                    'cv_source'       => $algorighm['cv_source'],
                    'cv_trade'        => $algorighm['cv_trade'],
                    'cv_title'        => $algorighm['cv_title'],
                    'cv_tag'          => $algorighm['cv_tag'],
                    'cv_entity'       => $algorighm['cv_entity'],
                    'cv_education'    => $algorighm['cv_education'],
                    'cv_feature'      => $algorighm['cv_feature'],
                    'cv_degree'       => $algorighm['cv_degree'],
                    'work_experience' => $cv_workyear,
                    'cv_json'         => json_encode($compress)
                )
            )
        ),false,false,true);
    }

    /**
     * 异步通知es服务
     * @param array $compress resumes_extras.compress
     * @param array $resume   resumes
     */
    public function EsServers($compress,$resume){
        $work = new Worker("es_servers");
        $work->client(array(
            'p'=>array(
                'm'                 => 'tobresume',
                't'                 => 'insert',
                'id'                => $compress['basic']['id'],
                'is_deleted'        => $resume['is_deleted'],
                'work_experience'   => (int)$resume['work_experience'],
                'resume_updated_at' => $resume['resume_updated_at'],
                'updated_at'        => $resume['updated_at'],
                'name'             => $resume['name'],
                'extra'             => json_encode($compress)
            )
        ),false,false,true);
    }

    private function cache($id,$model){
        $worker = new Worker("icdc_refresh");
        $worker->client(array(
                "c"=>"Logic_refresh",
                "m"=>"cache",
                "p"=>array(
                    "id"=>$id,
                    "model"=>$model
                )
            ),true,false,true);
    }

    /**
     * [phone2cv description] 根据加密的phone_id 或 mail_id 去算法接口获取简历id
     * @author 赵付涛<futao.zhao@ifchange.com>
     * @return [type] [description]
     */
    public function phone2cv(){

    }

}