<?php
/**
 * FileName: SourceController.class.php
 * User: Comos
 * Date: 2018/1/12 15:23
 */

namespace Home\Controller;


use Think\Controller;

class SourceController extends Controller
{
    /**
     * 生成订单二维码
     * @param $order_no
     * @param $order_id
     * @param $order_type
     * @param $merchant_id
     */
    public function orderQrcode($order_no, $order_id, $order_type, $merchant_id)
    {
        vendor("phpqrcode.phpqrcode");
        $data = [
            'order_no' => $order_no,
            'order_id' => $order_id,
            'order_type' => $order_type,
            'merchant_id' => $merchant_id,
            'flag' => 'kpz'
        ];
        \QRcode::png(json_encode($data), false, "L", 5, 1);
    }


    /**
     * 用户推广注册地址推广二维码URL
     * @param $url
     */
    public function userqrcode($url)
    {
        vendor("phpqrcode.phpqrcode");
        \QRcode::png($url, false, 'L', 10, 1);
    }
}