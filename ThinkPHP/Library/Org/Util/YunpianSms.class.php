<?php
/**
 * FileName: YunpianSms.class.php
 * User: Comos
 * Date: 2018/1/29 11:26
 */

namespace Org\Util;

use Think\Cache\Driver\Redis;

class YunpianSms
{
    private $apikey;
    private $maxSmsNumber;
    public $errMsg = '';

    public function __construct($apikey = '')
    {
        //写入云片apikey
        $apikey = $apikey ? $apikey : C('YUNPIAN.APIKEY');
        $this->apikey = $apikey ? $apikey : '6db00c6428f770705b472affda2d298b';

        //单手机号码每日最大短信获取条数
        $this->maxSmsNumber = C('MAX_SMS_NUMBER') ? C('MAX_SMS_NUMBER') : 10;
    }

    /**
     * 以POST方式请求数据
     * @param $data array 短信数据
     * @param $url string
     * @return mixed
     */
    public function post($data, $url)
    {
        //初始化curl
        $ch = curl_init();

        //设置验证方式
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:text/plain;charset=utf-8', 'Content-Type:application/x-www-form-urlencoded', 'charset=utf-8'));

        //设置返回结果为流
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //设置超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        //设置通信方式
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        //执行curl请求
        $result = curl_exec($ch);
        curl_close($ch);
        //提交失败
        if ($result == false) {
            //接收处理数据
            $error = curl_error($ch);
            //返回错误数据
            return $error;
        }

        //返回成功数据
        return json_decode($result, true);
    }

    /**
     * 单条发送接口
     * 提示：因为运营商政策，请先在后台完成报备签名、模板及做相关设置(详见接入引导)，再开发API。
     * @param $mobile int 接收的手机号，仅支持单号码发送；
     * @param $text string 已审核短信模板
     * @return mixed
     */
    public function singleSend($mobile, $text, $callback_url = null)
    {
        $data = array(
            'apikey' => $this->apikey,  //用户唯一标识，在管理控制台获取
            'mobile' => $mobile,    //接收的手机号，仅支持单号码发送；
            'text' => urlencode($text)     //已审核短信模板
        );

        //判断回调地址是否为空
        if (!is_null($callback_url)) {
            //短信发送后将向这个地址推送(运营商返回的)发送报告。 如推送地址固定，建议在"数据推送与获取”做批量设置。 如后台已设置地址，且请求内也包含此参数，将以请求内地址为准
            $data['callback_url'] = $callback_url;
        }

        //请求数据接口
        $result = self::post($data, 'https://sms.yunpian.com/v2/sms/single_send.json');
        if ($result['code'] != 0) {
            $this->errMsg = $result['msg'];
            return false;
        }

        //返回结果数据
        return true;
    }

    /**
     * 批量发送短信接口
     * 附注：因为运营商政策，请先在后台完成报备签名、模板及做相关设置(详见接入引导)，再开发API。
     * @param array $mobile 接收的手机号，一次不要超过1000个；
     * @param $text
     * @param null $callback_url
     * @return bool
     */
    public function batchSend(array $mobile, $text, $callback_url = null)
    {
        $mobile = array_filter($mobile);
        //发送内容
        $data = array(
            'apikey' => $this->apikey,  //用户唯一标识，在管理控制台获取
            'mobile' => implode(',', $mobile),    //接收的手机号，仅支持单号码发送；
            'text' => $text     //已审核短信模板
        );

        //判断回调地址是否为空
        if (!is_null($callback_url)) {
            //短信发送后将向这个地址推送(运营商返回的)发送报告。 如推送地址固定，建议在"数据推送与获取”做批量设置。 如后台已设置地址，且请求内也包含此参数，将以请求内地址为准
            $data['callback_url'] = $callback_url;
        }

        //请求数据接口
        $result = self::post($data, 'https://sms.yunpian.com/v2/sms/batch_send.json');
        if ($result['code'] != 0) {
            $this->errMsg = $result['msg'];
            return false;
        }

        //返回结果数据
        return true;
    }


