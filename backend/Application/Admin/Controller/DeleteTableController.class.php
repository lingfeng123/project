<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/19
 * Time: 18:07
 */

namespace Admin\Controller;


use Think\Controller;

class DeleteTableController extends Controller
{
    /**
     * 一键删除数据表
     * 需要删除的数据表:api_comment_member api_comment_employee api_comment_merchant api_employee_operation api_finance api_finance_recode api_member_order
     * api_member_record api_merchant_balance_day api_merchant_balance_month api_merchant_balance_year api_merchant_balance_total
     * api_message api_message_detection api_message_employee api_message_empsystem  api_order api_order_delay  api_order_everyday  api_order_pack
     * api_order_seat api_order_seat_offline api_paylog_wxpay api_refund  api_seat_lock  api_spread_expressive  api_spread_record
     * api_spread_record_expressive
     * 需要更新的数据表 :api_comment_empstar api_comment_mchstar api_member_capital api_merchant_customer api_order_total
     *
     */

    public function delTable()
    {
        $tableArrays = [
            'api_comment_member', 'api_employee_operation', 'api_finance', 'api_finance_recode', 'api_member_order', 'api_member_record', 'api_merchant_balance_day', 'api_merchant_balance_month', 'api_merchant_balance_year', 'api_merchant_balance_total', 'api_comment_employee', 'api_comment_merchant', 'api_message', 'api_message_detection', 'api_message_employee', 'api_message_empsystem', 'api_order', 'api_order_delay',
            'api_order_everyday', 'api_order_pack', 'api_order_seat', 'api_order_seat_offline', 'api_paylog_wxpay', 'api_refund', 'api_seat_lock', 'api_spread_expressive', 'api_spread_record', 'api_spread_record_expressive','api_goods_pack_stock'];
        //删除数据表
        foreach ($tableArrays as $table) {
            $sql = 'truncate table ' . $table;
            $res = M()->execute($sql);
        }

        $updatearrays = ['api_comment_empstar', 'api_comment_mchstar', 'api_member_capital', 'api_merchant_customer', 'api_order_total'];
        $updatesqls[] = 'update api_comment_empstar set `star`= 0,`amount`=0,`average`=0 WHERE 1';
        $updatesqls[] = 'UPDATE api_comment_mchstar SET `environment_star`=0,`atmosphere_star`=0,`service_star`=0,`amount`=0,`average`=0 WHERE 1';
        $updatesqls[] = 'UPDATE api_member_capital SET `consume_money`=0,`give_money`=0,`recharge_money`=0 WHERE 1';
        $updatesqls[] = 'UPDATE api_merchant_customer SET `come_number`=0,`grosses`=0 WHERE 1';
        $updatesqls[] = 'UPDATE api_order_total SET `order_total`=0 WHERE 1';
        $updatesqls[] = 'UPDATE api_member SET `coin`=0 ,`level`=1 ,`used_card`=0 WHERE 1';

        foreach ($updatesqls as $usql) {
            $res = M()->execute($usql);
        }
    }


}