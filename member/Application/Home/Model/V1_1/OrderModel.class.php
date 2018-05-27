<?php
/**
 * FileName: BuyWineModel.class.php
 * User: Comos
 * Date: 2018/3/9 16:34
 */

namespace Home\Model\V1_1;


use Org\Util\SocketPush;
use Org\Util\Tools;
use Think\Log;
use Think\Model;

class OrderModel extends Model
{
    public $pay_data;
    public $attach;
    public $order;


    /**
     * 线上支付订单结果处理  买酒买套餐
     * @param $pay_data
     * @param $attach
     * @return bool
     */
    public function orderPayResultProcessing($pay_data, $attach)
    {
        $this->pay_data = $pay_data;
        $this->attach = $attach;

        //查询订单数据是否存在
        if (!$order = M('order')->where(['id' => $attach['order_id']])->find()) {
            return false;
        }

        //验证订单编号是否相等
        if ($attach['order_no'] != $order['order_no']) {
            return false;
        }

        //订单数据
        $this->order = $order;
        //判断是否已处理订单状态, 满足订单状态的状态不进行二次处理,直接返回已处理
        $status_array = [2, 3, 4, 5, 6, 7];
        if (in_array($order['status'], $status_array)) {
            return true;
        }

        //判断金额是否相等
        if ($pay_data['receipt_fee'] != $order['pay_price']) {
            return false;
        }

        //处理订单
        if (!$this->updateOrderStatus($pay_data, $attach, $order)) {
            return false;
        }

        return true;
    }


