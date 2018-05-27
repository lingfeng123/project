<?php

/**
 * Created by PhpStorm.
 * User: nano
 * Date: 2018/3/11 0011
 * Time: 21:43
 */
class ExcuteRefundv1
{
    public $config;
    private $bar_id;
    private $order_no;
    private $order_id;

    /**
     * OrderHandle constructor.
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * 根据支付订单查询支付记录并执行退款
     * @param $bar_user_order
     * @param $order_no
     * @param $bar_id
     * @return bool
     */
    public function createRefund($bar_user_order, $order_no, $bar_id)
    {
        $this->order_id = $bar_user_order['id'];    //拼吧用户订单ID
        $this->order_no = $order_no;    //拼吧用户订单号
        $this->bar_id = $bar_id;        //拼吧订单ID

        $refund_reason = '订单取消自动退款';

        //余额退款
        if ($bar_user_order['pay_type'] == 1) {
            $pay_Record['refund_reason'] = $refund_reason;
            return $this->walletRefund($bar_user_order, $refund_reason);
        }

        //微信退款
        if ($bar_user_order['pay_type'] == 2) {
            $pay_Record = $this->getPayRecord();
            if (!$pay_Record) {
                return false;
            }
            $pay_Record['refund_reason'] = $refund_reason;
            return $this->wxpayRefund($pay_Record);
        }

        //支付宝退款
        if ($bar_user_order['pay_type'] == 3) {
            $pay_Record = $this->getPayRecord();
            if (!$pay_Record) {
                return false;
            }
            $pay_Record['refund_reason'] = $refund_reason;
            return $this->alipayRefund($pay_Record);
        }
    }

    /**
     * 获取支付记录
     * @return mixed
     */
    private function getPayRecord()
    {
        return M('payment_record')->where(['order_no' => $this->order_no])->find();
    }

    /**
     * 支付宝退款操作
     * @param $pay_Record
     * @return bool
     */
    private function alipayRefund($pay_Record)
    {
        //载入类文件
        require_once VENDOR_PATH . 'alipay/AopClient.php';
        require_once VENDOR_PATH . 'alipay/request/AlipayTradeRefundRequest.php';

        //获取支付宝支付配置信息
        $alipayConfig = $this->config['alipay'];

        //实例
        $aop = new AopClient();
        $aop->gatewayUrl = $alipayConfig['gate_way_url'];
        $aop->appId = $alipayConfig['app_id'];
        $aop->rsaPrivateKey = $alipayConfig['rsa_private_key'];
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->signType = $alipayConfig['sign_type'];
        $aop->alipayrsaPublicKey = $alipayConfig['alipay_rsa_public_key'];

        $request = new AlipayTradeRefundRequest();
        $bizcontent = [
            'out_trade_no' => $pay_Record['order_no'],     //订单支付时传入的商户订单号,不能和 trade_no同时为空。
            'trade_no' => $pay_Record['trade_no'],         //支付宝交易号，和商户订单号不能同时为空
            'refund_amount' => $pay_Record['receipt_fee'],    //需要退款的金额，该金额不能大于订单金额,单位为元，支持两位小数
            'refund_reason' => $pay_Record['refund_reason'],  //退款原因
        ];

        $request->setBizContent(json_encode($bizcontent));
        $refundResult = json_decode(json_encode($aop->execute($request)), true);
        $refundResult = $refundResult['alipay_trade_refund_response'];
        if (!empty($refundResult['code']) && $refundResult['code'] == 10000) {
            //写入退款记录
            return $this->createRefundRecord($pay_Record, $refundResult['refund_fee'], strtotime($refundResult['gmt_refund_pay']));
        } else {
            Tools::write(json_encode($refundResult), 'ERR', __FILE__, __METHOD__,LOG_PATH);
            return false;
        }
    }

    /**
     * 微信支付退款操作
     * @param $pay_Record
     * @return bool
     */
    private function wxpayRefund($pay_Record)
    {
        //微信支付配置数据
        $config = [];
        $cert = '';
        if ($pay_Record['trade_type'] == 'APP') {
            $config = $this->config['APP_WXPAY_OPTION'];
        } else {
            $config = $this->config['WXPAY_OPTION'];
        }

        $pay_Record['receipt_fee'] = $pay_Record['receipt_fee'] * 100;
        //微信退款请求数据
        $postData = [
            'appid' => $config['APPID'],    //应用ID
            'mch_id' => $config['MCH_ID'],  //微信支付分配的商户号
            'nonce_str' => strtoupper(md5(Tools::randString(20))), //随机字符串，不长于32位
            'transaction_id' => $pay_Record['trade_no'],
            'out_refund_no' => Tools::refund_number(),  //商户退款编号
            'total_fee' => $pay_Record['receipt_fee'],  //退款总金额
            'refund_fee' => $pay_Record['receipt_fee'], //退款金额
            'refund_desc' => $pay_Record['refund_reason'],
        ];
        $postData['sign'] = Tools::getWxPaySign($postData, $config['KEY']);

        //将数据转化为xml
        $postXml = Tools::arrayToXml($postData);
        $response = Tools::postSSLXml('https://api.mch.weixin.qq.com/secapi/pay/refund', $postXml, $config['CERT_PATH']);
        $refundResult = Tools::xmlToArray($response);
        if ($refundResult['return_code'] == "SUCCESS" && $refundResult['result_code'] == 'SUCCESS') {
            $pay_Record['receipt_fee'] = $pay_Record['receipt_fee']/100;
            $refundResult['refund_fee'] =$refundResult['refund_fee']/100;
            //记录日志
            Tools::write(json_encode($refundResult), 'ERR', __FILE__, __METHOD__,LOG_PATH);
            //写入退款记录
            return $this->createRefundRecord($pay_Record, $refundResult['refund_fee'], time());
        }

        return false;
    }

