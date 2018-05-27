<?php

/**
 * FileName: base.class.php
 * User: Comos
 * Date: 2018/3/15 18:22
 */
class base
{
    const SUCCESS = 200;                  //服务器成功返回用户请求的数据
    const INVALID_REQUEST = 400;          //请求失败
    const DB_CONNECT_FAIL = 109;          //数据库连接失败
    const DB_SAVE_ERROR = 102;           //数据存储失败
    const DB_READ_ERROR = 103;           //数据读取失败
    const NOT_STOCK = 104;               //库存不足
    const PARAM_WRONGFUL = 105;          //传入参数不合法

    /**
     * 输出响应数据
     * @param $code
     * @param string $msg
     * @param array $data
     * @return array|object|string
     */
    public static function response($code, $msg = '', $data = [])
    {
        $data = $data ? $data : (object)[];
        $returnData = array(
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        );
        $data = json_encode($returnData);
        return $data;
    }

    /**
     * 创建唯一订单号
     * @param integer $type 订单类型 1线上订单 2线下卡座预定订单 3签单订单
     * @return array|int|object|string
     */
    public static function _createOrderNumber($type = 1)
    {
        try {
            //建立PDO数据库连接
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_DBNAME, DB_USERNAME, DB_PASSWORD);
            $pdo->query('set names utf8;');
        } catch (PDOException $e) {
            return self::response(self::DB_CONNECT_FAIL, '数据库连接失败');
        }

        do {
            $order_no = self::create_order_number($type);   //生成订单号
            //查询数据库是否存在订单号
            $order = $pdo->query("SELECT `id` FROM `api_order` WHERE `api_order` . `order_no` = '$order_no' limit 1");
            $order = $order->fetch(PDO::FETCH_ASSOC);
            $order_id = $order['id'] ? 1 : 0;

        } while ($order_id);

        //返回生成的订单号
        return $order_no;
    }

    /**
     * 生成唯一订单号 订单长度为16位
     * @param integer $type 订单类型 1线上订单 2线下卡座预定订单 3签单订单
     * @return int|string
     */
    public static function create_order_number($type = 1)
    {
        @date_default_timezone_set("PRC");
        $time = microtime();
        $time_arr = explode(' ', $time);
        $time_arr[0] = substr($time_arr[0], 2, -2);
        $time_arr[1] = substr($time_arr[1], 1);
        $time_arr = array_reverse($time_arr);
        $time = implode('', $time_arr);
        $time = $type . $time;
        return $time;
    }
}