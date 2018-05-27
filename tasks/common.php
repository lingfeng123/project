<?php

@date_default_timezone_set("PRC");
$config = require_once 'config.php';    //获取配置
$GLOBALS['CONFIG'] = $config;  //获取队列名

//系统常量定义
define('ROOT_NAME', 'tasks');
define('VENDOR_NAME', 'vendor');
define('ROOT_PATH', substr(__FILE__, 0, strrpos(__FILE__, ROOT_NAME)) . ROOT_NAME . DIRECTORY_SEPARATOR);
define('VENDOR_PATH', ROOT_PATH . VENDOR_NAME . DIRECTORY_SEPARATOR);
define('LOG_PATH', ROOT_PATH . 'logs/');

//加载类文件
require_once VENDOR_PATH . 'lib/PDOMysql.class.php';    //数据库操作类
require_once VENDOR_PATH . 'lib/YunpianSms.class.php';  //云片短信发送类
require_once VENDOR_PATH . 'lib/Tools.class.php';       //扩展方法工具类
require_once VENDOR_PATH . 'lib/Wechat.class.php';      //微信开发工具类
require_once VENDOR_PATH . 'autoload.php';              //composer自动加载类

//定义RabbitMQ配置项
define('HOST', $config['RABBITMQ_OPTIONS']['HOST']);       //rabbitmq主机
define('PORT', $config['RABBITMQ_OPTIONS']['PORT']);       //rabbitmq端口
define('USER', $config['RABBITMQ_OPTIONS']['USER']);       //rabbitmq登录用户
define('PASS', $config['RABBITMQ_OPTIONS']['PASS']);       //rabbitmq登录密码
define('VHOST', $config['RABBITMQ_OPTIONS']['VHOST']);     //rabbitmq目录

define('AMQP_DEBUG', false);    //rabbitmq开启了debug可在命令行模式下打印执行结果

//定义数据库常量
define('DB_HOST', $config['DATABASE']['DB_HOST']);              //主机
define('DB_DBNAME', $config['DATABASE']['DB_DBNAME']);          //数据库名称
define('DB_USERNAME', $config['DATABASE']['DB_USERNAME']);      //数据库用户名
define('DB_PASSWORD', $config['DATABASE']['DB_PASSWORD']);      //数据库密码