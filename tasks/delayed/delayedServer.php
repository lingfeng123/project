<?php
/**
 * FileName: server.class.php
 * User: Comos
 * Date: 2018/3/16 15:35
 */

set_time_limit(0);
ignore_user_abort(true);

require_once __DIR__ . '/../common.php';


class delayedServer
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

        $this->connect();
    }

    /**
     * 创建beastalkd连接
     */
    protected function connect()
    {
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
        //记录日志
        $message = date('Y-m-d H:i:s') . ' Handling signal: #' . $signalNumber;
        Tools::write($message, 'INFO', __FILE__, __METHOD__, LOG_PATH);

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
                pcntl_alarm(1); // send an alarm in 1 second
                break;
            case SIGUSR2:  // 12 : kill -s USR2
                pcntl_alarm(10);    // send an alarm in 10 seconds
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
        $message = date('Y-m-d H:i:s') . ' Handling alarm: #' . $signalNumber . ' memory used:' . memory_get_usage(true);
        Tools::write($message, 'INFO', __FILE__, __METHOD__, LOG_PATH);
        return;
    }

    /**
     * 开启消费者服务
     */
    public function start()
    {
        while (true) {
            try {
                $this->beanstalkdStats();
                //$this->checkPsAuxInfo();

                //监听队列服务是否可用 true / false
                if ($this->pheanstalk->getConnection()->isServiceListening()) {

                    $job = $this->pheanstalk->watch($this->tubeName)->ignore('default')->reserve();
                    $this->pheanstalk->touch($job);

                    //获取队列消息内容
                    $data = $job->getData();

                    //判断队列数据是否存为空
                    if ($data) {
                        $this->messageHandler($data);
                    }

                    //删除工作任务
                    $this->pheanstalk->delete($job);
                } else {
                    //不可用写入日志
                    Tools::write('beanstalkd server bad', 'ERR', __FILE__, __METHOD__, LOG_PATH . 'beanstalkd_');
                }

                sleep(2);
            } catch (Exception $exception) {
                //restart connect
                $this->connect();

                //抛出程序异常写入日志
                Tools::write($exception, 'ERR', __FILE__, __METHOD__, LOG_PATH . 'beanstalkd_');
            }
        }
    }

    /**
     * 记录进程状态
     */
    protected function checkPsAuxInfo()
    {
        $cmd = 'ps axu|grep delayedServer.php';
        $result = shell_exec($cmd);
        Tools::write($result, 'INFO', __FILE__, __METHOD__, LOG_PATH . 'grep_');

        /*$fp = fopen('php://stdin', 'r');
        $content = '';
        if ($fp) {
            while ($line = fgets($fp, 4096)) {
                $content = $line . PHP_EOL;
            }
            fclose($fp);
        }
        var_dump($fp);

        Tools::write($content, 'INFO', __FILE__, __METHOD__, LOG_PATH . 'stdin_');*/
    }

    /**
     * 记录服务日志
     */
    protected function beanstalkdStats()
    {
        $serverInfo = $this->pheanstalk->stats();
        Tools::write(json_encode($serverInfo), 'INFO', __FILE__, __METHOD__, LOG_PATH . 'beanstalkd_');

        $tubeInfo = $this->pheanstalk->statsTube($this->tubeName);
        Tools::write(json_encode($tubeInfo), 'INFO', __FILE__, __METHOD__, LOG_PATH . 'beanstalkd_');
    }

    /**
     * 检查运行进程数量
     * @return bool
     */
    protected function checkAlive()
    {
        $cmd = 'ps axu|grep delayedServer.php|grep -v grep|wc -l';
        $ret = shell_exec("$cmd");
        $ret = intval(rtrim($ret, "rn"));
        if ($ret > 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 数据处理方法
     */
    protected function messageHandler($data)
    {
        /**
         * data应包含的内容
         * version: 版本号 v1.1
         * order_id:订单ID
         * exc_type:执行类型 1订单取消 2订单作废 3订单逾期 4 拼吧用户支付订单超时
         * buy_type:1普通下单 2普通续酒 3拼吧下单 4拼吧续酒
         * order_no: 订单编号
         */
        Tools::write($data, 'INFO', __FILE__, __METHOD__, LOG_PATH);
        $data = json_decode($data, true);

        try {
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
                            $rs = $OrderHandle->generalOrderTimeout($data);
                            /*if ($rs === false && ($OrderHandle->haveTime)>0) {
                                $this->resetToQueue($data, $OrderHandle->haveTime);
                            }*/
                        }

                        //订单作废
                        if ($data['exc_type'] == 2) {
                            $rs = $OrderHandle->seatExpiredCancel($data);
                            if ($rs === false && ($OrderHandle->haveTime) > 0) {
                                $this->resetToQueue($data, $OrderHandle->haveTime);
                            }
                        }

                        //订单逾期
                        if ($data['exc_type'] == 3) {
                            $rs = $OrderHandle->generalOrderOverdue($data);
                            /* if ($rs === false && ($OrderHandle->haveTime)>0) {
                                 $this->resetToQueue($data, $OrderHandle->haveTime);
                             }*/
                        }
                    }

                    if (in_array($data['buy_type'], [3, 4])) {
                        //拼吧订单超时
                        if ($data['exc_type'] == 1) {
                            $rs = $OrderHandle->pinBarTimeout($data);
                            /*if ($rs === false && ($OrderHandle->haveTime)>0) {
                                $this->resetToQueue($data, $OrderHandle->haveTime);
                            }*/
                        }

                        if ($data['exc_type'] == 4) {
                            $rs = $OrderHandle->memberBarTimeOut($data);
                            /* if ($rs === false && ($OrderHandle->haveTime)>0) {
                                 $this->resetToQueue($data, $OrderHandle->haveTime);
                             }*/
                        }
                    }
                    break;
            }

        } catch (Exception $exception) {
            Tools::write($exception, 'ERR', __FILE__, __METHOD__, LOG_PATH);
        }
    }

    /**
     * 重置消息到队列
     * @param $data
     * @param $time
     */
    protected function resetToQueue($data, $time)
    {
        if ($time > 0) {
            $this->pheanstalk->useTube($this->tubeName)->put(json_encode($data), 0, $time);
        }
    }
}

/**
 * 启动服务
 */
$consumer = new delayedServer();
$consumer->start();