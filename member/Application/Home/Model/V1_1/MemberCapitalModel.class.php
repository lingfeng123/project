<?php
/**
 * FileName: MemberCapitalModel.class.php
 * User: Comos
 * Date: 2017/10/31 18:21
 */

namespace Home\Model\V1_1;


use Think\Model;

class MemberCapitalModel extends Model
{
    private $payment_name = [
        1 => '余额支付', 2 => '微信支付', 3 => '支付宝', 4 => '银联支付'
    ];

    public $totalMoney = 0;

    /**
     * 查询充值订单状态
     * @param $attach array 支付回调附加数据
     * @return bool
     */
    public function findOrderStatus($attach)
    {
        //查询订单状态
        $recharge_order = M('member_order')->where(['order_no' => $attach['order_no']])->find();
        if ($recharge_order && $recharge_order['status'] == 1) {
            return true;
        }

        return false;
    }

    /**
     * 微信支付结果写入充值订单支付金额
     * @param $wxpay_data array 微信支付回调数据
     * @param $attach array 微信支付附加数据
     * @param $give_money int 充值金额
     * @return bool
     */
    public function createRechargePayData($pay_data, $attach, $give_money)
    {
        $time = time();
        //查询记录是否存在,不存在就创建,存在就修改
        $member_capital = $this->field('member_id,give_money,recharge_money')->where(['member_id' => $attach['member_id']])->find();
        $this->startTrans();
        //1、修改用户资金表 <充值金额> 与 <赠送金额>
        if ($member_capital) {
            //修改用户资金
            $money_data = [
                'give_money' => $member_capital['give_money'] + $give_money,
                'recharge_money' => $member_capital['recharge_money'] + $pay_data['receipt_fee'],
                'updated_time' => $time
            ];
            if (!$rs = $this->where(['member_id' => $attach['member_id']])->save($money_data)) {
                $this->error = '用户资金修改失败';
                $this->rollback();
                return false;
            }

        } else {
            //添加用户资金
            $money_data = [
                'member_id' => $attach['member_id'],
                'give_money' => $member_capital['give_money'] + $give_money,
                'recharge_money' => $member_capital['recharge_money'] + $pay_data['receipt_fee'],
                'updated_time' => $time
            ];
            if (!$rs = $this->add($money_data)) {
                $this->error = '用户资金添加失败';
                $this->rollback();
                return false;
            }
        }

        //2、用户充值订单表member_order修改订单状态为已支付
        $member_order_data = [
            'member_id' => $attach['member_id'],
            'recharge_money' => $pay_data['receipt_fee'],
            'give_money' => $give_money,
            'status' => 1,
            'order_no' => $attach['order_no'],
            'create_time' => $time,
        ];
        if (!$last_id = M('member_order')->add($member_order_data)) {
            $this->error = '修改充值订单失败';
            $this->rollback();
            return false;
        }

        //3、用户充值消费记录表中写入充值记录
        //写入支付记录
        $payment_record = [
            'member_id' => $attach['member_id'],
            'merchant_id' => 0,
            'order_id' => $last_id,
            'order_no' => $attach['order_no'],
            'appid' => $pay_data['appid'],
            'mch_id' => $pay_data['mch_id'],
            'trade_type' => $pay_data['trade_type'],
            'order_fee' => $pay_data['receipt_fee'],
            'receipt_fee' => $pay_data['receipt_fee'],
            'trade_no' => $pay_data['trade_no'],
            'end_time' => $pay_data['end_time'],
            'pay_type' => $attach['pay_type'],
            'buy_type' => 0,
            'payment' => $pay_data['payment'],
            'created_time' => $time,
        ];
        if (!$res = M('payment_record')->add($payment_record)) {
            $this->rollback();
            return false;
        }

        //4. 写入member_record支付记录
        $record_data = [
            'member_id' => $attach['member_id'],
            'type' => $attach['pay_type'],
            'change_money' => $pay_data['receipt_fee'] + $give_money,
            'trade_time' => $time,
            'source' => $this->payment_name[$pay_data['payment']],
            'terminal' => $attach['client'],    //数据变动终端(微信小程序,ios,android)
            'title' => "钱包余额充值",
            'order_no' => $attach['order_no'],
            'order_id' => $last_id,
            'before_recharge_money' => $member_capital['recharge_money'],
            'after_recharge_money' => $member_capital['recharge_money'] + $pay_data['receipt_fee'],
            'before_give_money' => $member_capital['give_money'],
            'after_give_money' => $member_capital['give_money'] + $give_money
        ];
        $this->totalMoney = $record_data['after_recharge_money'] + $record_data['after_give_money'];
        if (!$res = M('member_record')->add($record_data)) {
            $this->error = '用户支付记录失败';
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }

}