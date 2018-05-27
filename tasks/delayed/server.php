<?php
/**
 * beanstalk 消费者端
 */
//加载公共文件
require_once __DIR__ . '/../common.php';

//加载运行文件
require_once 'OrderHandlev1.class.php';
require_once 'ExcuteRefundv1.class.php';

/**
 * 执行beanstalkd代码
 */
$pheanstalk = new Pheanstalk\Pheanstalk($GLOBALS['CONFIG']['BEANS_OPTIONS']['HOST']);
$tube_name = $GLOBALS['CONFIG']['BEANS_OPTIONS']['TUBE_NAME'][0];

while (true) {
    $job = $pheanstalk->watch($tube_name)->reserve();
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
        $data = json_decode($data, true);

        $OrderHandle = new OrderHandle($GLOBALS['CONFIG']);
        //判断版本
        switch ($data['version']) {
            case 'v1.1':

                //购买类型 1普通下单 2续酒下单 3拼吧 4拼吧下单
                if (in_array($data['buy_type'], [1, 2])) {
                    //订单超时
                    if ($data['exc_type'] == 1) {
                        if ($time = $OrderHandle->generalOrderTimeout($data)) {
                            $pheanstalk->useTube($tube_name)->put(json_encode($data), 1024, $time);
                        }
                        var_dump($time);
                    }

                    //订单作废
                    if ($data['exc_type'] == 2) {
                        if ($time = $OrderHandle->seatExpiredCancel($data)) {
                            $pheanstalk->useTube($tube_name)->put(json_encode($data), 1024, $time);
                        }
                        var_dump($time);
                    }

                    //订单逾期
                    if ($data['exc_type'] == 3) {
                        if ($time = $OrderHandle->generalOrderOverdue($data)) {
                            $pheanstalk->useTube($tube_name)->put(json_encode($data), 1024, $time);
                        }
                        var_dump($time);
                    }
                }

                if (in_array($data['buy_type'], [3, 4])) {
                    //拼吧订单超时
                    $OrderHandle->pinBarTimeout($data);
                }

                break;
        }

        //删除工作任务
        $pheanstalk->delete($job);
        sleep(1);
    }
}
