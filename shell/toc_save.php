<?php

require_once './public.php';
$param=array(
    "hearder"=>head(),
    "request"=>array(
        "w"=>"icdc_basic",
        "c"=>"resumes/logic_resume",
        "m"=>"save",
        "p"=>array (
    'source' => 
    array (
      'src' => 99,
      'user_id' => 47074,
      'is_merge' => 1,
      'parsed_data' => 
      array (
        'work' => 
        array (
          1469416708 => 
          array (
            'start_time' => '2015年07月',
            'end_time' => NULL,
            'corporation_name' => '腾讯科技有限公司',
            'position_name' => '测试工程师',
            'responsibilities' => '很好dadadsaddadadadadadsadasad',
            'id' => '1469416708',
            'so_far' => 'Y',
          ),
        ),
        'setting' => 
        array (
          'salary_is_visible' => '1',
        ),
        'basic' => 
        array (
        ),
      ),
    ),
  )
    )
);
echo client($param);