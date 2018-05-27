<?php


namespace Home\Model;

use Think\Model;

class OrderModel extends Model
{

    /**
     *  修改订单状态与其他信息
     */
    public function updateOrderStatus($order_info, $wxpay_data, $attach)
    {
        $time = time();
        if ($attach['buy_type'] == 1) {
            $changeData = ['status' => 2, 'payment' => 2, 'updated_time' => $time];
        } elseif ($attach['buy_type'] == 2) {
            $changeData = ['status' => 7, 'payment' => 2, 'updated_time' => $time,'take_time'=>strtotime(date('Y-m-d', time()))];
        }
        //修改订单状态
        $order_rs = $this->where(['id' => $order_info['id']])->save($changeData);
        if ($order_rs === false) {
            $this->error='订单状态修改失败';
            $this->rollback();
            return false;
        }

        //写入订单支付日志
        $pay_logs = [
            'merchant_id' => $order_info['merchant_id'],
            'member_id' => $order_info['member_id'],
            'appid' => $wxpay_data['appid'],
            'mch_id' => $wxpay_data['mch_id'],
            'device_info' => '',
            'result_code' => $wxpay_data['result_code'],
            'err_code' => $wxpay_data['err_code'],
            'err_code_des' => $wxpay_data['err_code_des'],
            'openid' => $wxpay_data['openid'],
            'is_subscribe' => $wxpay_data['is_subscribe'],
            'trade_type' => $wxpay_data['trade_type'],
            'bank_type' => $wxpay_data['bank_type'],
            'total_fee' => $wxpay_data['total_fee'] / 100,
            'fee_type' => $wxpay_data['fee_type'],
            'transaction_id' => $wxpay_data['transaction_id'],
            'out_trade_no' => $wxpay_data['out_trade_no'],
            'time_end' => $wxpay_data['time_end'],
            'create_time' => $time,
            'pay_type' => $attach['pay_type'],
            'order_no' => $attach['order_no'],
            'order_id' => $order_info['id'],
        ];
        $log_rs = D('paylog_wxpay')->add($pay_logs);
        if (!$log_rs) {
            $this->error='支付日志记录失败';
            $this->rollback();
            return false;
        }

        //正常购买订单
        if ($attach['buy_type'] == 1) {
            //写入预订部员工消息记录表数据
            //TODO::写入预订部新订单消息到消息表中
            //模板: '新支付订单|客人：{contacts_realname}，{buy_goods}。'
            $tmps = C('SYS_MESSAGES_TMP');
            if ($order_info['order_type'] == 1) {
                $buy_goods = '预定了卡座';
            } elseif ($order_info['order_type'] == 2) {
                $buy_goods = '购买了卡座套餐';
            } elseif ($order_info['order_type'] == 3) {
                $buy_goods = '购买了优惠套餐';
            }

            //消息体组装替换
            $messageContent = str_replace(['{contacts_realname}', '{buy_goods}'], [$order_info['contacts_realname'], $buy_goods], $tmps[3]);
            $msg_data = [
                'employee_id' => 0, //员工ID不存在表示所有预定部员工都可以看到此消息
                'content' => $messageContent,
                'order_no' => $order_info['order_no'],
                'order_id' => $order_info['id'],
                'created_time' => time(),
                'type' => 3,    //3为只有预订部的员工才能接收消息
                'merchant_id' => $order_info['merchant_id'],
                'msg_type' => 1,
            ];
            $rs = M('message_employee')->add($msg_data);
            if ($rs === false) {
                $this->error='订单消息添加失败';
                $this->rollback();
                return false;
            }
        }


        //TODO::其他订单相关数据处理
        if ($attach['buy_type'] == 2) {
            //模板: '新支付订单|客人：{contacts_realname}，{buy_goods}。'
            $tmps = C('SYS_MESSAGES_TMP');
            if ($order_info['order_type'] == 1) {
                $buy_goods = '预定了卡座';
            } elseif ($order_info['order_type'] == 2) {
                $buy_goods = '购买了卡座套餐';
            } elseif ($order_info['order_type'] == 3) {
                $buy_goods = '购买了优惠套餐';
            }

            //消息体组装替换
            $messageContent = str_replace(['{contacts_realname}', '{buy_goods}'], [$order_info['contacts_realname'], $buy_goods], $tmps[3]);
            $msg_data = [
                'employee_id' =>$order_info['employee_id'] , //员工ID不存在表示所有预定部员工都可以看到此消息
                'content' => $messageContent,
                'order_no' => $order_info['order_no'],
                'order_id' => $order_info['id'],
                'created_time' => time(),
                'type' => 1,    //3为只有预订部的员工才能接收消息
                'merchant_id' => $order_info['merchant_id'],
                'msg_type' => 1,
            ];
            $rs = M('message_employee')->add($msg_data);
            if ($rs === false) {
                $this->error='订单消息添加失败';
                $this->rollback();
                return false;
            }

            //日统计计算
            $dayres = $this->_balanceday($order_info);
            if ($dayres === false) {
                $this->error = '更新订单日统计失败';
                $this->rollback();
                return false;
            }

            //月统计计算
            $monthres = $this->_balancemonth($order_info);
            if ($monthres === false) {
                $this->error = '更新订单月统计失败';
                $this->rollback();
                return false;
            }

            //年统计
            $yearres = $this->_balanceyear($order_info);
            if ($yearres === false) {
                $this->error = '更新订单年统计失败';
                $this->rollback();
                return false;
            }

            //总统计
            $dayres = $this->_balancetotal($order_info);
            if ($dayres === false) {
                $this->error = '更新订单总统计失败';
                $this->rollback();
                return false;
            }

            $times = strtotime(date('Y-m-d', time()));
            //更新每日订单数
            $res1 = M('order_everyday')->where(['merchant_id' => $order_info['merchant_id'], 'time' => $times])->setInc('amount');
            if ($res1 === false) {
                $this->error = '更新每日订单数失败';
                $this->rollback();
                return false;
            }

            //更新总订单总数
            $res2 = M('order_total')->where(['merchant_id' => $order_info['merchant_id']])->setInc('order_total');
            if ($res2 === false) {
                $this->error = '更新订单总数失败';
                $this->rollback();
                return false;
            }

            //更新顾客相关的消费总额,和最后一次到店消费时间
            //获取顾客的信息保存并存入客户表中,查找该名客户是否已经存在客户表
            $_model = M('merchant_customer');
            $m_where = [
                'merchant_id' => $order_info['merchant_id'],
                'customer_tel' => $order_info['contacts_tel']
            ];
            $m_data = $_model->where($m_where)->find();
            if ($m_data) {
                $m_cond = [
                    'last_time' => $time,
                    'grosses' => ($m_data['grosses'] + $order_info['pay_price']),
                ];
                //客户存在，就将用户的相关信息存入数据表，消费总额，最后一次消费，次数
                $res3 = $_model->where(['id' => $m_data['id']])->save($m_cond);
            } else {
                $res3 = false;
            }
            if ($res3 === false) {
                $this->error = '更新客户信息失败';
                $this->rollback();
                return false;
            }

            //更新订单会员积分和消费记录
            $consumedata = M('member_capital')->where(['member_id' => $order_info['member_id']])->find();
            //将消费总额度写入会员消费记录表中
            $consumeres = M('member_capital')->where(['member_id' => $order_info['member_id']])->setInc('consume_money', $order_info['pay_price']);
            if ($consumeres === false) {
                $this->error = '更新消费额度失败';
                $this->rollback();
                return false;
            }

            //根据用户的消费情况，获取KB,和提升会员等级
            $M_merber = M('member');
            //查找到用户表中对应的用户
            $mer_data = $M_merber->where(['id' => $order_info['member_id']])->find();
            // 积分计算规则 消费的总额*0.1
            $coin = $order_info['pay_price'] * C('COIN_RULE');
            $total_coin = $coin + $mer_data['coin'];
            // 获取用户当前的消费总额
            $total_free = $consumedata['consume_money'] + $order_info['pay_price'];
            //获取当前会员等级对应的权益
            $pril_data = $this->memberLevelData($mer_data['level'], $total_free, $total_coin);
            //更新用户表
            $res4 = $M_merber->where(['id' => $order_info['member_id']])->save($pril_data);
            if ($res4 === false) {
                $this->error = '更新会员权益失败';
                $this->rollback();
                return false;
            }
        }

        $this->commit();
        return true;
    }


