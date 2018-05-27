<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Home\Model;

use Think\Model;

class PaylogWxpayModel extends Model {

    /**
     * 添加微信支付日志
     * @param type $list
     * @return boolean
     */
    public function addWxPayLog($list,$merchant_id,$member_id,$order_type) {
        if(!$list)
        {
            return FALSE;
        }
        $attach = $list['attach'];
        $attach_str = json_decode($attach, "TRUE");     
        if($attach_str['pay_type'] == 1)
        {
            $pay_type = "支付订单";           
        }
        else 
        {
            $pay_type = " 线上充值";
        }
        $data['merchant_id'] = $merchant_id;
        $data['member_id'] = $member_id;
        $data['appid'] = $list['appid'];
        $data['mch_id'] = $list['mch_id'];
        $data['openid'] = $list['openid'];
        $data['trade_type'] = $list['trade_type'];
        $data['result_code'] = $list['result_code'];
        $data['bank_type'] = $list['bank_type'];
        $data['total_fee'] = $list['total_fee'];
        $data['transaction_id'] = $list['transaction_id'];
        $data['out_trade_no'] = $list['out_trade_no'];
        $data['time_end'] = $list['time_end'];
        $data['create_time'] = time();
        $data['pay_type'] = $pay_type;
        $data['order_no'] = $attach_str['order_no'];
        
        $result = $this->add($data);

        if ($result) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

}
