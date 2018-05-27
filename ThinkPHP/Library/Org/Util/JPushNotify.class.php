<?php
/**
 * FileName: JPushNotify.class.php
 * User: Comos
 * Date: 2018/2/27 17:29
 */

namespace Org\Util;

vendor('autoload');

use JPush\Client;
use JPush\Exceptions\APIConnectionException;
use JPush\Exceptions\APIRequestException;
use Think\Log;


class JPushNotify
{
    private static $appkey = '2d5f7409f8fe43421cdb37f3';
    private static $mastersecret = 'e6fd50390ad9f6e4dd56aa11';
    private static $is_product = false;

    /**
     * 获取推送对象实例
     */
    private static function getJPushObject()
    {
        $option = C('JPUSH_OPTION');
        $appkey = $option && isset($option['APPKEY']) ? $option['APPKEY'] : self::$appkey;
        $mastersecret = $option && isset($option['MASTERSECRET']) ? $option['MASTERSECRET'] : self::$mastersecret;

        return new Client($appkey, $mastersecret);
    }

    /**
     * 推送极光消息给用户
     * @param array $registrationId 用户的极光推送绑定ID
     * @param array $message 消息内容
     * @param null $audience 推送方式, 若值为true则为广播
     * @param null $appkey 应用key
     * @param null $mastersecret 应用secret
     * @return int
     */
    public static function singleNotify(array $registrationId, array $message)
    {
        //接收数据demo
        /*$message = array(
            'alert' => '推送出去的消息内容',  //消息内容本身 必填
            'title' => '推送出去的标题',      //消息标题 选填
            'extras' => array(),            //附加数据 根据业务需要填充
        );*/
        $client = self::getJPushObject();
        $response = self::buidNotify($client, $message, false, $registrationId);
        return $response;
    }

    /**
     * 全局广播推送消息
     * @param array $message 消息内容
     * @param null $appkey 应用key
     * @param null $mastersecret 应用secret
     * @return int
     */
    public static function allNotify(array $message)
    {
        /*$message = array(
            'alert' => '推送出去的消息内容',  //消息内容本身 必填
            'title' => '推送出去的标题',      //消息标题 选填
            'extras' => array(),            //附加数据 根据业务需要填充
        );*/
        $client = self::getJPushObject();
        $response = self::buidNotify($client, $message, true);
        return $response;
    }

    /**
     * 构建推送内容
     * @param $client object jpush实例
     * @param $message array 消息内容
     * @param $isAll bool 是否全局消息
     * @param null $registrationId 推送用户的regid
     * @return int
     */
    private static function buidNotify($client, array $message, $isAll, $registrationId = null)
    {
        //通知消息内容
        $alert = $message['alert'];
        $ios_notification = array('sound' => '', 'badge' => '+1', 'content-available' => true, 'extras' => $message['extras']);
        $android_notification = array('build_id' => 1, 'extras' => $message['extras']);
        if ($message['title']) {
            $android_notification['title'] = $message['title'];
        }

        //平台数据设置
        $options = array('time_to_live' => 864000, 'apns_production' => self::$is_product);

        try {
            $pushPayload = $client->push();
            $pushPayload->setPlatform(array('ios', 'android'));

            //是否全局广播
            if ($isAll) {
                $pushPayload->addAllAudience();
            } else {

                if (is_null($registrationId)) {
                    return 400;
                }
                $pushPayload->addRegistrationId($registrationId);
            }

            //其他参数
            $pushPayload->iosNotification($alert, $ios_notification)
                ->androidNotification($alert, $android_notification)
                ->options($options);

            //自定义消息
            $pushPayload->message($alert, [
                'title' => $message['title'] ? $message['title'] : '',
                'content_type' => 'text',
                'extras' => $message['extras']
            ]);

            $response = $pushPayload->send();
            return $response['http_code'];

        } catch (APIConnectionException $e) {
            self::WriteLog($e);
            return 400;

        } catch (APIRequestException $e) {
            self::WriteLog($e);
            return 400;
        }
    }

