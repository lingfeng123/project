<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/12
 * Time: 11:15
 */

namespace Home\Model\V1_1;


use Org\Util\JPushNotify;
use Org\Util\SocketPush;
use Org\Util\YunpianSms;
use Think\Log;
use Think\Model;

class BarMemberModel extends Model
{

    private $orderType = [0 => '单品酒水', 1 => '卡座', 2 => '卡座套餐', 3 => '优惠套餐'];
    private $sex = [1 => '先生', 2 => '女士'];
    public $callbackData = null;

    /**
     * 拼吧用户支付回调,判断用户的拼吧类型,bar_type,和购买类型 buy_type = 4
     * @param $pay_data
     * @param $attach
     * @return bool
     */
    public function barOrderHandle($pay_data, $attach)
    {
        //首先查找对应的拼吧订单信息
        $bar_member_info = M('bar_member')
            ->field('api_bar.*,api_bar_member.pay_status,api_bar_member.member_id as current_member_id,api_bar_member.tel,api_bar_member.pay_price as order_price')
            ->join('join api_bar ON api_bar.id=api_bar_member.bar_id')
            ->where(['api_bar_member.id' => $attach['order_id']])
            ->find();

        Log::write(json_encode($bar_member_info));

        if (!$bar_member_info) {
            $this->error = '订单数据不存在';
            return false;
        }

        //执行支付回调
        $data = $this->updateBarStatus($pay_data, $attach, $bar_member_info);
        if (!$data) {
            return false;
        }

        return true;
    }


