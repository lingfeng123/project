<?php
/**
 * FileName: ListenBeanstalkd.php
 * User: Comos
 * Date: 2018/4/8 16:44
 */

//加载公共文件
require_once __DIR__ . '/../common.php';

$host = $GLOBALS['CONFIG']['BEANS_OPTIONS']['HOST'];                //主机
$tubeName = $GLOBALS['CONFIG']['BEANS_OPTIONS']['TUBE_NAME'][0];    //队列名
$programName = 'beanstalkdDelayed';                                 //supervisor任务名称
$cmd = "supervisorctl restart $programName";                        //重启消费者shell命令

//实例beanstalkd
$pheanstalk = new Pheanstalk\Pheanstalk($host);

$stats = $pheanstalk->getConnection()->isServiceListening();
if (!$stats) {
    Tools::write('Beanstalkd Server is crash', 'FATAL', __FILE__, __METHOD__, LOG_PATH . 'beanstalkd_');
}

//获取$tubeName的状态
$phobj = $pheanstalk->statsTube($tubeName);

//判断$tubeName的状态
//if ($phobj->getResponseName() != 'OK' || $phobj['current-watching'] < 1 || $phobj['current-waiting'] < 1) {
if ($phobj->getResponseName() != 'OK' || $phobj['current-waiting'] < 1) {
    exec($cmd, $reslut);
    Tools::write('timmer run log', 'INFO', __FILE__, __METHOD__, LOG_PATH . 'crontab_');
}