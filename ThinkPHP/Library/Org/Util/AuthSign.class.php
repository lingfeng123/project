<?php

namespace Org\Util;
/**
 * 秘钥计算
 */


class AuthSign
{

    private $version;
    private $appInfo;

    public function __construct($version, $appInfo)
    {
        $this->version = $version;
        $this->appInfo = $appInfo;
    }

    public function getHeader($accessToken = '', $userToken = false)
    {
        $header['version'] = $this->version;
        if ($accessToken) {
            $header['access-token'] = $accessToken;
        }
        $city = cookie('365jxj_city');
        if ($city) {
            $header['city'] = $city;
        } else {
            $header['city'] = 'nj';
        }
        if ($userToken) {
            $header['user-token'] = $userToken;
        }

        return $header;
    }

    public function getAccessTokenData()
    {
        $data['app_id'] = $this->appInfo['appId'];
        $data['app_secret'] = $this->appInfo['appSecret'];
        $data['device_id'] = 'zuAdmin';
        $data['rand_str'] = md5(rand(1, 10000) . microtime());
        $data['timestamp'] = time();
        $sign = $this->getSignature($data);
        $data['signature'] = $sign;

        return $data;
    }

    /**
     * 获取身份秘钥
     * @param array $data
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     * @return string
     */
    private function getSignature($data)
    {
        ksort($data);
        $preStr = http_build_query($data);

        return md5($preStr);
    }


    /**
     * API接口通讯签名校验
     * @param array $data 客户端传入的数据，去除客户端签名字符串
     * @param string $sign 客户端传入的校验字符串
     * @return bool 校验结果 true|false
     */
    public static function getAuthSign($sign, $timestamp)
    {
        $str = self::getSign($timestamp);
        if ($sign != $str) return false;

        //验证时间差值是否合法
        //$number = $timestamp - time();
        //规定不允许时间差大于30s
        //if (abs($number) > 30) return false;

        return true;
    }

    /**
     * 获取签名字符串
     * @param $timestamp
     * @return string
     */
    public static function getSign($timestamp)
    {
        @date_default_timezone_set('PRC');

        //获取约定字符串
        $convention = C('CONVENTION');

        $str = md5($timestamp . '3516' . md5(md5($timestamp) . $timestamp) . $convention);
        return $str;
    }

    /**
     * 生成用户验证验证usertoken
     * @return string   32位字符串
     */
    public static function getUserToken($prefix = 'mch_', $len = 32, $md5 = true)
    {
        mt_srand((double)microtime() * 1000000);
        # Array of characters, adjust as desired
        $chars = array(
            'Q', '@', '8', 'y', '%', '^', '5', 'Z', '(', 'G', '_', 'O', '`',
            'S', '-', 'N', '<', 'D', '{', '}', '[', ']', 'h', ';', 'W', '.',
            '/', '|', ':', '1', 'E', 'L', '4', '&', '6', '7', '#', '9', 'a',
            'A', 'b', 'B', '~', 'C', 'd', '>', 'e', '2', 'f', 'P', 'g', ')',
            '?', 'H', 'i', 'X', 'U', 'J', 'k', 'r', 'l', '3', 't', 'M', 'n',
            '=', 'o', '+', 'p', 'F', 'q', '!', 'K', 'R', 's', 'c', 'm', 'T',
            'v', 'j', 'u', 'V', 'w', ',', 'x', 'I', '$', 'Y', 'z', '*'
        );
        # Array indice friendly number of chars;
        $numChars = count($chars) - 1;
        $token = '';
        # Create random token at the specified length
        for ($i = 0; $i < $len; $i++)
            $token .= $chars[mt_rand(0, $numChars)];
        # Should token be run through md5?
        if ($md5) {
            # Number of 32 char chunks
            $chunks = ceil(strlen($token) / 32);
            $md5token = '';
            # Run each chunk through md5
            for ($i = 1; $i <= $chunks; $i++)
                $md5token .= md5(substr($token, $i * 32 - 32, 32));
            # Trim the token
            $token = substr($md5token, 0, $len);
        }
        return $prefix . $token;
    }


    /**
     * 获取用户加密uid字符串
     * @param $id int  id
     * @param $client string 用户端或商户端用户
     * @return string
     */
    public static function getUserUid($id, $client)
    {
        $string = C('USER_ID_STR');
        $uid = md5($client . md5($string) . md5($id));
        return $uid;
    }


    /**
     * 验证token存不存在
     * return string
     */
    public static function verifyToken($token)
    {
        $redis = new \Redis();
        if (!$res = $redis->connect(C('REDIS_CONFIG.HOSTNAME'), C('REDIS_CONFIG.PORT'))) {
            return false;
        }
        $redis->auth(C('REDIS_CONFIG.PASSWORD'));   //redis连接密码

        //设置新的过期时间
        if ($redis->exists($token)) {
            $redis->expire($token, 604800);
            return true;
        }

        return false;
    }

    /**
     * 验证用户端token 是否合法
     * @param $token  string  chuan
     * @param $uid
     */
    public static function verifyMemToken($token, $uid)
    {
        $redis = new \Redis();
        if (!$res = $redis->connect(C('REDIS_CONFIG.HOSTNAME'), C('REDIS_CONFIG.PORT'))) {
            return false;
        }
        $redis->auth(C('REDIS_CONFIG.PASSWORD'));   //redis连接密码

        $key = 'member_' . $uid;
        $get_token = $redis->get($key);

        if (!$get_token && $token != $get_token) {
            return false;
        } else {
            //重置过期时间
            $setResult = $redis->expire($key, 604800);
            if ($setResult) {
                return false;
            }
        }
        return true;
    }


    /**
     * 验证商户端token是否合法
     * @param $token string 传入的token值
     * @return bool
     */
    public static function verifyMchToken($token, $uid)
    {
        $redis = new \Redis();
        if (!$res = $redis->connect(C('REDIS_CONFIG.HOSTNAME'), C('REDIS_CONFIG.PORT'))) {
            return false;
        }
        $redis->auth(C('REDIS_CONFIG.PASSWORD'));   //redis连接密码

        $key = 'mch_' . $uid;
        //获取redis中存储的token
        $redis_token = $redis->get($key);
        if (!$redis_token) return false;

        //验证token是否相等
        if ($token != $redis_token) return false;

        //重置过期时间
        $setResult = $redis->expire($key, 604800);
        if ($setResult === false) return false;

        return true;
    }

}