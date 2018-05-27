<?php
/**
 * FileName: redis.php
 * User: Comos
 * Date: 2018/5/2 10:34
 */
echo "<pre>";

//var_dump($_SERVER);
$tel = isset($_GET['tel']) ? $_GET['tel'] : '';
if (empty($tel)){
    exit('请传入电话号码tel,多个号码格式: 13523623121|13523623121|13523623121');
}
$redis = new Redis();
$redis->connect('127.0.0.1');
$redis->auth('OdJyBZivF%eVdtlF');

$tel = explode('|', $tel);

foreach ($tel as $item) {
    $rs = $redis->del('kpz_sms_anti_theft_' . $item);
    var_dump($rs);
}

//http://console.kongpingzi.net/redis.php?tel=13551273600|13730686533|18628996429|15883700780