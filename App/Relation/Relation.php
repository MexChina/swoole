<?php
namespace Swoole\App\Relation;
use Swoole\Core\Log;
use Swoole\Core\Helper\System;
use Swoole\Core\Lib\Worker;
class Relation extends \Swoole\Core\App\Controller{

    public function init(){}

    /**
     * 公司开通关系链的接口  全为异步操作
     * $p=>[
     *      "company_id"=>73655348, //公司id
     *      "user_id"=>21432        //用户id
     *      "type"=>        1=>开通公司  2=>每日自动刷  3=>更新公司下面的用户，一般多为增加  4=>更新索引
     * ]
     */
    public function company(){
        $worker = new Worker("relation_chain_company");
        $worker->worker(function($request){

            $request = isset($request['p']) ? $request['p'] : '';
            if(!is_array($request)){
                Log::write_log("Parameter error!");
                return array('err_no'=>1,'err_msg'=>'Parameter error!','results'=>array());
            }

            if(empty($request['company_id']) || empty($request['user_id'])){
                Log::write_log("公司id或用户id不能为空!");
                return array('err_no'=>1,'err_msg'=>'公司id或用户id不能为空!','results'=>array());
            }

            $request['type'] = empty($request['type']) ? 1 : $request['type'];
            $this->task($request);
            return array('err_no'=>0,'err_msg'=>'success','results'=>array());
        });
    }

    /**
     * 获取人脉简历
     * $p=>[
     *      "user_id"=>21432,
     *      "resume_id"=>[73655348,73655349,73655350]
     * ]
     */
    public function resume(){
        $worker = new Worker("relation_chain_resume");
        $resume = new Logics\Resume();
        $worker->worker(function($request) use($resume){
            System::exec_time();
            $request = isset($request['p']) ? $request['p'] : '';
            if(!is_array($request)){
                Log::write_log("Parameter error!");
                return array('err_no'=>1,'err_msg'=>'Parameter error!','results'=>array());
            }

            if(!isset($request['resume_id']) || !isset($request['user_id'])){
                Log::write_log("简历id或用户id不存在！");
                return array('err_no'=>1,'err_msg'=>'简历id或用户id不存在！','results'=>array());
            }

            if(empty($request['resume_id']) || empty($request['user_id'])){
                Log::write_log("简历id或用户id不能为空！");
                return array('err_no'=>1,'err_msg'=>'简历id或用户id不能为空！','results'=>array());
            }

            $results = $resume->mget($request);
            return array('results'=>array('resume_id'=>$results));
        });
    }

    /**
     * 源数据更新后，注册公司数据更新
     */
    public function update(){
        $worker = new Worker("relation_chain_update");
        $resume = new Logics\Resume();
        $worker->worker(function($request) use($resume){

            $request = isset($request['p']) ? $request['p'] : '';
            if(!is_array($request)){
                Log::write_log("Parameter error!");
                return array('err_no'=>1,'err_msg'=>'Parameter error!','results'=>array());
            }

            if(!empty($request['token'])){
                if($request['token'] != md5('bi_update_relation_chain')){
                    Log::write_log("身份认证错误!");
                    return array('err_no'=>1,'err_msg'=>'身份认证错误!','results'=>array());
                }
                $request['type'] = 2;
            }else{
                return array('err_no'=>1,'err_msg'=>'Parameter error!','results'=>array());
            }
            $this->task($request);
            return array('err_no'=>0,'err_msg'=>'','results'=>'success');
        });
    }

    /**
     * 根据公司id获取人脉简历列表
     * p=>[
     *      company_id  //父公司
     *      type   1=同事   0校友   default=1
     *      page
     *      page_size
     *      total
     * ]
     */
    public function search(){
        $worker = new Worker("relation_chain_search");
        $resume = new Logics\Resume();
        $worker->worker(function($request) use($resume){

            $request = isset($request['p']) ? $request['p'] : '';
            if(!is_array($request)){
                Log::write_log("Parameter error!");
                return array('err_no'=>1,'err_msg'=>'Parameter error!','results'=>array());
            }

            if(!isset($request['company_id']) || empty($request['company_id'])){
                Log::write_log("公司id不能为空!");
                return array('err_no'=>1,'err_msg'=>'公司id不能为空!','results'=>array());
            }

            $page = isset($request['page']) ? (int)$request['page'] : 1;
            $page_size = isset($request['page_size']) ? (int)$request['page_size'] : 10;
            $type = isset($request['type']) ? (int)$request['type'] : 1;
            $resule_list = $resume->search($request['company_id'],$page,$page_size,$type);

            $results['page'] = $page;
            $results['page_size'] = $page_size;
            $results['total'] = $resule_list['total'];
            unset($resule_list['total']);
            $results['resume_id'] = $resule_list;
            return array('err_no'=>0,'err_msg'=>'','results'=>$results);
        });
    }

}