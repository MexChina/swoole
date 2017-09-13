<?php
namespace Swoole\App\Relation;
use Swoole\App\Relation\Logics;
class RelationTask{

    public function init(){}
    public function index($params){
        unset($params['from_worker_id']);

        switch ($params['type']){
            case 1: $this->add_company($params);break;
            case 2: $this->update_db();break;
            case 3: $this->update_company($params);break;
            case 4: $this->asyn_select($params);break;
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

    /** 当批量获取数据的时候，中间某个别数据从缓存中无法取到，则异步去数据库查询
     * @param $params
     */
    private function asyn_select($params){
        $company = new Logics\Company();
        $company->find($params);
    }

}