    /**
     * @author   jiangling
     *  写入订单统计表,日统计
     * @param $orderdata  订单详情
     */

    private function _balanceday($orderdata)
    {
        $daymodel = M('merchant_balance_day');
        //日统计
        $daydata = $daymodel->where(['merchant_id' => $orderdata['merchant_id'], 'date' => strtotime(date('Y-m-d', time()))])->find();
        if (!$daydata) {
            $data = [
                'merchant_id' => $orderdata['merchant_id'],
                'order_total' => 1,
                'purchase_money' => $orderdata['pay_price'],
                'date' => strtotime(date('Y-m-d', time())),
                'created_time' => time()
            ];
            $res1 = $daymodel->add($data);
        } else {
            $num = $daydata['order_total'] + 1;
            $totalmoney = $orderdata['pay_price'] + $daydata['purchase_money'];
            $data = ['order_total' => $num, 'purchase_money' => $totalmoney, 'created_time' => time()];
            $res1 = $daymodel->where(['id' => $daydata['id']])->save($data);
        }
        return $res1;
    }


    /**
     * @author jiangling
     * 月统计
     * @param $orderdata
     * @return bool|mixed
     */
    private function _balancemonth($orderdata)
    {
        $monthmodel = M('merchant_balance_month');
        //月统计
        $monthdata = $monthmodel->where(['merchant_id' => $orderdata['merchant_id'], 'month' => strtotime(date('Y-m', time()))])->find();
        if (!$monthdata) {
            $data = [
                'merchant_id' => $orderdata['merchant_id'],
                'order_total' => 1,
                'purchase_money' => $orderdata['pay_price'],
                'month' => strtotime(date('Y-m', time())),
                'created_time' => time()
            ];
            $res2 = $monthmodel->add($data);
        } else {
            $num = $monthdata['order_total'] + 1;
            $totalmoney = $orderdata['pay_price'] + $monthdata['purchase_money'];
            $data = ['order_total' => $num, 'purchase_money' => $totalmoney, 'created_time' => time()];
            $res2 = $monthmodel->where(['id' => $monthdata['id']])->save($data);
        }
        return $res2;
    }