    /**
     * 指定模板单发（不推荐使用）
     * @param $mobile int 接收的手机号
     * @param $tpl_id string 模板id
     * @param $tpl_value array  变量名和变量值对。请先对您的变量名和变量值分别进行urlencode再传递。使用参考：代码示例。 注：模板中有变量时，变量名和变量值都不能为空，模板中没有变量时，赋值tplvalue=""
     * $tpl_value = [
     *      "#key1#" => value1
     *      "#key2#" => value2
     *      ...
     * ]
     * @return mixed
     */
    public function tplSingleSend($mobile, $tpl_id, $tpl_value)
    {
        //将参数进行转码
        $tpl_str = '';
        foreach ($tpl_value as $key => $item) {
            $key = urlencode($key);
            $item = urlencode($item);
            $tpl_str .= "{$key}={$item}&";
        }

        //转换为URL参数码
        $tpl_value = substr($tpl_str, 0, -1);
        $data = array(
            'apikey' => $this->apikey,  //用户唯一标识，在管理控制台获取
            'mobile' => $mobile,    //接收的手机号，仅支持单号码发送；
            'tpl_id' => $tpl_id,
            'tpl_value' => $tpl_value,
        );

        //请求数据接口
        $result = self::post($data, 'https://sms.yunpian.com/v2/sms/tpl_single_send.json');
        if ($result['code'] != 0) {
            $this->errMsg = $result['msg'];
            return false;
        }

        //返回结果数据
        return true;
    }


    /**
     * 指定模板群发
     * 防骚扰过滤：默认开启。过滤规则：同1个手机发相同内容，30秒内最多发送1次，5分钟内最多发送3次。
     * 特别说明：验证码短信，请在手机验证环节，加入图片验证码，以免被恶意攻击。
     * @param $mobile array 接收的手机号
     * @param $tpl_id string 模板id
     * @param $tpl_value array  变量名和变量值对。请先对您的变量名和变量值分别进行urlencode再传递。使用参考：代码示例。 注：模板中有变量时，变量名和变量值都不能为空，模板中没有变量时，赋值tplvalue=""
     * @return mixed
     */
    public function tplBatchSend(array $mobile, $tpl_id, $tpl_value)
    {
        //将参数进行转码
        $tpl_str = '';
        foreach ($tpl_value as $key => $item) {
            $key = urlencode($key);
            $item = urlencode($item);
            $tpl_str .= "{$key}={$item}&";
        }

        //转换为URL参数码
        $tpl_value = substr($tpl_str, 0, -1);
        $data = array(
            'apikey' => $this->apikey,  //用户唯一标识，在管理控制台获取
            'mobile' => implode(',', $mobile),
            'tpl_id' => $tpl_id,
            'tpl_value' => $tpl_value,
        );

        //请求数据接口
        $result = self::post($data, 'https://sms.yunpian.com/v2/sms/tpl_batch_send.json');
        if ($result['code'] != 0) {
            $this->errMsg = $result['msg'];
            return false;
        }

        //返回结果数据
        return true;
    }


    /**
     * 创建短信验证码
     * @param $tel int 手机号码
     * @return bool|string
     */
    public function createSmsCode($tel)
    {
        //验证码值
        $code = String::randString(6, 1);
        //存储验证码并设置过期时间
        $redis = new Redis();
        if ($redis->set($tel, $code, 600) === false) {
            $this->errMsg='redis缓存失败';
            return false;
        }

        //防盗刷短信验证码
        $rs = $this->antiTheft($tel);
        if ($rs == false) {
            $this->errMsg='获取验证码次数超限';
            return false;
        }

        return $code;
    }

    /**
     * 短信验证码合法性校验
     * @param $tel int 手机号码
     * @param $smscode int 短信验证码
     */
    public function valiCode($tel, $smscode)
    {
        //根据手机号码在redis中取出短信验证码
        $redis = new Redis();
        $code = $redis->get($tel);

        //替换短信中的空格
        $smscode = str_replace(' ', '', $smscode);

        //短信验证码验证
        if (!$code || (int)$code !== (int)$smscode) {
            //验证短信验证码失败
            $this->errMsg='验证码不正确';
            return false;
        }

        //验证短信验证码成功, 删除验证码缓存
        if (!$rs = $redis->rm($tel)) {
            $this->errMsg='删除缓存验证码失败';
            return false;
        }

        //返回验证成功结果
        return true;
    }

    /**
     * 防盗刷短信
     * @param $tel int 电话号码
     * @return bool
     */
    public function antiTheft($tel)
    {
        //简单判断电话号码合法性
        if (!$tel || !is_numeric($tel)) {
            return false;
        }

        //redis键名
        $redis_name = 'kpz_sms_anti_theft_' . $tel;

        //调用redis
        $redis = new Redis();
        $number = $redis->get($redis_name); //取得已有值
        if($number===false){
            $number = 0;
        }
        $number = $number ? $number : 0;    //短信防刷值
        if ($number >= $this->maxSmsNumber) {
            return false;   //禁止发送短信了
        }

        //获取redis过期时间
        $date = date('Y-m-d', time());
        $end_time = strtotime($date . '23:59:59');
        $expire_time = $end_time - time();

        //重新设置redis的值与过期时间
        $res = $redis->set($redis_name, $number + 1, $expire_time);

        return $res === false ? false : true;
    }
}