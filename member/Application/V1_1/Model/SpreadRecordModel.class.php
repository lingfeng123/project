<?php
/**
 * FileName: SpreadRecordModel.class.php
 * User: Comos
 * Date: 2017/9/18 17:26
 */

namespace V1_1\Model;


use Org\Util\Tools;
use Think\Model;

class SpreadRecordModel extends Model
{

    /**
     * 根据员工ID获取推广记录列表
     * @param $member_id
     * @param $page
     * @param $page_size
     * @return bool
     */
    public function getSpreadRecordList($member_id, $page, $page_size)
    {
        $where = ['account_type' => 1, 'employee_id' => $member_id];

        //获取推广总记录
        $data['total'] = $this->where($where)->count();

        //获取推广列表数据
        $lists = $this->field('
            api_spread_record.id,
            api_member.nickname,
            api_member.sex,
            replace(api_member.tel,substring(api_member.tel,4,4),"****") as member_tel,
            api_spread_record.reg_time,
            api_spread_record.is_consume')
            ->join('api_member ON api_member.id = api_spread_record.member_id', 'LEFT')
            ->where($where)
            ->page($page, $page_size)
            ->order('id desc')
            ->select();
        foreach ($lists as$key =>  $list){
            if($list['sex'] == 0) $list[$key]['sex'] = '1';
        }
        $data['list'] = $lists;

        if ($data['list'] === false || $data['total'] === false) {
            return false;
        }

        return $data;
    }


    /**
     * 获取产生收益的记录列表
     * @param $member_id
     * @param $page
     * @param $page_size
     * @return bool
     */
    public function getProceedsList($member_id, $page, $page_size)
    {
        $where = ['account_type' => 1, 'employee_id' => $member_id, 'is_consume' => 1];

        //获取用户未结算推广总金额(未结算, 已消费)
        $data['available_money'] = $this->where(['account_type' => 1, 'employee_id' => $member_id, 'is_consume' => 1, 'status' => 1])->sum('money');
        if ($data['available_money'] === false) {
            return false;
        }

        $data['available_money'] = $data['available_money'] <= 0 ? 0 : $data['available_money'];
        $data['available_money'] = Tools::formatMoney($data['available_money']);

        //获取推广总记录
        $data['total'] = $this->join('api_member ON api_member.id = api_spread_record.member_id', 'LEFT')->where($where)->count();
        if ($data['total'] === false) {
            return false;
        }

        //获取推广列表数据
        $data['list'] = $this->field('
                api_spread_record.id,
                api_spread_record.money,
                api_member.nickname,
                api_spread_record.profit_time,
                api_spread_record.is_consume,
                api_member.tel as member_tel
                ')
            ->join('api_member ON api_member.id = api_spread_record.member_id', 'LEFT')
            ->where($where)->page($page, $page_size)->order('id desc')->select();

        //判断是否查询成功
        if ($data['list'] === false) {
            return false;
        }

        return $data;
    }

}