    /**
     * 修改订单状态与其他信息
     * @param $pay_data array 微信支付回调数据
     * @param $attach  array
     * @param $order
     * @return bool
     */
    private function updateOrderStatus($pay_data, $attach, $order)
    {
        $time = time();
        $changeData = [];
        if ($attach['buy_type'] == 1) {
            //正常购买下单
            if ($order['order_type'] == 3 || $order['order_type'] == 0) {
                $changeData = [
                    'status' => 7,
                    'payment' => $pay_data['payment'],
                    'updated_time' => $time
                ];
            } else if ($order['order_type'] == 1 || $order['order_type'] == 2) {
                $changeData = [
                    'status' => 2,
                    'payment' => $pay_data['payment'],
                    'updated_time' => $time
                ];
            }
        } elseif ($attach['buy_type'] == 2) {
            //续酒购买下单
            $changeData = [
                'status' => 7,
                'payment' => $pay_data['payment'],
                'updated_time' => $time,
                'take_time' => strtotime(date('Y-m-d', time()))
            ];
        }

        $this->startTrans();
        //修改订单状态
        $order_rs = M('order')->where(['id' => $order['id']])->save($changeData);
        if ($order_rs === false) {
            $this->rollback();
            return false;
        }

        //写入支付记录
        $payment_record = [
            'member_id' => $order['member_id'],
            'merchant_id' => $order['merchant_id'],
            'order_id' => $order['id'],
            'order_no' => $order['order_no'],
            'appid' => $pay_data['appid'],
            'mch_id' => $pay_data['mch_id'],
            'trade_type' => $pay_data['trade_type'],
            'order_fee' => $order['pay_price'],
            'receipt_fee' => $pay_data['receipt_fee'],
            'trade_no' => $pay_data['trade_no'],
            'end_time' => $pay_data['end_time'],
            'pay_type' => $attach['pay_type'],
            'buy_type' => $attach['buy_type'],
            'payment' => $pay_data['payment'],
            'created_time' => $time,
        ];
        if (!$res = M('payment_record')->add($payment_record)) {
            $this->rollback();
            return false;
        }

        /**
         * 正常购买订单
         */
        if ($attach['buy_type'] == 1) {

            if ($order['order_type'] == 3 || $order['order_type'] == 0) {
                /*if ($this->countOrderData($order, $attach['buy_type']) === false) {
                    $this->rollback();
                    return false;
                }*/
            } else if ($order['order_type'] == 1 || $order['order_type'] == 2) {
                //写入预订部员工消息记录表数据  模板: '新支付订单|客人：{contacts_realname}，{buy_goods}。'
                $tmps = C('SYS_MESSAGES_TMP');
                $buy_goods = '';
                if ($order['order_type'] == 1) {
                    $buy_goods = '预定了卡座';
                } elseif ($order['order_type'] == 2) {
                    $buy_goods = '购买了卡座套餐';
                } elseif ($order['order_type'] == 3) {
                    $buy_goods = '购买了优惠套餐';
                } elseif ($order['order_type'] == 0) {
                    $buy_goods = '购买了单品酒水';
                }

                //消息体组装替换
                $messageContent = str_replace(['{contacts_realname}', '{buy_goods}'], [$order['contacts_realname'], $buy_goods], $tmps[3]);
                $msg_data = [
                    'employee_id' => 0, //员工ID不存在表示所有预定部员工都可以看到此消息
                    'content' => $messageContent,
                    'order_no' => $order['order_no'],
                    'order_id' => $order['id'],
                    'created_time' => time(),
                    'type' => 3,    //3为只有预订部的员工才能接收消息
                    'merchant_id' => $order['merchant_id'],
                    'msg_type' => 1,
                ];
                $rs = M('message_employee')->add($msg_data);
                if ($rs === false) {
                    $this->rollback();
                    return false;
                }
            }
        }

        /**
         * 正常购买酒水续酒
         */
        if ($attach['buy_type'] == 2) {

            // 主订单为卡座或者卡套的续酒
            if ($order['order_type'] == 1 || $order['order_type'] == 2) {
                //模板: '新支付订单|客人：{contacts_realname}，{buy_goods}。'
                $tmps = C('SYS_MESSAGES_TMP');
                $buy_goods = '';
                if ($order['order_type'] == 1) {
                    $buy_goods = '预定了卡座';
                } elseif ($order['order_type'] == 2) {
                    $buy_goods = '购买了卡座套餐';
                } elseif ($order['order_type'] == 3) {
                    $buy_goods = '购买了优惠套餐';
                } elseif ($order['order_type'] == 0) {
                    $buy_goods = '购买了单品酒水';
                }

                //消息体组装替换
                $messageContent = str_replace(['{contacts_realname}', '{buy_goods}'], [$order['contacts_realname'], $buy_goods], $tmps[3]);
                $msg_data = [
                    'employee_id' => $order['employee_id'], //员工ID不存在表示所有预定部员工都可以看到此消息
                    'content' => $messageContent,
                    'order_no' => $order['order_no'],
                    'order_id' => $order['id'],
                    'created_time' => time(),
                    'type' => 1,    //3为只有预订部的员工才能接收消息
                    'merchant_id' => $order['merchant_id'],
                    'msg_type' => 1,
                ];
                $rs = M('message_employee')->add($msg_data);
                if ($rs === false) {
                    $this->error = '订单消息添加失败';
                    $this->rollback();
                    return false;
                }
            }

            /*if ($this->countOrderData($order, $attach['buy_type']) === false) {
                $this->rollback();
                return false;
            }*/

        }

        $this->commit();

        //写入定时任务
        if ($attach['buy_type'] == 1) {
            //写入散套定时任务
            $this->setOverdueDelayedTask($order);
        }
        return true;
    }

