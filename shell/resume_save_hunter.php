<?php

/** Request *****************************************************************
 * 
 * 功能描述：猎头简历上传
 * 
 * 逻辑步骤：
 * 1、
 * *************************************************************************
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/logic_resume",
        "m"=>"save",
        "p"=>array(
            "parsed_data"=>array (
              'source' => array (
                'src' => 89,                            //来源标识符
              ),
              'basic' => array (
                'updated_at' => '2017-03-09 00:00:00',
                'text' => '',
                'resume_name' => '资料开发 齐燕',
                'name' => '',
                'gender' => 'F',
                'birth' => '1988年5月',
                'work_experience' => 7,
                'marital' => 'N',
                'degree' => '1',
                'address' => '321',
                'account' => '321',
                'political_status' => '中共党员(含预备党员)',
                'self_remark' => '项目中担任多款手册owner，在多版本多手册交付压力下能按时保量完成任务。',
                'expect_city_names' => '西安',
                'expect_salary_from' => '',
                'expect_salary_to' => '',
                'current_status' => '1',
                'expect_work_at' => '立即上岗',
                'expect_type' => '全职',
                'expect_position_name' => '软件/互联网开发/系统集成,硬件开发,IT质量管理/测试/配置管理,质量管理/安全防护,咨询/顾问/调研/数据分析',
                'expect_industry_name' => '计算机软件,IT服务(系统/数据/维护),通信/电信/网络设备,网络游戏,检验/检测/认证,外包服务,专业服务/咨询(财会/法律/人力资源等)',
                'avatar_mark' => 'http://mypics.zhaopin.com/pic/2013/2/26/3DC87B70371447BCA8A090B23797BB29.jpg',
                'expect_city_ids' => '321',
                'id'=>'',                   //如果是新增  则不传  如果是修改 传入此id
              ),
              'contact' => array (
                'tel' => '',            //电话
                'email' => '',          //email
                'phone'=>'',            //手机号码
              ),
              'work' =>  array (
                0 => array (
                  'start_time' => '2016年03月',
                  'end_time' => '',
                  'so_far' => 'Y',
                  'corporation_name' => '软通动力',
                  'position_name' => '资料工程师',
                  'basic_salary_from' => '8.0',
                  'basic_salary_to' => '10.0',
                  'industry_name' => 'IT服务(系统/数据/维护)',
                  'corporation_type' => '',
                  'scale' => '',
                  'reporting_to' => '',
                  'subordinates_count' => '',
                  'annual_salary_from' => '',
                  'annual_salary_to' => '',
                  'responsibilities' => '工作描述：
    主要负责uwin解决方案版本资料开发，业务宣传海报，社区维护，用户运营等工作。',
                ),
                1 => array (
                  'start_time' => '2013年11月',
                  'end_time' => '2017年02月',
                  'so_far' => 'N',
                  'corporation_name' => '中软集团',
                  'position_name' => '接入网资料',
                  'basic_salary_from' => '4.0',
                  'basic_salary_to' => '6.0',
                  'industry_name' => '计算机软件',
                  'corporation_type' => '上市公司',
                  'scale' => '1000-9999人',
                  'reporting_to' => '',
                  'subordinates_count' => '',
                  'annual_salary_from' => '',
                  'annual_salary_to' => '',
                  'responsibilities' => '工作描述：
    做产品对应的资料开发，主要产品为华为接入网的通讯设备。跟踪手册全流程，从特性分解到迭代到tr6到ga，细化具体工作，安排手册具体开发过程。重点验证命令，执行工具扫描等。',
                ),
                2 => array (
                  'start_time' => '2012年10月',
                  'end_time' => '2013年09月',
                  'so_far' => 'N',
                  'corporation_name' => '昌硕科技(上海)有限公司',
                  'position_name' => '品质技术确认部',
                  'basic_salary_from' => '6.0',
                  'basic_salary_to' => '8.0',
                  'industry_name' => '通信/电信/网络设备',
                  'corporation_type' => '合资',
                  'scale' => '10000人以上',
                  'reporting_to' => '',
                  'subordinates_count' => '',
                  'annual_salary_from' => '',
                  'annual_salary_to' => '',
                  'responsibilities' => '工作描述：
    根据test schedule和test plan安排分配各部分详细测试，参与测试并负责督导测试进度，发现问题立即开立bug并与研发协商验证解决问题，并负责该产品在后续量产销售中的软硬件更新测试，并应对产品的客诉部分提供验证解决的技术支持等；＼r＼n测试内容主要为笔记本无线，蓝牙等的一些基本function测试，性能测试等，.以Test case为基础，自由测试为主进行挖掘系统隐藏问题，提交相关的Bug及其log文件，同时及时与研发进行沟通，追踪难以复现Bug,',
                ),
                3 => array (
                  'start_time' => '2010年05月',
                  'end_time' => '2012年09月',
                  'so_far' => 'N',
                  'corporation_name' => '陕西省交通厅信息中心',
                  'position_name' => '网络运维管理科',
                  'basic_salary_from' => '2.0',
                  'basic_salary_to' => '4.0',
                  'industry_name' => '计算机硬件',
                  'corporation_type' => '事业单位',
                  'scale' => '10000人以上',
                  'reporting_to' => '',
                  'subordinates_count' => '',
                  'annual_salary_from' => '',
                  'annual_salary_to' => '',
                  'responsibilities' => '工作描述：
    负责大楼内用户正常上网，基本电脑故障修理，维护机房设备，定时上下线更新，数据备份等工作。',
                ),
              ),
              'project' => array (
                0 => array (
                  'start_time' => '2012年12月',
                  'end_time' => '2013年02月',
                  'so_far' => 'N',
                  'name' => 'Toshiba VGS/VGF/VGFT等机台',
                  'soft_env' => '',
                  'develop_tool' => '',
                  'describe' => 'Toshiba新机种上市前测试，基于sharkbay,chief river平台。',
                  'responsibilities' => '根据test schedule和test plan安排分配各部分详细测试，参与测试并负责督导测试进度，发现问题立即开立bug并与研发协商验证解决问题，并负责该产品在后续量产销售中的软硬件更新测试，并应对产品的客诉部分提供验证解决的技术支持等；负责无线，WiDi，蓝牙等模块功能测试，完成各项Testcase，
    寻找问题并在Bug库中提交，同时提供准确的各种Log。',
                ),
                1 => array (
                  'start_time' => '2012年11月',
                  'end_time' => '2012年12月',
                  'so_far' => 'N',
                  'name' => 'Asus P55va ER/PR阶段测试',
                  'soft_env' => '',
                  'develop_tool' => '',
                  'describe' => 'Asus 新产品上线前测试',
                  'responsibilities' => '负责无线，蓝牙等模块功能测试，完成各项Testcase，
    寻找问题并在Bug库中提交，同时提供准确的各种Log。',
                ),
              ),
              'education' => array (
                0 => array (
                  'start_time' => '2006年09月',
                  'end_time' => '2010年07月',
                  'so_far' => 'N',
                  'school_name' => '咸阳师范学院',
                  'discipline_name' => '计算机科学与技术专业（网络方向）',
                  'degree' => '1',
                ),
              ),
              'certificate' => array (
                0 => array (
                  'start_time' => '2008年12月',
                  'name' => '大学英语四级',
                  'description' => '',
                ),
                1 => array (
                  'start_time' => '2009年10月',
                  'name' => '全国计算机软件技术资格与水平考试',
                  'description' => '网络工程师(中级)',
                ),
              ),
              'training' => array (),
              'language' => array (
                0 => array (
                  'name' => '英语',
                  'level' => '良好',
                ),
              ),
              'skill' =>  array (),
        )     
        )
    )
);
echo client($param);
 
