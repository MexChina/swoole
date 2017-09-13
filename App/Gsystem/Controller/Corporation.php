<?php
namespace Swoole\App\Gsystem\Controller;

use Swoole\Core\App\Controller;
use Swoole\Core\Log;
use Swoole\Core\Helper\Tool;
use Swoole\Core\Helper\System;
use Swoole\Core\AppServer;

class Corporation extends Controller{
    private $tobusiness_db;
    private $gsystem_db;
    private $tobusiness_traffic_db;
    private $gsystem_traffic_db;
    private $redis;
    private $field = ['id','parent_id','name','website','city_id','city_name','nature_id','nature_name','size_id','size_name','uid','status','point_to','is_deleted','updated_at','created_at'];
    private $baidu_ak = 'bAF9v1ZluIZzI1mT4BmYkPlKGG7ZUN67';
    private $baidu_geocoder_url = 'http://api.map.baidu.com/geocoder/v2/?';
    private $baidu_search_url = 'http://api.map.baidu.com/place/v2/search?';
    private $page_size = 1000;
    private $baseinfos_extend_field = ['id','corporation_id','financing','scale','scale_min','scale_max','status','is_deleted','updated_at','created_at'];
    private $insert_baseinfos_extend_field = ['corporation_id','financing','scale'];
    private $address_filter_rule =  [
        '、','。','·','ˉ','ˇ','¨','〃','々','—','～','‖','…','‘','’','“','”','？','：','〔','〕','〈','〉','《','》',
        '「','」','『','』','〖','〗','【','】','±','×','÷','∶','∧','∨','∑','∏','∪','∩','∈','∷','√','⊥','∥',
        '∠','⌒','⊙','∫','∮','≡','≌','≈','∽','∝','≠','≮','≯','≤','≥','∞','∵','∴','♂','♀','°','′','″','℃','＄',
        '¤','￠','￡','‰','§','№','☆','★','○','●','◎','◇','◆','□','■','△','▲','※','→','←','↑','↓','〓','　','！',
        '＂','￥','％','＆','＇','＊','＋','，','－','．','／','；','＜','=','＞','＠','＼','＾','＿','｀','｛','｜','｝',
        '￣',' ','!','@','$','%','^','&','*','[',']','|','?','+','{','}','.','……',' ','\t','\n','\r','\f','\v','/'
    ];

    private $traffic_balck_list = [
        '大田路与山海关路交叉口',
        '四川省成都市武侯区天府大道',
        '闵行区老沪闵路1号(近沪闵路)',
        '金山教育附近',
        '广东省广州市白云区金沙洲路',
        '江苏省苏州市金阊区',
        '北京市海淀区',
        '惠州大道11号佳兆业广场4楼425',
        '布吉街道龙岭社区汇食街裕丰苑1楼102室'
    ];



    public function __construct($obj){
        $this->gsystem_db               = $this->db('gsystem');
        $this->tobusiness_db            = $this->db('tobusiness');
        $this->tobusiness_traffic_db    = $this->db('tobusiness_traffic');
        $this->gsystem_traffic_db       = $this->db('gsystem_traffic');
        $this->redis                    = $obj['redis'];
    }

    public function init(){

    }

    /** 查询  单/多
     * @param $param
     * @return array
     */
    public function select($param){
        $type = isset($param['type']) ? (int)$param['type'] : 1;  //0--单  1--多

        if(isset($param['field'])){
            $field_arr = explode(',',$param['field']);
            foreach($field_arr as $field){
                if(!in_array($field,$this->field)){
                    return $this->returns('',1,"$field 字段不存在！");
                }
            }
            $field = $param['field'];
        }else{
            $field = '*';
        }


        if($type){  //list
            $where = " `is_deleted` = 'N'";
            $page_size = isset($param['page_size']) ? (int)$param['page_size'] : 1000;
            $page = isset($param['page']) ? (int)$param['page'] : 1;
            $result = $this->gsystem_db->query("select count(1) as 'total' from corporations")->fetch();
            $total = ceil($result['total']/$page_size);
            $page = $page >= $total ? $total : $page;

            $sql = "SELECT $field FROM corporations WHERE id>= (SELECT id FROM corporations where $where ORDER BY id asc LIMIT ". ($page-1)*$page_size .", 1) and $where ORDER BY id asc LIMIT $page_size";
            $results = $this->gsystem_db->query($sql)->fetchall();

            return $this->returns(array(
                'page'=>$page,
                'page_size'=>$page_size,
                'total'=>$total,
                'list'=>$results
            ));
        }else{ //one
            $where = isset($param['where']) ? (string)$param['where'] : "`is_deleted` = 'N'";
            $order = isset($param['order']) ? (string)$param['order'] : 'id asc';
            $limit = isset($param['limit']) ? (int)$param['limit'] : 10;
            $result = $this->gsystem_db->query("select $field from corporations where $where order by $order limit $limit")->fetchall();
            return $this->returns($result);
        }
    }


