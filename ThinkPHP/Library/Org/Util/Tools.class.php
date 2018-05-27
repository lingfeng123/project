<?php

namespace Org\Util;


use Pheanstalk\Pheanstalk;
use Think\Exception;
use Think\Log;

class Tools
{
    /**
     * 加盐加密
     * @param string $password 原始密码.
     * @param string $salt 盐.
     * @return string 加盐加密后的结果.
     */
    public static function salt_mcrypt($password, $salt)
    {
        return md5(md5($password) . $salt);
    }

    /**
     * 统一格式化金钱表示形式.
     * @param float $number
     * @return string
     */
    public static function money_format($number)
    {
        return number_format($number, 2, '.', '');
    }

    /**
     * 验证金额输入合法性
     * @param $accountPrice
     */
    public static function validatePrice($money)
    {
        if (preg_match('/^[0-9]{1,8}+(\.[0-9]{1,2})?$/', $money)) {
            return true;
        }
        return false;
    }

    /**
     * 生成用户推广码算法
     * @param $account_type string 账户类型
     * @return string 长度为10位的字符串
     */
    public static function create_invite_code($account_type, $id)
    {
        $code = sprintf("%09d", $id);
        $code = $account_type . $code;
        return $code;
    }

    /**
     * 生成唯一订单号
     * 订单长度为16位
     * @param integer $type 订单类型 1线上订单 2线下卡座预定订单 3签单订单
     * @return int|string
     */
    public static function create_order_number($type = 1)
    {
        @date_default_timezone_set("PRC");
        $time = microtime();
        $time_arr = explode(' ', $time);
        $time_arr[0] = substr($time_arr[0], 2, -2);
        $time_arr[1] = substr($time_arr[1], 1);
        $time_arr = array_reverse($time_arr);
        $time = implode('', $time_arr);
        $time = $type . $time;
        return $time;
    }


    /**
     * 获取附件上传目录根地址
     * @return string   目录地址
     */
    public static function attachment_path()
    {
        //获取附件上传绝对路径
        $ds = DIRECTORY_SEPARATOR;
        $imgPath = $_SERVER['DOCUMENT_ROOT'];

        //分割成数组
        $arr = explode('\\', $imgPath);

        //若未分割成功
        if (!(count($arr) > 1)) {
            //执行再次分割
            $arr = explode('/', $imgPath);
        }
        array_pop($arr);
        $str = implode($arr, $ds);

        //组装附件目录
        $imgPath = $str . $ds . 'attachment';
        return $imgPath;
    }


    /**
     * 获取一个随机文件名
     * @return string   文件名
     */
    public static function file_name()
    {
        return String::randString(3, 3) . time() . String::randString(4, 3) . String::randString(3, 1);
    }

    /**
     * 实现下载远程图片保存到本地
     * @param $url  文件url
     * @param string $save_dir 保存文件目录
     * @param string $filename 保存文件名称
     * @param int $type 使用的下载方式
     * @return array
     */
    public static function getImage($url, $save_dir = '', $filename = '', $type = 0)
    {
        if (trim($url) == '') {
            return array('file_name' => '', 'save_path' => '', 'error' => 1);
        }
        if (trim($save_dir) == '') {
            $save_dir = './';
        }
        if (trim($filename) == '') {//保存文件名
            $ext = strrchr($url, '.');
            if ($ext != '.gif' && $ext != '.jpg') {
                return array('file_name' => '', 'save_path' => '', 'error' => 3);
            }
            $filename = time() . $ext;
        }
        if (0 !== strrpos($save_dir, '/')) {
            $save_dir .= '/';
        }
        //创建保存目录
        if (!file_exists($save_dir) && !mkdir($save_dir, 0777, true)) {
            return array('file_name' => '', 'save_path' => '', 'error' => 5);
        }
        //获取远程文件所采用的方法
        if ($type) {
            $ch = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $img = curl_exec($ch);
            curl_close($ch);
        } else {
            ob_start();
            readfile($url);
            $img = ob_get_contents();
            ob_end_clean();
        }
        //$size=strlen($img);
        //文件大小
        $fp2 = @fopen($save_dir . $filename, 'a');
        fwrite($fp2, $img);
        fclose($fp2);
        unset($img, $url);
        return array('file_name' => $filename, 'save_path' => $save_dir . $filename, 'error' => 0);
    }


