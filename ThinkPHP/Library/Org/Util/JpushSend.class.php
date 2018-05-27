<?php

namespace Org\Util;

use JPush\Client as JPush;
use Think\Log;

vendor('autoload');
date_default_timezone_set('PRC');

class JpushSend
{
    const appName = __METHOD__;
    const group_key = 'e7c6454c92d84ff47016fae9';
    const group_master_secret = 'e2d2a664eee49f71bf3268ba';

    /**
     * 指定RegistrationId推送
     * @param $regId
     * @param $type
     * @param $message
     * @param $order_list
     * @return mixed
     */
    static function singlePush($regId, $type, $message, $order_list)
    {
        $logfile = LOG_PATH . 'jpush_' . date('Y-m-d') . '.log';
        $client = new JPush(self::group_key, self::group_master_secret, $logfile);

        if (empty($message)) {
            $message = '您有新订单,请及时处理哦';
        }

        $push_payload = $client->push()
            ->setPlatform('ios')
            ->addRegistrationId($regId)
            ->iosNotification($message, [
//                'sound' => '4.wav',
                'badge' => '1',
                'extras' => [
                    'type' => $type,
                    'data' => [
                        'order_no' => [$order_list]
                    ]
                ]
            ]);
        try {
            $response = $push_payload->options(['apns_production' => True])->send();
            return $response['http_code'];
        } catch (\JPush\Exceptions\APIConnectionException $e) {
            // try something here
            Log::write(self::appName . $e, Log::WARN, '', 'jpush_' . date('Y-m-d') . '.log');
        } catch (\JPush\Exceptions\APIRequestException $e) {
            // try something here
            Log::write(self::appName . $e, Log::WARN, '', 'jpush_' . date('Y-m-d') . '.log');
        }
    }

    /**
     * 向所有用户推送消息
     * @param $msg
     * @return mixed
     */
    static function allPush($msg)
    {
        $logfile = LOG_PATH . 'jpush_' . date('Y-m-d') . '.log';
        $msgAll = array();
        $msgAll['type'] = '5';
        $msgAll['data'] = $msg;
        $client = new JPush(self::group_key, self::group_master_secret, $logfile);

        $push_payload = $client->push()
            ->setPlatform('ios')
            ->addAllAudience()
            ->iosNotification('您有新的订单，请及时处理！', [
                'sound' => '4.wav',
                'badge' => '1',
                'extras' => [
                    'type' => '5',
                    'data' => $msg
                ]
            ]);
        try {
            $response = $push_payload->options(['apns_production' => True])->send();
            return $response['http_code'];
        } catch (\JPush\Exceptions\APIConnectionException $e) {
            // try something here
            Log::write(self::appName . $e, Log::WARN, '', 'jpush_' . date('Y-m-d') . '.log');
        } catch (\JPush\Exceptions\APIRequestException $e) {
            // try something here
            Log::write(self::appName . $e, Log::WARN, '', 'jpush_' . date('Y-m-d') . '.log');
        }
    }


}