    private function returns($data,$no=0,$msg=''){
        return array('err_no'=>$no,'err_msg'=>$msg,'results'=>$data);
    }

    /*
     * 根据地址获取交通信息
     * @param string $address 地址
     * return array
     */
    public function get_traffic_info_by_address($address){
        Log::write_log("根据地址获取交通信息，开始处理地址...");
        if(empty($address)) return $this->returns([], 1, '地址为空，请核实');

        $cache_key = 'CORPORATION_TRAFFIC_' . md5('get_traffic_info_by_address_' . $address);
        if(empty($result = json_decode($this->redis->get($cache_key), true))){
            $result = [];
            $curl_address = urlencode($address);
            // 获取地址的经纬度
            $address_url = $this->baidu_geocoder_url . 'address=' . $curl_address . '&output=json&ak=' . $this->baidu_ak;
            $address_res = Tool::send_curl($address_url, 'GET');
            Log::write_log("根据地址获取经纬度的结果集：" . json_encode($address_res, JSON_UNESCAPED_UNICODE));
            if($address_res['status'] == 0 && !empty($address_res['result'])){
                $result = [
                    'address' => $address,
                    'location' => [
                        'lng' => isset($address_res['result']['location']['lng']) ? $address_res['result']['location']['lng'] : 0,
                        'lat' => isset($address_res['result']['location']['lat']) ? $address_res['result']['location']['lat'] : 0,
                    ],
                    'city_name' => '',
                    'district' => '',
                    'metro_list' => [],
                    'bus_list' => [],
                ];

                if(!empty($result['location']['lng']) && !empty($result['location']['lat'])){
                    // 根据经度和纬度获取城市名称
                    $city_url = $this->baidu_geocoder_url . 'ak=' . $this->baidu_ak. '&location=' . $result['location']['lat']. ','. $result['location']['lng']. '&output=json&pois=0';
                    $city_res = Tool::send_curl($city_url, 'GET');
                    $result['city_name'] = isset($city_res['result']['addressComponent']['city']) ? $city_res['result']['addressComponent']['city'] : '';
                    $result['district'] = isset($city_res['result']['addressComponent']['district']) ? $city_res['result']['addressComponent']['district'] : '';

                    Log::write_log('根据经纬度获取城市的结果集...');

                    // 获取地址周围地铁、公交数据
                    $traffic_url = $this->baidu_search_url . 'query=地铁$公交&radius=1000&page_size=50&page_num=0&scope=2&location='. $result['location']['lat'] . ',' . $result['location']['lng'] . '&output=json&ak=' . $this->baidu_ak;
                    $traffic_res = Tool::send_curl($traffic_url, 'GET');
                    Log::write_log('获取地址周围地铁、公交数据的结果集...');
                    if($traffic_res['status'] == 0 && $traffic_res['total'] > 0){
                        foreach ($traffic_res['results'] as $val){
                            $list_name = strpos($val['address'], '地铁') === false ? 'bus_list': 'metro_list';
                            $result[$list_name][] = [
                                'name'      => isset($val['name']) ? $val['name'] : '',
                                'lng'       => isset($val['location']['lng']) ? $val['location']['lng'] : '',
                                'lat'       => isset($val['location']['lat']) ? $val['location']['lat'] : '',
                                'address'   => isset($val['address']) ? $val['address'] : '',
                                'distance'  => isset($val['detail_info']['distance']) ? $val['detail_info']['distance'] : '',
                            ];
                        }
                    }
                }
            }
            $this->redis->set($cache_key, json_encode($result), 604800); // 缓存一周
        }
        Log::write_log('根据地址获取交通信息处理完毕...');
        return $this->returns($result);
    }

