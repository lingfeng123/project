<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/27
 * Time: 11:29
 */

namespace V1_1\Controller;


use Org\Util\String;
use Org\Util\Tools;
use Org\Util\YunpianSms;
use Think\Controller;
use Think\Log;

class OrderFlushController extends Controller
{
    private $_model;

    public function _initialize()
    {
        $this->_model = M('order');
    }


    /**
     * 刷新订单状态,并执行相关的退款操作
     */
    public function checkOrderstatus()
    {
        //获取所有处于 未支付,已逾期,已接单的订单数据
        $orders = M('order')
            ->field('api_order.id, 
            api_order.merchant_id, 
            api_order.member_id, 
            api_order.order_no, 
            api_order.status, 
            api_order.top_order_id, 
            api_order.contacts_realname, 
            api_order.contacts_tel, 
            api_order.contacts_sex, 
            api_order.order_type, 
            api_order.pay_price, 
            api_order.arrives_time, 
            api_order.incr_time, 
            api_order.created_time, 
            api_order.updated_time, 
            api_order.is_bar, 
            api_order.is_xu, 
            api_order.card_id, 
            api_merchant.title as merchant_title,
            api_merchant.begin_time, 
            api_merchant.delay_time, 
            api_merchant.end_time, 
            api_member.wx_openid, 
            api_member_privilege.delayed')
            ->join("api_merchant ON api_order.merchant_id = api_merchant.id")
            ->join("api_member ON api_order.member_id = api_member.id")
            ->join("api_member_privilege ON api_member.level = api_member_privilege.level")
            ->where(['api_order.status' => ['in', [1, 3, 7]], 'api_order.is_bar' => 0])//查询订单状态为1未支付,3已逾期,7已接单的订单数据
            ->order('api_order.id asc')
            ->select();

        //循环遍历所有的订单信息,根据不同状态执行不同的代码模块
        foreach ($orders as $order) {
            //订单超过时间未支付订单取消
            if ($order['status'] == 1) {
                if ($this->cancel_order($order) === false) {
                    break;
                };
            }
            //卡套订单在接单过后,逾期操作
            if ($order['status'] == 7 && $order['order_type'] == 2) {
                if ($this->orderOverdue() === false) {
                    break;
                }
            }

            //卡套订单逾期之后,作废订单
            if ($order['status'] == 3 && $order['order_type'] == 2) {
                if ($this->orderInvalidation($order) === false) {
                    break;
                }
            }

            //卡座逾期作废释放卡座
            if ($order['status'] == 7 && $order['order_type'] == 1) {
                if ($this->orderInvalidation($order) === false) {
                    break;
                }
            }

            if ($order['status'] == 7 && $order['order_type'] == 3) {
                if ($this->orderInvalidation($order) === false) {
                    break;
                }
            }
        }
    }

    /**
     * 普通取消订单
     */
    private function cancel_order($order)
    {
        $this->_model->startTrans();
        $cancel_time = C('ORDER_OVERTIME');
        //更改超过30分钟未支付的卡套散套订单状态为已取消
        if ($order['status'] == 1 && $order['is_bar'] == 0) {
            $abolish_time = $order['created_time'] + $cancel_time;   //订单超时时间
            if (time() >= $abolish_time) {
                if ($order['order_type'] == 1) {
                    //卡座订单超时释放卡座
                    $this->releaseHolder($this->_model, $order);
                } else {
                    //还原库存
                    $this->packRestoreStock($this->_model, $order);
                }
            } else {

                return false;
            }
        }
        $this->_model->commit();
        return true;
    }

    /**
     * 卡套散套订单超时还原库存
     * @param $orderModel
     * @param $order
     * @return bool
     */
    protected function packRestoreStock($orderModel, $order, $is_day_sales = 1)
    {
        //修改订单状态为已取消
        $rs = $orderModel->where(['id' => $order['id']])->save(['status' => 0, 'updated_time' => time()]);
        if ($rs === false) {
            $orderModel->rollback();
            return false;
        }

        //还原优惠券为未使用
        if ($order['card_id']) {
            $rs = M('coupon_member')->where(['member_id' => $order['member_id'], 'card_id' => $order['card_id']])->save(['card_status' => 0]);
            if ($rs === false) {
                $orderModel->rollback();
                return false;
            }
        }

        $time = date('Ymd', $order['arrives_time']);
        $pack_goods = M('order_pack')->where(['order_id' => $order['id']])->select();
        foreach ($pack_goods as $pack_good) {

            if ($order['is_xu'] == 1) {
                //续酒还原库存
                if ($is_day_sales == 1) {
                    //还原商品库存量
                    $res = M('goods_pack')->where(['id' => $pack_good['goods_pack_id']])->setInc('xu_stock', $pack_good['amount']);
                    if ($res === false) {
                        $orderModel->rollback();
                        return false;
                    }

                    //还原每日销量
                    $res = M('goods_pack_stock')->where(['goods_id' => $pack_good['goods_pack_id'], 'date' => $time])->setDec('xu_day_sales', $pack_good['amount']);
                    if ($res === false) {
                        $orderModel->rollback();
                        return false;
                    }
                }
            } else {

                //还原商品库存量
                $res = M('goods_pack')->where(['id' => $pack_good['goods_pack_id']])->setInc('stock', $pack_good['amount']);
                if ($res === false) {
                    $orderModel->rollback();
                    return false;
                }

                if ($is_day_sales == 1) {
                    //还原每日销量
                    $res = M('goods_pack_stock')->where(['goods_id' => $pack_good['goods_pack_id'], 'date' => $time])->setDec('day_sales', $pack_good['amount']);
                    if ($res === false) {
                        $orderModel->rollback();
                        return false;
                    }
                }

            }

        }
    }


    /**
     * 卡座订单超时释放卡座
     * @param $orderModel
     * @param $order
     * @return bool
     */
    private function releaseHolder($orderModel, $order)
    {
        //修改订单状态为已取消
        $rs = $orderModel->where(['id' => $order['id']])->save(['status' => 0, 'updated_time' => time()]);
        if ($rs === false) {
            $orderModel->rollback();
            return false;
        }

        //根据订单号获取被锁定的座位号
        $id = M('seat_lock')->where(['order_no' => $order['order_no']])->getField('id');

        //释放指定日期卡座
        $rs = M('seat_lock')->where(['id' => $id])->delete();
        if ($rs === false) {
            $orderModel->rollback();
            return false;
        }
    }

    /**
     * 订单过期作废操作
     */
    public function orderInvalidation($order)
    {
        $this->_model->startTrans();
        $start_time = $this->_formatToTime($order['arrives_time'], $order['begin_time']);
        $delay_time = C('FINISH_DELAY_TIME');
        //三套过期作废
        if ($order['status'] == 7 && $order['order_type'] == 3) {

            $last_time = $start_time + $delay_time;
            if (time() >= $last_time) {
                $this->updateInvalid($order['id'], $order['order_no']);
                $this->packRestoreStock($this->_model, $order);
                $this->toUserSms($order);   //发送短信通知
            }
        }

        //卡座过期作废
        if ($order['status'] == 7 && $order['order_type'] == 1) {
            if ($order['incr_time']) {
                $arrives_time = $start_time + $order['incr_time'] * 60;
            } else {
                $arrives_time = $start_time;
            }
            $last_time = $arrives_time + $delay_time;
            if ($last_time <= time()) {
                $this->updateInvalid($order['id'], $order['order_no']);
                $this->releaseHolder($this->_model, $order);
                $this->toUserSms($order);   //发送短信通知
            }
        }

        //卡套逾期作废
        if ($order['status'] == 3 && $order['order_type'] == 2) {
            $delayed = M('member')->join('api_member_privilege ON api_member.level = api_member_privilege.level')->where(['api_member.id' => $order['member_id']])->getField('delayed');
            $last_time = $order['updated_time'] + $delayed * 24 * 60 * 60;
            if (time() >= $last_time) {
                $this->updateInvalid($order['id'], $order['order_no']);
                $this->toUserSms($order);   //发送短信通知
            }
        }

        $this->_model->commit();
        return true;
    }

    /**
     * 卡套逾期作废操作
     */
    private function orderOverdue($order)
    {
        $overdue_time = $this->_formatToTime($order['arrives_time'], $order['end_time']);       //逾期时间的时间戳
        if ($order['end_time'] <= $order['begin_time']) {
            $overdue_time = $overdue_time + 24 * 60 * 60;   //小于等于营业起始时间则为第二日, 时间戳加一天
        }

        if (time() > $overdue_time) {
            //修改订单状态
            $this->_model->where(['id' => $order['id']])->save(['status' => 3, 'updated_time' => time()]);

            $delayed = M('member')->join('api_member_privilege ON api_member.level = api_member_privilege.level')->where(['api_member.id' => $order['member_id']])->getField('delayed');

            $tpl_value = [
                '#name#' => $order['contacts_realname'],
                '#merchant#' => $order['title'],
                '#money#' => $order['pay_price'],
                '#day#' => $delayed,
            ];
            $ypsms = new YunpianSms();
            $ypsms->tplSingleSend($order['contacts_tel'], $this->config['YUNPIAN']['kataoyuqi'], $tpl_value);
        }
    }


    /**
     * 更改订单状态为过期作废的状态,并删除相关的
     * @param $order_id
     * @param $order_no
     * @return bool
     */
    private function updateInvalid($order_id, $order_no)
    {
        $orderModel = M('order');
        $rs = $orderModel->where(['id' => $order_id])->save(['status' => 5, 'updated_time' => time()]);
        if ($rs === false) {
            $orderModel->rollback();
            return false;
        }

        //删除订单相关消息数据
        $rs = M('message_employee')->where(['order_no' => $order_no])->delete();
        if ($rs === false) {
            $orderModel->rollback();
            return false;
        }
    }

    /**
     * @param $order
     */
    private function toUserSms($order)
    {
        $ypsms = new YunpianSms();
        $tpl_value = [
            '#name#' => $order['contacts_realname'],
            '#orderno#' => $order['order_no'],
        ];
        $ypsms->tplSingleSend($order['contacts_tel'], $this->config['YUNPIAN']['zuofeitongzhi'], $tpl_value);
    }


    /**
     * 获取格式化时间
     * @param $his_time string 时分秒时间格式 00:00:00
     * @param $format_date string  年月日日期格式 2017-05-21
     * @return false|int    时间戳
     */
    private function _formatToTime($format_date, $his_time)
    {
        $format_date = date('Y-m-d', $format_date);
        $format_time = $format_date . ' ' . $his_time;
        return strtotime($format_time);
    }


    ////////////////*********************************拼吧刷新*******************************/////////////////////////////

    public function barListFlush()
    {
        $ypsms = new YunpianSms();

        $bars = M('bar')->where(['pay_status' => 1])->select();
        $this->_model->startTrans();

        foreach ($bars as $bar) {
            $begin_time = M('merchant')->where(['id' => $bar['merchant_id']])->getField('begin_time');
            $arrives_time = strtotime(date('Y-m-d', $bar['arrives_time']) . ' ' . $begin_time) - C('BEFORE_TIME');

            $bar_user_orders = M('bar_member')->where(['bar_id' => $bar['id']])->select();
            if ($bar_user_orders === false) {
                break;
            }
            foreach ($bar_user_orders as $bar_user_order) {
                $cancel_time = $bar_user_order['created_time'] + C('ORDER_OVERTIME');
                if (time() >= $cancel_time) {
                    $bar_member_rs = M('bar_member')->where(['id' => $bar_user_order['id']])->save(['pay_status' => 0, 'updated_time' => time()]);
                    if ($bar_member_rs === false) {
                        break;
                    }
                }
            }

            if (time() >= $arrives_time) {
                foreach ($bar_user_orders as $bar_user_order) {
                    if ($bar_user_order['pay_status'] = 2) {
                        //状态为已支付的订单变更为退款
                        $rs = $this->createRefund($bar_user_order, $bar_user_order['pay_no'], $bar['id']);
                        if ($rs) {
                            //发送短信
                            $tpl_value = [
                                '#orderno#' => $bar_user_order['pay_no'],
                                '#reason#' => '拼吧失败',
                            ];
                            $ypsms->tplSingleSend($bar_user_order['tel'], C('YUNPIAN.tuikuantongzhi'), $tpl_value);
                        } else {
                            //记录日志
                            Tools::writeLog(json_encode($bar_user_order), C('LOG_PATH'), '', 'flushorder');
                        }

                    } elseif ($bar_user_order['pay_status'] == 1) {
                        //状态为未支付的订单变更为取消
                        M('bar_member')->where(['id' => $bar_user_order['id']])->save(['pay_status' => 0, 'updated_time' => time()]);
                    }
                }

                if ($bar['bar_type'] == 1) {
                    $bar_time = date('Ymd', $bar['arrives_time']);
                    $bar_packs = M('bar_pack')->where(['bar_id' => $bar['id']])->select();
                    foreach ($bar_packs as $bar_pack) {

                        if ($bar['is_xu'] == 1) {
                            //续酒还原库存
                            $res = M('goods_pack')->where(['id' => $bar_pack['goods_pack_id']])->setInc('xu_stock', $bar_pack['amount']);
                            if ($res === false) {
                                $this->_model->rollback();
                                return false;
                            }

                            //还原续酒每日销量
                            $res = M('goods_pack_stock')->where(['goods_id' => $bar_pack['goods_pack_id'], 'date' => $bar_time])->setDec('xu_day_sales', $bar_pack['amount']);
                            if ($res === false) {
                                $this->_model->rollback();
                                return false;
                            }

                        } else {

                            //还原商品库存量
                            $res = M('goods_pack')->where(['id' => $bar_pack['goods_pack_id']])->setInc('stock', $bar_pack['amount']);
                            if ($res === false) {
                                $this->_model->rollback();
                                return false;
                            }

                            //还原每日销量
                            $res = M('goods_pack_stock')->where(['goods_id' => $bar_pack['goods_pack_id'], 'date' => $bar_time])->setDec('day_sales', $bar_pack['amount']);
                            if ($res === false) {
                                $this->_model->rollback();
                                return false;
                            }
                        }
                    }
                }

                //修改拼吧订单状态为已取消
                $res = M('bar')->save(['id' => $bar['id'], 'bar_status' => 0, 'updated_time' => time()]);
                if ($res === false) {
                    $this->_model->rollback();
                    return false;
                }
            }
        }

        $this->_model->commit();
        return true;
    }


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
        //获取支付宝支付配置信息
        $alipayConfig = C('alipay');
        vendor('alipay.AopClient');

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
            Tools::writeLog(json_encode($refundResult), C('LOG_PATH'), '', 'flush_ali');
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
            $config = C('APP_WXPAY_OPTION');
        } else {
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
        $postData['sign'] = Tools::getWxPaySign($postData, $config['KEY']);

        //将数据转化为xml
        $postXml = Tools::arrayToXml($postData);
        $response = Tools::postSSLXml('https://api.mch.weixin.qq.com/secapi/pay/refund', $postXml, $config['CERT_PATH']);
        $refundResult = Tools::xmlToArray($response);
        if ($refundResult['return_code'] == "SUCCESS" && $refundResult['result_code'] == 'SUCCESS') {
            //记录日志
            Tools::writeLog(json_encode($refundResult), C('LOG_PATH'), '', 'flush_wx');
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