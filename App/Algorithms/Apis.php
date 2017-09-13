<?php
namespace Swoole\App\Algorithm;
use Swoole\Core\Log;
use Swoole\Core\Lib\Worker;
class Api{

    /** 刷 cv_education、cv_degree  未完成
     * @param $educations array 教育信息
     * @param $work object gearmanclient
     * @return string sql 组装的update sql语句
     */
    public function cv_education($compress){
        $educations = $compress['education'];
        if(empty($educations)) return '';
        $p=array();
        foreach($educations as $education){
            $education_id = $education['id'];
            $school_name = isset($education['school_name']) ? $education['school_name'] : '';
            $discipline_name = isset($education['discipline_name']) ? $education['discipline_name'] : '';
            $degree = isset($education['degree']) ? $education['degree'] : 99;

            if(!empty($school_name) || !empty($discipline_name)){
                $p[$education_id] = json_encode(array('school'=>$school_name, 'major'=>$discipline_name, 'degree'=>$degree));
            }
        }
        if(empty($p)) return '';
        $work = new Worker("cv_education_service_online");
        $gearman_return = $work->client(array(
            'c'=>'CVEducation',
            'm'=>'query',
            'p'=>$p
        ),true);

        if(empty(!$gearman_return) || !is_array($gearman_return)){echo "will send email\n";
            $body = "<h1>Gearman信息：</h1><hr>";
            $body .= var_export($rs,true);
            $body .= "<h1>参数信息：</h1><hr>";
            $body .= var_export($input,true);
            $this->email(array(
                    'subject'=>'cv_education_service_online 接口调用失败!',
                    'content'=>$body,
                    //'cc_emails'=>'futao.zhao@ifchange.com'
                ));

            return '';
        }

        $results = $gearman_return['results'];

        if (isset($results['features'])) {
            foreach ($results['units'] as $education_id => $row) {
                $cv_education[$education_id] = array('school_id' => $row['school_id'], 'discipline_id' => $row['major_id']);
            }
            $cv_degree = (int) $results['features']['degree'];
        } else {
            foreach ($results as $education_id => $row) {
                $cv_education[$education_id] = array('school_id' => $row['school_id'], 'discipline_id' => $row['major_id']);
            }
        }
        return array(
                "cv_education"=>$cv_education,
                "cv_degree"=>$cv_degree
            );
    }


    public function cv_entity($compress){
        if(empty($compress['work'])) return '';
        
        $work_list = array();
        $cv_entity = array();
        foreach ($compress['work'] as $work) {
            $work_id = $work['id'];
            if (empty($work['position_name']) && empty($work['responsibilities'])) {
                $cv_entity[$work_id]['no_entity'] = '';
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
        $input = array(
            'c' => 'cv_entity',
            'm' => 'get_cv_entitys',
            'p' => array(
                'cv_id' => uniqid('cv_entity_'),
                'work_map' => $work_list
            ),
        );

        $work = new Worker("cv_entity_new_format");
        $rs = $work->client($input,true);

        if(isset($rs['err_no']) && $rs['err_no']==0){
            if (! empty($rs['results'])) {
                foreach ($rs['results'] as $work_id => $row) {
                    $cv_entity[$work_id] = $row;
                }
            }
            return $cv_entity;
        }else{
            $body = "<h1>Gearman信息：</h1><hr>";
            $body .= var_export($rs,true);
            $body .= "<h1>参数信息：</h1><hr>";
            $body .= var_export($input,true);
            $this->email(array(
                    'subject'=>'cv_entity_new_format 接口调用失败!',
                    'content'=>$body,
                    'cc_emails'=>'bei.liu@ifchange.com'
                ));
        }
    }

    /**
     * [cv_trade description]
     * @param  [type] $resume_id [description]
     * @param  [type] $compress  [description]
     * @return [type]            [description]
     */
    public function cv_trade($compress){
        if(empty($compress['work'])) return '';
        $input['cv_id'] = uniqid('cv_trade_');
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
            return $rs['result'];
        }else{
            $body = "<h1>Gearman信息：</h1><hr>";
            $body .= var_export($rs,true);
            $body .= "<h1>参数信息：</h1><hr>";
            $body .= var_export($input,true);
            $this->email(array(
                    'subject'=>'corp_tag 接口调用失败!',
                    'content'=>$body,
                    'cc_emails'=>'sinan.zhan@ifchange.com'
                ));
        }
    }

    /**
     * 邮件接口
     * @param  [type] $param array(
     *                       'subject'=>'',  //邮件标题
     *                       'body'=>'',     //邮件内容
     *                       'cc_emails'=>'' //抄送给谁  逗号分隔
     * )
     * @return [type]        [description]
     */
    public function email($param){
        $work = new Worker("jcsj_basic");echo "send mail\n";
        $res = $work->client(array(
                'c'  =>'sendmail',
                'm'=>'create',
                'p'  => array(
                    'subject'       => $param['subject']." ".date('Y-m-d H:i:s'),
                    'content'       => $param['body'],
                    'to_emails'     => "dongqing.shi@cheng95.com,jiqing.sun@cheng95.com",
                    'cc_emails'     => empty($param['cc_emails']) ? '' : $param['cc_emails'],
                ),
            ),true,false);  
    var_dump($res);
    }
}