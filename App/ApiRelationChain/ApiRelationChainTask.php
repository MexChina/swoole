<?php
namespace Swoole\App\ApiRelationChain;
use Swoole\App\ApiRelationChain\Logics;
class ApiRelationChainTask{

    public function init(){}
    public function index($params){
        unset($params['from_worker_id']);

        switch ($params['type']){
            case 1: $this->add_company($params);break;
            case 2: $this->update_db();break;
            case 3: $this->update_company($params);break;
            case 4: $this->update_indexes();break;
            default: echo 'sss';break;
        }
    }

    /** 开通公司
     * @param $params
     */
    private function add_company($params){
        $company = new Logics\Company();
        $company->index($params);
    }

    /**
     * 更新刷库，每日自动刷
     */
    private function update_db(){
        $company = new Logics\Company();
        $company->update();
    }

    /** 更新公司下面的用户，一般多为增加
     * @param $params
     */
    private function update_company($params){
        $company = new Logics\Company();
        $company->updateuc($params['company_id'],$params['user_id']);
    }

    /**
     * 更新索引
     */
    private function update_indexes(){
        $company = new Logics\Company();
        $company->del_indexes();
        $company->add_indexes();
    }

}