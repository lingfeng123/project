<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/21
 * Time: 14:36
 */
require_once __DIR__ . '/../common.php';

$Phbeanstalk=new Pheanstalk\Pheanstalk($GLOBALS['CONFIG']['BEANS_OPTIONS']['HOST']);
$tube_name=$GLOBALS['CONFIG']['BEANS_OPTIONS']['TUBE_NAME'][0];

$tubes=$Phbeanstalk->listTubes();
var_dump($tubes);

$job=$Phbeanstalk->peekDelayed($tube_name);

//$job=$Phbeanstalk->watch($tube_name)->peekDelayed();
var_dump($job);die;

//$jobstatus =$Phbeanstalk->statsJob($job);
