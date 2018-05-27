<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/13
 * Time: 17:08
 */

namespace Admin\Model;

use Org\Util\String;
use Org\Util\Tools;
use Think\Log;
use Think\Model;

class RefundRecordModel extends Model
{
    public function refundBar($bar_id,$user_id,$cancel_reason='')
    {
        $bar_info = M('bar')->where(['id' => $bar_id])->find();

        if (!$bar_info) {
            $this->error = '查询拼吧订单失败';
            return false;
        }
        if (in_array($bar_info['bar_status'], [4, 0 ,6])) {
            $this->error = '拼吧状态不正确，无法取消订单';
            return false;
        }

        if($bar_info['bar_type'] == 1){

        }



        $order['is_bar'] = 1;

        //查找对应的用户
        $members = M('bar_member')->field('id,pay_no,pay_type,pay_price,member_id')->where(['bar_id' => $bar_info['id'], 'pay_status' => 2])->select();

        foreach ($members as $member){
            switch ($member['pay_type']) {
                case 1:
                    $rs = $this->RechargeRefund($member);
                    if ($rs === false) {
                        Log::write('余额退款失败' . $this->getError() . $member['id']);
                    }

                    break;
                case 2:
                    $pay_record = $this->getPaymentRecord($member['pay_no']);
                    if (!$pay_record) {
                        continue;
                    }
                    $pay_record['refund_reason'] = $cancel_reason;
                    $rs = $this->wxRefund($order, $user_id, $cancel_reason,$pay_record);
                    if ($rs === false) {
                        Log::write('微信退款失败' . $this->getError() . $member['id']);
                    }

                    break;
                case 3:
                    $pay_record = $this->getPaymentRecord($member['pay_no']);
                    if (!$pay_record) {
                        continue;
                    }
                    $pay_record['refund_reason'] = $cancel_reason;
                    $rs = $this->aliPayRefund($order, $user_id, $cancel_reason,$pay_record);
                    if ($rs === false) {
                        Log::write('支付宝退款失败' . $this->getError() . $member['id']);
                    }
                    break;
                case 4:
                    break;
            }

        }

        $this->startTrans();

        $bar_rs = M('bar')->where(['id' => $bar_info['id']])
            ->save(['bar_status' => 6, 'updated_time' => time(), 'cancel_reason' => $cancel_reason]);
        if ($bar_rs === false) {
            $this->rollback();
            return false;
        }

        //后台记录用户操作记录:::只记录主订单记录
        $result = M('bar_operate_record')->add(
            [
                'user_id' => $user_id,
                'order_id' => $order['id'],
                'order_no' => $order['order_no'],
                'content' => '取消订单退款',
                'created_time' => time(),
            ]
        );
        if (!$result) {
            $this->error = '管理员操作记录写入失败';
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }

    /**
     * 拼吧拒绝退款，根据bar_member表中的pay_type来控制如何退款
     */
    public function refundBarMember($order, $user_id, $cancel_reason = '')
    {
        //拼吧信息
        $bar_info = M('bar_order')
            ->field('api_bar.*,api_bar_order.order_id')
            ->join('left join api_bar ON api_bar.id =api_bar_order.bar_id')
            ->where(['api_bar_order.order_id' => $order['id']])
            ->find();
        if (!$bar_info) {
            $this->error = '查询拼吧订单失败';
            return false;
        }

        //查找对应的用户
        $members = M('bar_member')->field('id,pay_no,pay_type,pay_price,member_id')->where(['bar_id' => $bar_info['id'], 'pay_status' => 2])->select();
        foreach ($members as $member) {
            switch ($member['pay_type']) {
                case 1:
                    $rs = $this->RechargeRefund($member);
                    if ($rs === false) {
                        Log::write('余额退款失败' . $this->getError() . $member['id']);
                    }

                    break;
                case 2:
                    $pay_record = $this->getPaymentRecord($member['pay_no']);
                    if (!$pay_record) {
                        continue;
                    }
                    $pay_record['refund_reason'] = $cancel_reason;
                    $rs = $this->wxRefund($order, $user_id, $cancel_reason,$pay_record);
                    if ($rs === false) {
                        Log::write('微信退款失败' . $this->getError() . $member['id']);
                    }

                    break;
                case 3:
                    $pay_record = $this->getPaymentRecord($member['pay_no']);
                    if (!$pay_record) {
                        continue;
                    }
                    $pay_record['refund_reason'] = $cancel_reason;
                    $rs = $this->aliPayRefund($order, $user_id, $cancel_reason,$pay_record);
                    if ($rs === false) {
                        Log::write('支付宝退款失败' . $this->getError() . $member['id']);
                    }
                    break;
                case 4:
                    break;
            }
        }

        $this->startTrans();

        //修改拼吧主订单的状态order表
        $rs = M('order')->where(['id' => $order['id']])->save(['status' => 6, 'updated_time' => time(), 'cancel_reason' => $cancel_reason]);
        if ($rs === false) {
            $this->rollback();
            return false;
        }

        $bar_rs = M('bar')->where(['id' => $bar_info['id']])->save(['bar_status' => 6, 'updated_time' => time(), 'cancel_reason' => $cancel_reason]);
        if ($bar_rs === false) {
            $this->rollback();
            return false;
        }

        //后台记录用户操作记录:::只记录主订单记录
        $result = M('order_operate_record')->add(
            [
                'user_id' => $user_id,
                'order_id' => $order['id'],
                'order_no' => $order['order_no'],
                'content' => '取消订单退款',
                'created_time' => time(),
            ]
        );
        if (!$result) {
            $this->error = '管理员操作记录写入失败';
            $this->rollback();
            return false;
        }

        //判断订单是否存在商品,如果存在商品需要退还库存(根据)
        if ($bar_info['bar_type'] == 1) {
            $time = date('Ymd', $bar_info['arrives_time']);
            //获取商品ID
            $pack_goods = M('bar_pack')->field('goods_pack_id,amount')->where(['bar_id' => $bar_info['id']])->select();

            foreach ($pack_goods as $pack_good) {
                if ($bar_info['is_xu'] == 1) {
                    //拼吧续酒
                    $field1 = 'xu_stock';
                    $field2 = 'xu_day_sales';
                } else {
                    //拼吧
                    $field1 = 'stock';
                    $field2 = 'day_sales';
                }

                $rs = M('goods_pack')->where(['id' => $pack_good['goods_pack_id']])->setInc($field1, $pack_good['amount']);
                if ($rs === false) {
                    $this->error = '库存还原失败2';
                    $this->rollback();
                    return false;
                }

                //查询每日库存数据,存在则执行还原,不存在就return
                $dayStockWhere = ['goods_id' => $pack_good['goods_pack_id'], 'date' => $time];
                $day_sales = M('goods_pack_stock')->where($dayStockWhere)->getField($field2);
                if (is_null($day_sales) || empty($day_sales)) {
                    $this->error = '获取' . $time . '库存失败';
                    $this->rollback();
                    return false;
                }

                //计算每日实际库存,若小于扣除库存则设置为0;
                $number = $day_sales - $pack_good['amount'];
                $number = $number >= 0 ? $number : 0;
                $rs = M('goods_pack_stock')->where($dayStockWhere)->save([$field2 => $number]);
                if ($rs === false) {
                    $this->error = '还原' . $time . '库存失败';
                    $this->rollback();
                    return false;
                }
            }
        }

        $rs = M('message_employee')->where(['order_id'=>$order['id']])->delete();
        if ($rs === false) {
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }


    /**
     * 余额退款
     */
    private function  RechargeRefund($member)
    {
        //查询会员的当前余额
        $memberCapitalModel = M('member_capital');
        $member_now_money = $memberCapitalModel->field('give_money, recharge_money')->where(['member_id' => $member['member_id']])->find();
        if (!$member_now_money) {
            $this->error = '查询会员当前余额失败';
            return false;
        }

        //查询当前订单对应的支付数据
        $memberRecordModel = M('member_record');
        $member_record = $memberRecordModel->field('change_money, before_recharge_money, after_recharge_money, before_give_money, after_give_money')
            ->where(['order_no' => $member['pay_no'], 'type' => 1])->find();
        if (!$member_record) {
            $this->error = '查询订单失败';
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

        //开启事务
        $this->startTrans();

        //修改用户资金表数据
        $capital_data = [
            'updated_time' => time(),
            'give_money' => $give_money,
            'recharge_money' => $recharge_money
        ];
        $data = $memberCapitalModel->where(['member_id' => $member['member_id']])->save($capital_data);
        if ($data === false) {
            $this->error = '修改用户资金失败';
            $this->rollback();
            return false;
        }

        //写入退款记录
        $res = $memberRecordModel->add(
            [
                'member_id' => $member['member_id'],
                'type' => 3,
                'change_money' => $member['pay_price'],
                'trade_time' => $time,
                'source' => I('post.client', ''),
                'terminal' => '微信小程序',
                'title' => "订单取消退款",
                'order_no' => $member['pay_no'],
                'order_id' => $member['id'],
                'before_recharge_money' => $member_record['before_recharge_money'],
                'after_recharge_money' => $recharge_money,
                'before_give_money' => $member_record['before_give_money'],
                'after_give_money' => $give_money
            ]
        );
        if (!$res) {
            $this->error = '写入退款记录失败';
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }

    /**
     * 微信退款
     */
    public function wxRefund($order, $user_id, $cancel_reason = '',$pay_record)
    {

        if ($pay_record['trade_type'] == 'APP') {
            $config = C('APP_WXPAY_OPTION');
        } else {
            $config = C('WXPAY_OPTION');
        }

        $pay_record['receipt_fee'] = $pay_record['receipt_fee'] * 100;

        //微信退款请求数据
        $postData = [
            'appid' => $config['APPID'],    //应用ID
            'mch_id' => $config['MCH_ID'],  //微信支付分配的商户号
            'nonce_str' => strtoupper(md5(String::randString(20))), //随机字符串，不长于32位
            'transaction_id' => $pay_record['trade_no'],
            'out_refund_no' => Tools::refund_number(),  //商户退款编号
            'total_fee' => $pay_record['receipt_fee'],  //退款总金额
            'refund_fee' => $pay_record['receipt_fee'], //退款金额
            'refund_desc' => $cancel_reason,
        ];
        $postData['sign'] = Tools::getWxPaySign($postData, $config['KEY']);

        //将数据转化为xml
        $postXml = Tools::arrayToXml($postData);
        $response = Tools::postSSLXml('https://api.mch.weixin.qq.com/secapi/pay/refund', $postXml, $config['CERT_PATH']);

        $refundResult = Tools::xmlToArray($response);

        //记录退款日志
        Log::write(json_encode($refundResult), Log::INFO);

        if ($refundResult['return_code'] == "SUCCESS" && $refundResult['result_code'] == 'SUCCESS') {
            $pay_record['receipt_fee'] = $pay_record['receipt_fee']/100;
            $refundResult['refund_fee'] =$refundResult['refund_fee']/100;
            //写入退款记录
            if ($order['is_bar'] == 1) {
                return $this->createRefundRecord($pay_record, $refundResult['refund_fee'], time(), 2);
            } else {
                return $this->createOrderRefundRecord($pay_record, $refundResult['refund_fee'], time(), $cancel_reason, $user_id, $order);
            }
        }

        $this->error = $refundResult['err_code'] . ': ' . $refundResult['err_code_des'];
        return false;
    }

    /**
     * 支付宝退款
     */
    public function aliPayRefund($order, $user_id, $cancel_reason = '',$pay_record)
    {
        //载入类文件
        vendor('alipay.AopClient');
        vendor('alipay.request.AlipayTradeRefundRequest');

        //获取支付宝支付配置信息
        $alipayConfig = C('alipay');

        //实例
        $aop = new \AopClient();
        $aop->gatewayUrl = $alipayConfig['gate_way_url'];
        $aop->appId = $alipayConfig['app_id'];
        $aop->rsaPrivateKey = $alipayConfig['rsa_private_key'];
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->signType = $alipayConfig['sign_type'];
        $aop->alipayrsaPublicKey = $alipayConfig['alipay_rsa_public_key'];

        $request = new \AlipayTradeRefundRequest();
        $bizcontent = [
            'out_trade_no' => $pay_record['order_no'],     //订单支付时传入的商户订单号,不能和 trade_no同时为空。
            'trade_no' => $pay_record['trade_no'],         //支付宝交易号，和商户订单号不能同时为空
            'refund_amount' => $pay_record['receipt_fee'],    //需要退款的金额，该金额不能大于订单金额,单位为元，支持两位小数
            'refund_reason' => $cancel_reason,  //退款原因
        ];

        $request->setBizContent(json_encode($bizcontent));
        $refundResult = json_decode(json_encode($aop->execute($request)), true);
        $refundResult = $refundResult['alipay_trade_refund_response'];

        Log::write(json_encode($refundResult), Log::INFO);  //记录退款日志

        if (!empty($refundResult['code']) && $refundResult['code'] == 10000) {
            //写入退款记录
            if ($order['is_bar'] == 1) {
                return $this->createRefundRecord($pay_record, $refundResult['refund_fee'], strtotime($refundResult['gmt_refund_pay']), 3);
            } else {
                return $this->createOrderRefundRecord($pay_record, $refundResult['refund_fee'], strtotime($refundResult['gmt_refund_pay']), $cancel_reason, $user_id, $order);
            }
        }

        $this->error = '';
        return false;
    }

    /**
     * 获取第三方支付平台支付记录
     */
    public function getPaymentRecord($order_no)
    {
        return M('payment_record')->where(['order_no' => $order_no])->find();
    }

    /**
     * 拼吧退款写入退款记录
     */
    private function createRefundRecord($pay_record, $refund_fee, $refund_time, $payment)
    {
        $this->startTrans();

        //写入退款记录
        $res = M('refund_record')->add(
            [
                'member_id' => $pay_record['member_id'],
                'merchant_id' => $pay_record['merchant_id'],
                'app_id' => $pay_record['appid'],
                'trade_no' => $pay_record['trade_no'],
                'order_no' => $pay_record['order_no'],
                'order_id' => $pay_record['order_id'],
                'trade_status' => 2,
                'receipt_fee' => $pay_record['receipt_fee'],
                'refund_fee' => $refund_fee,
                'buy_type' => $pay_record['buy_type'],
                'pay_type' => $pay_record['pay_type'],
                'refund_no' => Tools::refund_number(),
                'refund_time' => $refund_time,
                'created_time' => time(),
                'refund_desc' => $pay_record['refund_reason'],
                'payment' => $payment,
            ]
        );
        if ($res === false) {
            $this->error = '写入退款记录';
            $this->rollback();
            return false;
        }

        //变更用户订单为退款
        $rs = M('bar_member')->where(['id' => $pay_record['order_id']])->save(['pay_status' => 3, 'updated_time' => time()]);
        if ($rs === false) {
            $this->error = '变更用户订单为退款状态失败';
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }

    /**
     * 正常订单拒绝订单操作日志记录
     */
    private function createOrderRefundRecord($pay_record, $refund_fee, $refund_time, $cancel_reason, $user_id, $order)
    {
        $this->startTrans();

        //写入退款记录
        $rs = M('refund_record')->add(
            [
                'member_id' => $pay_record['member_id'],
                'merchant_id' => $pay_record['merchant_id'],
                'app_id' => $pay_record['appid'],
                'trade_no' => $pay_record['trade_no'],
                'order_no' => $pay_record['order_no'],
                'order_id' => $pay_record['order_id'],
                'trade_status' => 2,
                'receipt_fee' => $pay_record['receipt_fee'],
                'refund_fee' => $refund_fee,
                'buy_type' => $pay_record['buy_type'],
                'pay_type' => $pay_record['pay_type'],
                'refund_no' => Tools::refund_number(),
                'refund_time' => $refund_time,
                'created_time' => time(),
                'refund_desc' => $cancel_reason,
                'payment' => $pay_record['payment'],
            ]
        );
        if (!$rs) {
            $this->error = '写入退款记录失败';
            $this->rollback();
            return false;
        }

        //修改主订单表
        $order_rs = M('order')->where(['id' => $order['id']])->save(['status' => 6, 'updated_time' => time(), 'cancel_reason' => $cancel_reason]);
        if ($order_rs === false) {
            $this->error = '修改主订单状态失败';
            $this->rollback();
            return false;
        }

        //后台记录用户操作记录
        $result = M('order_operate_record')->add(
            [
                'user_id' => $user_id,
                'order_id' => $order['id'],
                'order_no' => $order['order_no'],
                'content' => '取消订单退款',
                'created_time' => time(),
            ]
        );
        if (!$result) {
            $this->error = '管理员操作记录写入失败';
            $this->rollback();
            return false;
        }

        //第一:卡座,释放卡座
        if ($order['order_type'] == 1) {

            if ($this->releaseSeat($order['order_no']) === false) {
                $this->error = $this->error ? $this->error : '释放卡座失败';
                $this->rollback();
                return false;
            };

        } else if ($order['order_type'] == 2 || $order['order_type'] == 3) {

            //退还库存
            if ($this->resetStock($order) === false) {
                $this->error = $this->error ? $this->error : '库存退还失败';
                $this->rollback();
                return false;
            }
        }

        $rs = M('message_employee')->where(['order_id'=>$order['id']])->delete();
        if ($rs === false) {
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }

    /**
     * 释放卡座(根据订单编号释放)
     */
    private function releaseSeat($order_no)
    {
        //根据订单id获取被锁定的座位号
        $id = M('seat_lock')->where(['order_no' => $order_no])->getField('id');
        //释放指定日期卡座
        $rs = M('seat_lock')->where(['id' => $id])->delete();
        if ($rs === false) {
            $this->error = '释放卡座失败';
            return false;
        }
        return true;
    }

    /**
     * 余额退款操作
     */
    public function refundOperation($order_info, $user_id, $cancel_reason)
    {
        //查询会员的当前余额
        $memberCapitalModel = M('member_capital');
        $member_now_money = $memberCapitalModel->field('give_money, recharge_money')->where(['member_id' => $order_info['member_id']])->find();
        if (!$member_now_money) {
            return false;
        }

        //查询当前订单对应的支付数据
        $memberRecordModel = M('member_record');
        $member_record = $memberRecordModel->field('change_money, before_recharge_money, after_recharge_money, before_give_money, after_give_money')
            ->where(['order_no' => $order_info['order_no'], 'type' => 1])->find();
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

        //开启事务
        $this->startTrans();

        //修改用户资金表数据
        $capital_data = [
            'updated_time' => time(),
            'give_money' => $give_money,
            'recharge_money' => $recharge_money
        ];
        $data = $memberCapitalModel->where(['member_id' => $order_info['member_id']])->save($capital_data);
        if ($data === false) {
            $this->error = '修改用户资金失败';
            $this->rollback();
            return false;
        }

        //写入退款记录
        $res = $memberRecordModel->add(
            [
                'member_id' => $order_info['member_id'],
                'type' => 3,
                'change_money' => $order_info['pay_price'],
                'trade_time' => $time,
                'source' => '后台主动退款',
                'terminal' => '后台管理面板',
                'title' => "订单取消退款",
                'order_no' => $order_info['order_no'],
                'order_id' => $order_info['id'],
                'before_recharge_money' => $member_record['before_recharge_money'],
                'after_recharge_money' => $recharge_money,
                'before_give_money' => $member_record['before_give_money'],
                'after_give_money' => $give_money
            ]
        );
        if (!$res) {
            $this->error = '余额退款失败';
            $this->rollback();
            return false;
        }

        //修改订单状态为已拒绝
        $rs = M('order')->where(['id' => $order_info['id']])->save(['status' => 6, 'updated_time' => $time, 'cancel_reason' => $cancel_reason]);
        if ($rs === false) {
            $this->error = '修改订单状态失败';
            $this->rollback();
            return false;
        }

        //判断是否存在卡券,如果存在就将该卡券状态改为未使用
        if ($order_info['card_id']) {
            $card_rs = M('coupon_member')->where(['member_id' => $order_info['member_id'], 'card_id' => $order_info['card_id']])->save(['card_status' => 0]);
            if ($card_rs === false) {
                $this->error = '优惠券退还失败';
                $this->rollback();
                return false;
            }
        }

        //后台记录用户操作记录
        $result = M('order_operate_record')->add(
            [
                'user_id' => $user_id,
                'order_id' => $order_info['id'],
                'order_no' => $order_info['order_no'],
                'content' => '取消订单退款',
                'created_time' => time(),
            ]
        );
        if (!$result) {
            $this->error = '管理员操作记录写入失败';
            $this->rollback();
            return false;
        }

        //拒单释放卡座
        if ($order_info['order_type'] == 1) {

            $res = $this->releaseSeat($order_info['order_no']);
            if ($res === false) {
                $this->rollback();
                return false;
            }
        } else {
            //还原库存
            $this->resetStock($order_info);
        }


        $rs = M('message_employee')->where(['order_id'=>$order_info['id']])->delete();
        if ($rs === false) {
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }

    /**
     * 还原库存
     */
    private function resetStock($order)
    {
        $time = date('Ymd', $order['arrives_time']);
        $pack_goods = M('order_pack')->where(['order_id' => $order['id']])->select();

        foreach ($pack_goods as $pack_good) {

            if ($order['is_xu'] == 1) {
                $filed1 = 'xu_stock';
                $filed2 = 'xu_day_sales';
            } else {
                $filed1 = 'stock';
                $filed2 = 'day_sales';
            }

            //还原库存
            $rs = M('goods_pack')->where(['id' => $pack_good['goods_pack_id']])->setInc($filed1, $pack_good['amount']);
            if ($rs === false) {
                $this->error = '还原商品库存失败';
                $this->rollback();
                return false;
            }

            //查询每日库存数据,存在则执行还原,不存在就return
            $dayStockWhere = ['goods_id' => $pack_good['goods_pack_id'], 'date' => $time];
            $day_sales = M('goods_pack_stock')->where($dayStockWhere)->getField($filed2);
            if (is_null($day_sales) || empty($day_sales)) {
                $this->error = '获取' . $time . '库存失败';
                $this->rollback();
                return false;
            }

            //计算每日实际库存,若小于扣除库存则设置为0;
            $number = $day_sales - $pack_good['amount'];
            $number = $number >= 0 ? $number : 0;
            $rs = M('goods_pack_stock')->where($dayStockWhere)->save([$filed2 => $number]);
            if ($rs === false) {
                $this->error = '还原' . $time . '库存失败';
                $this->rollback();
                return false;
            }
        }
    }
}