    /*
     * 同步公司交通信息 to tobusiness_traffic
     * @param array $param公司地址信息 [ ['id'=>'1','address'=>'地址'] ]
     * return bollean
     */
    public function sync_corporation_traffic($param){
        Log::write_log('开始同步公司信息...');
        if(empty($param)) return $this->returns([], 1, 'p为空，请核实');

        $success_num = 0;
        foreach($param as $val){
            if(empty($val['id']) || empty($val['address'])){
                continue;
            }

            Log::write_log('开始同步公司信息：公司ID->' . $val['id'] . '，公司地址->' . $val['address']);
            // 根据地址获取交通信息
            $traffic_results = $this->get_traffic_info_by_address($val['address']);

            if(empty($traffic_info = $traffic_results['results'])){
                Log::write_log($val['address'] . '该地址无效...');
                continue;
            }

            $city_id = $this->gsystem_db->query("select id from regions where `level` = 2 and `name` = '" . $traffic_info['city_name'] . "'")->fetch();

            $traffic_info['city_id'] = $city_id['id'] > 0 ? $city_id['id']: 0;

            Log::write_log('开始同步公司信息...');
            // 同步
            $this->sync_bi_corporation_traffic($traffic_info, $val['id']);
            $success_num++;
        }

        $result = [ 'success_num' => $success_num, 'total_num' => count($param) ];
        return $this->returns($result, 0, '处理成功');
    }

    /*
     * 同步BI数据库的公司交通信息
     * @param array $data
     * @param int $cid 公司ID
     * return boolean
     */
    public function sync_bi_corporation_traffic($data, $cid){
        if(empty($data['address'])) return false;
        // 新增公司地址关联信息
        $address_values = "(" . $cid . "," .$data['city_id'] . ",'" . addslashes(str_replace($this->address_filter_rule,'',$data['address'])) . "')";
        $this->tobusiness_traffic_db->query("insert INTO corporations_traffic_address (`corporation_id`,`city_id`,`address`) VALUES $address_values");
        $address_id = $this->tobusiness_traffic_db->insert_id();
        Log::write_log('公司地址关联关系插入成功...');

        $address_data = [
            'cid' => $cid,
            'address' => $data['address'],
            'location' => $data['location'],
            'address_id' => $address_id,
        ];

        // 新增公司地铁信息
        foreach($data['metro_list'] as $metro){
            $this->add_bi_corporations_traffic($metro, $address_data, 0);
        }
        // 新增公司公交信息
        foreach($data['bus_list'] as $bus){
            $this->add_bi_corporations_traffic($bus, $address_data, 1);
        }

        Log::write_log("sync_corporation_traffic 更新完成，用时： " . System::exec_time() . " ms, 内存使用： " . System::get_used_memory());
    }

    public function __destruct(){
        $this->tobusiness_traffic_db->close();
        $this->gsystem_db->close();
        $this->tobusiness_db->close();
        $this->gsystem_traffic_db->close();
    }

    /*
     * 新增bi 公司交通信息
     * @param array $traffic_data 交通信息
     * @param array $address_data 地址信息
     * @param int $type 交通类型 0：地铁 1：公交
     */
    public function add_bi_corporations_traffic($traffic_data, $address_data, $type = 0){
        $traffic_values  = "(" . $address_data['cid'] . ",'";
        $traffic_values .= $address_data['location']['lng'] . "','";
        $traffic_values .= $address_data['location']['lat'] . "','";
        $traffic_values .= $traffic_data['name'] . "','";
        $traffic_values .= $traffic_data['lng'] . "','";
        $traffic_values .= $traffic_data['lat'] . "',";
        $traffic_values .= $address_data['address_id'] . ",'";
        $traffic_values .= $type . "','";
        $traffic_values .= $traffic_data['distance'] . "')";

        $this->tobusiness_traffic_db->query("insert into corporations_traffic(`corporation_id`,`c_lng`,`c_lat`,`station_name`,`s_lng`,`s_lat`,`address_id`,`transportation`,`distance`) VALUES $traffic_values");
        $traffic_id = $this->tobusiness_traffic_db->insert_id();

        $traffic__relation_values = '';
        $address_arr = explode(';', $traffic_data['address']);
        foreach($address_arr as $a){
            if($this->checkTrafficBlackList($a)) continue;
            $traffic__relation_values .= "('".$traffic_id."','".$a."'),";
        }
        $traffic__relation_values = rtrim($traffic__relation_values,',');
        $this->tobusiness_traffic_db->query("insert into corporations_traffic_relation(`tid`,`traffic_name`) VALUES $traffic__relation_values");
        Log::write_log('公司交通信息插入成功...');
        return true;
    }


