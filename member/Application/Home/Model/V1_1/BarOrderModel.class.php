<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/9
 * Time: 18:48
 */

namespace Home\Model\V1_1;


use Think\Model;

class BarOrderModel extends Model
{
    /**
     * model 简介
     * 拼吧,拼吧续酒的model
     */


    public function barOrderHandle($wxpay_data,$attach,$bar_member_info)
    {
        $this->startTrans();
        //1 修改用户订单的状态为已支付
        $bar_member=M('bar_member')->where(['id'=>$attach['order_id']])->save(['pay_status'=>2,'pay_type'=>2,'updated_time'=>time()]);
        if($bar_member === false){
            $this->error = '修改用户订单状态失败';
            $this->rollback();
            return false;
        }

        //2 判断对应的拼吧订单是否已拼满
        $bar_info= M('bar_member')->field('api_bar.*')
            ->join('left api_bar ON api_bar.id=api_bar_member.bar_id')->where(['api_bar_member.id'=>$attach['order_id'],'api_bar_member.pay_status'=>2])->find();
        $total_number=$bar_info['woman_number']+$bar_info['man_number'];
        // 判断参与人数和预订人数是否相等
        $count=M('bar_member')->where(['bar_id'=>$bar_info['id']])->count();
        //如果已经拼满(当支付完成的人数和拼吧预订人数相同时表示已拼满)
        if($total_number == $count){
            $orderData=[
                'order_no'=>$bar_info['bar_no'],
                'merchant_id' =>$bar_info['merchant_id'],
                'member_id' =>$bar_info['member_id'],
                'contacts_realname' =>$bar_info['contacts_realname'],
                'contacts_tel' =>$bar_info['contacts_tel'],
                'contacts_sex'=>$bar_info['contacts_sex'],
                'total_price'=>$bar_info['total_price'],
                'pay_price'=>$bar_info['pay_price'],
                'purchase_price'=>$bar_info['purchase_price'],
                'settlement_status'=>0,
                'order_type'=>$bar_info['order_type'],
                'payment'=>0,
                'description'=>$bar_info['description'],
                'arrives_time'=>$bar_info['arrives_time'],
                'created_time'=>$bar_info['created_time'],
                'updated_time'=>time(),
                'take_time' => strtotime(date('Y-m-d', time())),
                'top_order_id'=>0,
                'is_evaluate'=>0,
                'is_bar'=>1,
                'is_xu'=>0,
            ];
            //1 派对  派对拼满,订单状态直接修改为 7 已接单 将拼吧订单信息录入 order表 ,并且写入订单统计表中,将消费记录到用户消费记录表,
            if($bar_info['bar_type'] == 2 ){

                $orderData['status']=7;

                $order_rs=M('order')->add($orderData);
                if($order_rs ===false){
                    $this->error="写入订单表失败";
                    $this->rollback();
                    return false;
                }

                if($this->_balanceday($orderData) === false){
                    $this->error="日统计失败";
                    $this->rollback();
                    return false;
                }

                if($this->_balancemonth($orderData) === false){
                    $this->error="月统计失败";
                    $this->rollback();
                    return false;
                }

                if($this->_balanceyear($orderData) === false){
                    $this->error="月统计失败";
                    $this->rollback();
                    return false;
                }

                if($this->_balancetotal($orderData) === false){
                    $this->error="月统计失败";
                    $this->rollback();
                    return false;
                }

                //order_everyday,order_total表
                $time = strtotime(date('Y-m-d', time()));
                //更新每日订单数
                $res1 = M('order_everyday')->where(['merchant_id' => $bar_info['merchant_id'], 'time' => $time])->setInc('amount');
                if ($res1 === false) {
                    $this->error = '更新每日订单数失败';
                    $this->rollback();
                    return false;
                }

                //更新总订单总数
                $res2 = M('order_total')->where(['merchant_id' => $bar_info['merchant_id']])->setInc('order_total');
                if ($res2 === false) {
                    $this->error = '更新订单总数失败';
                    $this->rollback();
                    return false;
                }

                //更新订单会员积分和消费记录
                $consumedata = M('member_capital')->where(['member_id' => $judgment_set['member_id']])->find();
                //将消费总额度写入会员消费记录表中
                $consumeres = M('member_capital')->where(['member_id' => $judgment_set['member_id']])->setInc('consume_money', $bar_info['average_cost']);
                if ($consumeres === false) {
                    $this->error = '更新消费额度失败';
                    $this->rollback();
                    return false;
                }

                //根据用户的消费情况，获取KB,和提升会员等级
                $M_merber = M('member');
                //查找到用户表中对应的用户
                $mer_data = $M_merber->where(['id' => $judgment_set['member_id']])->find();
                // 积分计算规则 消费的总额*0.1
                $coin = $bar_info['average_cost'] * C('COIN_RULE');
                $total_coin = $coin + $mer_data['coin'];
                // 获取用户当前的消费总额
                $total_free = $consumedata['consume_money'] + $bar_info['average_cost'];
                //获取当前会员等级对应的权益
                $pril_data = $this->memberLevelData($mer_data['level'], $total_free, $total_coin);
                //更新用户表
                $res4 = $M_merber->where(['id' => $judgment_set['member_id']])->save($pril_data);
                if ($res4 === false) {
                    $this->error = '更新会员权益失败';
                    $this->rollback();
                    return false;
                }

            }else if($bar_info['bar_type'] == 1){
                //2 酒局  酒局拼满 拼吧订单状态修改为 2 待接单
                $bar_rs=M('bar')->where(['id'=>$bar_info['id']])->save(['bar_status'=>2,'updated_time'=>time()]);
                if($bar_rs ===false){
                    $this->error="更新拼吧表失败";
                    $this->rollback();
                    return false;
                }

                // 将拼吧信息录入order表中
                $orderData['status'] =2;
                $order_rs=M('order')->add($orderData);
                if($order_rs ===false){
                    $this->error="写入订单表失败";
                    $this->rollback();
                    return false;
                }

                //将bar_pack表中的数据导入order_pack表中
                $bar_pack=M('bar_pack')->where(['bar_id'=>$bar_info['id']])->find();
                $order_pack=[
                    'order_id'=>$order_rs,
                    'goods_pack_id'=>$bar_pack['goods_pack_id'],
                    'title'=>$bar_pack['title'],
                    'amount'=>$bar_pack['amount'],
                    'price'=>$bar_pack['price'],
                    'image'=>$bar_pack['image'],
                    'merchant_id'=>$bar_pack['merchant_id'],
                    'member_id'=>$bar_pack['member_id'],
                    'pack_description'=>$bar_pack['pack_description'],
                    'purchase_price'=>$bar_pack['purchase_price'],
                    'market_price'=>$bar_pack['market_price'],
                    'goods_type'=>$bar_pack['goods_type'],
                ];

                $order_pack_rs=M('order_pack')->add($order_pack);
                if($order_pack_rs === false){
                    $this->error="写入订单商品表的数据失败";
                    $this->rollback();
                    return false;
                }

                if($bar_pack['goods_type']==1){
                    $buy_goods="拼购了优惠套餐";
                }else if($bar_pack['goods_type']==2){
                    $buy_goods="拼购了卡座套餐";
                }else if($bar_pack['goods_type']==3){
                    $buy_goods="拼购了单品酒水";
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
            $bar_order_rs=M('bar_order')->add(['bar_id'=>$bar_info['id'],'order_id'=>$order_rs]);
            if($bar_order_rs === false){
                $this->error = '拼吧订单关联表写入失败';
                $this->rollback();
                return false;
            }
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





}