<?php
/**
 * 算法接口参数获取  传入参数，组装成接口需要的参数
 *
 * 凡是返回  false 的 都不要再参与接口调用了
 */
namespace Swoole\App\Kafka;
class Algorithm{


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
        if(empty($educations)) return false;
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
        return empty($p) ? false : $p;
    }


    /**************************************************************
     * 简历职级标签
     * @param  array $compress resumes_extras.compress
     * @author 刘治<zhi.liu@ifchange.com>
     * @return string
     * 2017-5-17 检
     **************************************************************/
    public function cv_title($compress){
        if(empty($compress['work'])) return flase;

        $position_names = array();
        foreach ($compress['work'] as $work) {
            if (empty($work['position_name'])) continue;
            $work_id = (string)$work['id'];
            $position_names["$work_id"] = $work['position_name'];
        }

        return empty($position_names) ? false : $position_names;
    }
    /**************************************************************
     * 语言识别
     * @param  array $compress resumes_extras.compress
     * @author 刘治<zhi.liu@ifchange.com>
     * @return string
     * 2017-5-17 检
     **************************************************************/
    public function cv_language($compress){
        if (empty($compress['language'])) return flase;
        $cvs['id'] = $compress['basic']['id'];
        foreach ($compress['language'] as $language) {
            $cvs["language"][$language["id"]] = [
                "certificate" => $language["certificate"],
                "name"        => $language["name"],
                "level"       => $language["level"]
            ];
        }
        return ['cvs' => [$cvs]];
    }
    
    /**************************************************************
     * 工作年限(单位:月)
     * @param  array $compress resumes_extras.compress
     * @author 刘治<zhi.liu@ifchange.com>
     * @return int 存放resumes表
     * 2017-5-17 检
     **************************************************************/
    public function cv_workyear($compress){
        if (empty($compress['work'])) return false;
        return json_encode(array(
                    'work'=>$compress['work'],
                    'education'=>$compress['education'],
                ));
    }

    /*************************************************************
     * [cv_feature description]
     * @param  [type] $compress [description]
     * @author 姚程<cheng.yao@ifchange.com>
     * @return [type]           [description]
     *************************************************************/
    public function cv_feature($compress){
        if (empty($compress['work']) && empty($compress['project'])) {
            return false;
        }
        return ['cv_id'   => $compress['basic']['id'],'cv_json' => json_encode($compress)];
    }

    /**
     * 学校识别接口，根据学校名获取学校id
     * @param  [type]  $school [description]
     * @param  string  $major  [description]
     * @param  integer $degree [description]
     * @return [type]          [description]
     */
    public function school($school, $major='', $degree=1){
        if(empty($school)) return false;
        return json_encode(array('school'=>$school, 'major'=>$major, 'degree'=>$degree));
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
        return array(
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
                );
    }

    /*************************************************************
     * 简历职能标签
     * @param  array $compress resumes_extras.compress
     * @author 刘贝<bei.liu@ifchange.com>
     * @return string  智能标签数据
     * 2017-5-17 检
     *************************************************************/
    public function cv_tag($compress){
        if(empty($compress['work'])) return false;
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
        return empty($work_list) ? false : array('cv_id' => $compress['basic']['id'],'work_map' => $work_list);
    }

    /*************************************************************
     * 职能技能标签
     * @param  array $compress resumes_extras.compress
     * @author 姚程<cheng.yao@ifchange.com>
     * @return string  智能标签数据
     * 2017-5-17 检
     *************************************************************/
    public function cv_entity($compress){
        if(empty($compress['work'])) return false;
        
        $work_list = array();
        $cv_entity = array();
        foreach ($compress['work'] as $work){
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
        return empty($work_list) ? false : array('cv_id' => $compress['basic']['id'],'work_map' => $work_list);
    }

    /*************************************************************
     * 公司识别接口 
     * @param  array $compress  resumes_extras.compress
     * @author 战思南<sinan.zhan@ifchange.com>
     * @return string  公司识别接口 
     * 2017-5-17 检
     *************************************************************/
    public function cv_trade($compress){
        if(empty($compress['work'])) return false;
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
        return empty($input['work_list']) ? false : $input;
    }

    /**
     * 新增简历的算法离职率 离线的
     * @return [type] [description]
     */
    public function cv_resign($compress,$deliver=array(),$history=''){
        $deliver = empty($deliver) ? array('days7_deliver_num'=>0,'days7_update_num'=>0) : $deliver;
        $resume_id = $compress['basic']['id'];
        return array(
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
                );
    }


    /**
     * [CVUniq description]
     * @author 赵付涛<futao.zhao@ifchange.com>
     * @param [type] $compress [description]
     */
    public function CVUniq($compress){
        return array('-1'=>json_encode($compress,JSON_UNESCAPED_UNICODE));
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
        return array(
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
                    'cv_json'         => json_encode($compress,JSON_UNESCAPED_UNICODE)
                )
            );
    }

    /**
     * 异步通知es服务
     * @param array $compress resumes_extras.compress
     * @param array $resume   resumes
     */
    public function EsServers($compress,$resume){
        return array(
                'm'                 => 'tobresume',
                't'                 => 'insert',
                'id'                => $compress['basic']['id'],
                'is_deleted'        => $resume['is_deleted'],
                'work_experience'   => (int)$resume['work_experience'],
                'resume_updated_at' => $resume['resume_updated_at'],
                'updated_at'        => $resume['updated_at'],
                'name'             => $resume['name'],
                'extra'             => json_encode($compress,JSON_UNESCAPED_UNICODE)
            );
    }

    private function cache($id,$model){
        return array(
                    "id"=>$id,
                    "model"=>$model
                );
    }
}