    /**
     * @author jiangling
     * 年统计
     * @param $orderdata
     * @return bool|mixed
     */
    private function _balanceyear($orderdata)
    {
        $year = strtotime(date('Y', time()) . '-01-01 00:00:00');
        $yearmodel = M('merchant_balance_year');
        //年统计
        $yeardata = $yearmodel->where(['merchant_id' => $orderdata['merchant_id'], 'year' => $year])->find();
        if (!$yeardata) {
            $data = [
                'merchant_id' => $orderdata['merchant_id'],
                'order_total' => 1,
                'purchase_money' => $orderdata['pay_price'],
                'year' => $year,
                'created_time' => time()
            ];
            $res3 = $yearmodel->add($data);
        } else {
            $num = $yeardata['order_total'] + 1;
            $totalmoney = $orderdata['pay_price'] + $yeardata['purchase_money'];
            $data = ['order_total' => $num, 'purchase_money' => $totalmoney, 'created_time' => time()];
            $res3 = $yearmodel->where(['id' => $yeardata['id']])->save($data);
        }
        return $res3;
    }

    /**
     * @author jiangling
     * 总统计表(订单总数,订单总金额)
     * @param $orderdata
     * @return bool|mixed
     */
    private function _balancetotal($orderdata)
    {

        $totalmodel = M('merchant_balance_total');

        //总统
        $totaldata = $totalmodel->where(['merchant_id' => $orderdata['merchant_id']])->find();
        if (!$totaldata) {
            $data = [
                'merchant_id' => $orderdata['merchant_id'],
                'order_total' => 1,
                'purchase_money' => $orderdata['pay_price'],
                'created_time' => time(),
                'last_time' => time()
            ];
            $res4 = $totalmodel->add($data);
        } else {
            $num = $totaldata['order_total'] + 1;
            $totalmoney = ($orderdata['pay_price'] + $totaldata['purchase_money']);
            $data = ['order_total' => $num, 'purchase_money' => $totalmoney, 'last_time' => time()];
            $res4 = $totalmodel->where(['id' => $totaldata['id']])->save($data);
        }

        return $res4;
    }


    /**
     * 获取员工消费等级优惠权限表
     * @author jiangling
     * @param $level int 会员等级
     * @param $total_free string 总消费额度
     * @param $total_coin int 积分/K币
     * @return array
     */
    private function memberLevelData($level, $total_free, $total_coin)
    {
        $pril_model = M('member_privilege');
        //获取当前客户的等级对应的优惠
        $pr_data = $pril_model->where(['level' => $level])->find();
        //总K币
        $total_coin = $total_coin + $pr_data['coin'];
        //获取所有等级对应的优惠
        $pr_data1 = $pril_model->field('level,quota')->select();

        if ($total_free >= $pr_data1[0]['quota'] && $total_free < $pr_data1[1]['quota']) {
            $level = $pr_data1[0]['level'];
        } else if ($total_free >= $pr_data1[1]['quota'] && $total_free < $pr_data1[2]['quota']) {
            $level = $pr_data1[1]['level'];
        } else if ($total_free >= $pr_data1[2]['quota'] && $total_free < $pr_data1[3]['quota']) {
            $level = $pr_data1[2]['level'];
        } else if ($total_free >= $pr_data1[3]['quota'] && $total_free < $pr_data1[4]['quota']) {
            $level = $pr_data1[3]['level'];
        } else if ($total_free >= $pr_data1[4]['quota'] && $total_free < $pr_data1[5]['quota']) {
            $level = $pr_data1[4]['level'];
        } else {
            $level = $pr_data1[5]['level'];
        }
        $p_arr = ['coin' => $total_coin, 'level' => $level];

        return $p_arr;
    }

}