    /**
     * 余额退款操作
     * @param $bar_member_order array 订单数据
     * @param $cancel_reason string 拒绝原因
     * @return bool
     */
    private function walletRefund($bar_member_order, $cancel_reason)
    {
        //查询会员的当前余额
        $memberCapitalModel = M('member_capital');
        $member_now_money = $memberCapitalModel->field('give_money, recharge_money')->where(['member_id' => $bar_member_order['member_id']])->find();
        if (!$member_now_money) {
            return false;
        }

        //查询当前订单对应的支付数据
        $member_record = M('member_record')->field('change_money, before_recharge_money, after_recharge_money, before_give_money, after_give_money')
            ->where(['order_no' => $bar_member_order['pay_no'], 'type' => 1])->find();
        if (!$member_record) {
            return false;
        }

        //计算赠送扣除金额
        $back_give_money = $member_record['before_give_money'] - $member_record['after_give_money'];
        $back_recharge_money = $member_record['before_recharge_money'] - $member_record['after_recharge_money'];

        $back_give_money = $back_give_money < 0 ? 0 : $back_give_money;
        $back_recharge_money = $back_recharge_money < 0 ? 0 : $back_recharge_money;

        //用户资金新总额
        $give_money = $member_now_money['give_money'] + $back_give_money;
        $recharge_money = $member_now_money['recharge_money'] + $back_recharge_money;

        $time = time();

        $memberCapitalModel->startTrans();

        //修改用户资金表数据
        $capital_data = ['updated_time' => time(), 'give_money' => $give_money, 'recharge_money' => $recharge_money];
        $data = $memberCapitalModel->where(['member_id' => $bar_member_order['member_id']])->save($capital_data);
        if ($data === false) {
            $memberCapitalModel->rollback();
            return false;
        }

        //写入退款记录
        $record_data = [
            'member_id' => $bar_member_order['member_id'],
            'type' => 3,
            'change_money' => $bar_member_order['pay_price'],
            'trade_time' => $time,
            'source' => '服务端自动退款',
            'terminal' => 'auto robot',
            'title' => $cancel_reason,
            'order_no' => $bar_member_order['pay_no'],
            'order_id' => $bar_member_order['id'],
            'before_recharge_money' => $member_record['before_recharge_money'],
            'after_recharge_money' => $recharge_money,
            'before_give_money' => $member_record['before_give_money'],
            'after_give_money' => $give_money
        ];
        if (!M('member_record')->add($record_data)) {
            $memberCapitalModel->rollback();
            return false;
        }

        //变更用户订单为退款
        $rs = M('bar_member')->where(['id' => $bar_member_order['id']])->save(['pay_status' => 3, 'updated_time' => time()]);
        if ($rs === false) {
            $memberCapitalModel->rollback();
            return false;
        }

        $memberCapitalModel->commit();
        return true;
    }

    /**
     * 微信支付/支付宝支付写入退款记录
     * @param $pay_Record
     * @param $refund_fee
     * @param $refund_time
     * @return bool
     */
    private function createRefundRecord($pay_Record, $refund_fee, $refund_time)
    {
        $refund_record_model = M('refund_record');

        $refund_record_model->startTrans();

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
            'payment' => $pay_Record['payment'],
            'refund_no' => Tools::refund_number(),
            'refund_time' => $refund_time,
            'created_time' => time(),
            'refund_desc' => $pay_Record['refund_reason'],
        ];
        if (!$refund_record_model->add($refundData)) {
            $refund_record_model->rollback();
            return false;
        }

        //变更用户订单为退款
        $rs = M('bar_member')->where(['id' => $pay_Record['order_id']])->save(['pay_status' => 3, 'updated_time' => time()]);
        if ($rs === false) {
            $refund_record_model->rollback();
            return false;
        }

        $refund_record_model->commit();
        return true;
    }
}