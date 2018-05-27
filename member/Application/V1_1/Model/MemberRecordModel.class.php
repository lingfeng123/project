<?php
/**
 * FileName: MemberRecordModel.class.php
 * User: Comos
 * Date: 2017/9/1 16:51
 */

namespace V1_1\Model;


use Org\Util\Tools;
use Think\Model;

class MemberRecordModel extends Model
{
    private $buyTypeName = [1 => '购买', 2 => '续酒', 3 => '拼吧', 4 => '拼吧续酒'];
    private $orderTypeName = [0 => '单品酒水', 1 => '卡座预定', 2 => '卡座套餐', 3 => '优惠套餐'];
    public $extData = null;
    public $barOrderId = 0;

    /**
     * 获取会员充值记录列表
     * @param $member_id int 用户ID
     * @param $page int int 当前页码
     * @param $page_size int 每页显示数量
     * @return bool
     */
    public function getMemberRecordList($member_id, $page, $page_size)
    {
        $data['total'] = $this->where(['member_id' => $member_id])->count();
        $data['list'] = $this->field('id,type,change_money,from_unixtime(trade_time) as trade_time,title')
            ->where(['member_id' => $member_id])->page($page, $page_size)->order('id desc')->select();
        if ($data === false) {
            return false;
        }
        return $data;
    }

