<?php
/**
 * FileName: SocketPush.class.php
 * User: Comos
 * Date: 2017/11/16 9:51
 */

namespace Org\Util;

use Think\Log;

class SocketPush
{

    private $_redis;
    private $_logFileName;

    /**
     * redis连接初始化
     * SocketController constructor.
     */
    public function __construct()
    {
        //日志文件名
        $this->_logFileName = "socket" . date('Y-m-d') . '.log';

        //redis连接
        $this->_redis = new \Redis();
        if (!@$this->_redis->connect(C('REDIS_CONFIG.HOSTNAME'), C('REDIS_CONFIG.PORT'))) {
            Log::write('redis connection failed', 'WARN', '', $this->_logFileName);
        }
        $this->_redis->auth(C('REDIS_CONFIG.PASSWORD'));   //redis连接密码
    }

    /**
     * 推送订单socket消息到用户
     *
     * @param $unique_ids  array|int   用户ID数组
     * @param $type int 推送消息类型: 1心跳 2连接 3线上订单 4线下新订单 5系统通知 6线下订座审核通过 7线下订座拒绝
     * @param $order_no array|string 订单编号
     * @param $message string  消息内容
     * @return mixed 无返回数据
     */
    public function pushOrderSocketMessage($unique_ids, $type, $order_no, $message = '')
    {
        $unique_ids = is_array($unique_ids) ? $unique_ids : array($unique_ids);
        $order_no = 'D' . $order_no;

        //循环推送socket消息
        foreach ($unique_ids as $unique_id) {
            //获取用户的uid
            $uid = AuthSign::getUserUid($unique_id, C('ACCOUNT_TYPE.EMPLOYEE'));
            //开始推送消息
            if (Gateway::isUidOnline($uid)) {
                //将消息存储到redis中
                $this->_saveSocketMessageToRedis($uid, $type, $order_no, $message);
                //组装推送数据
                $push_message = array(
                    'type' => $type,
                    'version' => 'v1',
                    'data' => array(
                        'uid' => $uid,
                        'order_no' => [$order_no],
                        'message' => $message
                    )
                );

                try {
                    //执行socket消息推送
                    Gateway::sendToUid($uid, json_encode($push_message));
                } catch (\Exception $sendException) {
                    //推送发生错误,记录日志
                    Log::write($sendException, Log::WARN, '', $this->_logFileName);
                }

            } else {
                //socket不在线,获取用户的regid
                if ($regid = $this->_getRegId($unique_id)) {
                    JpushSend::singlePush($regid, $type, $message, $order_no);  //regid存在,执行jpush推送
                } else {
                    //regid不存在,将消息保存到redis中
                    $this->_saveSocketMessageToRedis($uid, $type, $order_no, $message);
                }
            }
        }
    }


    /**
     * 将消息数据保存到redis中
     * @param $uid string 推送对象
     * @param $type int 推送消息类型: 1心跳 2连接 3线上订单 4线下新订单 5系统通知 6线下订座审核通过 7线下订座拒绝
     * @param $order_no int 订单号
     * @param string $message string 消息内容
     * @return bool
     */
    private function _saveSocketMessageToRedis($uid, $type, $order_no, $message = '')
    {
        //检测redis中是否存在数据
        if ($this->_redis->hExists('kpzsocket', $uid)) {
            //取出已存在的消息内容
            $redis_data = $this->_redis->hGet('kpzsocket', $uid);
            $orders = unserialize($redis_data);

            //去除重复的消息内容
            $orders = array_unique($orders);
            //删除新订单类型相同的历史消息
            foreach ($orders as $key => $order) {
                if ($order == $type) {
                    unset($orders[$key]);
                }
            }

            //将新消息订单与redis中的订单整合
            $orders[$order_no] = $type;
            //写入改变redis中的消息内容
            if ($this->_redis->hSet('kpzsocket', $uid, serialize($orders)) === false) {
                //记录日志
                Log::write('写入数据到redis失败||' . serialize($orders), Log::WARN, '', $this->_logFileName);
            }

        } else {
            //redis中不存在该uid数据,直接添加消息记录
            $insertData = array($order_no => $type);
            if ($this->_redis->hSet('kpzsocket', $uid, serialize($insertData)) === false) {
                //记录日志
                Log::write('写入数据到redis失败||' . serialize($insertData), Log::WARN, '', $this->_logFileName);
            }
        }
    }

    /**
     * 获取用户regID
     * @param $id   用户ID
     * @return bool|string  regid/false
     */
    private function _getRegId($id)
    {
        $regId = $this->_redis->hGet('kpziosregid', $id);

        return $regId === false ? false : $regId;
    }
}