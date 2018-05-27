<?php
/**
 * websocket业务逻辑
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */

use \GatewayWorker\Lib\Gateway;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    public static function onWorkerStart($businessWorker)
    {
        global $redis;
        //实例化redis
        $redis = new Redis();
        if (!$redis->connect('127.0.0.1', '6379')) {
            return;
        }
        $redis->auth('OdJyBZivF%eVdtlF');
    }


    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 消息
     */
    public static function onMessage($client_id, $message)
    {
        global $redis;
        //var_dump($message);
        //如果message是json对象就转换为json字符串再转换为数组
        $res = json_decode($message, true);

        //var_dump($res);
        //echo "----------------------------------------------------------" . PHP_EOL;
        //解析客户端传的数据
        //判断数据是否存在.
        if ($res == false || is_array($res) === false) return;

        //获取用户uid
        if (!isset($res['data']['uid'])) return;
        $uid = $res['data']['uid'];
        //判断type是否存在
        if (!isset($res['type'])) return;

        //判断socket是否传入token
        if (!isset($res['token'])) return;

        //在redis中查询uid对应的token是否存在
        if ($token = $redis->get('mch_' . $uid)) {
            //对比传入token是否与redis中token一致
            if ($token != $res['token']) {
                Gateway::sendToClient($client_id, json_encode(["type" => 2, "version" => "v1", "data" => ['uid' => $uid, 'isclose' => 1]]));
                return;
            }
        } else {
            Gateway::sendToClient($client_id, json_encode(["type" => 2, "version" => "v1", "data" => ['uid' => $uid, 'isclose' => 1]]));
            return;
        }

        //消息类型为心跳时，直接返回消息数据
        if ($res['type'] == 1) {
            //将客户端发送的消息返回
            Gateway::sendToClient($client_id, $message);
            return;
        }

        //断开连接后重连/首次连接（服务端无绑定记录）
        if ($res['type'] == 2) {

            //如果还没有client_id与uid绑定就执行绑定
            Gateway::bindUid($client_id, $uid);

            //获取当前uid绑定的所有client_id
            $cids = Gateway::getClientIdByUid($uid);

            //统计绑定的client_id数量
            $count = count($cids);
            if ($count > 1) {
                array_pop($cids);
                foreach ($cids as $cid) {
                    //将当前所有已连接断掉
                    Gateway::closeClient($cid);
                }
                //清除绑定记录后重新绑定一次
                Gateway::bindUid($client_id, $uid);
            }

            //判断redis中是否存在数据
            if ($redis->hExists('kpzsocket', $uid)) {
                $orders = $redis->hGet('kpzsocket', $uid);
                $orders = unserialize($orders);
                //如果订单为空
                if (!$orders) return;
                $list = array_unique($orders);
                foreach ($list as $v) {
                    $data = array_keys($orders, $v);
                    //推送历史消息
                    Gateway::sendToClient($client_id, json_encode(["type" => $v, "version" => "v1", "data" => ['uid' => $uid, 'order_no' => $data]]));
                }
            }
        }

        //收到客户端的回执
        if ($res['type'] == 3) {
            //删除用户的所有历史消息记录
            $redis->hDel('kpzsocket', $uid);

            //判断订单是否存在
            /*if (!isset($res['data']['order_no']) || empty($res['data']['order_no'])) {
                return;
            }*/

            //判断当前uid是否在redis中存在
            /*if ($redis->hExists('kpzsocket', $uid)) {
                $orders = $redis->hGet('kpzsocket', $uid);
                //将数据反序列化转化成数组
                $orders = unserialize($orders);

                $userOrders = $res['data']['order_no'];
                //判断传入订单是否是数组
                $userOrders = is_array($userOrders) ? $userOrders : array($userOrders);
                $orderArray = array();
                foreach ($userOrders as $order) {
                    $orderArray[] = $order;
                }

                //遍历传入的订单号,删除指定的订单.
                foreach ($orderArray as $v) {
                    unset($orders[$v]);
                }

                //判断$orders是否为空
                if (empty($orders)) {
                    $redis->hDel('kpzsocket', $uid);
                } else {
                    //重新设置redis中的uid对应值
                    $redis->hSet('kpzsocket', $uid, serialize($orders));
                }
            }*/
        }
    }


}
