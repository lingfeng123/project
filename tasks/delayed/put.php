<?php
/**
 * beanstalk 消费者端
 */
//加载公共文件
require_once __DIR__ . '/../common.php';

$pheanstalk = new Pheanstalk\Pheanstalk($GLOBALS['CONFIG']['BEANS_OPTIONS']['HOST']);
$tube_name = $GLOBALS['CONFIG']['BEANS_OPTIONS']['TUBE_NAME'][0];

/**
 * data应包含的内容
 * version: 版本号 v1.1
 * order_id:订单ID
 * exc_type:执行类型 1订单取消 2订单作废 3订单逾期
 * buy_type:1普通下单 2普通续酒 3拼吧下单 4拼吧续酒
 * order_no: 订单编号
 */
$data = [
    'version' => 'v1.1',
    'order_id' => 1,
    'exc_type' => 3,
    'buy_type' => 1,
    'order_no' => 1632534625425636,
];
$pheanstalk = new Pheanstalk\Pheanstalk($GLOBALS['CONFIG']['BEANS_OPTIONS']['HOST']);
$pheanstalk->useTube($tube_name)->put(json_encode($data), 1024, 10);