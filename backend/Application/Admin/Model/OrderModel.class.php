<?php
/**
 * FileName: OrderModel.class.php
 * User: Comos
 * Date: 2017/10/17 16:19
 */

namespace Admin\Model;


use Think\Log;
use Think\Model;
use Think\Page;

class OrderModel extends Model
{

    private  $day_order;

    /**
     * 获取订单列表
     */
    public function getOrderList($page, $parma)
    {
        //判断搜索条件
        $where = [];
        if (!empty($parma['keywords'])) {
            switch ($parma['search_type']) {
                case 1:
                    $where['contacts_tel'] = $parma['keywords'];
                    break;
                case 2:
                    $where['contacts_realname'] = $parma['keywords'];
                    break;
                case 3:
                    $where['employee_realname'] = $parma['keywords'];
                    break;
                case 4:
                    $where['employee_tel'] = $parma['keywords'];
                    break;
                case 5:
                    $where['order_no'] = $parma['keywords'];
                    break;
                default:
            }
        }

        //订单类型
        if (isset($parma['status']) && $parma['status'] != '') {
            $where['api_order.status'] = $parma['status'];
        }

        //结算状态
        if (isset($parma['settlement_status']) && $parma['settlement_status'] != '') {
            $where['api_order.settlement_status'] = $parma['settlement_status'];
        }

        //订单类型
        if (isset($parma['order_type']) && $parma['order_type'] != '') {
            $where['api_order.order_type'] = $parma['order_type'];
        }

        if (!isset($parma['order_type'])) {
            $where['api_order.order_type'] = 3;
        }

        //支付方式
        if (isset($parma['payment']) && $parma['payment'] != '') {
            $where['api_order.payment'] = $parma['payment'];
        }

        //时间范围
        if (isset($parma['start_time']) && !empty($parma['start_time']) && isset($parma['stop_time']) && !empty($parma['stop_time'])){
            $start_time = strtotime($parma['start_time']);
            $stop_time = strtotime($parma['stop_time']);
            $where['api_order.created_time'] = [['EGT', $start_time], ['ELT',$stop_time]];
        }

        //$where['is_bar'] = 0;

        $pagesize = C('PAGE.PAGESIZE');
        $count = $this->join('api_merchant ON api_merchant.id = api_order.merchant_id', 'LEFT')
            ->join('api_member ON api_member.id = api_order.member_id', 'LEFT')
            ->where($where)
            ->count();

        $pay_prices = $this->join('api_merchant ON api_merchant.id = api_order.merchant_id', 'LEFT')
            ->join('api_member ON api_member.id = api_order.member_id', 'LEFT')
            ->sum('pay_price');

        $discount_prices = $this->join('api_merchant ON api_merchant.id = api_order.merchant_id', 'LEFT')
            ->join('api_member ON api_member.id = api_order.member_id', 'LEFT')
            ->sum('discount_money');

        $data['list'] = $this->field('
        api_order.id,
        api_merchant.title as merchant_title,
        api_member.nickname,
        api_order.order_no,
        api_order.contacts_realname,
        api_order.contacts_tel,
        api_order.contacts_sex,
        api_order.total_price,
        api_order.pay_price,
        api_order.purchase_price,
        api_order.discount_money,
        api_order.status,
        api_order.settlement_status,
        api_order.order_type,
        api_order.payment,
        api_order.description,
        from_unixtime(api_order.arrives_time, "%Y-%m-%d") as arrives_time,
        api_order.incr_time,
        api_order.created_time,
        from_unixtime(api_order.created_time) as created_time,
        api_order.employee_id,
        api_order.employee_realname,
        api_order.employee_avatar,
        api_order.employee_tel,
        api_order.take_time,
        api_order.relation_order_no,
        api_order.is_bar,
        api_order.is_xu
        ')
            ->join('api_merchant ON api_merchant.id = api_order.merchant_id', 'LEFT')
            ->join('api_member ON api_member.id = api_order.member_id', 'LEFT')
            ->join('api_coupon ON api_coupon.id = api_order.card_id', 'LEFT')
            ->page($page, $pagesize)
            ->where($where)
            ->order('api_order.id desc')
            ->select();

        if ($count === false || $data['list'] === false || $pay_prices === false || $discount_prices === false) {
            return false;
        }

        $pages = new Page($count, $pagesize);
        $pages->setConfig('header', '共%TOTAL_ROW%条');
        $pages->setConfig('first', '首页');
        $pages->setConfig('last', '末页');
        $pages->setConfig('prev', '上一页');
        $pages->setConfig('next', '下一页');
        $pages->setConfig('theme', C('PAGE.THEME'));

        $data['pageHtml'] = urldecode($pages->show());
        $data['money'] = [
            'pay_prices' => $pay_prices ? $pay_prices : '0.00',
            'discount_prices' => $discount_prices ? $discount_prices : '0.00'
        ];

        return $data;
    }

    /**
     * 获取订单详情
     */
    public function getDetailInfo($id)
    {

        $order_info = $this->field('
        api_order.id,
        api_merchant.title as merchant_title,
        api_member.nickname,
        api_order.merchant_id,
        api_order.order_no,
        api_order.member_id,
        api_order.contacts_realname,
        api_order.contacts_tel,
        api_order.contacts_sex,
        api_order.total_price,
        api_order.pay_price,
        api_order.purchase_price,
        api_order.discount_money,
        api_order.status,
        api_order.settlement_status,
        api_order.order_type,
        api_order.payment,
        api_order.description,
        from_unixtime(api_order.arrives_time, "%Y-%m-%d") as arrives_time,
        from_unixtime(api_order.arrives_time, "%Y-%m-%d") as arrives_time,
        api_order.incr_time,
        from_unixtime(api_order.created_time) as created_time,
        api_order.employee_id,
        api_order.employee_realname,
        api_order.employee_avatar,
        api_order.employee_tel,
        from_unixtime(api_order.updated_time) as updated_time,
        api_order.relation_order_no')
            ->join('api_merchant ON api_merchant.id = api_order.merchant_id', 'LEFT')
            ->join('api_member ON api_member.id = api_order.member_id', 'LEFT')
            ->where(['api_order.id' => $id])
            ->find();

        if (!$order_info) return false;

        //如果是卡座
        if ($order_info['order_type'] == 1) {

            $data = M('order_seat')->field('goods_seat_id,max_people,floor_price,set_price,total_people,seat_number')
                ->where(['order_no' => $order_info['order_no']])
                ->find();

        } elseif ($order_info['order_type'] == 2) {

            $data = M('order_pack')->field('goods_pack_id,title as pack_title,amount,price as pack_price,image as pack_image,pack_description')
                ->where(['order_no' => $order_info['order_no']])
                ->find();

            $seat = M('order_seat')->field('goods_seat_id,max_people,floor_price,set_price,total_people,seat_number')
                ->where(['order_no' => $order_info['order_no']])
                ->find();
            $seat = $seat ? $seat : [];

            //合并数组
            $data = array_merge($data, $seat);

        } else {
            $data = M('order_pack')->field('goods_pack_id,title as pack_title,amount,price as pack_price,image as pack_image,pack_description')
                ->where(['order_no' => $order_info['order_no']])
                ->find();
        }

        //合并数据
        $detail = array_merge($order_info, $data);

        //返回订单数据
        return $detail;
    }


    /**
     * 微信支付退款数据操作
     * @param $wx_tk
     * @param $order_info
     * @param $cancel_reason
     * @return bool
     */
    public function wxRefundUpdateData($wx_tk, $order_info, $refund_desc)
    {
        $this->startTrans();
        $time = time();
        //将微信退款数据写入数据表
        $refund_data = [
            'appid' => $wx_tk['appid'],
            'mch_id' => $wx_tk['mch_id'],
            'transaction_id' => $wx_tk['transaction_id'],
            'order_no' => $order_info['order_no'],
            'out_refund_no' => $wx_tk['out_refund_no'],
            'total_fee' => $wx_tk['total_fee'] / 100,
            'refund_fee' => $wx_tk['refund_fee'] / 100,
            'refund_desc' => $refund_desc,
            'refund_account' => '微信支付',
            'merchant_id' => $order_info['merchant_id'],
            'create_time' => $time,
            'refund_id' => $wx_tk['refund_id'],
            'member_id' => $order_info['member_id'],
        ];
        $rs = M('refund')->add($refund_data);
        if (!$rs) {
            $this->rollback();
            return false;
        }

        //修改订单状态为已拒绝
        $rs = $this->where(['id' => $order_info['id']])->save(['status' => 6, 'updated_time' => $time, 'cancel_reason' => $refund_desc]);
        if ($rs === false) {
            $this->rollback();
            return false;
        }

        //删除消息()
        $mrs = M('message_employee')->where(['order_no' => $order_info['order_no']])->delete();
        if ($mrs === false) {
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }


    /**
     * 退款操作
     * @param $order_info array 订单数据
     * @param $refund_desc string 退款备注
     * @return bool
     */
    public function refundOperation($order_info, $refund_desc)
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
            $this->rollback();
            return false;
        }

        //写入退款记录
        $record_data = [
            'member_id' => $order_info['member_id'],
            'type' => 3,
            'change_money' => $order_info['pay_price'],
            'trade_time' => $time,
            'source' => '空瓶子平台后台',
            'terminal' => '空瓶子平台后台',
            'title' => "订单取消退款",
            'order_no' => $order_info['order_no'],
            'before_recharge_money' => $member_record['before_recharge_money'],
            'after_recharge_money' => $recharge_money,
            'before_give_money' => $member_record['before_give_money'],
            'after_give_money' => $give_money
        ];
        $res = $memberRecordModel->add($record_data);
        if (!$res) {
            $this->rollback();
            return false;
        }

        //修改订单状态为已拒绝
        $rs = $this->where(['id' => $order_info['id']])->save(['status' => 6, 'updated_time' => $time, 'cancel_reason' => $refund_desc]);
        if ($rs === false) {
            $this->rollback();
            return false;
        }

        //删除消息()
        $mrs = M('message_employee')->where(['order_no' => $order_info['order_no']])->delete();
        if ($mrs === false) {
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }


    /**
     * 订单完成状态修改
     */
    public function markSuccessStatus($orderAndMember, $user_id)
    {
        //获取商户的营业时间
        $merchant_begin_time = D('merchant')->where(['id' => $orderAndMember['merchant_id']])->getField('begin_time');
        //当前日期
        $now_time = time();
        $merchant_begin_time = date('Y-m-d', $orderAndMember['arrives_time']) . ' ' . $merchant_begin_time;
        $merchant_begin_time = strtotime($merchant_begin_time);
        $merchant_begin_time = $merchant_begin_time - C('EARLY_COMPLETION_TIME');

        //判断是否超过营业时间,
        if ($now_time < $merchant_begin_time) {
            $this->error = "未到该订单的当日营业时间";
            return false;
        }

        $time = strtotime(date('Y-m-d', time()));
        //改变每日订单，和订单总数表中+1,首先要判定这两张表中的数据是否存在,如果存在，就更新信息，不存在就添加信息
        $day_order = M('order_everyday')->where(['merchant_id' => $orderAndMember['merchant_id'], 'time' => $time])->find();

        //日统计
        $daymodel = M('merchant_balance_day');
        $where = ['merchant_id' => $orderAndMember['merchant_id'], 'date' => strtotime(date('Y-m-d', $orderAndMember['created_time']))];
        $daydata = $daymodel->field('id,order_total,purchase_money,date')->where($where)->find();

        //月统计
        $monthmodel = M('merchant_balance_month');
        $where = ['merchant_id' => $orderAndMember['merchant_id'], 'month' => strtotime(date('Y-m', $orderAndMember['created_time']))];
        $monthdata = $monthmodel->field('id,order_total,purchase_money,month')->where($where)->find();

        //年统计
        $year = strtotime(date('Y', $orderAndMember['created_time']) . '-01-01 00:00:00');
        $yearmodel = M('merchant_balance_year');
        $yeardata = $yearmodel->field('id,order_total,purchase_money,year')
            ->where(['merchant_id' => $orderAndMember['merchant_id'], 'year' => $year])->find();

        //总统
        $totalmodel = M('merchant_balance_total');
        $totaldata = $totalmodel->field('id,order_total,purchase_money')->where(['merchant_id' => $orderAndMember['merchant_id']])->find();

        //获取所有等级对应的优惠
        $pril_model = M('member_privilege');
        $pr_data1 = $pril_model->field('level,quota')->select();

        //判断是否是拼吧订单
        if ($orderAndMember['is_bar'] == 1) {
            //获取拼吧主订单的id
            $bar_id = M('bar_order')->where(['order_id' => $orderAndMember['id']])->getField('bar_id');
            //获取所有参与的用户的id和支付金额
            $memberAll = M('bar_member')->field('member_id,pay_price,realname')->where(['bar_id' => $bar_id, 'pay_status' => 2])->select();
        }

        $memberKb[] = [
            'member_id' => $orderAndMember['member_id'],
            'pay_price' => $orderAndMember['pay_price'],
            'realname' => $orderAndMember['contacts_realname']
        ];

        //计算KB算法
        $memberAll = $memberAll ? $memberAll : $memberKb;
        if ($orderAndMember['is_bar'] == 1) {
            $record_name = '参与拼吧';
        } else {
            if ($orderAndMember['order_type'] == 1) {
                $record_name = '购买卡座';
            } elseif ($orderAndMember['order_type'] == 2) {
                $record_name = '购买卡座套餐';
            } elseif ($orderAndMember['order_type'] == 3) {
                $record_name = '购买优惠套餐';
            }elseif ($orderAndMember['order_type'] == 0){
                $record_name = '购买单品酒水';
            }
        }

        $member_data = [];
        foreach ($memberAll as $member) {
            //KB计算并写入积分兑换规则记录表
            $consumedata = M('member_capital')->where(['member_id' => $member['member_id']])->getField('consume_money');

            //根据用户的消费情况，获取KB,和提升会员等级
            $M_merber = M('member');
            //查找到用户表中对应的用户
            $mer_data = $M_merber->field('level,promoter_code,coin')->where(['id' => $member['member_id']])->find();
            // 积分计算规则 消费的总额*0.1
            $coin = $member['pay_price'] * C('COIN_RULE');
            $total_coin = $coin + $mer_data['coin'];

            //获取当前会员等级的赠送kB数量
            $give_coin = $pril_model->where(['level' => $mer_data['level']])->getField('coin');

            $data=[
                'consume'=>$consumedata,
                'coin'=>$coin,
                'total_coin'=>$total_coin,
                'give_coin'=>$give_coin,
                'pay_price'=>$member['pay_price'],
                'realname'=>$member['realname'],
                'member_data'=>$mer_data,
            ];

            $member_data[$member['member_id']]=$data;
        }


        //开启事务
        $this->startTrans();

        //订单状态修改为已完成
        $rs = $this->where(['id' => $orderAndMember['id']])->save(['updated_time' => $now_time, 'status' => 4]);
        if ($rs === false) {
            $this->error = '修改订单状态失败1';
            $this->rollback();
            return false;
        }

        //存在关联逾期订单
        if ($orderAndMember['relation_order_no']) {
            $rs = $this->where(['order_no' => $orderAndMember['relation_order_no']])->save(['updated_time' => $now_time, 'status' => 4]);
            if ($rs === false) {
                $this->error = '修改订单状态失败2';
                $this->rollback();
                return false;
            }
        }

        //判断如果是拼吧订单
        if ($orderAndMember['is_bar'] == 1) {
            $rs = M('bar')->where(['id' => $bar_id])->save(['bar_status' => 4, 'updated_time' => $now_time]);
            if ($rs === false) {
                $this->error = '修改拼吧状态失败';
                $this->rollback();
                return false;
            }
        }


        //后台记录用户操作记录
        $result = M('order_operate_record')->add(
            [
                'user_id' => $user_id,
                'order_id' => $orderAndMember['id'],
                'order_no' => $orderAndMember['order_no'],
                'content' => '订单完成操作',
                'created_time' => time(),
            ]
        );
        if ($result === false) {
            $this->error = '管理员操作记录写入失败';
            $this->rollback();
            return false;
        }


        $this->_TongjiOrder($member_data,$M_merber,$pr_data1,$record_name);


        //更新每日订单数
        if ($day_order) {
            $res1 = M('order_everyday')->where(['merchant_id' => $orderAndMember['merchant_id'], 'time' => $time])->setInc('amount', 1);
        } else {
            $rdata = ['merchant_id' => $orderAndMember['merchant_id'], 'amount' => 1, 'time' => $time];
            $res1 = M('order_everyday')->add($rdata);
        }
        if ($res1 === false) {
            $this->error = '每日库存更新是失败';
            $this->rollback();
            return false;
        }

        //总订单数
        $res2 = M('order_total')->where(['merchant_id' => $orderAndMember['merchant_id']])->setInc('order_total');
        if ($res2 === false) {
            $this->error = '更新总订单数失败';
            $this->rollback();
            return false;
        }

        //日统计
        $daybalance = $this->_balanceday($daymodel,$orderAndMember,$daydata);
        if ($daybalance === false) {
            $this->error = '日统计失败';
            $this->rollback();
            return false;
        }

        //月统计
        $monthbalance = $this->_balancemonth($monthmodel,$orderAndMember,$monthdata);
        if ($monthbalance === false) {
            $this->error = '月统计失败';
            $this->rollback();
            return false;
        }

        //年统计
        $yearbalance = $this->_balanceyear($yearmodel,$orderAndMember,$yeardata,$year);
        if ($yearbalance === false) {
            $this->error = '年统计失败';
            $this->rollback();
            return false;
        }

        //总统计
        $totalbalance = $this->_balancetotal($totalmodel,$orderAndMember,$totaldata);
        if ($totalbalance === false) {
            $this->error = '更新总统计失败';
            $this->rollback();
            return false;
        }


        //提交事务
        $this->commit();

        M('MessageEmployee')->where(['order_no' => $orderAndMember['order_no']])->delete();
        return true;
    }


    /**
     * 会员KB、消费额度，kb记录操作
     * @param $memberdatas
     * @param $M_merber
     * @param $pr_data1
     * @param $record_name
     * @return bool
     */
    private function _TongjiOrder($memberdatas,$M_merber,$pr_data1,$record_name)
    {
        foreach ($memberdatas as $key => $member) {
            //将消费总额度写入会员消费记录表中
            $consumeres = M('member_capital')->where(['member_id' => $key])->setInc('consume_money', $member['pay_price']);
            if ($consumeres === false) {
                $this->error = '更新消费额度失败';
                $this->rollback();
                return false;
            }

            if ($member['coin'] > 0) {
                // 获取用户当前的消费总额
                $total_free = $member['consume'] + $member['pay_price'];
                //获取当前会员等级对应的权益
                $total_coin = $member['total_coin']+$member['give_coin'];

                $pril_data = $this->memberLevelData($total_free, $total_coin,$pr_data1);
                //更新用户表
                $res4 = $M_merber->where(['id' => $key])->save($pril_data);
                if ($res4 === false) {
                    $this->error = '更新会员权益失败' . $member['member_id'];
                    $this->rollback();
                    return false;
                }

                //写入KB记录表中 api_member_kcoin_record()
                $kb_data = [
                    'member_id' => $key,
                    'record_name' => $record_name,
                    'number' => $member['coin'],
                    'type' => 1,
                    'before_number' => $member['member_data']['coin'],
                    'after_number' => $total_coin,
                    'created_time' => time(),
                ];

                if (floor($member['coin']) > 0) {
                    $kb_record = M('member_kcoin_record')->add($kb_data);
                    if ($kb_record === false) {
                        $this->error = '更新会员KB记录失败' . $member['member_id'];
                        $this->rollback();
                        return false;
                    }
                }


                //用户推广收益增加
                $reslut = $this->spreedSum($key,$member['member_data']['promoter_code'], $member['realname']);
                if ($reslut === false) {
                    $this->error = '推广收益数据更新失败';
                    $this->rollback();
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 日统计
     */
    private function _balanceday($daymodel,$orderdata,$daydata)
    {
        if (!$daydata) {
            $data = [
                'merchant_id' => $orderdata['merchant_id'],
                'order_total' => 1,
                'purchase_money' => $orderdata['pay_price'],
                'date' => strtotime(date('Y-m-d', $orderdata['created_time'])),
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
     * 月统计
     */
    private function _balancemonth($monthmodel,$orderdata,$monthdata)
    {
        if (!$monthdata) {
            $data = [
                'merchant_id' => $orderdata['merchant_id'],
                'order_total' => 1,
                'purchase_money' => $orderdata['pay_price'],
                'month' => strtotime(date('Y-m', $orderdata['created_time'])),
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
     * 年统计
     */
    private function _balanceyear($yearmodel,$orderdata,$yeardata,$year)
    {
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
     * 总统
     */
    private function _balancetotal($totalmodel,$orderdata,$totaldata)
    {
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
     * 推广收益的计算
     * @param $member_id
     * @param $promoter_code
     * @param $realname
     * @return bool
     */

    private function spreedSum($member_id,$promoter_code, $realname)
    {
        if (!empty($promoter_code)) {
            //根据推广码查询用户数据
            $prefix = substr($promoter_code['promoter_code'], 0, 1);
            $account_type = 1;
            switch ($prefix) {
                case 1: //用户端推广码
                    $account_type = 1;
                    break;
                case 2: //商户端推广码
                    $account_type = 2;
                    break;
            }
            $data = [
                'profit_time' => time(),
                'money' => C('PROMOTION_QUOTA'),
                'is_consume' => 1,
                'member_realname' => $realname
            ];
            //更新员工总收益
            $rs = M('spread_record')->where(['account_type' => $account_type, 'member_id' => $member_id, 'profit_time' => 0])->save($data);
            if ($rs === false) {
                $this->error = '推广增益记录更新失败';
                return false;
            }
        }
        return true;
    }


    /**
     * 会员等级统计
     * @param $total_free
     * @param $total_coin
     * @param $pr_data1
     * @return array
     */
    private function memberLevelData($total_free, $total_coin,$pr_data1)
    {
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