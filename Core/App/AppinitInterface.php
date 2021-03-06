<?php

namespace Swoole\Core\App;

/**
 *
 * @author xuelin.zhou
 */
interface AppinitInterface {

    /**
     * 初始化缓存
     *
     * @return viod
     */
    public function init_cache();

    /**
     * 更具需求动态设置worker进程数
     *
     * @return init 需要开启的worker进程的数量，如果为空，则使用配置文件的worker_num
     */
    public function get_worker_number();

    /**
     * 进程初始化，进程分发，在系统workstart的时候调用
     *
     * @return viod
     */
    public function worker_init();

    /**
     * 定时任务初始化
     *
     * @return viod
     */
    public function timer_init();

    /**
     * 任务进程初始化
     *
     * @return viod
     */
    public function tasker_init();

    /**
     * 进程关闭服务时清理工作
     *
     * @return viod
     */
    public function worker_stop();

    /**
     * 服务关闭服务时清理工作
     *
     * @return viod
     */
    public function server_close();
}
