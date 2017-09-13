<?php
/**
 * Created by PhpStorm.
 * User: qing
 * Date: 16-12-26
 * Time: 上午11:03
 */
namespace Swoole\App\IcdcQueue;
use Swoole\Core\Log;
class IcdcQueueTask{
    public function init(){}
    public function index($params){
        unset($params['from_worker_id']);
        $result = $params['client']->client(array('id'=>$params['resume_id'],'is_throw'=>false));
        Log::write_log($params['resume_id']."\t".json_encode($result));
    }
}