<?php
/**
 * Created by PhpStorm.
 * User: qing
 * Date: 16-11-10
 * Time: 上午10:26
 */

$client= new GearmanClient();
$client->addServer("192.168.1.108",4730);


$param['request']['c'] = 'resumes/logic_resume';
$param['request']['m'] = 'save';
//335250518
$p_json = '{
    "training": [],
    "language": [{
        "name": "日语",
        "certificate": "",
        "level": "\t读写良好"
    }, {
        "name": "英语",
        "certificate": "",
        "level": "\t读写良好"
    }],
    "certificate": [],
    "work": [{
        "corporation_name": "车易拍（北京）汽车技术服务有限公司  （1年5个月）",
        "annual_salary_from": 300,
        "subordinates_count": 0,
        "position_name": "高级产品经理",
        "city": "",
        "scale": "",
        "architecture_name": "",
        "responsibilities": "工作描述：负责车易拍互联网金融部门下的各类产品设计于产品规划，其中包括：\n1.车商供应链金融及库存金融的整体产品规划，其中包括WEB端及移动端的前端页面设计以及后端管理设计；\n2.完成个人消费贷款购车的前端产品对接设计；\n3.金融产品的渠道管理，分润管理，销售管理，以及相关的清结算管理\n4.金融延保产品的设计，其中包括直销，代理销售的产品流程，用户操作流程以及对应的销售管理及清结算管理\n5.金融产品调研及落地的推广，宣传与培训；\n6.优化公司现有支付系统的出款及钱包功能模块；\n7.分析产品运营数据，优化、调整系统以支持业务发展，把握用户需求和动向，持续改进产品。",
        "basic_salary_to": 25,
        "so_far": "Y",
        "bonus": "",
        "start_time": "2014年10月",
        "salary_month": "",
        "management_experience": "N",
        "station_name": "",
        "industry_name": "",
        "reporting_to": "",
        "corporation_type": "",
        "annual_salary_to": 300,
        "corporation_desc": "",
        "basic_salary_from": 25,
        "end_time": ""
    }, {
        "corporation_name": "贵州银行北京研发中心  （11个月）",
        "annual_salary_from": 180,
        "subordinates_count": 0,
        "position_name": "电商产品经理",
        "city": "",
        "scale": "",
        "architecture_name": "",
        "responsibilities": "工作描述：1.负责贵银电商整体B端用户的功能模块规划，具体包括，商户申请，商户授信管理，商户产品管理，商户订单管理，商户结算管理，商户借贷管理。\n2.设计B端用户订单中心，支付平台清结算产品模型 ；\n3.负责B端用户供应链金融产品的设计；\n4.方案讲解，与技术及设计团队进行对接 ；\n5.跟踪产品开发进度，完成产品的开发、测试、版本管理、评审发布上线等相关工作；\n6.撰写和完善与产品有关的资料、方案等相关文档并组织产品培训。",
        "basic_salary_to": 25,
        "so_far": "N",
        "bonus": "",
        "start_time": "2013年10月",
        "salary_month": "",
        "management_experience": "N",
        "station_name": "",
        "industry_name": "",
        "reporting_to": "",
        "corporation_type": "",
        "annual_salary_to": 300,
        "corporation_desc": "",
        "basic_salary_from": 15,
        "end_time": "2014年09月"
    }, {
        "corporation_name": "黑龙江路捷经贸有限公司  （1年）",
        "annual_salary_from": 120,
        "subordinates_count": 0,
        "position_name": "数据分析产品经理",
        "city": "",
        "scale": "",
        "architecture_name": "",
        "responsibilities": "工作描述：1.负责资生堂、汉高在京东、亚马逊、1号店，苏宁平台的品牌推广及促销方案；\n2.分析各大电商销售数据，设计，调整并制定对应的营销方案；\n3.根据公司经营目标与战略，做好产品生命周期管理；\n4.通过数据分析，对新品，畅销品，套装品设计宣传及促销力度，优化改进旗舰店产品曝光率，提高品牌宣传；\n5.根据产品活动及销售额，分析市场投入占比并优化相关活动及品牌方案；\n6.与厂家及电商平台紧密配合，完成销售目标。\n工作业绩：京东618大促中，资生堂洗护系列产品销售额突破4000万，全年资生堂销售额突破1亿",
        "basic_salary_to": 15,
        "so_far": "N",
        "bonus": "",
        "start_time": "2012年09月",
        "salary_month": "",
        "management_experience": "N",
        "station_name": "",
        "industry_name": "",
        "reporting_to": "",
        "corporation_type": "",
        "annual_salary_to": 180,
        "corporation_desc": "",
        "basic_salary_from": 10,
        "end_time": "2013年09月"
    }, {
        "corporation_name": "北京多多禧科技有限公司  （2年4个月）",
        "annual_salary_from": 96,
        "subordinates_count": 0,
        "position_name": "互联网产品经理\/主管",
        "city": "",
        "scale": "",
        "architecture_name": "",
        "responsibilities": "工作描述：1.负责在线服装B2C网站的整体网站规划，其中包括产品设计，市场调研，市场分析等工作，并确定网站的市场定位及营销策略，带领团队完成网站的整体搭建；\n2.负责B2C核心模块的设计及定制软件的创意设计；\n3.负责网站整体设计风格的定义和视觉呈现效果的把控；\n4.负责产品上线后改进，组织产品测试，进行BUG跟踪、收集改进意见、提供改进方案 ，引导用户熟悉使用服装在线设计产品；\n5.负责产品开发过程中的进度管理与跟踪，分析用户行为从而实现对网站产品的调整和优化等工作。\n主要搭建B2C平台中的核心功能模块，具体为：\n1.CRM管理模块\n2.供应链采购模块\n3.分销模块\n4.结算模块\n5.订单管理模块",
        "basic_salary_to": 10,
        "so_far": "N",
        "bonus": "",
        "start_time": "2009年08月",
        "salary_month": "",
        "management_experience": "N",
        "station_name": "",
        "industry_name": "",
        "reporting_to": "",
        "corporation_type": "",
        "annual_salary_to": 120,
        "corporation_desc": "",
        "basic_salary_from": 8,
        "end_time": "2011年12月"
    }, {
        "corporation_name": "北京金宏蓝科技有限公司  （4年）",
        "annual_salary_from": 96,
        "subordinates_count": 0,
        "position_name": "客户经理",
        "city": "",
        "scale": "",
        "architecture_name": "",
        "responsibilities": "工作描述：1.挖掘客户对互联网及相关应用系统的潜在需求，与客户沟通，了解项目的整体需求，制定并形成可执行的项目方案；\n2.跟进项目，即时反馈阶段性的成果，合理更改客户提出的相关需求；\n3.制定项目开发计划文档，量化任务，并合理分配给相应的人员；\n4.负责项目文案的撰写，具体包括立项目标，功能模块介绍，开发人员配置，项目开发周期及开发预算等内容；\n5.产品的培训及使用。\n工作业绩：\n在工作期间，完成过北京银行门户网站，耐克B2C销售平台，索尼官方网站，金象大药房电商平台，博洛尼家具平台，长久汽车车辆物流管理，Hyperion等公司的互联网建设咨询工作",
        "basic_salary_to": 10,
        "so_far": "N",
        "bonus": "",
        "start_time": "2005年08月",
        "salary_month": "",
        "management_experience": "N",
        "station_name": "",
        "industry_name": "",
        "reporting_to": "",
        "corporation_type": "",
        "annual_salary_to": 120,
        "corporation_desc": "",
        "basic_salary_from": 8,
        "end_time": "2009年08月"
    }],
    "project": [],
    "source": {
        "src": 100,
        "src_no": "",
        "user_id": "",
        "is_overwrite": 0
    },
    "contact": {
        "qq": "",
        "tel": "13521669639",
        "ten": "",
        "msn": "",
        "phone": "13521669639",
        "wechat": "",
        "phone_area": 1,
        "email": "guanhong9639@126.com",
        "sina": ""
    },
    "basic": {
        "birth": "1983年11月01日",
        "account_province": "6",
        "updated_at": "2016-03-08 00:00:00",
        "marital": "Y",
        "work_experience": 11,
        "expect_salary_month": "",
        "is_house": "U",
        "is_validate": "U",
        "degree": 99,
        "expect_salary_to": 25,
        "focused_corporations": "",
        "card": "152524198311160044",
        "expect_annual_salary_from": 300,
        "resume_name": "",
        "interests": "",
        "not_expect_corporation_status": 0,
        "expect_position_name": "互联网产品经理 主管",
        "political_status": "",
        "expect_annual_salary_to": 300,
        "other_info": "",
        "expect_city_ids": "2",
        "expect_work_at": "",
        "self_remark": "10年产品工作经验,对电商,互联网金融,及第三方支付具有相当的了解,本人具有很强的沟通能力及团队协调能力,从对产品的市场分析,产品需求分析,产品的具体实现及后期的产品培训有足够的经验。 \r\n",
        "address": "2",
        "not_expect_corporation_name": "",
        "my_project": "",
        "not_expect_corporation_ids": "",
        "account": "6",
        "name": "关虹",
        "project_info": "",
        "is_fertility": "U",
        "gender": "U",
        "age": 34,
        "focused_feelings": "",
        "current_status": 2,
        "expect_city_names": "北京",
        "expect_industry_name": "互联网 电子商务 教育 培训",
        "expect_salary_from": 25,
        "overseas": "N",
        "expect_bonus": "",
        "expect_type": "",
        "live_family": "U",
        "address_province": "2"
    },
    "skill": [],
    "education": [{
        "so_far": "N",
        "discipline_desc": "",
        "degree": 1,
        "start_time": "2001年09月",
        "school_name": "内蒙古科技大学",
        "end_time": "2005年07月",
        "discipline_name": "计算机科学与技术",
        "is_entrance": "Y"
    }, {
        "so_far": "N",
        "discipline_desc": "",
        "degree": 90,
        "start_time": "1997年09月",
        "school_name": "内蒙古职业技术学院",
        "end_time": "2001年07月",
        "discipline_name": "英语",
        "is_entrance": "Y"
    }],
    "user_tag": [],
    "setting": {
        "salary_is_visible": 1
    }
}
';

$param['request']['p'] = json_decode($p_json,true);
$param['header']['log_id'] = time();
$send_data = json_encode($param);

$packedResponse = $client->doNormal("icdc_basic", $send_data);

$returnCode = $client->returnCode();
if (GEARMAN_SUCCESS === $returnCode) {
    $response = json_decode($packedResponse,true);
    if (0 == $response["response"]["err_no"]) {
        var_dump($response["response"]["results"]);
    } else {
        echo "调用失败", PHP_EOL, "错误码:", $response["response"]["err_no"], PHP_EOL, "错误信息:", $response["response"]["err_msg"], PHP_EOL;
    }
} else {
    echo "调用失败", PHP_EOL, "错误码:", $returnCode, PHP_EOL, "错误信息:", $client->error(), PHP_EOL;
}