    /**
     * 写入支付数据
     */
    public function insertPayInfo($give_money,
                                  $recharge_money,
                                  $before_give_money,
                                  $before_recharge_money,
                                  $after_give_money,
                                  $after_recharge_money,
                                  $order_info,
                                  $judgment_set)
    {
        //开始事务
        $this->startTrans();

        /*
         * 修改用户余额数据
         */
        $rs = M('member_capital')->where(['member_id' => $order_info['member_id']])->save(['give_money' => $give_money, 'recharge_money' => $recharge_money]);
        if ($rs === false) {
            $this->error = '余额修改失败';
            $this->rollback();
            return false;
        }

        //获取消费名称
        $title = '';

        if($judgment_set['buy_type'] ==1 && $judgment_set['buy_type'] ==2){
            $title .= $this->buyTypeName[$judgment_set['buy_type']] . $this->orderTypeName[$order_info['order_type']];
        }else{
            $title .= $this->buyTypeName[$judgment_set['buy_type']];
        }

        //判断客户端类型
        //写入支付记录
        $record_data = [
            'member_id' => $order_info['member_id'],
            'type' => 1,
            'change_money' => $order_info['pay_price'],
            'trade_time' => time(),
            'source' => $judgment_set['client'],
            'terminal' => $judgment_set['client'],
            'title' => $title,
            'order_no' => $order_info['order_no'],  //订单编号
            'order_id' => $order_info['id'],    //订单ID
            'before_recharge_money' => $before_recharge_money,
            'after_recharge_money' => $after_recharge_money,
            'before_give_money' => $before_give_money,
            'after_give_money' => $after_give_money
        ];

        $res = $this->add($record_data);
        if (!$res) {
            $this->error = '支付记录失败';
            $this->rollback();
            return false;
        }

        //获取订单名称描述
        if ($order_info['order_type'] == 1) {
            $buy_goods = '预定了卡座';
        } elseif ($order_info['order_type'] == 2) {
            $buy_goods = '购买了卡座套餐';
        } elseif ($order_info['order_type'] == 3) {
            $buy_goods = '购买了优惠套餐';
        } else {
            $buy_goods = '购买了单品酒水';
        }

        //buy_type  购买类型 1正常购买 2续酒
        switch ($judgment_set['buy_type']) {
            case 1: //正常购买
                // 散套
                if ($order_info['order_type'] == 3 || $order_info['order_type'] == 0) {

                    // 改变订单状态为已接单状态
                    $rs = D('order')->where(['id' => $order_info['id']])->save(['status' => 7, 'payment' => 1]);
                    if ($rs === false) {
                        $this->error = '订单状态修改失败';
                        $this->rollback();
                        return false;
                    }

                   /* if ($this->countOrderData($order_info) === false) {
                        $this->rollback();
                        return false;
                    }*/

                    $this->sanpackTube($order_info);


                } else if ($order_info['order_type'] == 2 || $order_info['order_type'] == 1) {
                    //改变订单状态为已支付
                    $rs = D('order')->where(['id' => $order_info['id']])->save(['status' => 2, 'payment' => 1]);
                    if ($rs === false) {
                        $this->error = '订单状态修改失败';
                        $this->rollback();
                        return false;
                    }

                    //写入预订部员工消息记录表数据  模板: '新支付订单|客人：{contacts_realname}，{buy_goods}。'
                    $tmps = C('SYS_MESSAGES_TMP');
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
                    $rs = D('message_employee')->add($msg_data);
                    if ($rs === false) {
                        $this->error = "消息记录失败";
                        $this->rollback();
                        return false;
                    }
                }
                break;

            case 2:  //正常购买的续酒

                //改变订单状态为已支付
                $rs = D('order')->where(['id' => $order_info['id']])->save(['status' => 7, 'payment' => 1, 'take_time' => strtotime(date('Y-m-d', time()))]);
                if ($rs === false) {
                    $this->error = '续酒订单状态修改失败';
                    $this->rollback();
                    return false;
                }

                if ($order_info['order_type'] == 2 || $order_info['order_type'] == 1) {

                    //写入员工消息记录表数据 模板: '新支付订单|客人：{contacts_realname}，{buy_goods}。'
                    $tmps = C('SYS_MESSAGES_TMP');
                    $messageContent = str_replace(['{contacts_realname}', '{buy_goods}'], [$order_info['contacts_realname'], $buy_goods], $tmps[3]);
                    $msg_data = [
                        'employee_id' => $order_info['employee_id'], //员工ID
                        'content' => $messageContent,
                        'order_no' => $order_info['order_no'],
                        'order_id' => $order_info['order_id'],
                        'created_time' => time(),
                        'type' => 1,    //消息类型 1已接单消息 2线下卡座消息 3新待接单消息
                        'merchant_id' => $order_info['merchant_id'],
                        'msg_type' => 1,
                    ];
                    $rs = D('message_employee')->add($msg_data);
                    if ($rs === false) {
                        $this->error = '续酒订单消息添加失败';
                        $this->rollback();
                        return false;
                    }
                }

               /* if($this->countOrderData($order_info) === false){
                    $this->rollback();
                    return false;
                };*/
                break;

            case 3: //正常拼吧
                //1 修改用户订单的状态为已支付
                $bar_member = M('bar_member')->where(['id' => $order_info['id']])
                    ->save(['pay_status' => 2, 'pay_type' => $judgment_set['pay_type'], 'updated_time' => time()]);
                if ($bar_member === false) {
                    $this->error = '修改用户订单状态失败';
                    $this->rollback();
                    return false;
                }

                //2 判断对应的拼吧订单是否已拼满
                $bar_info = M('bar_member')->field('api_bar.*')
                    ->join('left join api_bar ON api_bar.id = api_bar_member.bar_id')
                    ->where(['api_bar_member.id' => $order_info['id'], 'api_bar_member.pay_status' => 2])
                    ->find();

                $total_number = $bar_info['woman_number'] + $bar_info['man_number'];
                // 判断参与人数和预订人数是否相等
                //$count = M('bar_member')->where(['bar_id' => $bar_info['id'], 'pay_status' => 2])->count();
                $members = M('bar_member')->field('member_id,tel')->where(['bar_id' => $bar_info['id'], 'pay_status' => 2])->select();
                $count = count($members);

                $this->extData = [
                    'success' => ($total_number == $count),
                    'bar_id' => $bar_info['id'],
                    'bar_info' => $bar_info,
                    'member_id' => $bar_info['member_id'],
                    'members' => $members,
                ];

                if ($total_number == $count) {
                    //1 派对  派对拼满,订单状态直接修改为 7 已接单 将拼吧订单信息录入 order表 ,并且写入订单统计表中,将消费记录到用户消费记录表,
                    if ($bar_info['bar_type'] == 2) {

                        $bar_rs = M('bar')->where(['id' => $bar_info['id']])->save(['bar_status' => 7, 'updated_time' => time()]);
                        if ($bar_rs === false) {
                            $this->error = "更新拼吧表失败";
                            $this->rollback();
                            return false;
                        }

                      /*  //获取订单对应的所有用户信息
                        $members = M('bar_member')->field('member_id,pay_price')
                            ->where(['bar_id' => $bar_info['id'], 'pay_status' => 2])->select();
                        //循环执行更新订单会员积分和消费记录
                        foreach ($members as $member) {
                            $consumedata = M('member_capital')->where(['member_id' => $member['member_id']])->find();
                            //将消费总额度写入会员消费记录表中
                            $consumeres = M('member_capital')
                                ->where(['member_id' => $member['member_id']])->setInc('consume_money', $member['pay_price']);
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
                            $this->member_kcoin_record($mer_data, $coin, $total_coin, 3,0);
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
                        }*/

                    } else if ($bar_info['bar_type'] == 1) {

                        // 将拼吧信息录入order表中
                        $orderData = [
                            'order_no' => $bar_info['bar_no'],
                            'merchant_id' => $bar_info['merchant_id'],
                            'member_id' => $bar_info['member_id'],
                            'contacts_realname' => $bar_info['contacts_realname'],
                            'contacts_tel' => $bar_info['contacts_tel'],
                            'contacts_sex' => $bar_info['contacts_sex'],
                            'total_price' => $bar_info['total_price'],
                            'pay_price' => $bar_info['pay_price'],
                            'purchase_price' => $bar_info['purchase_price'],
                            'settlement_status' => 0,
                            'order_type' => $bar_info['order_type'],
                            'payment' => 0,
                            'description' => $bar_info['description'],
                            'arrives_time' => $bar_info['arrives_time'],
                            'created_time' => $bar_info['created_time'],
                            'updated_time' => time(),
                            'take_time' => strtotime(date('Y-m-d', time())),
                            'top_order_id' => 0,
                            'is_evaluate' => 0,
                            'is_bar' => 1,
                            'is_xu' => 0,
                            'obegin_time' => $bar_info['obegin_time'],
                            'oend_time' => $bar_info['oend_time'],
                        ];

                        // 拼单品酒水和散套
                        if ($bar_info['order_type'] == 3 || $bar_info['order_type'] == 0) {
                            //2 酒局  酒局拼满 拼吧订单状态修改为 2 待接单
                            $bar_rs = M('bar')->where(['id' => $bar_info['id']])->save(['bar_status' => 7, 'updated_time' => time()]);
                            if ($bar_rs === false) {
                                $this->error = "更新拼吧表失败";
                                $this->rollback();
                                return false;
                            }

                            $orderData['status'] = 7;

                            $order_rs = M('order')->add($orderData);
                            if ($order_rs === false) {
                                $this->error = "写入订单表失败";
                                $this->rollback();
                                return false;
                            }

                            $this->barOrderId = $order_rs;

                         /*   if($this->countBarData($orderData,$bar_info['merchant_id'],$bar_info['id']) === false){
                                $this->rollback();
                                return false;
                            };*/

                        } else if ($bar_info['order_type'] == 2) { // 拼卡套
                            //2 酒局  酒局拼满 拼吧订单状态修改为 2 待接单
                            $bar_rs = M('bar')->where(['id' => $bar_info['id']])->save(['bar_status' => 2, 'updated_time' => time()]);
                            if ($bar_rs === false) {
                                $this->error = "更新拼吧表失败";
                                $this->rollback();
                                return false;
                            }

                            $orderData['status'] = 2;
                            $order_rs = M('order')->add($orderData);
                            if ($order_rs === false) {
                                $this->error = "写入订单表失败";
                                $this->rollback();
                                return false;
                            }

                            //3 员工消息推送表记录(记录message表)给预订部推送消息
                            $tmps = C('SYS_MESSAGES_TMP');
                            $messageContent = str_replace(['{contacts_realname}', '{buy_goods}'], [$bar_info['contacts_realname'], $buy_goods], $tmps[4]);
                            $msg_data = [
                                'employee_id' => 0, //员工ID为0表示所有的预订部员工可以看见
                                'content' => $messageContent,
                                'order_no' => $bar_info['bar_no'],
                                'order_id' => $bar_info['id'],
                                'created_time' => time(),
                                'type' => 3,    //消息类型 1已接单消息 2线下卡座消息 3新待接单消息
                                'merchant_id' => $bar_info['merchant_id'],
                                'msg_type' => 1,
                            ];

                            $rs = D('message_employee')->add($msg_data);
                            if ($rs === false) {
                                $this->error = '续酒订单消息添加失败';
                                $this->rollback();
                                return false;
                            }
                        }

                        //拼吧与订单关联表的写入
                        $bar_order_rs = M('bar_order')->add(['bar_id' => $bar_info['id'], 'order_id' => $order_rs]);
                        if ($bar_order_rs === false) {
                            $this->error = '拼吧订单关联表写入失败';
                            $this->rollback();
                            return false;
                        }

                        //将bar_pack表中的数据导入order_pack表中
                        $bar_pack = M('bar_pack')->where(['bar_id' => $bar_info['id']])->find();
                        $order_pack = [
                            'order_id' => $order_rs,
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
                }

                break;
            case 4:
                //拼吧续酒
                /**
                 * step1  : 更新拼吧用户表中的数据pay_status
                 */
                $bar_member = M('bar_member')->where(['id' => $order_info['id']])->save(['pay_status' => 2, 'pay_type' => $judgment_set['pay_type'], 'updated_time' => time()]);
                if ($bar_member === false) {
                    $this->error = '修改用户订单状态失败';
                    $this->rollback();
                    return false;
                }

                /**
                 * step2  : 检查所有参与用户的是否支付完成
                 */
                //2 判断对应的拼吧订单是否已拼满
                $bar_info = M('bar_member')->field('api_bar.*')
                    ->join('left join api_bar ON api_bar.id=api_bar_member.bar_id')
                    ->where(['api_bar_member.id' => $order_info['id'], 'api_bar_member.pay_status' => 2])
                    ->find();

                $total_number = M('bar_member')->where(['bar_id' => $bar_info['id']])->count();

                // 判断参与人数和预订人数是否相等
                //$count = M('bar_member')->where(['bar_id' => $bar_info['id'], 'pay_status' => 2])->count();
                $members = M('bar_member')->field('member_id,tel')->where(['bar_id' => $bar_info['id'], 'pay_status' => 2])->select();
                $count = count($members);

                $this->extData = [
                    'success' => ($total_number == $count),
                    'bar_id' => $bar_info['id'],
                    'bar_info' => $bar_info,
                    'member_id' => $bar_info['member_id'],
                    'members' => $members,
                ];

                /**
                 * step2.1  : 支付完成之后,将拼吧写入订单表
                 */
                if ($total_number == $count) {
                    //获取相关联的腹肌订单
                    $top_order_id = M('bar_order')->where(['bar_id' => $bar_info['top_bar_id']])->getField('order_id');
                    $employee = M('order')
                        ->field('employee_id,employee_realname,employee_avatar,employee_tel')
                        ->where(['id'=>$top_order_id])->find();

                    $orderData = [
                        'order_no' => $bar_info['bar_no'],
                        'merchant_id' => $bar_info['merchant_id'],
                        'member_id' => $bar_info['member_id'],
                        'contacts_realname' => $bar_info['contacts_realname'],
                        'contacts_tel' => $bar_info['contacts_tel'],
                        'contacts_sex' => $bar_info['contacts_sex'],
                        'total_price' => $bar_info['total_price'],
                        'pay_price' => $bar_info['pay_price'],
                        'purchase_price' => $bar_info['purchase_price'],
                        'status' => 7,
                        'settlement_status' => 0,
                        'order_type' => $bar_info['order_type'],
                        'payment' => 0,
                        'description' => $bar_info['description'],
                        'arrives_time' => $bar_info['arrives_time'],
                        'created_time' => $bar_info['created_time'],
                        'updated_time' => time(),
                        'take_time' => strtotime(date('Y-m-d', time())),
                        'top_order_id' => $top_order_id,
                        'is_evaluate' => 0,
                        'is_bar' => 1,
                        'is_xu' => 1,
                        'employee_id' => $employee['employee_id'],
                        'employee_realname' => $employee['employee_realname'],
                        'employee_avatar' => $employee['employee_avatar'],
                        'employee_tel' => $employee['employee_tel'],
                    ];

                    $order_rs = M('order')->add($orderData);
                    if ($order_rs === false) {
                        $this->error = "写入订单表失败";
                        $this->rollback();
                        return false;
                    }

                    $bars_rs = M('bar')->where(['id' => $bar_info['id']])->save(['bar_status' => 7, 'updated_time' => time()]);
                    if ($bars_rs === false) {
                        $this->error = "更新主拼吧订单失败";
                        $this->rollback();
                        return false;
                    }

                    //拼吧与订单关联表的写入
                    $bar_order_rs = M('bar_order')->add(['bar_id' => $bar_info['id'], 'order_id' => $order_rs]);
                    if ($bar_order_rs === false) {
                        $this->error = '拼吧订单关联表写入失败';
                        $this->rollback();
                        return false;
                    }

                    //将bar_pack表中的数据导入order_pack表中
                    $bar_packs = M('bar_pack')->where(['bar_id' => $bar_info['id']])->select();
                    foreach ($bar_packs as $bar_pack){
                        $order_pack = [
                            'order_id' => $order_rs,
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

                    // 拼吧计算
                   /* if ($this->countBarData($orderData, $bar_info['merchant_id'], $bar_info['id']) === false) {
                        $this->rollback();
                        return false;
                    }*/
                }
                break;
        }

        $this->commit();
        return true;
    }


    private function member_kcoin_record($mer_data, $coin, $total_coin, $buy_type,$order_type)
    {
        if ($buy_type == 4) {
            $string = '购买拼吧续酒';
        } else if ($buy_type == 3) {
            $string = '购买拼吧订单';
        } else if ($buy_type == 2) {
            $string = '购买续酒订单';
        } else if($buy_type == 1){
            if($order_type == 3){
                $string = '购买散套订单';
            }else if($order_type == 0){
                $string = '购买单品酒水';
            }
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
     * 写入订单统计表
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
     * 普通订单订单统计和会员相关的处理
     * @param $order_info
     * @return bool
     */
    private function countOrderData($order_info)
    {
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

        $time = strtotime(date('Y-m-d', time()));
        //更新每日订单数
        $res1 = M('order_everyday')->where(['merchant_id' => $order_info['merchant_id'], 'time' => $time])->setInc('amount');
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
        if($coin > 0){
            $this->member_kcoin_record($mer_data, $coin, $total_coin, 2,$order_info['order_type']);
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

    }


    private function countBarData($orderData,$merchant_id,$bar_id)
    {
        //写入订单统计表中(api_merchant_balance_day,api_merchant_balance_month,api_merchant_balance_total,api_merchant_balance_year)
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
        $res1 = M('order_everyday')->where(['merchant_id' => $merchant_id, 'time' => $time])->setInc('amount');
        if ($res1 === false) {
            $this->error = '更新每日订单数失败';
            $this->rollback();
            return false;
        }

        //更新总订单总数
        $res2 = M('order_total')->where(['merchant_id' => $merchant_id])->setInc('order_total');
        if ($res2 === false) {
            $this->error = '更新订单总数失败';
            $this->rollback();
            return false;
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
                $this->member_kcoin_record($mer_data, $coin, $total_coin, 4,$orderData['order_type']);
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
    }


    private function sanpackTube($order_info)
    {
        $pheanstalk = Tools::pheanstalk();
        $config = C('BEANS_OPTIONS');
        $delayed_data = [
            'version' => 'v1.1',
            'order_id' => $order_info['id'],
            'order_no' => $order_info['order_no'],
            'buy_type' => 1,    //1普通下单 2普通续酒 3拼吧下单 4拼吧续酒
            'exc_type' => 2,    //执行类型 1订单取消 2订单作废 3订单逾期
        ];
        $tube_name = $config['TUBE_NAME'][0];

        $merchant = M('merchant')->field('open_buy')->find($order_info['merchant_id']);


        if ($merchant['open_buy']) {
            $abolish_time = $order_info['oend_time'];
        } else {
            $abolish_time = $order_info['obegin_time'];
        }

        $arrives_time = $abolish_time + C('FINISH_DELAY_TIME');
        $haveTime = $arrives_time - time();
        $haveTime = $haveTime > 0 ? $haveTime : 0;

        $pheanstalk->putInTube($tube_name, json_encode($delayed_data), 0, $haveTime);
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