    /**
     * 统计订单数据
     * @param $order
     * @param $buy_type
     * @return bool
     */
    private function countOrderData($order, $buy_type)
    {
        //日统计计算
        $dayres = $this->_balanceday($order);
        if ($dayres === false) {
            $this->error = '更新订单日统计失败';
            $this->rollback();
            return false;
        }

        //月统计计算
        $monthres = $this->_balancemonth($order);
        if ($monthres === false) {
            $this->error = '更新订单月统计失败';
            $this->rollback();
            return false;
        }

        //年统计
        $yearres = $this->_balanceyear($order);
        if ($yearres === false) {
            $this->error = '更新订单年统计失败';
            $this->rollback();
            return false;
        }

        //总统计
        $dayres = $this->_balancetotal($order);
        if ($dayres === false) {
            $this->error = '更新订单总统计失败';
            $this->rollback();
            return false;
        }

        $times = strtotime(date('Y-m-d', time()));
        //更新每日订单数
        $res1 = M('order_everyday')->where(['merchant_id' => $order['merchant_id'], 'time' => $times])->setInc('amount');
        if ($res1 === false) {
            $this->error = '更新每日订单数失败';
            $this->rollback();
            return false;
        }

        //更新总订单总数
        $res2 = M('order_total')->where(['merchant_id' => $order['merchant_id']])->setInc('order_total');
        if ($res2 === false) {
            $this->error = '更新订单总数失败';
            $this->rollback();
            return false;
        }

        //更新订单会员积分和消费记录
        $consumedata = M('member_capital')->where(['member_id' => $order['member_id']])->find();

        //将消费总额度写入会员消费记录表中
        $consumeres = M('member_capital')->where(['member_id' => $order['member_id']])->setInc('consume_money', $order['pay_price']);
        if ($consumeres === false) {
            $this->error = '更新消费额度失败';
            $this->rollback();
            return false;
        }

        //根据用户的消费情况，获取KB,和提升会员等级
        $M_merber = M('member');

        //查找到用户表中对应的用户
        $mer_data = $M_merber->where(['id' => $order['member_id']])->find();

        //积分计算规则 消费的总额*0.1
        $coin = $order['pay_price'] * C('COIN_RULE');
        $total_coin = $coin + $mer_data['coin'];

        if ($coin > 0) {
            $this->member_kcoin_record($mer_data, $coin, $total_coin, $buy_type, $order['order_type']);
            // 获取用户当前的消费总额
            $total_free = $consumedata['consume_money'] + $order['pay_price'];
            //获取当前会员等级对应的权益
            $pril_data = $this->memberLevelData($mer_data['level'], $total_free, $total_coin);
            //更新用户表
            $res4 = $M_merber->where(['id' => $order['member_id']])->save($pril_data);
            if ($res4 === false) {
                $this->error = '更新会员权益失败';
                $this->rollback();
                return false;
            }
        }
    }

    /**
     * 增加K币记录
     * @param $mer_data
     * @param $coin
     * @param $total_coin
     * @param $buy_type
     * @param $order_type
     * @return bool
     */
    private function member_kcoin_record($mer_data, $coin, $total_coin, $buy_type, $order_type)
    {
        $string = '';
        if ($buy_type == 1) {
            if ($order_type == 1) {
                $string = '购买卡座';
            } else if ($order_type == 2) {
                $string = '购买卡座套餐';
            } else if ($order_type == 3) {
                $string = '购买优惠套餐';
            } else if ($order_type == 0) {
                $string = '购买单品酒水';
            }
        } else if ($buy_type == 2) {
            $string = '购买续酒';
        }

        $kb_data = [
            'member_id' => $mer_data['id'],
            'record_name' => $string,
            'number' => $coin,
            'type' => 1,
            'before_number' => $mer_data['coin'],
            'after_number' => $total_coin,
            'created_time' => time()
        ];
        $rs = M('member_kcoin_record')->add($kb_data);
        if ($rs === false) {
            $this->rollback();
            $this->error = 'KB记录失败';
            return false;
        }
    }