    /*
     * 刷新公司交通信息
     * @param string $type tobusiness  gsystem
     */
    public function refresh_corporation_traffic($param){
        switch($param['type']){
            case 'tobusiness':
                $this->refresh_tobusiness_corporation_traffic();
                break;
            case 'gsystem':
                $this->refresh_gsystem_corporation_traffic();
                break;
            default:
                return $this->returns([], 1, "请求的 type参数：{$param['type']} 不存在，请检查接口参数是否正确...");
        }

        return $this->returns([], 0, '处理成功');
    }

    /*
     * 将tobusiness的公司交通信息同步到tobusiness_traffic中
     */
    public function refresh_tobusiness_corporation_traffic(){
        Log::write_log("开始更新 tobusiness_traffic corporations_traffic_address ...");
        $res = $this->tobusiness_db->query("select count(1) as `count` from company_address_relation")->fetch();

        $count = $res['count'];
        $page_count = ceil($count/$this->page_size);
        for($page=1;$page<=$page_count;$page++){

            $sql = "SELECT * FROM `company_address_relation` WHERE id <= (SELECT id FROM `company_address_relation` ORDER BY id desc LIMIT ".($page-1)*$this->page_size.", 1) ORDER BY id desc LIMIT $this->page_size";
            $result = $this->tobusiness_db->query($sql)->fetchall();
            $values = "";
            foreach($result as $r){
                $res2 = $this->gsystem_db->query("select id from regions where `name` = '{$r['city_name']}'")->fetch();
                if(empty($res2) || empty($res2['id'])) $res2['id']=0;
                $values .= "('" . $r['corporation_id'] . "','".$res2['id']."','" . addslashes(str_replace($this->address_filter_rule,'',$r['address'])) . "'),";
            }
            $values = rtrim($values,',');
            if($values){
                $this->tobusiness_traffic_db->query("insert INTO corporations_traffic_address(`corporation_id`,`city_id`,`address`) VALUES $values");
                Log::write_log("tobusiness_traffic company_address_relation  $page / $page_count 更新成功...");
            }
        }
        Log::write_log("tobusiness_traffic corporations_traffic_address 更新完成，用时： " . System::exec_time() . " ms, 内存使用： " . System::get_used_memory());


        Log::write_log("开始更新 tobusiness_traffic corporations_traffic ...");
        $res = $this->tobusiness_db->query("select count(1) as `count` from company_traffic")->fetch();

        $count = $res['count'];
        $page_count = ceil($count/$this->page_size);

        for($page=1;$page<=$page_count;$page++){
            $sql = "SELECT * FROM `company_traffic` WHERE id >= (SELECT id FROM `company_traffic` ORDER BY id asc LIMIT ".($page-1)*$this->page_size.", 1) ORDER BY id asc LIMIT $this->page_size";
            $result = $this->tobusiness_db->query($sql)->fetchall();
            $values1 = "";
            $values2 = "";
            foreach($result as $k => $r){
                $values1 .= "('" . $r['corporation_id'] . "','";
                $values1 .= $r['c_lng'] . "','";
                $values1 .= $r['c_lat']."','";
                $values1 .= $r['station_name']."','";
                $values1 .= $r['s_lng']."','";
                $values1 .= $r['s_lat']."','";
                $values1 .= $r['address_id']."','";
                $values1 .= $r['transportation']."','";
                $values1 .= $r['distance']. "')";

                $this->tobusiness_traffic_db->query("insert into corporations_traffic(`corporation_id`,`c_lng`,`c_lat`,`station_name`,`s_lng`,`s_lat`,`address_id`,`transportation`,`distance`) VALUES $values1");
                $traffic_id = $this->tobusiness_traffic_db->insert_id();

                $address_arr = explode(';',$r['s_address']);
                foreach($address_arr as $a){
                    if($this->checkTrafficBlackList($a)) continue;
                    $values2 .= "('".$traffic_id."','".$a."'),";
                }

                $values2 = rtrim($values2,',');
                $this->tobusiness_traffic_db->query("insert into corporations_traffic_relation(`tid`,`traffic_name`) VALUES $values2");
                Log::write_log("tobusiness_traffic corporations_traffic  $page 页 第 $k 条 更新成功...");
                unset($values1,$values2,$address_arr,$traffic_id);
            }

            unset($resource,$result,$values1,$values2,$address_arr,$traffic_id);
        }
        Log::write_log("tobusiness_traffic corporations_traffic 更新完成，用时： " . System::exec_time() . " ms, 内存使用： " . System::get_used_memory());
    }

