<?php

/** Request
 * 功能描述：根据用户id 获取简历详情
 */
require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/Logic_user_resume",
        "m"=>"get_resume_extra",
        "p"=>array(
            "id"=>18,   //用户id
        )
    )
);
echo client($param);

/** Response
{
    "response": {
        "err_no": 0,
        "err_msg": "",
        "results": {
            "basic": {
                "name": "",
                "gender": "F",
                "birth": "",
                "degree": "0",
                "school_name": "",
                "school_id": "0",
                "discipline_name": "",
                "discipline_id": "0",
                "corporation_name": "Coach",
                "corporation_id": "727285",
                "industry_name": "",
                "industry_id": "0",
                "architecture_name": "杭州万象城店",
                "architecture_id": "35196",
                "work_type": "店铺",
                "position_name": "",
                "position_id": "0",
                "station_name": "店铺",
                "station_id": "0",
                "internal_title_name": "",
                "internal_title_id": "0",
                "title_name": "",
                "title_id": "0",
                "start_time": "",
                "marital": "",
                "is_fertility": "U",
                "is_house": "U",
                "live_family": "",
                "account_province": "0",
                "account": "0",
                "address_province": "12",
                "address": "119",
                "expect_city_ids": "",
                "expect_city_names": "",
                "my_project": "",
                "focused_corporations": ",,",
                "focused_feelings": ",",
                "basic_salary": "0.0",
                "salary_month": "0.0",
                "bonus": "0.0",
                "annual_salary": "0.0",
                "expect_annual_salary": "0.0",
                "expect_annual_salary_to": "0.0",
                "management_experience": "U",
                "work_experience": "0",
                "current_status": "0",
                "is_core": "",
                "src": "1",
                "resume_cnt": "0",
                "remark": "",
                "customization": "",
                "created_at": "2012-11-21 10:03:16",
                "status": "NG",
                "updated_at": "2014-09-12 18:08:51",
                "is_add_v": "N",
                "locked_time": "0",
                "is_author": "U",
                "basic_salary_from": "0.0",
                "basic_salary_to": "0.0",
                "annual_salary_from": "0.0",
                "annual_salary_to": "0.0",
                "expect_annual_salary_from": "0.0",
                "contact_id": "13",
                "is_validate": "Y",
                "phone_id": "47EDCF972A33FCB22B1CDF975C60FB45",
                "expect_salary_from": "0.0",
                "expect_salary_to": "0.0",
                "expect_salary_month": "0.0",
                "expect_bonus": "0.0",
                "is_increased": "U",
                "is_active": "U",
                "actived_at": "2013-06-07 00:00:00",
                "contact_visited_at": "2013-06-07 00:00:00",
                "last_updated_at": "2014-09-12 18:08:51",
                "expect_work_at": "",
                "bonus_text": "",
                "options": "",
                "other_welfare": "",
                "category": "",
                "is_private": "0",
                "resume_updated_at": "2012-11-21 10:04:26"
            },
            "education": {
                "1415861102": {
                    "start_time": "",
                    "end_time": "",
                    "so_far": "N",
                    "school_name": "",
                    "discipline_name": "",
                    "degree": "0",
                    "id": "1415861102"
                }
            },
            "work": {
                "1415861103": {
                    "end_time": "",
                    "so_far": "Y",
                    "reporting_to": "",
                    "subordinates_count": "0",
                    "responsibilities": "",
                    "corporation_name": "Coach",
                    "start_time": "",
                    "industry_name": "",
                    "architecture_name": "杭州万象城店",
                    "position_name": "",
                    "title_name": "",
                    "management_experience": "U",
                    "work_type": "店铺",
                    "basic_salary_from": "0.0",
                    "basic_salary_to": "0.0",
                    "annual_salary_to": "0.0",
                    "annual_salary_from": "0.0",
                    "bonus": "0.0",
                    "salary_month": "0.0",
                    "id": "1415861103",
                    "industry_ids": "0",
                    "corporation_id": "0",
                    "title_category_id": "1",
                    "title_id": "0",
                    "cv_tag": [

                    ]
                }
            },
            "project": [

            ],
            "language": [

            ],
            "basic_industry": {
                "industry_id_0": "0",
                "industry_id_1": "0",
                "industry_id_2": "0",
                "industry_id_3": "0",
                "industry_id_4": "0",
                "updated_at": "",
                "0": {
                    "id": "1415861102",
                    "industry_id_0": "403",
                    "industry_id_1": "0",
                    "industry_id_2": "0",
                    "industry_id_3": "0",
                    "industry_id_4": "0",
                    "type": "1",
                    "created_at": "2013-08-05 16:15:58",
                    "updated_at": "2012-07-29 11:19:00"
                }
            },
            "resume": {
                "id": "13",
                "contact_id": "13",
                "has_phone": "Y",
                "attachment_ids": "",
                "resume_updated_at": "2012-11-21 10:04:26",
                "user_id": "5000001",
                "name": "",
                "resume_name": "",
                "industry_ids": "0",
                "work_experience": "0",
                "is_validate": "Y",
                "is_increased": "U",
                "is_private": "0",
                "is_processing": "0",
                "is_deleted": "Y",
                "created_at": "2014-11-13 14:45:01",
                "updated_at": "2014-09-12 18:08:51"
            },
            "contact": {
                "name": "",
                "phone": "(0571) 8970 5730",
                "phone_area": "2",
                "email": "",
                "qq": "",
                "tel": "",
                "sina": "",
                "ten": "",
                "msn": "",
                "wechat": "",
                "updated_at": "2014-11-13 14:45:02",
                "id": "13",
                "is_deleted": "N",
                "created_at": "2014-11-13 14:45:01"
            }
        }
    }
}
 */