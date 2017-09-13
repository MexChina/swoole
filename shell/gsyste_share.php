<?php
/**
 * 公司营收数据
 */
class Share{

    private $data;

    public function __construct(){
        $this->data = array(
            "专业、科研服务业"=>array(535,"学术科研"),
            "专业设计服务业"=>array(525,"服务业"),
            "专用化学产品制造业"=>array(677,"化工工程"),
            "专用设备制造业"=>array(620,"机械制造"),
            "中药材及中成药加工业"=>array(640,"生物制药"),
            "乳制品制造业"=>array(593,"食品"),
            "交通运输设备制造业"=>array(625,"交通设施"),
            "交通运输辅助业"=>array(520,"交通物流"),
            "人寿保险业"=>array(700,"人寿险"),
            "人造板制造业"=>array(627,"建材"),
            "仓储业"=>array(635,"仓储"),
            "仪器仪表及文化、办公用机械制造业"=>array(623,"仪器仪表"),
            "保险业"=>array(538,"保险"),
            "信息传播服务业"=>array(517,"文化传媒"),
            "公共设施服务业"=>array(529,"公共事业"),
            "公路管理及养护业"=>array(529,"公共事业"),
            "公路运输业"=>array(520,"交通物流"),
            "其他专用设备制造业"=>array(519,"工业"),
            "其他交通运输业"=>array(520,"交通物流"),
            "其他交通运输辅助业"=>array(520,"交通物流"),
            "其他传播、文化产业"=>array(517,"文化传媒"),
            "其他公共设施服务业"=>array(529,"公共事业"),
            "其他农业"=>array(531,"农业"),
            "其他加工业"=>array(633,"原材料及加工"),
            "其他批发业"=>array(536,"零售"),
            "其他生物制品业"=>array(640,"生物制药"),
            "其他电器机械制造业"=>array(601,"家电"),
            "其他电子设备制造业"=>array(419,"电子技术/半导体/集成电路"),
            "其他社会服务业"=>array(529,"公共事业"),
            "其他纤维制品制造业"=>array(527,"化工"),
            "其他通用零部件制造业"=>array(519,"工业"),
            "其他金属制品业"=>array(519,"工业"),
            "其他零售业"=>array(536,"零售"),
            "农、林、牧、渔、水利业机械制造业"=>array(531,"农业"),
            "农业"=>array(531,"农业"),
            "冶金、矿山、机电工业专用设备制造业"=>array(620,"机械制造"),
            "出版业"=>array(588,"出版"),
            "制糖业"=>array(593,"食品"),
            "制造业"=>array(620,"机械制造"),
            "制鞋业"=>array(597,"服饰"),
            "化学农药制造业"=>array(527,"化工"),
            "化学原料及化学制品制造业"=>array(527,"化工"),
            "化学纤维制造业"=>array(527,"化工"),
            "化学肥料制造业"=>array(527,"化工"),
            "化学药品制剂制造业"=>array(527,"化工"),
            "化学药品原药制造业"=>array(527,"化工"),
            "医疗器械制造业"=>array(523,"医疗器械"),
            "医药制造业"=>array(522,"医药"),
            "卫生、保健、护理服务业"=>array(522,"医药"),
            "印刷业"=>array(630,"印刷"),
            "合成材料制造业"=>array(633,"原材料及加工"),
            "商业经纪与代理业"=>array(515,"专业服务"),
            "土木工程建筑业"=>array(514,"建筑与房地产"),
            "基本化学原料制造业"=>array(527,"化工"),
            "塑料制造业"=>array(527,"化工"),
            "塑料板、管、棒材制造业"=>array(519,"工业"),
            "塑料零件制造业"=>array(519,"工业"),
            "天然原油开采业"=>array(526,"能源"),
            "家具制造业"=>array(627,"建材"),
            "屠宰及肉类蛋类加工业"=>array(593,"食品"),
            "市内公共交通业"=>array(529,"公共事业"),
            "广告业"=>array(579,"广告"),
            "广播电影电视业"=>array(583,"影视"),
            "广播电视设备制造业"=>array(519,"工业"),
            "建筑、工程咨询服务业"=>array(514,"建筑与房地产"),
            "房地产中介服务业"=>array(550,"房地产代理"),
            "房地产开发与经营业"=>array(514,"建筑与房地产"),
            "房地产管理业"=>array(547,"房地产开发"),
            "文教体育用品制造业"=>array(518,"消费品"),
            "旅游业"=>array(657,"旅游"),
            "旅馆业"=>array(657,"旅游"),
            "日用电器制造业"=>array(519,"工业"),
            "日用电子器具制造业"=>array(519,"工业"),
            "日用百货零售业"=>array(536,"零售"),
            "普通机械制造业"=>array(620,"机械制造"),
            "有色金属冶炼及压延加工业"=>array(671,"冶炼"),
            "有色金属压延加工业"=>array(671,"冶炼"),
            "有色金属矿采选业"=>array(670,"采掘业"),
            "服装制造业"=>array(597,"服饰"),
            "服装及其他纤维制品制造业"=>array(597,"服饰"),
            "木制家具制造业"=>array(627,"建材"),
            "木材批发业"=>array(627,"建材"),
            "机场及航空运输辅助业"=>array(624,"航空/航天"),
            "林业"=>array(532,"林业"),
            "橡胶制造业"=>array(527,"化工"),
            "毛皮鞣制及制品业"=>array(597,"服饰"),
            "毛纺织业"=>array(598,"纺织"),
            "水上运输业"=>array(520,"交通物流"),
            "水产品加工业"=>array(593,"食品"),
            "水泥制品和石棉水泥制品业"=>array(519,"工业"),
            "水泥制造业"=>array(633,"原材料及加工"),
            "汽车制造业"=>array(629,"汽车"),
            "沿海运输业"=>array(520,"交通物流"),
            "海洋渔业"=>array(534,"渔业"),
            "渔业"=>array(534,"渔业"),
            "渔业服务业"=>array(534,"渔业"),
            "港口业"=>array(520,"交通物流"),
            "炼钢业"=>array(671,"冶炼"),
            "煤气生产和供应业"=>array(665,"石油天然气"),
            "煤炭开采业"=>array(669,"矿产"),
            "煤炭采选业"=>array(526,"能源"),
            "照明器具制造业"=>array(519,"工业"),
            "牲畜饲养放牧业"=>array(533,"畜牧业"),
            "生物制品业"=>array(522,"医药"),
            "电力、蒸汽、热水的生产和供应业"=>array(526,"能源"),
            "电力生产业"=>array(666,"电力"),
            "电器机械及器材制造业"=>array(519,"工业"),
            "电子元件制造业"=>array(419,"电子技术/半导体/集成电路"),
            "电子元器件制造业"=>array(419,"电子技术/半导体/集成电路"),
            "电子器件制造业"=>array(419,"电子技术/半导体/集成电路"),
            "电子测量仪器制造业"=>array(623,"仪器仪表"),
            "电子计算机制造业"=>array(419,"电子技术/半导体/集成电路"),
            "电工器械制造业"=>array(519,"工业"),
            "电机制造业"=>array(620,"机械制造"),
            "电视"=>array(583,"影视"),
            "畜牧业"=>array(533,"畜牧业"),
            "皮革、毛皮、羽绒及制品制造业"=>array(597,"服饰"),
            "石化及其他工业专用设备制造业"=>array(676,"化工设备"),
            "石墨及碳素制品业"=>array(519,"工业"),
            "石油加工及炼焦业"=>array(527,"化工"),
            "石油和天然气开采业"=>array(526,"能源"),
            "矿物纤维及其制品业"=>array(526,"能源"),
            "种植业"=>array(532,"林业"),
            "租赁服务业"=>array(663,"租赁服务"),
            "稀有稀土金属冶炼业"=>array(671,"冶炼"),
            "管道运输业"=>array(729,"管道"),
            "粮食及饲料加工业"=>array(593,"食品"),
            "纺织业"=>array(598,"纺织"),
            "纺织品、服装、鞋帽零售业"=>array(536,"零售"),
            "综合类证券公司"=>array(539,"证券"),
            "能源、材料和机械电子设备批发业"=>array(519,"工业"),
            "能源批发业"=>array(526,"能源"),
            "自来水的生产和供应业"=>array(788,"水气矿产"),
            "航空客货运输业"=>array(520,"交通物流"),
            "航空航天器制造业"=>array(624,"航空/航天"),
            "航空运输业"=>array(520,"交通物流"),
            "药品及医疗器械批发业"=>array(522,"医药"),
            "药品及医疗器械零售业"=>array(522,"医药"),
            "装修装饰业"=>array(746,"装饰材料"),
            "装卸搬运业"=>array(520,"交通物流"),
            "计算机及相关设备制造业"=>array(415,"计算机硬件"),
            "计算机应用服务业"=>array(416,"计算机服务系统、数据服务、维修"),
            "计算机相关设备制造业"=>array(415,"计算机硬件"),
            "计算机软件开发与咨询"=>array(414,"计算机软件"),
            "计量器具制造业"=>array(519,"工业"),
            "证券、期货业"=>array(513,"金融"),
            "证券经纪公司"=>array(539,"证券"),
            "贵金属冶炼业"=>array(671,"冶炼"),
            "贵金属矿采选业"=>array(670,"采掘业"),
            "轻纺工业专用设备制造业"=>array(519,"工业"),
            "输配电及控制设备制造业"=>array(777,"输配电"),
            "通信及相关设备制造业"=>array(417,"通信/电信/网络设备"),
            "通信服务业"=>array(418,"通信/电信运营、增值服务"),
            "通信设备制造业"=>array(417,"通信/电信/网络设备"),
            "通用仪器仪表制造业"=>array(623,"仪器仪表"),
            "通用设备制造业"=>array(519,"工业"),
            "造纸及纸制品业"=>array(631,"造纸"),
            "酒精及饮料酒制造业"=>array(596,"酒品"),
            "采掘服务业"=>array(670,"采掘业"),
            "重有色金属冶炼业"=>array(633,"原材料及加工"),
            "金属制品业"=>array(628,"五金材料"),
            "金属加工机械制造业"=>array(620,"机械制造"),
            "金属材料批发业"=>array(633,"原材料及加工"),
            "金属结构制造业"=>array(620,"机械制造"),
            "金融信托业"=>array(541,"信托"),
            "钢压延加工业"=>array(633,"原材料及加工"),
            "铁矿采选业"=>array(786,"金属矿产"),
            "铁路、公路、隧道、桥梁建筑业"=>array(529,"公共事业"),
            "铁路运输业"=>array(741,"高铁"),
            "银行业"=>array(537,"银行"),
            "铸件制造业"=>array(620,"机械制造"),
            "铸铁管制造业"=>array(621,"流体控制"),
            "陶瓷制品业"=>array(518,"消费品"),
            "零售业"=>array(536,"零售"),
            "非金属矿物制品业"=>array(787,"非金属矿产"),
            "食品、饮料、烟草和家庭用品批发业"=>array(536,"零售"),
            "食品制造业"=>array(593,"食品"),
            "食品加工业"=>array(593,"食品"),
            "餐饮业"=>array(656,"餐饮"),
            "饮料制造业"=>array(594,"饮料"),
            "黑色金属冶炼及压延加工业"=>array(671,"冶炼"),
            "黑色金属矿采选业"=>array(786,"金属矿产")
        );
    }

