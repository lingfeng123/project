<?php
/**
 * FileName: MemberCapitalModel.class.php
 * User: Comos
 * Date: 2017/10/31 18:21
 */

namespace Home\Model;


use Think\Model;

class MemberCapitalModel extends Model
{

    /**
     * 写入充值订单支付金额
     * @param $wxpay_data
     * @param $attach
     * @param $give_money
     * @return bool
     */
    public function createRechargeOrderData($wxpay_data, $attach, $give_money)
    {
        $time = time();
        $this->startTrans();
        //1、修改用户资金表 <充值金额> 与 <赠送金额>
        $member_capital = $this->field('member_id,give_money,recharge_money')->where(['member_id' => $attach['member_id']])->find();
        //查询记录是否存在,不存在就创建,存在就修改
        if ($member_capital) {
            //修改用户资金
            $money_data = [
                'give_money' => $member_capital['give_money'] + $give_money,
                'recharge_money' => $member_capital['recharge_money'] + $wxpay_data['total_fee'],
                'updated_time' => $time
            ];
            if (!$rs = $this->where(['member_id' => $attach['member_id']])->save($money_data)) {
                $this->error='用户资金修改失败';
                $this->rollback();
                return false;
            }

        } else {
            //添加用户资金
            $money_data = [
                'member_id' => $attach['member_id'],
                'give_money' => $member_capital['give_money'] + $give_money,
                'recharge_money' => $member_capital['recharge_money'] + $wxpay_data['total_fee'],
                'updated_time' => $time
            ];
            if (!$rs = $this->add($money_data)) {
                $this->error='用户资金添加失败';
                $this->rollback();
                return false;
            }
        }

        //2、用户充值订单表member_order修改订单状态为已支付
        $member_order_data = [
            'member_id' => $attach['member_id'],
            'recharge_money' => $wxpay_data['total_fee'],
            'give_money' => $give_money,
            'status' => 1,
            'order_no' => $attach['order_no'],
            'create_time' => $time,
        ];
        if (!$last_id = M('member_order')->add($member_order_data)) {
            $this->error='修改充值订单失败';
            $this->rollback();
            return false;
        }

        //3、用户充值消费记录表中写入充值记录
        //写入paylog_wxpay微信支付日志
        $pay_logs = [
            'merchant_id' => 0,
            'member_id' => $attach['member_id'],
            'appid' => $wxpay_data['appid'],
            'mch_id' => $wxpay_data['mch_id'],
            'openid' => $wxpay_data['openid'],
            'is_subscribe' => $wxpay_data['is_subscribe'],
            'trade_type' => $wxpay_data['trade_type'],
            'total_fee' => $wxpay_data['total_fee'],
            'transaction_id' => $wxpay_data['transaction_id'],
            'time_end' => $wxpay_data['time_end'],
            'create_time' => $time,
            'pay_type' => 2,
            'order_no' => $attach['order_no'],
            'order_id' => $last_id
        ];
        if (!$log_rs = M('paylog_wxpay')->add($pay_logs)) {
            $this->error='支付日志记录失败';
            $this->rollback();
            return false;
        }

        //4. 写入member_record支付记录
        $record_data = [
            'member_id' => $attach['member_id'],
            'type' => $attach['pay_type'],
            'change_money' => $wxpay_data['total_fee'],
            'trade_time' => $time,
            'source' => '微信支付',
            'terminal' => $attach['client'],    //数据变动终端(微信小程序,ios,android)
            'title' => "钱包余额充值",
            'order_no' => $attach['order_no'],
            'order_id' => $last_id,
            'before_recharge_money' => $member_capital['recharge_money'],
            'after_recharge_money' => $member_capital['recharge_money'] + $give_money,
            'before_give_money' => $member_capital['give_money'],
            'after_give_money' => $member_capital['give_money'] + $wxpay_data['total_fee']
        ];

        if (!$res = M('member_record')->add($record_data)) {
            $this->error='用户支付记录失败';
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }

}