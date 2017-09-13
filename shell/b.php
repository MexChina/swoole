<?php

function merge_items($deleted_items, &$persisted_items) {
        // 把要删除的简历的工作经历按时间从最远到最近升序排列
    $deleted_items = array_reverse($deleted_items);
    $last_item = current($persisted_items);
var_dump($last_item);
    $is_newer = false;
    if ('' == $last_item['end_time'] || '至今' === $last_item['end_time']) { // 最近一段经历没有截止日期
 $last_key = key($persisted_items);
        foreach ($deleted_items as $deleted_item) {
            if ($is_newer) {
                $persisted_items[] = $deleted_item;
                continue;
            }
            if (0 < strcmp($deleted_item['start_time'], $last_item['start_time'])) { // 用最近一段经历的开始时间比
                $persisted_items[$last_key]['end_time'] = $deleted_item['start_time'];
                $persisted_items[] = $deleted_item;
                $is_newer = true;
            }
        }
    } else {
        foreach ($deleted_items as $deleted_item) {
            if ($is_newer) {
                $persisted_items[] = $deleted_item;echo '111111111';
                continue;
            }
	echo "s",strcmp($deleted_item['start_time'], $last_item['end_time']);
            if (0 < strcmp($deleted_item['start_time'], $last_item['end_time'])) { // 用最近一段经历的结束时间比
                $persisted_items[] = $deleted_item;echo '22222222222';
                $is_newer = true;
            }
        }
    }
}

function merge_itemss($deleted_items,&$persisted_items){
    
    // var_dump($persisted_items);exit;

    foreach($deleted_items as $deleted_item){
        $flag=true;
        foreach($persisted_items as $persisted_item){
            if($deleted_item['start_time'] === $persisted_item['start_time'] &&
                $deleted_item['end_time'] === $persisted_item['end_time'] &&
                $deleted_item['so_far'] == $persisted_item['so_far']
                ){
                $flag=false;
            }
        }
        if($flag){
            $persisted_items[]=$deleted_item;
        }
    }
    
}

$deleted_items = '{"1496806195":{"start_time":"2003\u5e746\u6708","end_time":"2004\u5e747\u6708","school_name":"\u5e7f\u5dde\u5927\u5b66","discipline_name":"\u8ba1\u7b97\u673a","degree":99,"so_far":"N","is_entrance":"N","discipline_desc":"","updated_at":"2017-06-07 11:29:53","created_at":"2017-06-07 11:29:53","id":1496806195},"1496806194":{"start_time":"1999\u5e746\u6708","end_time":"2003\u5e746\u6708","school_name":"\u4e2d\u5c71\u5927\u5b66","discipline_name":"\u8ba1\u7b97\u673a","degree":99,"so_far":"N","is_entrance":"N","discipline_desc":"","updated_at":"2017-06-07 11:29:53","created_at":"2017-06-07 11:29:53","id":1496806194},"1496806193":{"start_time":"1984\u5e746\u6708","end_time":"1997\u5e746\u6708","school_name":"\u6267\u884c\u4e2d\u5b66","discipline_name":"\u7269\u7406","degree":99,"so_far":"N","is_entrance":"N","discipline_desc":"","updated_at":"2017-06-07 11:29:53","created_at":"2017-06-07 11:29:53","id":1496806193}}';
$persisted_items='{"1496647908":{"start_time":"1999\u5e746\u6708","end_time":"2003\u5e746\u6708","school_name":"\u4e2d\u5c71\u5927\u5b66","discipline_name":"\u8ba1\u7b97\u673a","degree":0,"so_far":"N","is_entrance":"N","discipline_desc":"","updated_at":"2017-06-05 15:31:47","created_at":"2017-06-05 15:31:47","id":1496647908},"1496647907":{"start_time":"1984\u5e746\u6708","end_time":"1997\u5e746\u6708","school_name":"\u6267\u884c\u4e2d\u5b66","discipline_name":"\u7269\u7406","degree":0,"so_far":"N","is_entrance":"N","discipline_desc":"","updated_at":"2017-06-05 15:31:47","created_at":"2017-06-05 15:31:47","id":1496647907}}';

$dd = json_decode($deleted_items,true);
$pp = json_decode($persisted_items,true);
merge_items($dd,$pp);
  var_dump($pp);
