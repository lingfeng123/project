<?php
/**
 * 官方文档：http://mp.weixin.qq.com/wiki/7/aaa137b55fb2e0456bf8dd9148dd613f.html
 * 微信支付：http://pay.weixin.qq.com/wiki/doc/api/index.php?chapter=9_1#
 * 官方示例：http://demo.open.weixin.qq.com/jssdk/sample.zip
 * UCToo示例:http://git.oschina.net/uctoo/uctoo/blob/master/Addons/Jssdk/Controller/JssdkController.class.php
 *
 * 微信JSSDK支付类,主要实现了微信JSSDK支付，参考官方提供的示例文档，
 * @命名空间版本
 * @author uctoo (www.uctoo.com)
 * @date 2015-5-15 14:10
 */

namespace Org\Util;

class JsSdkPay
{
    private $appId;
    private $appSecret;
    public $debug = false;
    public $parameters;//获取prepay_id时的请求参数
    //受理商ID，身份标识
    public $MCHID = '';
    //商户支付密钥Key。审核通过后，在微信商户平台中查看 https://pay.weixin.qq.com
    public $KEY = '';
    //=======【JSAPI路径设置】===================================
    //获取access_token过程中的跳转uri，通过跳转将code传入jsapi支付页面
    public $JS_API_CALL_URL = '';
    //=======【证书路径设置】=====================================
    //证书路径,注意应该填写绝对路径
    public $SSLCERT_PATH = '/xxx/xxx/xxxx/WxPayPubHelper/cacert/apiclient_cert.pem';
    public $SSLKEY_PATH = '/xxx/xxx/xxxx/WxPayPubHelper/cacert/apiclient_key.pem';
    //=======【异步通知url设置】===================================
    //异步通知url，商户根据实际开发过程设定
    //C('url')."admin.php/order/notify_url.html";
    public $NOTIFY_URL = '';
    //=======【curl超时设置】===================================
    //本例程通过curl使用HTTP POST方法，此处可修改其超时时间，默认为30秒
    public $CURL_TIMEOUT = 30;
    public $prepay_id;

    public function __construct($options = [])
    {
        $this->appId = isset($options['appid']) ? $options['appid'] : '';
        $this->appSecret = isset($options['appsecret']) ? $options['appsecret'] : '';
        $this->MCHID = isset($options['mchid']) ? $options['mchid'] : '';
        $this->KEY = isset($options['key']) ? $options['key'] : '';
    }
    //微信支付相关方法

    /**
     *    作用：格式化参数，签名过程需要使用
     */
    function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = "";
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    /**
     *    作用：设置jsapi的参数
     */
    public function getParameters()
    {
        $jsApiObj["appId"] = $this->appId;           //请求生成支付签名时需要,js调起支付参数中不需要
        $timeStamp = time();
        $jsApiObj["timeStamp"] = "$timeStamp";      //用大写的timeStamp参数请求生成支付签名
        $jsParamObj["timestamp"] = $timeStamp;      //用小写的timestamp参数生成js支付参数，还要注意数据类型，坑！
        $jsParamObj["nonceStr"] = $jsApiObj["nonceStr"] = strtoupper(md5(String::randString(20)));
        $jsParamObj["package"] = $jsApiObj["package"] = "prepay_id=$this->prepay_id";
        $jsParamObj["signType"] = $jsApiObj["signType"] = "MD5";
        $jsParamObj["paySign"] = $jsApiObj["paySign"] = $this->getSign($jsApiObj);
        $jsParam = json_encode($jsParamObj);
        return $jsParam;
    }

    /**
     * 获取prepay_id
     */
    function getPrepayId()
    {
        $result = $this->xmlToArray($this->postXml());
        $prepay_id = $result["prepay_id"];
        return $prepay_id;
    }

    /**
     *    作用：将xml转为array
     */
    public function xmlToArray($xml)
    {
        //将XML转为array
        $xml_data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $array_data = json_decode(json_encode($xml_data), true);
        return $array_data;
    }

    /**
     *    作用：post请求xml
     */
    function postXml()
    {
        $xml = $this->createXml();
        return $this->postXmlCurl($xml, "https://api.mch.weixin.qq.com/pay/unifiedorder", $this->CURL_TIMEOUT);
    }


    /**
     *    作用：以post方式提交xml到对应的接口url
     */
    public function postXmlCurl($xml, $url, $second = 30)
    {
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->CURL_TIMEOUT);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);
            return false;
        }
    }

    /**
     *    作用：设置标配的请求参数，生成签名，生成接口参数xml
     */
    function createXml()
    {
        $this->parameters["appid"] = $this->appId;//公众账号ID
        $this->parameters["mch_id"] = $this->MCHID;//商户号
        $this->parameters["nonce_str"] = strtoupper(md5(String::randString(20)));//随机字符串
        $this->parameters["sign"] = $this->getSign($this->parameters);//签名
        return $this->arrayToXml($this->parameters);
    }

    /**
     * 生成接口签名
     * @return string
     */
    public function createSignStr()
    {
        $this->parameters["appid"] = $this->appId;//公众账号ID
        $this->parameters["mch_id"] = $this->MCHID;//商户号
        $this->parameters["nonce_str"] = strtoupper(md5(String::randString(20)));//随机字符串
        $this->parameters["sign"] = $this->getSign($this->parameters);//签名
        return $this->arrayToXml($this->parameters);
    }

    /**
     *    作用：array转xml
     */
    function arrayToXml($arr)
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
     *    作用：生成签名
     */
    public function getSign($Obj)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //echo '【string1】'.$String.'</br>';
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->KEY;
        //echo "【string2】".$String."</br>";
        //签名步骤三：MD5加密
        $String = md5($String);
        //echo "【string3】 ".$String."</br>";
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        //echo "【result】 ".$result_."</br>";
        return $result_;
    }

    function postSSLXml($url, $vars, $second = 30, $aHeader = array())
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

        $ds = DIRECTORY_SEPARATOR;
        $certPath = getcwd() . $ds . 'Application' . $ds . 'Common' . $ds . 'cert' . $ds;

        //以下两种方式需选择一种

        //第一种方法，cert 与 key 分别属于两个.pem文件
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, $certPath . 'apiclient_cert.pem');

        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, $certPath . 'apiclient_key.pem');

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
     * 微信退款通知数据解密
     * @param $str string 微信返回的加密字符串
     * @param $key string 商户设置的api秘钥
     * @return bool|string
     */
    public function decryptAES($str, $key)
    {
        $str = utf8_encode($str);
        $str = base64_decode($str);
        $key = md5($key);
        $str = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $str, MCRYPT_MODE_ECB);
        $len = strlen($str);
        $pad = ord($str[$len - 1]);
        $str = substr($str, 0, strlen($str) - $pad);
        return $str;
    }

}