    /*
     * 将gsystem的公司交通信息同步到gsystem_traffic中
     */
    public function refresh_gsystem_corporation_traffic(){
        Log::write_log("开始更新 gsystem_traffic corporations_traffic_address ...");
        $res = $this->gsystem_db->query("select count(1) as `count` from corporations_addresses")->fetch();

        $count = $res['count'];
        $page_count = ceil($count/$this->page_size);
        for($page=1;$page<=$page_count;$page++){
            $sql = "SELECT * FROM `corporations_addresses` WHERE id >= (SELECT id FROM `corporations_addresses` ORDER BY id asc LIMIT ".($page-1)*$this->page_size.", 1) ORDER BY id asc LIMIT $this->page_size";
            $result = $this->gsystem_db->query($sql)->fetchall();

            foreach($result as $r){
                if(empty($r['corporation_id']) || empty($r['address'])){
                    continue;
                }

                Log::write_log('refresh_gsystem_corporation_traffic 开始同步公司信息：公司ID->' . $r['corporation_id'] . '，公司地址->' . $r['address']);
                // 根据地址获取交通信息
                $traffic_results = $this->get_traffic_info_by_address($r['address']);

                if(empty($traffic_info = $traffic_results['results'])){
                    Log::write_log($r['address'] . '该地址无效...');
                    continue;
                }

                $city_id = $this->gsystem_db->query("select id from regions where `level` = 2 and `name` = '" . $traffic_info['city_name'] . "'")->fetch();

                $traffic_info['city_id'] = $city_id['id'] > 0 ? $city_id['id']: 0;

                Log::write_log('refresh_gsystem_corporation_traffic 开始同步公司信息...');
                // 同步
                $this->sync_gsystem_corporation_traffic($traffic_info, $r['corporation_id']);
            }
        }
        Log::write_log("refresh_gsystem_corporation_traffic 更新完成，用时： " . System::exec_time() . " ms, 内存使用： " . System::get_used_memory());
    }

    /*
     * 同步gsystem数据库的公司交通信息
     * @param array $data
     * @param int $cid 公司ID
     * return boolean
     */
    public function sync_gsystem_corporation_traffic($data, $cid){
        if(empty($data['address'])) return false;
        // 新增公司地址关联信息
        $address_values = "(" . $cid . "," .$data['city_id'] . ",'" . addslashes(str_replace($this->address_filter_rule,'',$data['address'])) . "')";
        $this->gsystem_traffic_db->query("insert INTO corporations_traffic_address (`corporation_id`,`city_id`,`address`) VALUES $address_values");
        $address_id = $this->gsystem_traffic_db->insert_id();
        Log::write_log('公司地址关联关系插入成功...');

        $address_data = [
            'cid' => $cid,
            'address' => $data['address'],
            'location' => $data['location'],
            'address_id' => $address_id,
        ];

        // 新增公司地铁信息
        foreach($data['metro_list'] as $metro){
            $this->add_gsystem_corporations_traffic($metro, $address_data, 0);
        }
        // 新增公司公交信息
        foreach($data['bus_list'] as $bus){
            $this->add_gsystem_corporations_traffic($bus, $address_data, 1);
        }

        Log::write_log("sync_corporation_traffic 更新完成，用时： " . System::exec_time() . " ms, 内存使用： " . System::get_used_memory());
    }


