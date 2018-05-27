<?php
/**
 * FileName: MessagePush.class.php
 * User: Comos
 * Date: 2017/8/15 16:47
 */

namespace Org\Util;


class MessagePush
{

    /**
     * redis连接初始化
     * MessagePush constructor.
     */
    public function __construct()
    {
        global $redis;
        $redis = new \Redis();
        if (!$redis->connect(C('REDIS_CONFIG.HOSTNAME'), C('REDIS_CONFIG.PORT'))) {
            throw new \Exception($redis->getLastError());
        }
        $redis->auth(C('REDIS_CONFIG.PASSWORD'));   //redis连接密码
    }

    /**
     * 向单个或多个用户推送消息
     * @param $uid array|string 消息接收用户
     * @param $order string|int  订单编号
     * @param $orderType 消息类型 3线上订单 4线下订单 5系统通知
     * @return bool 返回状态
     * @throws \Exception   抛出异常
     */
    public static function pushMsg($uid, $order, $orderType)
    {
        global $redis;
        //如果是系统消息,不允许用此方法发送
        if (empty($order) || $orderType == 5 || empty($uid)) return false;

        //判断uid是否是数组
        if (!is_array($uid)) $uid = array($uid);

        //多个用户消息处理
        foreach ($uid as $u) {
            //检测redis中是否存在数据
            if ($redis->hExists('socketMessage', $u)) {
                //取出已存在的消息内容
                $redisdata = $redis->hGet('socketMessage', $u);
                $orders = unserialize($redisdata);
                //将新消息订单与redis中的订单整合
                $orders[$order] = $orderType;
                //写入改变redis中的消息内容
                if ($redis->hSet('socketMessage', $u, serialize($orders)) === false) {
                    return false;
                }

            } else {
                //redis中不存在该uid数据,直接添加消息记录
                $insertData = array(
                    $order => $orderType
                );
                if ($redis->hSet('socketMessage', $u, serialize($insertData)) === false) {
                     return false;
                }
            }

        }

        //执行消息推送
        Gateway::sendToUid($uid, json_encode(array(
            'type' => $orderType,
            'data' => array(
                'order' => (string)$order
            )
        )));

    }

    /**
     * 获取用户regID
     * @param $id   用户ID
     * @return bool|string  regid/false
     */
    public static function getRegId($id){
        global $redis;
        $regId = $redis->hGet('iosRegId', $id);
        return $regId === false ? false : $regId;
    }


    /**
     * 获取redis中的该用户消息
     * @param $uid
     */
    public static function getListOrder($uid){
        global $redis;
        if ($redis->hExists('socketMessage', $uid)) {
            //取出已存在的消息内容
            $redisdata = $redis->hGet('socketMessage', $uid);
            $redisdata = unserialize($redisdata);
            return $redisdata;
        }
        return false;
    }

    /**
     * @param $message
     */
    public function pushToAll($message)
    {
        $message = array(
            'type' => 5,
            'data' => array(
                'message' => $message
            )
        );

        Gateway::sendToAll($message);
    }
}