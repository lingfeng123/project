<?php

/**
 * FileName: server.class.php
 * User: Comos
 * Date: 2018/3/16 15:35
 */
require_once __DIR__ . '/../common.php';

class server
{
    protected $pheanstalk = null;   //当前队列服务连接
    protected $pcntlSwitch = false;     //信号控制开关
    protected $tubeName = null;

    /**
     * 设置信号和连接
     * Consumer constructor.
     */
    public function __construct()
    {
        if ($this->pcntlSwitch){
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

        //队列连接服务
        $this->pheanstalk = new Pheanstalk\Pheanstalk($GLOBALS['CONFIG']['BEANS_OPTIONS']['HOST']);
        //队列名
        $this->tubeName = $GLOBALS['CONFIG']['BEANS_OPTIONS']['TUBE_NAME'][0];
    }

    /**
     * 信号处理程序
     * @param $signalNumber
     */
    protected function signalHandler($signalNumber)
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
    protected function alarmHandler($signalNumber)
    {
        echo '[X] ' . date('Y-m-d H:i:s') . ' Handling alarm: #' . $signalNumber . PHP_EOL;
        echo memory_get_usage(true) . PHP_EOL;
        return;
    }

    /**
     * 重启消费者
     */
    public function start()
    {
        while (true) {
            $job = $this->pheanstalk->watch($this->tubeName)->reserve();
            $data = $job->getData();

            /**
             * data应包含的内容
             * version: 版本号 v1.1
             * order_id:订单ID
             * exc_type:执行类型 1订单取消 2订单作废 3订单逾期
             * buy_type:1普通下单 2普通续酒 3拼吧下单 4拼吧续酒
             * order_no: 订单编号
             */
            //判断队列数据是否存为空
            if ($data) {
                $this->messageHandler($data);
                //删除工作任务
                $this->pheanstalk->delete($job);
                sleep(1);
            }
        }
    }

    /**
     * 数据处理方法
     */
    protected function messageHandler($data)
    {
        $data = json_decode($data, true);

        //判断版本
        switch ($data['version']) {
            case 'v1.1':

                //加载运行文件
                require_once 'OrderHandlev1.class.php';
                require_once 'ExcuteRefundv1.class.php';

                $OrderHandle = new OrderHandlev1($GLOBALS['CONFIG']);
                //购买类型 1普通下单 2续酒下单 3拼吧 4拼吧下单
                if (in_array($data['buy_type'], [1, 2])) {
                    //订单超时
                    if ($data['exc_type'] == 1) {
                        $time = $OrderHandle->generalOrderTimeout($data);
                        $this->resetToQueue($data, $time);
                    }

                    //订单作废
                    if ($data['exc_type'] == 2) {
                        if ($time = $OrderHandle->seatExpiredCancel($data)) {
                            $this->pheanstalk->useTube($this->tubeName)->put(json_encode($data), 1024, $time);
                        }
                    }

                    //订单逾期
                    if ($data['exc_type'] == 3) {
                        if ($time = $OrderHandle->generalOrderOverdue($data)) {
                            $this->pheanstalk->useTube($this->tubeName)->put(json_encode($data), 1024, $time);
                        }
                    }
                }

                if (in_array($data['buy_type'], [3, 4])) {
                    //拼吧订单超时
                    $OrderHandle->pinBarTimeout($data);
                }
                break;
        }
    }

    /**
     * 重置消息到队列
     * @param $data
     * @param $time
     */
    protected function resetToQueue($data, $time){
        if ($time){
            $this->pheanstalk->useTube($this->tubeName)->put(json_encode($data), 1024, $time);
        }
    }
}

/**
 * 启动服务
 */
$consumer = new server();
$consumer->start();