    public function updateBarStatus($pay_data, $attach, $bar_member_info)
    {
        $this->startTrans();

        /**
         *  set1  :  更新用户订单表中的
         */
        $member_status = M('bar_member')->where(['id' => $attach['order_id']])->save(['pay_status' => 2, 'pay_type' => $pay_data['payment'], 'updated_time' => time()]);
        if ($member_status === false) {
            $this->rollback();
            $this->error = '更新用户信息表用户订单状态失败';
            return false;
        }

        /**
         * set_p 2 将用户支付记录写入payment-record表中
         */
        $payment_record = [
            'member_id' => $attach['member_id'],
            'merchant_id' => $bar_member_info['merchant_id'],
            'order_id' => $attach['order_id'],
            'order_no' => $attach['order_no'],
            'appid' => $pay_data['appid'],
            'mch_id' => $pay_data['mch_id'],
            'trade_type' => $pay_data['trade_type'],
            'order_fee' => $bar_member_info['order_price'],
            'receipt_fee' => $pay_data['receipt_fee'],
            'trade_no' => $pay_data['trade_no'],
            'end_time' => $pay_data['end_time'],
            'pay_type' => $attach['pay_type'],
            'buy_type' => $attach['buy_type'],
            'payment' => $pay_data['payment'],
            'created_time' => time(),
        ];
        $member_record = M('payment_record')->add($payment_record);
        if ($member_record === false) {
            $this->rollback();
            $this->error = '支付日志记录失败';
            return false;
        }

        /**
         *    set 3 : 判断用户是否已经全部支付完毕
         */
        if ($attach['buy_type'] == 3) {

            $total_person_number = $bar_member_info['man_number'] + $bar_member_info['woman_number'];

        } else if ($attach['buy_type'] == 4) {

            $total_person_number = M('bar_member')->where(['bar_id' => $bar_member_info['id']])->count();

        }

        $curr_number = M('bar_member')->where(['bar_id' => $bar_member_info['id'], 'pay_status' => 2])->count();

        // 如果全部支付成功
        if ($total_person_number == $curr_number) {

            $orderData = [
                'order_no' => $bar_member_info['bar_no'],
                'merchant_id' => $bar_member_info['merchant_id'],
                'member_id' => $bar_member_info['member_id'],
                'contacts_realname' => $bar_member_info['contacts_realname'],
                'contacts_tel' => $bar_member_info['contacts_tel'],
                'contacts_sex' => $bar_member_info['contacts_sex'],
                'total_price' => $bar_member_info['total_price'],
                'pay_price' => $bar_member_info['pay_price'],
                'purchase_price' => $bar_member_info['purchase_price'],
                'settlement_status' => 0,
                'order_type' => $bar_member_info['order_type'],
                'payment' => 0,
                'description' => $bar_member_info['description'],
                'arrives_time' => $bar_member_info['arrives_time'],
                'created_time' => $bar_member_info['created_time'],
                'updated_time' => time(),
                'take_time' => strtotime(date('Y-m-d', time())),
                'top_order_id' => 0,
                'is_evaluate' => 0,
                'is_bar' => 1,
                'obegin_time' => $bar_member_info['obegin_time'],
                'oend_time' => $bar_member_info['oend_time'],
            ];

            // 3.1 判断是续酒,还是正常拼吧,续酒拼吧 (酒局/派对)
            if ($attach['buy_type'] == 3) {

                $orderData['is_xu'] = 0;

                // 3.1.1 如果是酒局
                if ($bar_member_info['bar_type'] == 1) {

                    if($bar_member_info['order_type'] == 3 || $bar_member_info['order_type'] == 0){
                        $orderData['status'] = 7;

                        // 3.1.2 首先修改bar表中的数据
                        $bar_rs = M('bar')->where(['id' => $bar_member_info['id']])->save(['bar_status' => 7, 'updated_time' => time()]);
                        if ($bar_rs === false) {
                            $this->rollback();
                            $this->error = '更新主订单信息表失败';
                            return false;
                        }

                        //写入订单统计相关操作数据,和KB计算的操作
                        /*if ($this->statistics($orderData, $bar_member_info['id'], 3, 1) === false) {
                            $this->rollback();
                            return false;
                        };*/


                    }else if($bar_member_info['order_type'] == 1 || $bar_member_info['order_type'] == 2){
                        $orderData['status'] = 2;

                        // 3.1.2 首先修改bar表中的数据
                        $bar_rs = M('bar')->where(['id' => $bar_member_info['id']])->save(['bar_status' => 2, 'updated_time' => time()]);
                        if ($bar_rs === false) {
                            $this->rollback();
                            $this->error = '更新主订单信息表失败';
                            return false;
                        }

                    }

                    //3.1.3 写入写入订单order表中
                    $order_id = M('order')->add($orderData);
                    if (!$order_id) {
                        $this->rollback();
                        $this->error = '新增订单信息失败';
                        return false;
                    }

                    //3.1.4 将订单表和拼吧表结合起来
                    if (M('bar_order')->add(['order_id' => $order_id, 'bar_id' => $bar_member_info['id']]) === false) {
                        $this->rollback();
                        $this->error = '订单与拼吧关联失败';
                        return false;
                    };

                    //3.1.5 将bar_pack表中的数据导入order_pack表中
                    $bar_pack = M('bar_pack')->where(['bar_id' => $bar_member_info['id']])->find();
                    $order_pack = [
                        'order_id' => $order_id,
                        'goods_pack_id' => $bar_pack['goods_pack_id'],
                        'title' => $bar_pack['title'],
                        'amount' => $bar_pack['amount'],
                        'price' => $bar_pack['price'],
                        'image' => $bar_pack['image'],
                        'merchant_id' => $bar_pack['merchant_id'],
                        'member_id' => $bar_pack['member_id'],
                        'pack_description' => $bar_pack['pack_description'],
                        'purchase_price' => $bar_pack['purchase_price'],
                        'market_price' => $bar_pack['market_price'],
                        'goods_type' => $bar_pack['goods_type'],
                    ];

                    $order_pack_rs = M('order_pack')->add($order_pack);
                    if ($order_pack_rs === false) {
                        $this->error = "写入订单商品表的数据失败";
                        $this->rollback();
                        return false;
                    }

                    if($bar_member_info['order_type'] == 1 || $bar_member_info['order_type'] == 2){
                        //给预订部(商户端)写入通知消息到员工消息记录表中
                        if ($bar_pack['goods_type'] == 1) {
                            $buy_goods = "拼购了优惠套餐";
                        } else if ($bar_pack['goods_type'] == 2) {
                            $buy_goods = "拼购了卡座套餐";
                        } else if ($bar_pack['goods_type'] == 3) {
                            $buy_goods = "拼购了单品酒水";
                        }

                        //3 员工消息推送表记录(记录message表)给预订部推送消息
                        $tmps = C('SYS_MESSAGES_TMP');
                        $messageContent = str_replace(['{contacts_realname}', '{buy_goods}'], [$bar_member_info['contacts_realname'], $buy_goods], $tmps[4]);
                        $msg_data = [
                            'employee_id' => 0, //员工ID为0表示所有的预订部员工可以看见
                            'content' => $messageContent,
                            'order_no' => $bar_member_info['bar_no'],
                            'order_id' => $order_id,
                            'created_time' => time(),
                            'type' => 3,    //消息类型 1已接单消息 2线下卡座消息 3新待接单消息
                            'merchant_id' => $bar_member_info['merchant_id'],
                            'msg_type' => 1,
                        ];
                        $rs = D('message_employee')->add($msg_data);
                        if ($rs === false) {
                            $this->error = "员工消息录入失败";
                            $this->rollback();
                            return false;
                        }
                    }


                    //如果是派对直接进行接单操作,订单统计等操作
                } else if ($bar_member_info['bar_type'] == 2) {
                    $orderData['status'] = 7;

                    //更新主拼吧表中的订单状态bar_status =7
                    $bar_rs = M('bar')->where(['id' => $bar_member_info['id']])->save(['bar_status' => 7, 'updated_time' => time()]);
                    if ($bar_rs === false) {
                        $this->rollback();
                        $this->error = '更新主订单信息表失败';
                        return false;
                    }

                    //写入订单统计相关操作数据,和KB计算的操作
                    /*if ($this->statistics($orderData, $bar_member_info['id'], 3, 2) === false) {
                        $this->rollback();
                        return false;
                    };*/
                }

            } else if ($attach['buy_type'] == 4) {

                //获取该订单的top_bar_id
                $top_bar_id = $bar_member_info['top_bar_id'];
                $top_order_id = M('bar_order')
                    ->where(['bar_id' => $top_bar_id])->getField('order_id');

                //获取父级订单的信息
                $top_order = M('order')->where(['id' => $top_order_id])->find();
                if (!$top_order) {
                    $this->error = '未找到关联的父级订单';
                    return false;
                }

                //续酒 拼满
                $orderData['status'] = 7;
                $orderData['is_xu'] = 1;
                $orderData['top_order_id'] = $top_order_id;
                $orderData['employee_id'] = $top_order['employee_id'];
                $orderData['employee_realname'] = $top_order['employee_realname'];
                $orderData['employee_avatar'] = $top_order['employee_avatar'];
                $orderData['employee_tel'] = $top_order['employee_tel'];

                //更新主拼吧表中的订单状态bar_status =7
                $bar_rs = M('bar')->where(['id' => $bar_member_info['id']])->save(['bar_status' => 7, 'updated_time' => time()]);
                if ($bar_rs === false) {
                    $this->rollback();
                    $this->error = '更新主订单信息表失败';
                    return false;
                }

                //新增到order表中
                $order_id = M('order')->add($orderData);
                if (!$order_id) {
                    $this->rollback();
                    $this->error = '新增订单信息失败';
                    return false;
                }

                //新增order_bar关联表中数据
                if (M('bar_order')->add(['order_id' => $order_id, 'bar_id' => $bar_member_info['id']]) === false) {
                    $this->rollback();
                    $this->error = '订单与拼吧关联失败';
                    return false;
                };

                $bar_packs = M('bar_pack')->where(['bar_id' => $bar_member_info['id']])->select();
                foreach ($bar_packs as $bar_pack) {
                    $order_pack = [
                        'order_id' => $order_id,
                        'goods_pack_id' => $bar_pack['goods_pack_id'],
                        'title' => $bar_pack['title'],
                        'amount' => $bar_pack['amount'],
                        'price' => $bar_pack['price'],
                        'image' => $bar_pack['image'],
                        'merchant_id' => $bar_pack['merchant_id'],
                        'member_id' => $bar_pack['member_id'],
                        'pack_description' => $bar_pack['pack_description'],
                        'purchase_price' => $bar_pack['purchase_price'],
                        'market_price' => $bar_pack['market_price'],
                        'goods_type' => $bar_pack['goods_type'],
                    ];
                    $order_pack_rs = M('order_pack')->add($order_pack);
                    if ($order_pack_rs === false) {
                        $this->error = "写入订单商品表的数据失败";
                        $this->rollback();
                        return false;
                    }
                }

                //写入订单统计相关操作数据,和KB计算的操作
                /*if ($this->statistics($orderData, $attach['member_id'], 4, 1) === false) {
                    $this->rollback();
                    return false;
                };*/

                if($bar_member_info['order_type'] === 1 || $bar_member_info['order_type'] === 2){
                    //3 员工消息推送表记录(记录message表)给预订部推送消息
                    $tmps = C('SYS_MESSAGES_TMP');
                    $messageContent = str_replace(['{contacts_realname}', '{buy_goods}'], [$bar_member_info['contacts_realname'], '拼够了续酒'], $tmps[4]);
                    $msg_data = [
                        'employee_id' => $top_order['employee_id'], //员工ID为0表示所有的预订部员工可以看见
                        'content' => $messageContent,
                        'order_no' => $bar_member_info['bar_no'],
                        'order_id' => $order_id,
                        'created_time' => time(),
                        'type' => 3,    //消息类型 1已接单消息 2线下卡座消息 3新待接单消息
                        'merchant_id' => $bar_member_info['merchant_id'],
                        'msg_type' => 1,
                    ];

                    $rs = D('message_employee')->add($msg_data);
                    if ($rs === false) {
                        $this->error = "员工消息录入失败";
                        $this->rollback();
                        return false;
                    }
                }
            }
        }

        $this->commit();

        if (isset($orderData)) {
            $orderData['bar_id'] = $bar_member_info['id'];
            $orderData['bar_type'] = $bar_member_info['bar_type'];
            $this->callbackData = $orderData;
        }

        return true;
    }


