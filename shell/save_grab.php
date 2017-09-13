<?php

/** Request
 * 功能描述：简历操作 算法字段更新
 */
require_once './public.php';
$json='{
    "request": {
        "c": "resumes/logic_resume",
        "m": "save",
        "p": {
            "source":{
                "src":"1",
                "src_no":"xxxxx",
                "parsed_data":{
                    "training": [],
                    "language": [
                        {
                            "name": "英语",
                            "certificate": "",
                            "level": "良好"
                        }
                    ],
                    "certificate": [],
                    "work": [
                        {
                            "corporation_name": "长城宽带网络服务有限公司",
                            "annual_salary_from": "",
                            "subordinates_count": 0,
                            "position_name": "营业厅文员",
                            "city": "",
                            "scale": "",
                            "architecture_name": "财务部",
                            "responsibilities": "工作职责：\n工作经历：\n一）2005/10-2007/4：上海晨隆国际贸易有限公司           职位：销售部助理兼采购\n岗位描述：1、负责公司销售部销售业绩的统计工作，完成公司业绩日、月、年报表；\n2、负责公司销售部客户相关资料的管理及维护；\n3、负责公司销售记录相关单据的归类整理和存档。\n4、完成公司所需的面料及其辅料等采购工作及供应商的开发和维护。\n二）2009/3-2010/9：上海福源智业投资集团有限公司          职位：质保文员\n岗位描述：1. 根据质检员的报告，协助经理制作质量保证书；\n          2. 报验产品的送达及与实验室的联系\n3. 协助部门经理做好整理质保文件，与其他部门的衔接工作\n4.通过3个月时间就能对公司的材料和产品熟悉，能够独立操作得到了大家的一致好评！\n三）2010/10-至今：上海长城宽带网络服务有限公司            职位：营业厅文员\n岗位描述：1.负责所在社区客户服务中心（营业网点）的营业收入、用户信息系统录入等财务相关工作； \n2.负责用户日常网络维护要求的调度工作； \n3.接听客户热线，及日常营业厅用户的接待、业务受理等工作。",
                            "basic_salary_to": "",
                            "so_far": "Y",
                            "bonus": "",
                            "start_time": "2010年10月",
                            "salary_month": "",
                            "management_experience": "N",
                            "station_name": "",
                            "industry_name": "通信/电信运营、增值服务",
                            "reporting_to": "",
                            "corporation_type": "",
                            "annual_salary_to": "",
                            "corporation_desc": "",
                            "basic_salary_from": "",
                            "end_time": ""
                        }
                    ],
                    "project": [],
                    "source": {
                        "src": "1",
                        "src_no": "xxxxx"
                    },
                    "contact": {
                        "qq": "",
                        "tel": "",
                        "ten": "",
                        "msn": "",
                        "phone": 17050072145,
                        "wechat": "",
                        "phone_area": 1,
                        "email": "sh-caoxiulan@163.com",
                        "sina": "",
                        "name": "曹秀兰"
                    },
                    "basic": {
                        "birth": "1982年10月23日",
                        "account_province": "",
                        "updated_at": "",
                        "marital": "Y",
                        "work_experience": 7,
                        "expect_salary_month": "",
                        "is_house": "U",
                        "is_validate": "U",
                        "degree": 99,
                        "expect_salary_to": "4.5",
                        "focused_corporations": "",
                        "card": "",
                        "expect_annual_salary_from": "",
                        "resume_name": "",
                        "interests": "",
                        "expect_position_name": "",
                        "political_status": "",
                        "expect_annual_salary_to": "",
                        "other_info": "",
                        "expect_city_ids": "105",
                        "expect_work_at": "待定",
                        "self_remark": "本人工作认真负责，积极主动，能吃苦耐劳，待人热情，做事有主见，对新事物的敏感度高，具有较强的适应能力、应变能力、组织能力。熟悉Office 软件操作。有会计基础。适合从事文书管理类工作。\n期望薪资：4000元",
                        "address": "105",
                        "not_expect_corporation_name": "",
                        "my_project": "",
                        "not_expect_corporation_ids": "",
                        "account": "105",
                        "name": "曹秀兰@",
                        "project_info": "",
                        "is_fertility": "U",
                        "gender": "F",
                        "focused_feelings": "",
                        "current_status": "1",
                        "expect_city_names": "上海",
                        "expect_industry_name": "",
                        "expect_salary_from": "4.0",
                        "overseas": "N",
                        "expect_bonus": "",
                        "expect_type": "全职",
                        "live_family": "U",
                        "address_province": ""
                    },
                    "skill": [],
                    "education": [
                        {
                            "so_far": "N",
                            "discipline_desc": "从系统的角度讲，电子商务网站可以看作一个开放的信息管理系统。作为网站，这个系统要想得到全面、彻底实施，粗略分一下的话，大概需要四个层次：\n<br><br>　　第一层，电子商务建立在网络硬件层的基础上。在这一层次，需要了解一般计算机、服务器、交换机、路由器及其它网络设备的功能。\n<br>　　第二层，电子商务实施的软件平台。在这一层次，涉及服务器端操作系统、数据库、安全、电子商务系统的选择、安装、调试和维护。比如在微软的windows操作平台上，服务器操作系统目前有server2003;数据库有SQLserver;电子商务应用有commerce server、content management server;安全保证有ISA server 等等。\n<br>    第三层，电子商务应用层。在这一层次，涉及商业逻辑、网站产品的设计、开发，比如界面设计，可能就需要涉及html、css、xml、脚本语言方面的知识，以及Dreamweaver，Photoshop等网页设计和图像处理方面的技能;或网络应用程序的开发。\n<br>    第四层，电子商务运营、管理层，在这一层次，涉及各类商务支持人员，如客户服务、市场、贸易、物流和销售等诸多方面。",
                            "degree": 4,
                            "start_time": "2001年9月",
                            "school_name": "上海商业职业技术学院",
                            "end_time": "2004年7月",
                            "discipline_name": "电子商务",
                            "is_entrance": "Y"
                        }
                    ],
                    "user_tag": [
                        ""
                    ],
                    "setting": {
                        "salary_is_visible": 1
                    }
                }
                },
                "is_overwrite":"0"
            }
            
    },
    "header": {
        "client_ip": "192.168.1.228",
        "local_ip": "192.168.1.228",
        "log_id": "3092530397",
        "user_ip": "192.168.1.228",
        "ip": "192.168.1.196",
        "version": 1,
        "signid": "3092530545",
        "provider": "2b",
        "auth": "2b",
        "product_name": "tob_web",
        "session_id": "em7112oigorvjktdtdbkmfmir5",
        "uid": "1",
        "uname": "root",
        "appid": "5"
    }
}';
$param = json_decode($json,true);
$param['request']['w']='icdc_basic';
echo client($param);

/** Response
 *{
 *   "response": {
 *       "err_no": 0,
 *       "err_msg": "",
 *       "results": "Refresh success....."
 *   }
 *}
 */