    public function client($request){
        $client= new GearmanClient();
        $client->addServer("192.168.8.13",4730);
        $param['header']['product_name'] = 'shares';
        $param['header']['uname'] = 'test';
        $param['header']['session_id'] = '22';
        $param['header']['user_ip'] = '127.0.0.1';
        $param['header']['local_ip'] = '192.168.100.100';
        $param['header']['log_id'] = '123456';
        $param['request']=$request;
        $send_data = msgpack_pack($param);
        $packedResponse = $client->doNormal("gsystem_basic", $send_data);
        $response = msgpack_unpack($packedResponse);
        return $response["response"];
    }

    public function select(){
        $request = array(
            'c'=>'Logic_share',
            'm'=>'select',
            'p'=>[
                'field'=>'',
                'code'=>6000,
                'page'=>1
            ]
        );
        $res = $this->client($request);
        var_dump($res);
    }

    public function save(){
        $request = array(
            'c'=>'Logic_share',
            'm'=>'save',
            'p'=>array(
                'code'=>'6001',
                'company_id'=>1013468,
                'company_name'=>'浦发银行',
                'company_fullname_cn'=>'',
                'company_fullname_en'=>'',
                'total'=>'341568822767.60',
                'trade'=>[
                    'eid'=>123,
                    'trade'=>'xxx'
                ],
                'place'=>'A股',
                'finance'=>[
                    ['income'=>4219100000,'net_profit'=>139220000,'close_date'=>1459353600],
                    ['income'=>4359100000,'net_profit'=>139224500,'close_date'=>1451353600],
                    ['income'=>4789100000,'net_profit'=>139220096,'close_date'=>1443353600],
                ]
            )
        );
        $res = $this->client($request);
        var_dump($res);
    }