    /**
     * 指定用户推送通知 redis版
     */
    public static function tosingleNotify($member_id, array $message)
    {
        /*$message = [
            'alert' => $alert,
            'title' => $title,
            'extras' => [
                'msg_type' => $msg_type,  //system order bar
                'title' => $msg_title,
                'content' => $msg_content,
                'icon' => $msg_icon,
                'order_id' => $order_id
            ]
        ];*/

        try{
            $redis = new \Redis();
            if (!$res = $redis->connect(C('REDIS_CONFIG.HOSTNAME'), C('REDIS_CONFIG.PORT'))) {
                Response::error(ReturnCode::INVALID_REQUEST, 'Cache server connection failed');
            }

            $redis->auth(C('REDIS_CONFIG.PASSWORD'));

            $memberName = 'kpz_app_member_ids';     //member_id => regid
            $registerName = 'kpz_app_member_regid'; //regid => member_id

            $regid = $redis->hGet($memberName, $member_id);
            if (!$regid) return false;

            $redis_member_id = $redis->hGet($registerName, $regid);
            if (!$redis_member_id) return false;

            if ($redis_member_id != $member_id) return false;

            if (!is_array($regid)) {
                $regid = [$regid];
            }
            return self::singleNotify($regid, $message);

        } catch (\Exception $exception){

            Log::write($exception, Log::ERR);
            return false;

        }
    }

    /**
     * 别名方式推送
     * @param $member_id
     * @param array $message
     * @return bool|int
     */
    public static function toAliasNotify($member_id, array $message)
    {
        try{

            $redis = new \Redis();
            if (!$res = $redis->connect(C('REDIS_CONFIG.HOSTNAME'), C('REDIS_CONFIG.PORT'))) {
                Response::error(ReturnCode::INVALID_REQUEST, 'Cache server connection failed');
            }
            $redis->auth(C('REDIS_CONFIG.PASSWORD'));

            $registration_id = $redis->hGet('kpz_app_member_ids', $member_id); //member_id => regid
            if (!$registration_id) {
                return false;
            }

            $client = self::getJPushObject();
            $device = $client->device();
            $alias = $device->getDevices($registration_id);
            if ($alias['http_code'] != 200) {
                Log::write(json_encode($alias), Log::NOTICE);
                return false;
            }

            $alias_value = $alias['body']['alias'];
            if ($alias_value != $member_id) {
                return false;
            }

            $registration_id = [$registration_id];
            return self::singleNotify($registration_id, $message);

        } catch (\Exception $exception){

            Log::write($exception, Log::ERR);
            return false;

        }
    }

    /**
     * 设置设备别名
     */
    public static function setAlias($registration_id, $member_id)
    {
        $client = self::getJPushObject();
        $device = $client->device();
        $alias = $device->getDevices($registration_id);
        if ($alias['http_code'] != 200) {
            Log::write('Requst code:' . $alias['http_code'], Log::NOTICE);
            return false;
        }

        $alias_value = $alias['body']['alias'];
        if (!empty($alias_value) && $alias_value == $member_id) {
            return true;
        } else {
            return $device->updateAlias($registration_id, (string)$member_id);
        }
    }

    /**
     * 删除设备别名
     */
    public static function delAlias($member_id)
    {
        $client = self::getJPushObject();
        $device = $client->device();
        $result = $device->deleteAlias((string)$member_id);
        if ($result && $result['http_code'] != 200) {
            Log::write(json_encode($result), Log::NOTICE);
            return false;
        }

        return true;
    }

    /**
     * 获取日志记录地址
     */
    private static function getLogPath()
    {
        return LOG_PATH . 'jpush_' . date('Y-m-d') . '.log';
    }

    /**
     * 记录日志
     * @param $e
     */
    private static function WriteLog($e)
    {
        global $logFile;
        $text = 'excute method: ' . __METHOD__;
        $text .= 'Exceptions: ' . $e;
        Log::write($text, Log::NOTICE, '', self::getLogPath());
    }

}