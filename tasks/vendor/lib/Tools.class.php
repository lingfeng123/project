<?php

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
        $birthday = strtotime(date('Y-m-d', $birthday));
        list($y1, $m1, $d1) = explode("-", date("Y-m-d", $birthday));
        $now = strtotime("now");
        list($y2, $m2, $d2) = explode("-", date("Y-m-d", $now));
        $birthday = $y2 - $y1;
        if ((int)($m2 . $d2) < (int)($m1 . $d1))
            $birthday -= 1;
        return $birthday;
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
     * 产生随机字串，可用来自动生成密码
     * 默认长度6位 字母和数字混合 支持中文
     * @param string $len 长度
     * @param string $type 字串类型
     * 0 字母 1 数字 其它 混合
     * @param string $addChars 额外字符
     * @return string
     */
    static public function randString($len = 6, $type = '', $addChars = '')
    {
        $str = '';
        switch ($type) {
            case 0:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case 1:
                $chars = str_repeat('0123456789', 3);
                break;
            case 2:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
                break;
            case 3:
                $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            default :
                // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
                $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
                break;
        }
        if ($len > 10) {//位数过长重复字符串一定次数
            $chars = $type == 1 ? str_repeat($chars, $len) : str_repeat($chars, 5);
        }

        $chars = str_shuffle($chars);
        $str = substr($chars, 0, $len);
        return $str;
    }

    /**
     * 日志写入接口
     * @param string $log 日志信息
     * @param string $destination 写入目标
     */
    public static function write($message, $level = 'ERR', $file, $method, $destination = '')
    {
        $log = $level . ': ' . $message;
        $now = date('Y-m-d H:i:s');
        $destination = $destination . date('y_m_d') . '.log';
        // 自动创建日志目录
        $log_dir = dirname($destination);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        //检测日志文件大小，超过配置大小则备份日志文件重新生成
        if (is_file($destination) && floor(2097152) <= filesize($destination)) {
            rename($destination, dirname($destination) . '/' . time() . '-' . basename($destination));
        }
        error_log("[{$now}] " . $file . ' ' . $method . "\r\n{$log}\r\n", 3, $destination);
    }
}