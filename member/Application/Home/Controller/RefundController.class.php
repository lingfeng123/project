<?php
/**
 * Created by PhpStorm.
 * User: nano
 * Date: 2018/3/11 0011
 * Time: 14:07
 */

namespace Home\Controller;


use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\String;
use Org\Util\Tools;
use Think\Controller;

@date_default_timezone_set('PRC');

class RefundController extends Controller
{
    /**
     *
     */
    public function createRefund()
    {
        //查询订单
        $order_no = '1517308908368347';     //订单编号

        //查询退款数据
        $pay_Record = M('payment_record')->where(['order_no' => $order_no])->find();
        if (!$pay_Record){
            return false;
        }
        $pay_Record['refund_reason'] = '拒绝订单退款';    //退款原因

        //微信退款
        if ($pay_Record['payment'] == 2){
            $this->wxpayRefund($pay_Record);
        }

        //支付宝退款
        if ($pay_Record['payment'] == 3){
            $this->alipayRefund($pay_Record);
        }
    }

    /**
     * 支付宝退款操作
     * @param $pay_Record
     * @return bool
     */
    public function alipayRefund($pay_Record)
    {
        //载入类文件
        vendor('alipay.AopClient');
        vendor('alipay.request.AlipayTradeRefundRequest');

        //获取支付宝支付配置信息
        $alipayConfig = C('alipay');

        //实例
        $aop = new \AopClient;
        $aop->gatewayUrl = $alipayConfig['gate_way_url'];
        $aop->appId = $alipayConfig['app_id'];
        $aop->rsaPrivateKey = $alipayConfig['rsa_private_key'];
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->signType = $alipayConfig['sign_type'];
        $aop->alipayrsaPublicKey = $alipayConfig['alipay_rsa_public_key'];

        $request = new \AlipayTradeRefundRequest();
        $bizcontent = [
            'out_trade_no' => $pay_Record['order_no'],     //订单支付时传入的商户订单号,不能和 trade_no同时为空。
            'trade_no' => $pay_Record['trade_no'],         //支付宝交易号，和商户订单号不能同时为空
            'refund_amount' => $pay_Record['receipt_fee'],    //需要退款的金额，该金额不能大于订单金额,单位为元，支持两位小数
            'refund_reason' => $pay_Record['refund_reason'],  //退款原因
        ];

        $request->setBizContent(json_encode($bizcontent));
        $refundResult = json_decode(json_encode($aop->execute($request)), true);
        $refundResult = $refundResult['alipay_trade_refund_response'];
        if (!empty($refundResult['code']) && $refundResult['code'] == 10000){
            //写入退款记录
            $this->createRefundRecord($pay_Record, $refundResult['refund_fee'], strtotime($refundResult['gmt_refund_pay']));
            Response::setSuccessMsg('支付宝退款成功');
            Response::success();
        }else{
            Response::error(ReturnCode::DB_SAVE_ERROR, '支付宝退款失败');
        }
    }

    /**
     * 微信支付退款操作
     * @param $pay_Record
     * @return bool
     */
    public function wxpayRefund($pay_Record)
    {
        //微信支付配置数据
        $config = [];
        if ($pay_Record['trade_type'] == 'APP'){
            $config = C('APP_WXPAY_OPTION');
        }else{
            $config = C('WXPAY_OPTION');
        }

        $pay_Record['receipt_fee'] = $pay_Record['receipt_fee'] * 100;
        //微信退款请求数据
        $postData = [
            'appid' => $config['APPID'],    //应用ID
            'mch_id' => $config['MCH_ID'],  //微信支付分配的商户号
            'nonce_str' => strtoupper(md5(String::randString(20))), //随机字符串，不长于32位
            'transaction_id' => $pay_Record['trade_no'],
            'out_refund_no' => Tools::refund_number(),  //商户退款编号
            'total_fee' => $pay_Record['receipt_fee'],  //退款总金额
            'refund_fee' => $pay_Record['receipt_fee'], //退款金额
            'refund_desc' => $pay_Record['refund_desc'],
        ];
        $postData['sign'] = Tools::getWxPaySign($postData,$config['KEY']);

        //将数据转化为xml
        $postXml = Tools::arrayToXml($postData);
        $response = Tools::postSSLXml('https://api.mch.weixin.qq.com/secapi/pay/refund', $postXml, $config['CERT_PATH']);
        $refundResult = Tools::xmlToArray($response);
        if ($refundResult['return_code'] == "SUCCESS" && $refundResult['result_code'] == 'SUCCESS'){
            //写入退款记录
            $this->createRefundRecord($pay_Record, $refundResult['refund_fee'], time());
            Response::setSuccessMsg('微信'.$pay_Record['trade_type'].'退款成功');
            Response::success();
        }else{
            Response::error(ReturnCode::DB_SAVE_ERROR, '微信退款失败');
        }
    }

    /**
     * 写入退款记录
     * @param $pay_Record
     * @param $refundResult
     * @return bool
     */
    private function createRefundRecord($pay_Record, $refund_fee, $refund_time)
    {
        //写入退款记录
        $refundData = [
            'member_id' => $pay_Record['member_id'],
            'merchant_id' => $pay_Record['merchant_id'],
            'app_id' => $pay_Record['merchant_id'],
            'trade_no' => $pay_Record['trade_no'],
            'order_no' => $pay_Record['order_no'],
            'order_id' => $pay_Record['order_id'],
            'trade_status' => 2,
            'receipt_fee' => $pay_Record['receipt_fee'],
            'refund_fee' => $refund_fee,
            'buy_type' => $pay_Record['buy_type'],
            'pay_type' => $pay_Record['pay_type'],
            'refund_no' => Tools::refund_number(),
            'refund_time' => $refund_time,
            'created_time' => time(),
            'refund_desc' => $pay_Record['refund_reason'],
        ];
        if (!M('refund_record')->add($refundData)){
            return false;
        }

        return true;
    }
}