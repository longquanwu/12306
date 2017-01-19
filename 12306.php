<?php
/**
 * 12306.php
 * User: wlq314@qq.com
 * Date: 16/8/26 Time: 09:43
 */
require_once './Tool/Email.php';
require_once './Tool/Http.php';

class train{

    private $conf = [];  //配置文件
    private $from_station_name;  //出发地
    private $from_station_code;  //出发地对应的代码
    private $to_station_name;  //目的地
    private $to_station_code;  //目的地对应的代码
    private $query_date;  //查询日期
    private $query_num = 1;  //统计查询次数
    private $train_list = [];  //查询车次
    private $ticket_list = [];  //车站信息
    private $mail;  //邮箱地址
    private $ticket_flag = false;  //有票标记
    
    private $stations = [];  //全部站点信息
    private $ticket_type = [
        '商务座' => 'swz_num',
        '特等座' => 'tz_num',
        '一等座' => 'zy_num',
        '二等座' => 'ze_num',
        '高级软卧' => 'gr_num',
        '软卧' => 'rw_num',
        '硬卧' => 'yw_num',
        '软座' => 'rz_num',
        '硬座' => 'yz_num',
        '无座' => 'wz_num',
        '其它' => 'qt_num'
    ];
    
    public function run(){
        self::init();
        self::query();
    }

    /**
     * 检测并初始化参数
     */
    private function init(){
        $this->conf = require_once('./config.php');
        $this->stations = $this->formatStationInfo($this->conf['stationInfo']);
        
        $args = getopt('f:t:d:m:n:e:');
        if (empty($args['f']) || empty($args['t']) || empty($args['d']) || empty($args['m']) || empty($args['n']))
            $this->error("请输入参数:\n -f 出发地\n -t 目的地\n -d 出发时间\n -m 车次(多个请用,隔开)\n -n 车座(多个请用,隔开)\n -e 邮箱(有票邮件通知,可不填) \n 如:  php 12306.php -f 北京 -t 井冈山 -d 2016-09-30 -m z133 -n 软卧,硬卧");
        $this->checkStation($args['f']);
        $this->checkStation($args['t']);
        $this->from_station_name = $args['f'];
        $this->from_station_code = $this->stations[$this->from_station_name];
        $this->to_station_name = $args['t'];
        $this->to_station_code = $this->stations[$this->to_station_name];
        $this->query_date = $args['d'];
        $trainArr = explode(',', $args['m']);
        foreach ($trainArr as $train){
            $this->train_list[] = strtoupper($train);
        }
        $this->ticket_list = $this->checkTicket($args['n']);
        isset($args['e']) && $this->mail = $args['e'];
    }

    /**
     * 检测车站正确性
     * @param $station
     */
    private function checkStation($station){
        array_key_exists($station, $this->stations) or $this->error("没有找到车站 -> " . $station . ",请确认");
    }

    /**
     * 检测车座类型正确性并返回车座对应代码
     * @param $ticket
     * @return array
     */
    private function checkTicket($ticket){
        $res = [];
        $ticketNameArr = explode(',', $ticket);
        foreach ($ticketNameArr as $ticketName) {
            array_key_exists($ticketName, $this->ticket_type) or $this->error("车座: {$ticketName} 无效,仅限: 商务座, 特等座, 一等座, 二等座, 高级软卧, 软卧, 硬卧, 软座, 硬座, 无座, 其它");
            $res[] = $this->ticket_type[$ticketName];
        }
        return $res;
    }

    /**
     * 格式化车站信息
     * @param $stationStr  字符串
     * @return array  [车站名称 => 车站代码]
     */
    private function formatStationInfo($stationStr){
        $stationInfo = [];
        $stations = explode('|', $stationStr);
        for ($i = 0; $i < count($stations); $i += 5){
            if (isset($stations[$i + 1]) && isset($stations[$i + 2]))
                $stationInfo[$stations[$i + 1]] = $stations[$i + 2];
        }
        return $stationInfo;
    }

