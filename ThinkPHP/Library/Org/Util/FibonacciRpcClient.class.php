<?php
/**
 * Created by PhpStorm.
 * User: nano
 * Date: 2017/10/31 0031
 * Time: 22:12
 */

namespace Org\Util;

vendor('autoload');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class FibonacciRpcClient
{
    private $connection;
    private $channel;
    private $callback_queue;
    private $response;
    private $corr_id;

    private $queue_name;

    /**
     * 建立AMQP连接并绑定创建队列
     * FibonacciRpcClient constructor.
     */
    public function __construct($option)
    {
        $this->queue_name = $option['queue_name'];
        $this->connection = new AMQPStreamConnection($option['host'], $option['port'], $option['account'], $option['password']);
        $this->channel = $this->connection->channel();

        //回调队列
        list($this->callback_queue, ,) = $this->channel->queue_declare($option['callback_queue_name'], false, false, false, false);
        $this->channel->basic_consume($this->callback_queue, $option['callback_consume_name'], false, false, false, false, array($this, 'on_response'));
    }

    /**
     * 收到消费者处理完成后返回的数据
     * @param $rep
     */
    public function on_response($rep)
    {
        if ($rep->get('correlation_id') == $this->corr_id) {
            $this->response = $rep->body;
        }

        //ack消息确认
        $rep->delivery_info['channel']->basic_ack($rep->delivery_info['delivery_tag'], true);
    }

    /**
     * 业务代码中调用的方法,用于发送数据到队列
     * @param $n string 数据包
     * @return null RPC应答
     */
    public function call($n)
    {
        $this->response = null;
        $this->corr_id = uniqid();

        //创建消息对象
        $msg = new AMQPMessage(
            (string)$n,
            array(
                'correlation_id' => $this->corr_id,
                'reply_to' => $this->callback_queue
            )
        );

        //发送消息到队列
        $this->channel->basic_publish($msg, '', $this->queue_name);

        //阻塞接收返回消息
        while (!$this->response) {
            $this->channel->wait();
        }

        //关闭当前连接,释放资源
        $this->channel->close();
        $this->connection->close();

        return $this->response;
    }
}