    /*
     * 新增gsystem 公司交通信息
     * @param array $traffic_data 交通信息
     * @param array $address_data 地址信息
     * @param int $type 交通类型 0：地铁 1：公交
     */
    public function add_gsystem_corporations_traffic($traffic_data, $address_data, $type = 0){
        $traffic_values  = "(" . $address_data['cid'] . ",'";
        $traffic_values .= $address_data['location']['lng'] . "','";
        $traffic_values .= $address_data['location']['lat'] . "','";
        $traffic_values .= $traffic_data['name'] . "','";
        $traffic_values .= $traffic_data['lng'] . "','";
        $traffic_values .= $traffic_data['lat'] . "',";
        $traffic_values .= $address_data['address_id'] . ",'";
        $traffic_values .= $type . "','";
        $traffic_values .= $traffic_data['distance'] . "')";

        $this->gsystem_traffic_db->query("insert into corporations_traffic(`corporation_id`,`c_lng`,`c_lat`,`station_name`,`s_lng`,`s_lat`,`address_id`,`transportation`,`distance`) VALUES $traffic_values");
        $traffic_id = $this->gsystem_traffic_db->insert_id();

        $traffic__relation_values = '';
        $address_arr = explode(';', $traffic_data['address']);
        foreach($address_arr as $a){
            if($this->checkTrafficBlackList($a)) continue;
            $traffic__relation_values .= "('".$traffic_id."','".$a."'),";
        }
        $traffic__relation_values = rtrim($traffic__relation_values,',');
        $this->gsystem_traffic_db->query("insert into corporations_traffic_relation(`tid`,`traffic_name`) VALUES $traffic__relation_values");
        Log::write_log('gsystem_traffic 公司交通信息插入成功...');
        return true;
    }



    /** 查询公司扩展信息  单/多
     * @param array $param
     * @return array
     */
    public function select_baseinfos_extend($param){
        if(isset($param['field'])){
            $field_arr = explode(',',$param['field']);
            foreach($field_arr as $field){
                if(!in_array($field,$this->baseinfos_extend_field)){
                    return $this->returns('',1,"$field 字段不存在！");
                }
            }
            $field = $param['field'];
        }else{
            $field = '*';
        }

        $where = " `is_deleted` = 'N'";
        // 如果id为空 则多条查询，否则为单条查询
        if(empty($param['id'])){
            if(!empty($param['corporation_id'])){
                $where .=  " and `corporation_id` = " . $param['corporation_id'];
            }
            $page_size = isset($param['page_size']) ? intval($param['page_size']) : 10;
            $page = isset($param['page']) ? (intval($param['page']) > 0 ? intval($param['page']): 1) : 1;
            $result = $this->gsystem_db->query("select count(1) as 'total' from corporations_baseinfos_extend where $where")->fetch();
            $total_page = ceil($result['total']/$page_size);
            $page = $page >= $total_page ? ($total_page > 0?$total_page:1) : $page;

            $sql = "SELECT $field FROM corporations_baseinfos_extend WHERE id>= (SELECT id FROM corporations_baseinfos_extend where $where ORDER BY id asc LIMIT ". ($page-1)*$page_size .", 1) and $where ORDER BY id asc LIMIT $page_size";
            $results = $this->gsystem_db->query($sql)->fetchall();

            return $this->returns([
                'page'          => $page,
                'page_size'     => $page_size,
                'total'         => $result['total'],
                'list'          => $results
            ]);
        }else{ // one
            $where .=  " and `id` = " . $param['id'];
            $result = $this->gsystem_db->query("select $field from corporations_baseinfos_extend where $where")->fetch();
            return $this->returns($result);
        }
    }

    /*
     * 添加公司扩展信息
     * @param array $param
     * return array
     */
    public function add_baseinfos_extend($param){
        Log::write_log('添加公司扩展信息 数据校验...');
        foreach($param as $key => $val){
            if(!in_array($key, $this->insert_baseinfos_extend_field)){
                return $this->returns('', 1, "$key 字段不符合规则，请查阅接口文档！");
            }
            if($key == 'corporation_id' && empty($val)){
                return $this->returns('', 1, "$key 字段不能为空！");
            }
        }

        $scale_min = $scale_max = 0;
        if(!empty($param['scale'])){
            $scale = explode('-', $param['scale']);
            $scale_min = !empty($scale[0]) ? intval($scale[0]): 0;
            $scale_max = !empty($scale[1]) ? intval($scale[1]): 0;
        }

        // 先查询是否有这条数据，如果有则不作任何操作，没有则插入
        $where  = "`corporation_id` = " . $param['corporation_id'] . " and ";
        $where  .= "`financing` = '" . $param['financing'] . "' and ";
        $where  .= "`scale` = '" . $param['scale'] . "' and ";
        $where  .= "`is_deleted` = 'N' and ";
        $where  .= "`scale_min` = " . $scale_min . " and ";
        $where  .= "`scale_max` = " . $scale_max;

        Log::write_log('添加公司扩展信息 重复查询...');
        $detail = $this->gsystem_db->query("select * from corporations_baseinfos_extend where $where")->fetch();

        if(!empty($detail)) return $this->returns(['id' => $detail['id']], 2, "当前数据已存在");
        $values  = "(" . $param['corporation_id'] . ",'";
        $values .= $param['financing'] . "','";
        $values .= $param['scale'] . "',";
        $values .= $scale_min . ",";
        $values .= $scale_max . ")";
        Log::write_log('添加公司扩展信息 插入数据...');
        $this->gsystem_db->query("insert into corporations_baseinfos_extend(`corporation_id`,`financing`,`scale`,`scale_min`,`scale_max`) VALUES $values");

        if(empty($id = $this->gsystem_db->insert_id())){
            return $this->returns([], 1, "添加失败");
        }
        return $this->returns(['id' => $id], 0, "添加成功");
    }