    /** log日志
     * @param $msg
     * @return bool
     */
    public function logger($msg){
        $destination = "/opt/log/shares.".date('Y-m-d');
        if (!is_string($msg)) {
            $msg = json_encode($msg,JSON_UNESCAPED_UNICODE);
        }
        $log_id = getmypid();
        $log_info = date("Y-m-d H:i:s")."\t$log_id\t$msg\r\n";
        echo $log_info;
        return error_log($log_info, 3,$destination);
    }


    public function refresh(){
        $db = new PDO('mysql:host=192.168.8.46;dbname=toc_grab;', 'biuser', 'W4l1LoY7VU7Lpq9H');
        $db->query('set names utf8;');

        //trade
        $result = $db->query("SELECT trade_type from shares GROUP BY trade_type")->fetchAll(PDO::FETCH_ASSOC);
        $count = count($result);
        $this->logger("shares_trade_dic $count will update......");

        $trade_data=array();
        foreach($result as $r){
            $trade = isset($this->data[$r['trade_type']]) ? $this->data[$r['trade_type']] : array(0,'');
            if($r['trade_type']){
                $trade_data[]=array(
                    'trade'=>addslashes($r['trade_type']),
                    'eid'=>$trade[0],
                    'trade_alias'=>$trade[1]
                );
            }
        }

        $this->client(array(
            'c'=>'Logic_share',
            'm'=>'refresh',
            'p'=>array(
                'type'=>2,
                'data'=>$trade_data
            )
        ));
        $this->logger("shares_trade_dic complete....");

        //place
        $result = $db->query("SELECT listing_place from shares GROUP BY listing_place")->fetchAll(PDO::FETCH_ASSOC);
        $count = count($result);
        $this->logger("shares_place_dic $count will update......");

        $place_data=array();
        foreach($result as $r){
            $place_data[]=array(
                'name'=>addslashes($r['listing_place']),
                'name_alias'=>'',
            );
        }

        $this->client(array(
            'c'=>'Logic_share',
            'm'=>'refresh',
            'p'=>array(
                'type'=>3,
                'data'=>$place_data
            )
        ));

        $this->logger("shares_place_dic complete....");

        //share
        $result = $db->query("select count(1) as mycount from shares")->fetch(PDO::FETCH_ASSOC);
        $this->logger("shares {$result['mycount']} will update......");
        $page_total = ceil($result['mycount']/1000);
        for($page=1;$page<=$page_total;$page++){

            $data=[];
            $shares = $db->query("SELECT * FROM `shares` WHERE code >= (SELECT code FROM `shares` ORDER BY code asc LIMIT " . ($page - 1) * 1000 . ", 1) ORDER BY code asc LIMIT 1000")->fetchAll(PDO::FETCH_ASSOC);
            foreach($shares as $k=>$share){
                $total = $db->query("select total from shares_total where code='{$share['code']}'")->fetch(PDO::FETCH_ASSOC);
                $resume = $db->query("select resume from shares_manager where code='{$share['code']}'")->fetch(PDO::FETCH_ASSOC);
                $data[$k]=array(
                    'code'=>$share['code'],
                    'company_name'=>$share['name'],
                    'company_fullname_cn'=>$share['full_name'],
                    'company_fullname_en'=>$share['en_name'],
                    'trade'=>$share['trade_type'],
                    'place'=>$share['listing_place'],
                    'finance'=>$db->query("select income,net_profit,close_date from shares_finance where code='{$share['code']}'")->fetchAll(PDO::FETCH_ASSOC),
                    'resume'=> $resume['resume'],
                    'total'=>$total['total']
                );
            }
            var_dump($data);
            $this->client(array(
                'c'=>'Logic_share',
                'm'=>'refresh',
                'p'=>array(
                    'type'=>4,
                    'data'=>$data
                )
            ));
            $this->logger("shares $page/$page_total complete......");
        }
        $this->logger("shares {$result['mycount']} complete......");
    }

    public function mapping(){
        $file = fopen('share_company_mapping.csv','r');
        $mapping=[];
        while ($data = fgetcsv($file)) { //每次读取CSV里面的一行内容
            $mapping[] = array(
                'code'=>$data[0],
                'company_id'=>$data[1],
                'short_name'=>$data[2]
            );
        }
        fclose($file);
        $count = count($mapping);
        $this->logger("share_company_mapping $count will import......");
        $this->client(array(
            'c'=>'Logic_share',
            'm'=>'refresh',
            'p'=>array(
                'type'=>1,
                'data'=>$mapping
            )
        ));
        $this->logger("share_company_mapping complete......");
    }
}

$test = new Share();
$test->refresh();
//$test->mapping();
//$test->test();
//$test->select();
//$test->save();