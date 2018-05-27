<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace V1_1\Model;

use Think\Model;

class GoodsSeatModel extends Model
{

    /**
     * 获取未预订的卡座列表卡座列表
     * @param $merchant_id
     * @param $time
     * @return bool|mixed
     */
    public function seatList($merchant_id, $date)
    {
        //获取全部卡座数据
        $all_seat_list = $this->field('id as seat_id, merchant_id, max_people, floor_price, set_price, status, seat_number, floor, axis_x, axis_y, rotate, compartment')
            ->where(['merchant_id' => $merchant_id, 'status' => 1])
            ->order('id asc')
            ->select();

        if ($all_seat_list === false) {
            return false;
        }

        //获取已预定卡座列表
        $time = strtotime($date);
        $lock_seat = M('seat_lock')->where(['merchant_id' => $merchant_id, 'arrives_time' => $time])->getField('goods_seat_id', true);
        if ($date === false) {
            return false;
        }

        if (!is_array($lock_seat)) {
            $lock_seat = [];
        }

        foreach ($all_seat_list as $k => $val) {
            if (in_array($val['seat_id'], $lock_seat)) {
                $val['is_lock'] = 1;
            } else {
                $val['is_lock'] = 0;
            }

            //数据转换
            $val['merchant_id'] = (int)$val['merchant_id'];
            $val['seat_id'] = (int)$val['seat_id'];
            $val['max_people'] = (int)$val['max_people'];
            $val['status'] = (int)$val['status'];
            $val['floor'] = (int)$val['floor'];
            $val['axis_x'] = (int)$val['axis_x'];
            $val['axis_y'] = (int)$val['axis_y'];
            $val['rotate'] = (int)$val['rotate'];
            $val['compartment'] = (int)$val['compartment'];

            $seat_list[] = $val;
        }
        //删除已处理的数据
        unset($all_seat_list);
        return $seat_list;
    }

    /**
     * 验证当前是否可购买卡座
     */
    public function validateSeatStock($merchant_id, $seat_id, $member_id, $date)
    {
        //查看当前卡座是否已下架
        $rs = $this->where(['id' => $seat_id, 'merchant_id' => $merchant_id, 'status' => 1])->getField('id');
        if (!$rs) {
            $this->error = '该卡座已下架';
            return false;
        }

        //格式化时间
        $date_int = (int)date('Ymd', strtotime($date));    //传入为字符串日期 2017-10-10
        $date_now = (int)date('Ymd', time());
        if ($date_int < $date_now) {
            $this->error = '不能选择今天之前的日期';
            return false;
        }

        //当日是否已到营业时间
        $today_begin_time = M('merchant')->where(['id' => $merchant_id])->getField('begin_time');
        $now_date_time = date('H:i:s');
        if ($date_int == $date_now &&  $now_date_time >= $today_begin_time){
            $this->error = '已到营业时间,不可预定';
            return false;
        }

        //获取商户的最大预定周期时间
        $preordain_cycle = D('merchant')->where(['id' => $merchant_id])->getField('preordain_cycle');
        $preordain_cycle_second = $preordain_cycle * 24 * 60 * 60;
        //支持的最大选择时间
        $max_second = strtotime(date('Y-m-d', time())) + $preordain_cycle_second;
        $send_second = strtotime($date);
        if ($max_second < $send_second) {
            $this->error = '日期超出预定周期';
            return false;
        }

        //查询用户全局订单是否存在符合条件的数据
        $user_order = D('order')->field('id,arrives_time')
            ->where(['member_id' => $member_id, 'order_type' => ['IN', [1, 2]], 'status' => ['IN', [1, 2, 7]],'is_bar'=>0])
            ->select();

        //根据全局订单判断是否存在符合限制预定的日期
        if ($user_order) {
            foreach ($user_order as $value) {
                //预定时间
                $user_arrives_time = date('Y-m-d', $value['arrives_time']);
                if ($user_arrives_time == $date) {
                    $this->error = '您当日不可再预定该酒吧卡座';
                    return false;
                }
            }
        }

        //检查当前酒吧是否预定了符合限定条件的订单
        $order_info = D('order')->field('id,arrives_time')
            ->where(['member_id' => $member_id, 'merchant_id' => $merchant_id, 'order_type' => ['IN', [1, 2]], 'status' => ['IN', [1, 2, 7]],'is_bar'=>0 ])
            ->order('arrives_time desc')
            ->find();

        if ($order_info === false) {
            $this->error = '数据请求失败';
            return false;
        }

        //获取到店日期
        $arrives_time = date('Y-m-d', $order_info['arrives_time']);
        if ($order_info) {

            //如果到店日期是选择日期,则不允许订购
            if ($arrives_time == $date) {
                $this->error = '您当日不可再预定该酒吧卡座';
                return false;
            }

            //判断是否当日已购买卡套
            if ($order_info['order_type'] == 2 && in_array($order_info['status'], [1, 2, 7]) && $arrives_time == $date) {
                $this->error = '您当日已购买该酒吧卡座套餐';
                return false;
            }
        }

        //查询当前卡座是否已被预定
        $time = strtotime($date);
        $goods_seat_id = M('seat_lock')->where(['goods_seat_id' => $seat_id, 'arrives_time' => $time])->getField('goods_seat_id', true);
        if ($goods_seat_id === false) {
            $this->error = '数据请求失败';
            return false;
        }

        if ($goods_seat_id) {
            $this->error = '此卡座已被预定';
            return false;
        } else {
            return true;
        }
    }
}