    /**
     * 向指定手机号码发送短信
     * @param $tel  int 电话号码
     * @param $template_id string 短信模板
     * @param array $data array 短信内容
     * @return bool 返回结果
     */
    public static function sendsms($tel, $template_id, array $data)
    {
        //加载阿里大鱼短信扩展
        vendor('autoload');
        //获取阿里大鱼配置项
        $params = C("ALIDAYU");
        $sms = new Sms($params['ACCESSKEYID'], $params['ACCESSKEYSECRET']);
        $outid = date('Ymdhis', time());
        //执行发送短信
        $response = $sms->sendSms(
            $params['SIGNNAME'], // 短信签名
            $template_id, // 短信模板编号
            $tel, // 短信接收者电话号码
            $data, // 短信模板中字段的值
            $outid  //发送短信流水号
        );


        //判断发送短信是否成功
        if (strtoupper($response->Code) !== 'OK') {
            return false;
        }

        //短信发送成功
        return true;
    }

    /**
     * 发送微信模板消息
     * @param $wx_openid int 微信公众号openID
     * @param $temp_msg array 模板消息内容
     */
    public static function sendTmpMessage($wx_openid, $temp_msg)
    {
        //判断用户openID是否存在
        if ($wx_openid) {
            $weObj = new Wechat(C('WECHAT_OPTION'));
            //获取对应openid的用户信息
            $userInfo = $weObj->getUserInfo($wx_openid);
            //判断用户是否关注公众号
            if ($userInfo['subscribe']) {
                $result = $weObj->sendTemplateMessage($temp_msg);
                if (!$result) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * xml转换为数组
     * @param $xml
     * @return mixed
     */
    public static function xmlToArray($xml)
    {
        //将XML转为array
        $xml_data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $array_data = json_decode(json_encode($xml_data), true);
        return $array_data;
    }

    /**
     * 数组转换xml
     * @param $arr
     * @return string
     */
    public static function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * 替换掉emoji表情
     * @param $text
     * @param string $replaceTo
     * @return mixed|string
     */
    public static function filterEmoji($text, $replaceTo = '')
    {
        $clean_text = "";
        // 匹配的表情
        $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clean_text = preg_replace($regexEmoticons, $replaceTo, $text);
        // 比赛的其他符号和象形文字
        $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clean_text = preg_replace($regexSymbols, $replaceTo, $clean_text);
        // 匹配运输和地图符号
        $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clean_text = preg_replace($regexTransport, $replaceTo, $clean_text);
        // 比赛的其他符号
        $regexMisc = '/[\x{2600}-\x{26FF}]/u';
        $clean_text = preg_replace($regexMisc, $replaceTo, $clean_text);
        // Match Dingbats
        $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
        $clean_text = preg_replace($regexDingbats, $replaceTo, $clean_text);
        $clean_text = trim($clean_text);
        return $clean_text;
    }

    /**
     * 生成一个唯一的退款号
     * @return false|string
     */
    public static function refund_number()
    {
        @date_default_timezone_set("PRC");
        $time = date('YmdHis');
        $refund_number = $time . mt_rand(100, 999);
        return $refund_number;
    }

    /**
     * 格式化金钱小数补零
     * @param $money
     * @param int $number 格式化补零个数
     */
    public static function formatMoney($money, $number = 2)
    {
        return sprintf("%01." . $number . "f", $money);
    }


    /**
     * 根据出生日期计算年龄
     * @param $birthday int 10位时间戳
     * @return bool|false|int
     */
    public static function calculateAge($birthday)
    {
        if (strlen($birthday) == 8) {
            $birthday = self::intToDate($birthday);
        } else {
            $birthday = date("Y-m-d", $birthday);
        }

        list($y1, $m1, $d1) = explode("-", $birthday);
        list($y2, $m2, $d2) = explode("-", date("Y-m-d", time()));
        $birthday = $y2 - $y1;
        if ((int)($m2 . $d2) < (int)($m1 . $d1)) {
            $birthday -= 1;
        }

        return $birthday;
    }


    /**
     * 根据存入的时间整数时间获取格式化的时间
     * @param $time
     * @return mixed
     */
    public static function intToDate($time)
    {
        return preg_replace('/^(\d{4})(\d{2})(\d{2})$/', "$1-$2-$3", $time);
    }


    /**
     * 格式化相册数组组装地址
     * @param $delimiter string 分隔符
     * @param $attachmentUrl string 图片URL前缀
     * @param $albumStr string 原始相册字符串
     */
    public static function albumsFormat($albums, $delimiter = null, $attachmentUrl = null)
    {
        //判断是否是数组
        if (!is_array($albums)) {
            if (!$delimiter) {
                return false;
            }
            //将字符串转化为数组
            $albums = explode($delimiter, $albums);
        }

        //判断URL前缀是否存在
        if ($attachmentUrl != false) {
            //将图片地址组装上url前缀
            $albums = array_map(function ($value) use ($attachmentUrl) {
                return $attachmentUrl . $value;
            }, $albums);
        }

        return $albums;
    }


    /**
     * 二维数组排序
     * @param $multi_array array 需要进行排序的数组
     * @param $sort_key string 排序依据键名
     * @param int $sort 排序方式
     * @return bool
     */
    public static function multiArraySort($multi_array, $sort_key, $sort = SORT_ASC)
    {
        if (is_array($multi_array)) {
            foreach ($multi_array as $row_array) {
                if (is_array($row_array)) {
                    $key_array[] = $row_array[$sort_key];
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
        array_multisort($key_array, $sort, $multi_array);
        return $multi_array;
    }


    /**
     * 获取微信支付接口签名校验
     * @param $sign_arr array 等待签名的数据
     * @param $key string 商户api秘钥
     * @return string
     */
    public static function getWxPaySign($sign_arr, $key)
    {
        ksort($sign_arr);   //将参数以字典序排列
        $sign_str = urldecode(http_build_query($sign_arr)); //生成字典序字符串
        $stringSignTemp = $sign_str . '&key=' . $key;
        return strtoupper(md5($stringSignTemp));
    }


    /**
     * 自定义记录数据日志
     * @param $content array 写入日志的数据
     * @param $log_path string 系统日志路径
     * @param string $level string 日志级别
     * @param null $prefix string 日志文件前缀
     */
    public static function writeLog($content, $log_path, $level = 'INFO', $prefix = null)
    {
        if (!$level) {
            $level = 'INFO';
        }
        $file_name = $log_path . $prefix . '_' . date('Y_m_d') . '.log';
        Log::write(json_encode($content), $level, '', $file_name);
    }

    /**
     * 微信支付返回xml响应数据数据
     * @param string $return_code 返回状态码
     * @param string $return_msg 返回消息内容
     * @param array $responseArray 传入的数组
     */
    public static function responseXml($return_code = 'SUCCESS', $return_msg = 'OK', $responseArray = [])
    {
        if ($responseArray) {
            $arr = $responseArray;
        } else {
            $arr = ['return_code' => $return_code, 'return_msg' => $return_msg];
        }
        $reslut = self::arrayToXml($arr);
        header("Content-type:text/xml");
        exit($reslut);
    }

    /**
     * 转换yyyymmddhhiiss为时间戳
     * @param $timeStr string/number 需要转换的时间字符串
     * @return int 时间戳
     */
    public static function strToTimestamp($timeStr)
    {
        //转换yyyymmddhhiiss为时间戳
        if (strlen($timeStr) != 14) {
            $timeStr = sprintf("%14d", $timeStr);
        }
        $parttern = '/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/';
        $replacement = "$1-$2-$3 $4:$5:$6";
        $formatTime = preg_replace($parttern, $replacement, $timeStr);
        $timestamp = strtotime($formatTime);
        return (int)$timestamp;
    }

    /**
     * 微信支付退款带证书请求方法
     * @param $url string 请求URL
     * @param $vars mixed 请求数据
     * @param $certPath string 操作证书路径(绝对地址)
     * @param int $second int 请求超时时间
     * @param array $aHeader array 附加参数
     * @return bool|mixed
     */
    public static function postSSLXml($url, $vars, $certPath, $second = 30, $aHeader = array())
    {
        $ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        //以下两种方式需选择一种
        //第一种方法，cert 与 key 分别属于两个.pem文件
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, $certPath . '/apiclient_cert.pem');

        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, $certPath . '/apiclient_key.pem');

        //第二种方式，两个文件合成一个.pem文件
        //curl_setopt($ch,CURLOPT_SSLCERT,$certPath . 'all.pem');

        if (count($aHeader) >= 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
        $data = curl_exec($ch);
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "call faild, errorCode:$error\n";
            curl_close($ch);
            return false;
        }
    }

    /**
     * 获取经纬度的四角经纬度
     * @param $lat float 纬度
     * @param $lng float 经度
     * @param $raidus int 辐射距离 单位:m
     * @return array 四角数据
     */
    public static function getRange($lat, $lng, $raidus)
    {
        //计算纬度
        $degree = (24901 * 1609) / 360.0;
        $dpmLat = 1 / $degree;
        $radiusLat = $dpmLat * $raidus;
        $minLat = $lat - $radiusLat; //得到最小纬度
        $maxLat = $lat + $radiusLat; //得到最大纬度
        //计算经度
        $mpdLng = $degree * cos($lat * (pi() / 180));
        $dpmLng = 1 / $mpdLng;
        $radiusLng = $dpmLng * $raidus;
        $minLng = $lng - $radiusLng; //得到最小经度
        $maxLng = $lng + $radiusLng; //得到最大经度
        //范围
        $range = array(
            'minLat' => $minLat,
            'maxLat' => $maxLat,
            'minLng' => $minLng,
            'maxLng' => $maxLng
        );
        return $range;
    }

    /**
     * 获取beanstalk实例
     * @return Pheanstalk
     */
    public static function pheanstalk()
    {
        vendor('autoload');
        $config = C('BEANS_OPTIONS');
        $host = $config['HOST'];
        $port = $config['PORT'];
        return new Pheanstalk($host, $port);
    }

    /**
     * 获取beanstalk是否可用状态
     */
    public static function beanstalkStats($tubeName)
    {
        $pheanstalk = self::pheanstalk();
        $pheanstalk->useTube($tubeName);
        $isAlive = $pheanstalk->getConnection()->isServiceListening();
        if ($isAlive == false) {
            return false;
        }

        /*$phobj = $pheanstalk->statsTube($tubeName);
        $code = $phobj->getResponseName();
        $watching = $phobj['current-watching'];
        $waiting = $phobj['current-waiting'];

        //if ($code != 'OK' || ($watching < 1 && $waiting < 1)) {
        if ($code != 'OK' || ($watching < 1 && $waiting < 1)) {
            return false;
        }*/

        return true;
    }

    /**
     * 营业时间格式化为H:i格式
     * @param $time
     * @return bool|string
     */
    public static function formatTimeStr($time)
    {
        return substr($time, 0, -3);
    }

    /**
     * 获取redis实例
     * @return bool|\Redis
     */
    public static function redisInstance()
    {
        try {
            $redis = new \Redis();
            if (!$res = $redis->connect(C('REDIS_CONFIG.HOSTNAME'), C('REDIS_CONFIG.PORT'))) {
                return false;
            }
            $redis->auth(C('REDIS_CONFIG.PASSWORD'));
            return $redis;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * 验证下单时间是否到达
     */
    public static function orderAllowedValid()
    {
        $allowTime = date('Y-m-d') . ' ' . C('ORDER_ALLOWED_TIME');
        $nowTime = date('Y-m-d H:i');

        if (strtotime($nowTime) < strtotime($allowTime)) {
            Response::error(ReturnCode::INVALID, '每日' . C('ORDER_ALLOWED_TIME') . '后才可下单');
        }
    }
}