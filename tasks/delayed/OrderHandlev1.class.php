<?php

/**
 * Created by PhpStorm.
 * User: nano
 * Date: 2018/3/11 0011
 * Time: 17:52
 */
class OrderHandlev1
{
    public $timeout;    //订单超时时间
    public $config;
    public $haveTime = 0;   //剩余时间

    /**
     * 公共参数设置
     * OrderHandle constructor.
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->timeout = $config['ORDER_OVERTIME'];
        $this->bartimeout = $config['BEFORE_TIME'];
    }

    /**
     * 普通购买订单超时修改订单状态
     * @param $data array
     * @return bool
     */
    public function generalOrderTimeout($data)
    {
        $orderModel = M('order');
        $order = $orderModel->where(['id' => $data['order_id']])->find();
        if (!$order) return false;

        //更改超过30分钟未支付的卡套散套订单状态为已取消
        if ($order['status'] == 1 && $order['is_bar'] == 0) {
            //$abolish_time = $order['created_time'] + $this->timeout;   //订单超时时间
            if ($order['order_type'] == 1) {
                //卡座订单超时释放卡座
                $orderModel->startTrans();
                //修改订单状态为已取消
                $rs = $orderModel->where(['id' => $order['id']])->save(['status' => 0, 'updated_time' => time()]);
                if ($rs === false) {
                    $orderModel->rollback();
                    return false;
                }

                $this->releaseHolder($orderModel, $order);
                $orderModel->commit();

            } else {
                //还原库存
                $orderModel->startTrans();
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

                $this->packRestoreStock($orderModel, $order);


                $orderModel->commit();
            }
        }

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

                    $xu_day_sales = M('goods_pack_stock')->where(['goods_id' => $pack_good['goods_pack_id'], 'date' => $time])->getField('xu_day_sales');

                    $total_xu = $xu_day_sales - $pack_good['amount'];
                    if ($total_xu < 0) {
                        $total_xu = 0;
                    }
                    //还原每日销量
                    $res = M('goods_pack_stock')
                        ->where(['goods_id' => $pack_good['goods_pack_id'], 'date' => $time])->save(['xu_day_sales' => $total_xu]);
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

                    $day_sales = M('goods_pack_stock')
                        ->where(['goods_id' => $pack_good['goods_pack_id'], 'date' => $time])->getField('day_sales');

                    $total_sales = $day_sales - $pack_good['amount'];
                    if ($total_sales < 0) {
                        $total_sales = 0;
                    }
                    //还原每日销量
                    $res = M('goods_pack_stock')
                        ->where(['goods_id' => $pack_good['goods_pack_id'], 'date' => $time])->save(['day_sales' => $total_sales]);
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
    protected function releaseHolder($orderModel, $order)
    {
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
     * 普通订单逾期操作
     * @param $data
     * @return bool|false|int
     */
    public function generalOrderOverdue($data)
    {
        $orderModel = M('order');
        $order = $orderModel->where(['id' => $data['order_id']])->find();
        if (!$order) return false;

        $merchant = M('merchant')->field('title,end_time, begin_time')->find($order['merchant_id']);
        if (!$merchant) return false;

        //卡套逾期操作 => 已接单|卡套
        if ($order['status'] == 7 && $order['order_type'] == 2 && $order['is_bar'] == 0) {

            //修改订单状态
            $orderModel->where(['id' => $order['id']])->save(['status' => 3, 'updated_time' => time()]);

            M('message_employee')->where(['order_no' => $order['order_no']])->delete();

            //发送短信通知
            //【空瓶子】尊敬的#name#，您购买的#merchant#的#money#元卡座套餐由于未准时到店消费，系统将为您保留该套餐#day#日，您须再次预定卡座到店消费。
            //获取用户的逾期周期
            $delayed = M('member')->join('api_member_privilege ON api_member_privilege.level = api_member.level')
                ->where(['api_member.id' => $order['member_id']])
                ->getField('delayed');

            $tpl_value = [
                '#name#' => $order['contacts_realname'],
                '#merchant#' => $merchant['title'],
                '#money#' => $order['pay_price'],
                '#day#' => $delayed,
            ];
            $ypsms = new YunpianSms();
            $ypsms->tplSingleSend($order['contacts_tel'], $this->config['YUNPIAN']['kataoyuqi'], $tpl_value);
        }
    }

    /**
     * 普通过期作废操作
     * @param $data
     * @return bool|false|int
     */
    public function seatExpiredCancel($data)
    {
        $orderModel = M('order');
        $order = $orderModel->where(['id' => $data['order_id']])->find();
        if (!$order) return false;

        //卡套逾期作废操作 => 订单状态：已逾期, 订单类型：卡套
        /* if ($order['status'] == 3 && $order['order_type'] == 2 && $order['top_order_id'] == 0 && $order['is_bar'] == 0) {

             //获取当前用户的可逾期期限 单位:天
             $delayed = M('member')->join('api_member_privilege ON api_member.level = api_member_privilege.level')->where(['api_member.id' => $order['member_id']])->getField('delayed');

             //订单作废的截止时间
             $cancel_time = $order['updated_time'] + $delayed * 24 * 60 * 60;

             //可逾期总时间小于当前时间
             if (time() >= $cancel_time) {
                 $this->updateInvalid($order['id'], $order['order_no']);
                 $this->toUserSms($order);   //发送短信通知
             } else {
                 $this->haveTime = $cancel_time - time();
                 return false;
             }
         }*/

        //散套作废操作 => 订单状态：已接单,订单类型：散套
        if ($order['status'] == 7 && $order['order_type'] == 3 && $order['is_xu'] == 0 && $order['is_bar'] == 0) {

            $orderModel->startTrans();
            $this->updateInvalid($order['id'], $order['order_no']);
            $this->packRestoreStock($orderModel, $order);
            $this->toUserSms($order);   //发送短信通知
            $orderModel->commit();

            return true;
        }

        //卡座预定作废操作 => 订单状态：已接单, 订单类型：卡座
        if ($order['status'] == 7 && $order['order_type'] == 1 && $order['is_bar'] == 0) {

            //判断延期时间是否设置
            if ($order['incr_time']) {
                $arrives_time = $order['obegin_time'] + $order['incr_time'] * 60;
            } else {
                $arrives_time = $order['obegin_time'];
            }

            //加上延迟作废的时间
            $arrives_time = $arrives_time + $this->config['FINISH_DELAY_TIME'];

            //修改作废订单状态
            if (time() >= $arrives_time) {

                $orderModel->startTrans();
                $rs = $orderModel->where(['id' => $order['id']])->save(['status' => 5, 'updated_time' => time()]);
                if ($rs === false) {
                    $orderModel->rollback();
                    return false;
                }

                $this->releaseHolder($orderModel, $order);
                $orderModel->commit();

                //删除订单相关消息数据
                M('message_employee')->where(['order_no' => $order['order_no']])->delete();
                $this->toUserSms($order);   //发送短信通知

                return true;
            } else {
                $this->haveTime = $arrives_time - time();
                return false;
            }
        }

        return true;
    }

    /**
     * 给用户发送作废短信通知
     * @param $order
     */
    private function toUserSms($order)
    {
        $ypsms = new YunpianSms();
        $tpl_value = [
            '#name#' => '用户您好',
            '#orderno#' => $order['order_no'],
        ];
        $ypsms->tplSingleSend($order['contacts_tel'], $this->config['YUNPIAN']['zuofeitongzhi'], $tpl_value);
    }

    /**
     * 作废数据操作
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
        M('message_employee')->where(['order_no' => $order_no])->delete();
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

    /**
     * 拼吧超时过期
     * @param $data array
     * @return bool
     */
    public function pinBarTimeout($data)
    {
        $barMemberModel = M('bar_member');
        $ypsms = new YunpianSms();
        $time = time();

        $barModel = M('bar');
        $bar_order = $barModel->where(['id' => $data['order_id'], 'bar_status' => 1])->find();
        if (!$bar_order) {
            return true;
        }

        $bar_user_orders = $barMemberModel->where(['bar_id' => $data['order_id']])->select();
        $barModel->startTrans();

        //(还原库存操作)拼吧类型 1 酒局 2 派对
        if ($bar_order['bar_type'] == 1) {
            $bar_time = date('Ymd', $bar_order['arrives_time']);
            $bar_packs = M('bar_pack')->where(['bar_id' => $bar_order['id']])->select();
            foreach ($bar_packs as $bar_pack) {

                if ($bar_order['is_xu'] == 1) {

                    $res = M('goods_pack')->where(['id' => $bar_pack['goods_pack_id']])->setInc('xu_stock', $bar_pack['amount']);
                    if ($res === false) {
                        $barModel->rollback();
                        return false;
                    }

                    $xu_day_sales = M('goods_pack_stock')
                        ->where(['goods_id' => $bar_pack['goods_pack_id'], 'date' => $bar_time])->getField('xu_day_sales');
                    $total_xu = $xu_day_sales - $bar_pack['amount'];
                    $total_xu = $total_xu >= 0 ? $total_xu : 0;

                    $res = M('goods_pack_stock')
                        ->where(['goods_id' => $bar_pack['goods_pack_id'], 'date' => $bar_time])->save(['xu_day_sales' => $total_xu]);
                    if ($res === false) {
                        $barModel->rollback();
                        return false;
                    }

                } else {

                    $res = M('goods_pack')->where(['id' => $bar_pack['goods_pack_id']])->setInc('stock', $bar_pack['amount']);
                    if ($res === false) {
                        $barModel->rollback();
                        return false;
                    }

                    $day_sales = M('goods_pack_stock')
                        ->where(['goods_id' => $bar_pack['goods_pack_id'], 'date' => $bar_time])->getField('day_sales');
                    $total_sales = $day_sales - $bar_pack['amount'];
                    $total_sales = $total_sales >= 0 ? $total_sales : 0;

                    $res = M('goods_pack_stock')
                        ->where(['goods_id' => $bar_pack['goods_pack_id'], 'date' => $bar_time])->save(['day_sales' => $total_sales]);
                    if ($res === false) {
                        $barModel->rollback();
                        return false;
                    }
                }
            }
        }

        if (count($bar_user_orders) > 0) {
            //实例退款类
            $excuteRefund = new ExcuteRefundv1($this->config);
            foreach ($bar_user_orders as $bar_user_order) {
                if ($bar_user_order['pay_status'] == 2) {
                    //状态为已支付的订单变更为退款
                    $rs = $excuteRefund->createRefund($bar_user_order, $bar_user_order['pay_no'], $data['order_id']);
                    if ($rs) {

                        $tpl_value = [
                            '#orderno#' => $bar_user_order['pay_no'],
                            '#reason#' => '拼吧失败',
                        ];
                        $ypsms->tplSingleSend($bar_user_order['tel'], $GLOBALS['CONFIG']['YUNPIAN']['tuikuantongzhi'], $tpl_value);
                    } else {

                        Tools::write(json_encode($bar_user_order), 'ERR', __FILE__, __METHOD__, LOG_PATH);
                    }

                } elseif ($bar_user_order['pay_status'] == 1) {

                    $barMemberModel->where(['id' => $bar_user_order['id']])->save(['pay_status' => 0, 'updated_time' => $time]);
                }
            }
        }

        Tools::write(json_encode($bar_user_orders), 'MEMBER', __FILE__, __METHOD__, LOG_PATH);

        //修改拼吧订单状态为已取消
        $res = $barModel->save(['id' => $data['order_id'], 'bar_status' => 0, 'updated_time' => time()]);
        if ($res === false) {
            $barModel->rollback();
            return false;
        }

        $barModel->commit();
        return true;

    }

    /**
     * 用户参与拼吧的支付订单超时取消的按钮
     */
    public function memberBarTimeOut($data)
    {
        $memberModel = M('bar_member');
        $rs = $memberModel->where(['id' => $data['order_id'], 'pay_status' => 1])->find();
        if (!$rs) {
            return false;
        }
        $bar = M('bar')->field('member_id,bar_type')->where(['id' => $rs['bar_id']])->find();

        $bar = M('bar')->field('member_id,bar_type')->where(['id' => $rs['bar_id']])->find();
        $memberModel->startTrans();
        $member_rs = $memberModel->where(['id' => $data['order_id']])->save(['pay_status' => 0, 'updated_time' => time()]);
        if ($member_rs === false) {
            $memberModel->rollback();
            return false;
        }

        if ($bar['member_id'] == $rs['member_id'] && $bar['bar_type'] == 1) {
            $bar_rs = M('bar')->where(['id' => $rs['bar_id']])->save(['bar_status' => 0, 'updated_time' => time()]);
            if ($bar_rs === false) {
                $memberModel->rollback();
                return false;
            }
        }

        $memberModel->commit();
        return true;
    }
}