    /**
     * 订单统计相关操作
     * @param $orderData
     * @param $member_id
     * @param $order_price
     * @return bool
     */
    private function statistics($orderData, $bar_id, $buy_type, $bar_type)
    {
        if ($bar_type == 1) {
            //更新订单呢统计表中的数据
            if ($this->_balanceday($orderData) === false) {
                $this->error = "日统计失败";
                $this->rollback();
                return false;
            }

            if ($this->_balancemonth($orderData) === false) {
                $this->error = "月统计失败";
                $this->rollback();
                return false;
            }

            if ($this->_balanceyear($orderData) === false) {
                $this->error = "月统计失败";
                $this->rollback();
                return false;
            }

            if ($this->_balancetotal($orderData) === false) {
                $this->error = "月统计失败";
                $this->rollback();
                return false;
            }

            //order_everyday,order_total表
            $time = strtotime(date('Y-m-d', time()));
            //更新每日订单数
            $res1 = M('order_everyday')->where(['merchant_id' => $orderData['merchant_id'], 'time' => $time])->setInc('amount');
            if ($res1 === false) {
                $this->error = '更新每日订单数失败';
                $this->rollback();
                return false;
            }

            //更新总订单总数
            $res2 = M('order_total')->where(['merchant_id' => $orderData['merchant_id']])->setInc('order_total');
            if ($res2 === false) {
                $this->error = '更新订单总数失败';
                $this->rollback();
                return false;
            }
        }

        //获取订单对应的所有用户信息
        $members = M('bar_member')->field('member_id,pay_price')->where(['bar_id' => $bar_id, 'pay_status' => 2])->select();

        //循环执行更新订单会员积分和消费记录
        foreach ($members as $member) {
            $consumedata = M('member_capital')->where(['member_id' => $member['member_id']])->find();
            //将消费总额度写入会员消费记录表中
            $consumeres = M('member_capital')->where(['member_id' => $member['member_id']])->setInc('consume_money', $member['pay_price']);
            if ($consumeres === false) {
                $this->error = '更新消费额度失败';
                $this->rollback();
                return false;
            }

            //根据用户的消费情况，获取KB,和提升会员等级
            $M_merber = M('member');
            //查找到用户表中对应的用户
            $mer_data = $M_merber->where(['id' => $member['member_id']])->find();
            // 积分计算规则 消费的总额*0.1
            $coin = $member['pay_price'] * C('COIN_RULE');
            $total_coin = $coin + $mer_data['coin'];
            if($coin > 0){
                $this->member_kcoin_record($mer_data, $coin, $total_coin, $buy_type);
                // 获取用户当前的消费总额
                $total_free = $consumedata['consume_money'] + $member['pay_price'];
                //获取当前会员等级对应的权益
                $pril_data = $this->memberLevelData($mer_data['level'], $total_free, $total_coin);
                //更新用户表
                $res4 = $M_merber->where(['id' => $member['member_id']])->save($pril_data);
                if ($res4 === false) {
                    $this->error = '更新会员权益失败';
                    $this->rollback();
                    return false;
                }
            }
        }
        return true;
    }