    /*
     * 检查交通信息名是否在黑名单中
     * @param string $name 名字
     * return boolean
     */
    public function checkTrafficBlackList($name){
        if(in_array($name, $this->traffic_balck_list)){
            return true;
        }

        return false;
    }

    /*
     * 保存公司福利
     * @param array 参数  id：公司id，weals：福利集
     * return string
     */
    public function save_corporation_weal($param){
        if(empty($param['id'])) return $this->returns([], 1, '公司ID为空，请核实');
        if(empty($param['weals'])) return $this->returns([], 1, '公司福利为空，请核实');
        if(!is_array($param['weals'])) return $this->returns([], 1, '公司福利数据不合法，请核实');

        Log::write_log('保存公司福利 start...');

        // 获取福利数据
        $weal_list = $this->get_weal_ids($param['weals']);

        if(empty($weal_list)) return $this->returns([], 0, '无数据需处理');

        // 删除当前公司的福利
        Log::write_log("保存公司福利 删除公司关联福利");
        $this->gsystem_db->query("delete from corporations_weal_map where corporation_id = {$param['id']}");

        // 新增公司福利
        Log::write_log("保存公司福利 新增公司关联福利");
        $values = '';
        foreach($weal_list as $val){
            $values .= "('".$param['id']."','".$val."'),";
        }
        $values = rtrim($values, ',');
        $this->gsystem_db->query("insert INTO corporations_weal_map (`corporation_id`,`weal_id`) VALUES $values");

        return $this->returns([], 0, '保存成功');
    }

    /*
     * 根据福利名称集获取对应id集
     * @param array $param 福利集
     */
    public function get_weal_ids($param){
        Log::write_log('保存公司福利 获取福利ID集...');
        $param = array_unique($param);

        $result = [];
        $where = " `is_deleted` = 'N' and name in (";
        $weal_list = [];
        foreach($param as $val){
            $temp = trim($val);
            $weal_list[$temp] = $temp;
            $where .= "'" . $temp . "',";
        }
        $where = rtrim($where, ',') . ")";

        $list = $this->gsystem_db->query("select `id`,`name` from corporations_weal where $where order by id asc")->fetchall();

        // 收集存在数据库中的福利
        foreach($list as $val){
            unset($weal_list[$val['name']]);
            $result[] = $val['id'];
        }

        // 未存在数据库中的福利，进行新增
        foreach($weal_list as $val){
            if($id = $this->addCorporationsWeal($val)){
                Log::write_log("保存公司福利 新增福利 $id：$val...");
                $result[] = $id;
            }
        }
        return $result;
    }

    /*
     * 新增公司福利
     * @param string $name 福利名称
     * return boolean
     */
    public function addCorporationsWeal($name){
        if(empty($name)) return 0;
        $this->gsystem_db->query("INSERT INTO corporations_weal (`name`) VALUES ('$name')");
        return $this->gsystem_db->insert_id();
    }

    /** 查询公司福利列表
     * @param array $param
     * @return array
     */
    public function get_corporation_weals($param){
        if(empty($param['id'])) return $this->returns([], 1, '公司ID为空，请核实');
        $sql = "select m.`corporation_id`,m.`weal_id`,w.`name` as weal_name,w.`status` from corporations_weal_map m inner join corporations_weal w on w.id = m.weal_id where m.`is_deleted` = 'N' and m.corporation_id = " . intval($param['id']);
        $result  = $this->gsystem_db->query($sql)->fetchall();
        return $this->returns($result, 0, '请求成功');
    }
}