    /**
     * CURL查询余票信息
     */
    private function query(){
        $params = [
            'leftTicketDTO.train_date' => $this->query_date,
            'leftTicketDTO.from_station' => $this->from_station_code,
            'leftTicketDTO.to_station' => $this->to_station_code,
            'purpose_codes' => 'ADULT',
        ];
        $url = $this->conf['queryApi'] . http_build_query($params);
        while (true) {
            $UA = [
                'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9) Gecko/2008052906 Firefox/3.0', 
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.95 Safari/537.36',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:50.0) Gecko/20100101 Firefox/50.0',
            ];
            $header = $this->randIP();
            $header['User-Agent'] = $UA[array_rand($UA, 1)]; 
            $header['Referer'] = 'https://kyfw.12306.cn';
            $result = json_decode(Http::get($url, $header), true);
            if ($result['messages']) {
                print_r($result['messages']);
            } else if ($result['status']) {
                if ($result['data']){
                    $this->analyzeData($result['data']);
                } else {
                    $this->error('未查询到有效车次');
                }
            } else {
                $this->msg('请求未响应,正在重试 ￣へ￣');
            }
        }
    }

    /**
     * 此函数提供了国内的IP地址
     */
    private function randIP(){
        $ip_long = array(
            array('607649792', '608174079'), //36.56.0.0-36.63.255.255
            array('1038614528', '1039007743'), //61.232.0.0-61.237.255.255
            array('1783627776', '1784676351'), //106.80.0.0-106.95.255.255
            array('2035023872', '2035154943'), //121.76.0.0-121.77.255.255
            array('2078801920', '2079064063'), //123.232.0.0-123.235.255.255
            array('-1950089216', '-1948778497'), //139.196.0.0-139.215.255.255
            array('-1425539072', '-1425014785'), //171.8.0.0-171.15.255.255
            array('-1236271104', '-1235419137'), //182.80.0.0-182.92.255.255
            array('-770113536', '-768606209'), //210.25.0.0-210.47.255.255
            array('-569376768', '-564133889'), //222.16.0.0-222.95.255.255
        );
        $rand_key = mt_rand(0, 9);
        $ip= long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
        $headers['CLIENT-IP'] = $ip;
        $headers['X-FORWARDED-FOR'] = $ip;
        return $headers;
    }

    /**
     * 分析余票并输出信息
     * @param array $trains
     */
    private function analyzeData(array $trains){
        $this->msg('查询次数: ' . $this->query_num++ . "\t" . $this->query_date . "\t" . $this->from_station_name . '(' . $this->from_station_code . ')' . ' ==> ' . $this->to_station_name . '(' . $this->to_station_code . ")\n");
        foreach ($trains as $key => $item){
            $train  = $item['queryLeftNewDTO'];
            if (in_array($train['station_train_code'], $this->train_list)){
                foreach ($this->ticket_list as $ticket) {
                    if (empty($row)){
                        $row = $train['station_train_code'] . "\t";
                    }
                    $row .= array_search($ticket, $this->ticket_type) . ':' . $train[$ticket] . "\t";
                    if ($train[$ticket] !== '--')
                        $this->ticket_flag = true;
                }
                if (empty($msg)){
                    $msg = $row . "\n";
                } else {
                    $msg .= $row . "\n";
                }
                unset($row);
            }
        }
        $this->msg($msg);
        if ($this->ticket_flag){
            if ($this->mail){
                $mail = new Email($this->conf['mailConf']);
                $mail->send($this->mail, '有余票,快去12306购买', $msg . " <a href='https://kyfw.12306.cn/otn/lcxxcx/init'>购票</a>");
            }
            exit;
        }
    }

    /**
     * 输出错误信息
     * @param $msg
     */
    private function error($msg){
        echo $msg,"\n";exit;
    }

    /**
     * 输出提示信息
     * @param $msg
     */
    private function msg($msg){
        echo $msg,"\n";
    }
    
}

$train = new train();
$train->run();