    private function member_kcoin_record($mer_data, $coin, $total_coin, $buy_type)
    {
        if ($buy_type == 4) {
            $string = '购买了拼吧续酒';
        } else if ($buy_type == 3) {
            $string = '购买了拼吧续酒';
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
     * 日统计订单写入
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
     * @author jiangling
     * 月统计订单写入
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


    /**
     * 想预定部推送socket消息
     */
    public function employee_socket_message($orderdata, $buy_type)
    {
        Log::write(json_encode($orderdata));
        Log::write($buy_type);

        $socketPush = new SocketPush();
        $ypsms = new YunpianSms();
        $sex = [1 => '先生', 2 => '女士'];
        $tpls = C("YUNPIAN");

        $employee_ids = false;
        $product = '';
        $msg_title = '';
        if ($buy_type == 3) {

            $yudingbu_permission = C('YUDINGBU_PERMISSION');
            $employee_ids = M('EmployeeJobPermission')->distinct(true)
                ->join("api_employee_andjob ON api_employee_andjob.job_id = api_employee_job_permission.job_id")
                ->where(['permission_id' => ['IN', $yudingbu_permission], 'api_employee_job_permission.merchant_id' => $orderdata['merchant_id']])
                ->getField('employee_id', true);

            //顾客${name}购买了${product}，请及时处理订单。
            $tels = M('employee')->where(['id' => ['IN', $employee_ids]])->getField('tel', true);

            //发送短信
            $ypsms->tplBatchSend($tels, $tpls['daijiedan'], [
                '#name#' => $orderdata['contacts_realname'] . $sex[$orderdata['contacts_sex']],
                '#product#' => '拼吧订单'
            ]);

            $msg_title = '您发起的拼吧人数已满, 拼吧成功！';
            $product = '拼吧';

            $memberTels = M('bar_member')->where(['bar_id' => $orderdata['bar_id']])->getField('tel', true);
            if ($memberTels) {

                $orderType = [1 => '卡座', 2 => '卡座套餐', 3 => '优惠套餐',0=>'单品酒水'];

                if($orderdata['bar_type'] ==1){
                    if($orderdata['order_type'] == 3 || $orderdata['order_type'] ==0){
                        $tpl_value = [
                            '#product#' => '由'.$orderdata['contacts_realname'].'发起的'.$orderType[$orderdata['order_type']],
                            '#time#' => date('Y年m月d日H时i分', $orderdata['obegin_time']),
                            '#telphone#' => C('KPZKF_PHONE'),
                        ];
                        $tpl = $tpls['pinsanok'];
                    }else{
                        $tpl_value = [
                            '#name#' => $orderdata['contacts_realname'],
                        ];
                        $tpl = $tpls['pinman'];
                    }
                }else{
                    $tpl_value = [
                        '#product#' => '由'.$orderdata['contacts_realname'].'发起的派对',
                        '#time#' => date('Y年m月d日H时i分', $orderdata['obegin_time']),
                        '#telphone#' => C('KPZKF_PHONE'),
                    ];
                    $tpl = $tpls['pinsanok'];
                }


                $ypsms->tplBatchSend($memberTels,$tpl,$tpl_value);
            }

        } else if ($buy_type == 4) {

            $employee_ids = $orderdata['employee_id'];
            //给指定员工发送新支付订单消息  【空瓶子】顾客#name#购买了#product#，请及时处理订单。
            $tpl_value = [
                '#name#' => $orderdata['contacts_realname'] . $sex[$orderdata['contacts_sex']],
                '#product#' => '续酒订单'
            ];
            //发送短信
            $ypsms->tplSingleSend($orderdata['employee_tel'], $tpls['daijiedan'], $tpl_value);

            $msg_title = '您发起的拼吧续酒人数已满, 续酒成功！';
            $product = '拼吧续酒';
        }

        if ($employee_ids) {
            try {
                //向预订部推送socket消息 ::socket::
                $socketPush->pushOrderSocketMessage($employee_ids, 3, (string)$orderdata['order_no']);
            } catch (\Exception $exception) {
                //记录日志
                Log::write($exception, Log::WARN);
            }
        }

        //给用户发送短信和消息推送
        if ($orderdata['bar_id']) {

            JPushNotify::toAliasNotify(
                $orderdata['member_id'],
                [
                    'alert' => '点击查看详情',
                    'title' => $msg_title,
                    'extras' => [
                        'msg_type' => 'bar',  //system order bar
                        'title' => $msg_title,
                        'content' => '',
                        'icon' => '',
                        'order_id' => $orderdata['bar_id']
                    ]
                ]);
        }
    }


    private function sanBarTube($bar_info)
    {
        $pheanstalk = Tools::pheanstalk();
        $config = C('BEANS_OPTIONS');
        $delayed_data = [
            'version' => 'v1.1',
            'order_id' => $bar_info['id'],
            'order_no' => $bar_info['bar_no'],
            'buy_type' => 3,    //1普通下单 2普通续酒 3拼吧下单 4拼吧续酒
            'exc_type' => 2,    //执行类型 1订单取消 2订单作废 3订单逾期
        ];
        $tube_name = $config['TUBE_NAME'][0];

        $merchant = M('merchant')->field('open_buy')->find($bar_info['merchant_id']);

        if ($merchant['open_buy']) {
            $abolish_time = $bar_info['oend_time'];
        } else {
            $abolish_time = $bar_info['obegin_time'];
        }

        $arrives_time = $abolish_time + C('FINISH_DELAY_TIME');
        $haveTime = $arrives_time - time();
        $haveTime = $haveTime > 0 ? $haveTime : 0;

        $pheanstalk->putInTube($tube_name, json_encode($delayed_data), 0, $haveTime);
    }



















}