<?php
/**
 * 使用信号来处理消费者
 */
require_once __DIR__ . '/../common.php';

class rabbitmqServer
{
    protected $connection = null;
    protected $pcntlSwitch = false;
    protected $channel = null;
    protected $consumerTag = 'kpz_order_consumer';

    /**
     * 设置信号和连接
     * Consumer constructor.
     */
    public function __construct()
    {
        if ($this->pcntlSwitch) {
            if (extension_loaded('pcntl')) {
                define('AMQP_WITHOUT_SIGNALS', false);
                pcntl_signal(SIGTERM, [$this, 'signalHandler']);
                pcntl_signal(SIGHUP, [$this, 'signalHandler']);
                pcntl_signal(SIGINT, [$this, 'signalHandler']);
                pcntl_signal(SIGQUIT, [$this, 'signalHandler']);
                pcntl_signal(SIGUSR1, [$this, 'signalHandler']);
                pcntl_signal(SIGUSR2, [$this, 'signalHandler']);
                pcntl_signal(SIGALRM, [$this, 'alarmHandler']);
            } else {
                echo '[X] ' . date('Y-m-d H:i:s') . ' Unable to process signals.' . PHP_EOL;  //无法处理信号。
                exit(1);
            }
        }

        $ssl = null;
        if (PORT === 5671) {
            $ssl = [
                'verify_peer' => false,
                'verify_peer_name' => false
            ];
        }
        $this->connection = new PhpAmqpLib\Connection\AMQPSSLConnection(
            HOST,
            PORT,
            USER,
            PASS,
            VHOST,
            $ssl,
            [
                'read_write_timeout' => 30,    // 心跳超时时间,至少是心跳时间的2倍
                'keepalive' => false, // 不使用SSL连接
                'heartbeat' => 15
            ]
        );
    }

    /**
     * 信号处理程序
     * @param $signalNumber
     */
    public function signalHandler($signalNumber)
    {
        echo '[X] ' . date('Y-m-d H:i:s') . ' Handling signal: #' . $signalNumber . PHP_EOL;
        global $consumer;
        switch ($signalNumber) {
            case SIGTERM:  // 15 : supervisor default stop
            case SIGQUIT:  // 3  : kill -s QUIT
                $consumer->stopHard();
                break;
            case SIGINT:   // 2  : ctrl+c
                $consumer->stop();
                break;
            case SIGHUP:   // 1  : kill -s HUP
                $consumer->restart();
                break;
            case SIGUSR1:  // 10 : kill -s USR1
                // send an alarm in 1 second
                pcntl_alarm(1);
                break;
            case SIGUSR2:  // 12 : kill -s USR2
                // send an alarm in 10 seconds
                pcntl_alarm(10);
                break;
            default:
                break;
        }
        return;
    }

    /**
     * 报警处理程序
     * @param $signalNumber
     */
    public function alarmHandler($signalNumber)
    {
        echo '[X] ' . date('Y-m-d H:i:s') . ' Handling alarm: #' . $signalNumber . PHP_EOL;
        echo memory_get_usage(true) . PHP_EOL;
        return;
    }

    /**
     * 消息处理程序
     * @param \PhpAmqpLib\Message\AMQPMessage $message
     */
    public function messageHandler(PhpAmqpLib\Message\AMQPMessage $message)
    {
        echo "\n--------\n";
        echo $message->body;
        echo "\n--------\n";
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        if ($message->body === 'quit') {
            $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
        }
    }

    /**
     * 开启一个消费者连接
     */
    public function start()
    {
        echo 'Starting consumer.' . PHP_EOL;
        $exchange = 'router';
        $queue = 'msgs';
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($queue, false, true, false, false);
        $this->channel->exchange_declare($exchange, 'direct', false, true, false);
        $this->channel->queue_bind($queue, $exchange);
        $this->channel->basic_consume(
            $queue,
            $this->consumerTag,
            false,
            false,
            false,
            false,
            [$this, 'messageHandler'],
            null,
            ['x-cancel-on-ha-failover' => ['t', true]] // 故障转移到另一个节点
        );
        echo 'Enter wait.' . PHP_EOL;
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
        echo 'Exit wait.' . PHP_EOL;
    }

    /**
     * 开启一个rpc消费者服务
     */
    public function rpcStart()
    {
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare('kpz_order', false, false, false, false);

        echo '>>> Waiting Request <<<' . PHP_EOL;

        //每次只处理一条消息
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume(
            'kpz_order',
            'kpz_order_consume',
            false,
            false,   //no_ack true不确认消息 false确认消息
            false,
            false,
            [$this, 'rpcMessageHandler'],
            null,
            ['x-cancel-on-ha-failover' => ['t', true]] // 故障转移到另一个节点
        );

        //一有消息就马上处理
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }

        //退出等待
        echo 'Exit wait.' . PHP_EOL;
    }

    /**
     * RPC消息处理程序
     * @param \PhpAmqpLib\Message\AMQPMessage $message
     */
    public function rpcMessageHandler(PhpAmqpLib\Message\AMQPMessage $message)
    {
        //获取唯一识别码与回调队列
        $corre_id = $message->get('correlation_id');
        $reply_to = $message->get('reply_to');

        //将队列消息字符串解析
        $data = json_decode($message->body, true);

        $return_data = '';
        //1卡座订单 2卡套订单 3散套订单
        //buy_type 购买类型 1正常下单 2续酒下单
        $version = isset($data['version']) ? $data['version'] : '0';

        //初始化设置值
        $data['buy_type'] = isset($data['buy_type']) ? $data['buy_type'] : 99999;
        $data['order']['is_bar'] = isset($data['order']['is_bar']) ? $data['order']['is_bar'] : 99999;

        switch ($version) {
            case 'v1.1':
                /**
                 * api服务端开发版本号
                 * version v1.1
                 */
                require_once __DIR__ . '/queuev2.class.php';

                //创建套餐订单
                if (in_array($data['order']['order_type'], array(0, 2, 3)) && $data['buy_type'] == 1) {
                    //执行卡座锁定与订单创建
                    $return_data = queuev2::buildGoodsOrder($data);
                }

                //线上预定卡座订单
                if ($data['order']['order_type'] == 1 && $data['buy_type'] == 1) {
                    //执行卡座锁定与订单创建
                    $return_data = queuev2::buidSeatOrder($data);
                }

                //order_type为10,线下卡座订单
                if ($data['order']['order_type'] == 10) {
                    //执行卡座锁定与订单创建
                    $return_data = queuev2::buidOfflineSeatOrder($data);
                }

                //续酒 购买类型为:2续酒, 并且不是拼吧订单
                if ($data['buy_type'] == 2 && $data['order']['is_bar'] == 0) {
                    $return_data = queuev2::buildMultiRenewOrder($data);
                }

                //拼吧下单(正常)
                if ($data['order']['is_bar'] == 1 && $data['buy_type'] == 3) {
                    $return_data = queuev2::buidBarOrder($data);
                }

                //拼吧续酒
                if ($data['order']['is_bar'] == 1 && $data['buy_type'] == 4) {
                    $return_data = queuev2::buidRenewBarOrder($data);
                }

                break;
            default:
                /**
                 * api服务端开发版本号
                 * version v1.0
                 */
                require_once __DIR__ . '/queuev1.class.php';

                if (!$data['order']['order_type']) {
                    //线上预定卡座订单
                    if ($data['order']['order_type'] == 1 && $data['buy_type'] == 1) {
                        //执行卡座锁定与订单创建
                        $return_data = queuev1::buidSeatOrder($data);
                    }

                    //为2和3时,创建套餐订单
                    if (in_array($data['order']['order_type'], array(2, 3)) && $data['buy_type'] == 1) {
                        //执行卡座锁定与订单创建
                        $return_data = queuev1::buidPackOrder($data);
                    }

                    //order_type为10,线下卡座订单
                    if ($data['order']['order_type'] == 10) {
                        //执行卡座锁定与订单创建
                        $return_data = queuev1::buidOfflineSeatOrder($data);
                    }

                    //续酒=>续散套酒
                    if ($data['order']['order_type'] == 3 && $data['buy_type'] == 2) {
                        $return_data = queuev1::buidRenewPackOrder($data);
                    }
                }
        }

        if (!isset($return_data) || empty($return_data)) {
            $return_data = '{"code":400,"msg":"无法处理的请求数据"}';
        }

        //处理完成的回调数据
        $return_data = (string)$return_data;
        //创建回调信息
        $msg = new PhpAmqpLib\Message\AMQPMessage(
            $return_data,
            array('correlation_id' => $corre_id)
        );

        //将消息推送到客户端
        $message->delivery_info['channel']->basic_publish($msg, '', $reply_to);
        //手动ack消息确认
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag'], true);
    }

    /**
     * 重启消费者
     */
    public function restart()
    {
        echo 'Restarting consumer.' . PHP_EOL;
        $this->stopSoft();
        $this->start();
    }

    /**
     * 停止关闭的连接与消费者
     */
    public function stopHard()
    {
        echo 'Stopping consumer by closing connection.' . PHP_EOL;
        $this->connection->close();
    }

    /**
     * 停止关闭的频道与消费者
     */
    public function stopSoft()
    {
        echo 'Stopping consumer by closing channel.' . PHP_EOL;
        $this->channel->close();
    }

    /**
     * 告诉服务器你将停止消耗, 它将结束最后一条消息，不再给你发送任何信息。
     */
    public function stop()
    {
        echo 'Stopping consumer by cancel command.' . PHP_EOL;
        // 这会卡住，没有最后两个参数集就无法退出。
        $this->channel->basic_cancel($this->consumerTag, false, true);
    }
}

/******************************
 * 启动服务
 * new Consumer 实例化消费者对象
 * rpcStart 启动rpc服务
 *****************************/
$consumer = new rabbitmqServer();
$consumer->rpcStart();