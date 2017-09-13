<?php

$kafka_config=array(
    'id'=>'201707131609',
    'storm_task'=>'refresh_cv_tag',
    'topic'=>'resume_arth_test',
    'worker'=>"102",
     //'cv_trade'=>'',
    // 'cv_title'=>'',
    'cv_tag'=>"100",
    // 'cv_entity'=>'',
    // 'cv_education'=>'',
    // 'cv_feature',
    // 'cv_workyear'=>'',
    // 'cv_quality'=>'',
    // 'cv_language'=>'',
    // 'cv_resign'=>''
);

$rk = new \RdKafka\Producer();
$rk->setLogLevel(LOG_DEBUG);
$rk->addBrokers('hadoop1:9092,hadoop2:9092,hadoop3:9092,hadoop4:9092,hadoop5:9092,hadoop7:9092');
$kafka_config = json_encode($this->kafka_config);
$config_topic = $rk->newTopic('flume-test');
$config_topic->produce(RD_KAFKA_PARTITION_UA, 0, $kafka_config);
$rk->poll(0);
