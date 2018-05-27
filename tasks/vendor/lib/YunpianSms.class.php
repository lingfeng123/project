<?php

/**
 * FileName: YunpianSms.class.php
 * User: Comos
 * Date: 2018/1/29 11:26
 */
class YunpianSms
{
    private $apikey;
    private $maxSmsNumber;
    public $errMsg = '';

    public function __construct($apikey = '')
    {
        //写入云片apikey
        $this->apikey = $apikey ? $apikey : '6db00c6428f770705b472affda2d298b';

        //单手机号码每日最大短信获取条数
        $this->maxSmsNumber = 10;
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
}