    /**
     * 订单统计表,日统计
     * @param $orderdata
     * @return bool|mixed
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
     * 订单统计月统计
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
     * 订单统计年统计
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
     * 订单统计总统计表(订单总数,订单总金额)
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
     * @param $level
     * @param $total_free
     * @param $total_coin
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

    /**
     * 推送消息通知和发送短信
     */
    public function pushMsgAndSms($ypsms, $smstpl)
    {
        $orderType = [0 => '单品酒水', 1 => '卡座', 2 => '卡座套餐', 3 => '优惠套餐'];
        $sex = [1 => '先生', 2 => '女士'];

        $socketPush = new SocketPush();
        //获取短信模板
        $param = C("ALIDAYU.TEMPLATECODE");
        if ($this->attach['buy_type'] == 1) {
            //正常购买订单
            //获取所有预订部的员工ID
            $yudingbu_permission = C('YUDINGBU_PERMISSION');
            $employee_ids = M('EmployeeJobPermission')->distinct(true)
                ->join("api_employee_andjob ON api_employee_andjob.job_id = api_employee_job_permission.job_id")
                ->where(['permission_id' => ['IN', $yudingbu_permission], 'api_employee_job_permission.merchant_id' => $this->order['merchant_id']])
                ->getField('employee_id', true);

            if ($employee_ids) {
                try {
                    //向预订部推送socket消息 ::socket::
                    $socketPush->pushOrderSocketMessage($employee_ids, 3, (string)$this->order['order_no']);
                } catch (\Exception $exception) {
                    //记录日志
                    Log::write($exception, Log::WARN);
                }

                //给预订部员工发送新支付订单提醒
                //顾客${name}购买了${product}，请及时处理订单。
                /*$tels = M('employee')->where(['id'=>['IN', $employee_ids]])->getField('tel', true);
                $tpl_value = [
                    '#name#' => $order['contacts_realname'].$sex[$order['contacts_sex']],
                    '#product#'=> $orderType[$order['order_type']] .'订单'
                ];
                //发送短信
                $ypsms->tplBatchSend($tels, $smstpl['daijiedan'], $tpl_value);*/
            }

            //给用户发送散套已接单短信
            $order = $this->order;
            if ($order['order_type'] == 0 || $order['order_type'] == 3) {
                $this->sendSmsToMember($ypsms, $smstpl, $orderType);
            }

        } elseif ($this->attach['buy_type'] == 2) {
            //续酒购买订单
            try {
                //向预订部推送socket消息 ::socket::
                $socketPush->pushOrderSocketMessage($this->order['employee_id'], 3, (string)$this->order['order_no']);
            } catch (\Exception $exception) {
                //记录日志
                Log::write($exception, Log::WARN);
            }

            //给指定员工发送新支付订单消息  【空瓶子】顾客#name#购买了#product#，请及时处理订单。
            $tpl_value = [
                '#name#' => $this->order['contacts_realname'] . $sex[$this->order['contacts_sex']],
                '#product#' => '续酒订单'
            ];
            //发送短信
            $ypsms->tplSingleSend($this->order['employee_tel'], $smstpl['daijiedan'], $tpl_value);
        }
    }

    /**
     * 给用户发送订单接单成功短信提醒
     */
    private function sendSmsToMember($ypsms, $smstpl, $orderType)
    {
        try {
            $order_info = $this->order;
            $merchant = M('merchant')->field('province,city,area,address')->where(['id' => $order_info['merchant_id']])->find();
            $tpl_value = [
                '#product#' => $orderType[$order_info],
                '#time#' => date('Y年m月d日 H时i分', $order_info['obegin_time']),
                '#address#' => $merchant['province'] . $merchant['city'] . $merchant['area'] . $merchant['address'],
            ];
            $rs = $ypsms->tplSingleSend($order_info['contacts_tel'], $smstpl['santaojiedan'], $tpl_value);
            if (!$rs) Log::write($ypsms->errMsg, Log::NOTICE);
        } catch (\Exception $exception) {
            Log::write($exception);
        }
    }

    /**
     * 设置订单接单后订单作废定时任务
     */
    private function setOverdueDelayedTask($order)
    {
        if ($order['order_type'] == 3 && $order['is_xu'] == 0 && $order['is_bar'] == 0) {

            $pheanstalk = Tools::pheanstalk();
            $config = C('BEANS_OPTIONS');
            $delayed_data = [
                'version' => 'v1.1',
                'order_id' => $order['id'],
                'order_no' => $order['order_no'],
                'buy_type' => 1,    //1普通下单 2普通续酒 3拼吧下单 4拼吧续酒
                'exc_type' => 2,    //执行类型 1订单取消 2订单作废 3订单逾期
            ];
            $tube_name = $config['TUBE_NAME'][0];

            $merchant = M('merchant')->field('begin_time, end_time, open_buy')->find($order['merchant_id']);

            if ($merchant['open_buy']) {
                $abolish_time = $order['oend_time'];
            } else {
                $abolish_time = $order['obegin_time'];
            }

            $arrives_time = $abolish_time + C('FINISH_DELAY_TIME');
            $haveTime = $arrives_time - time();
            $haveTime = $haveTime > 0 ? $haveTime : 0;

            try {
                $pheanstalk->putInTube($tube_name, json_encode($delayed_data), 0, $haveTime);
            } catch (\Exception $exception) {
                Log::write($exception);
            }
        }
    }
}