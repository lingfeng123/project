<?php
/**
 * FileName: SpreadExpressiveModel.class.php
 * User: Comos
 * Date: 2017/9/19 12:03
 */

namespace V1_1\Model;


use Think\Model;

class SpreadExpressiveModel extends Model
{

    /**
     * 根据员工ID获取员工提现记录
     * @param $member_id
     * @param $page
     * @param $page_size
     * @return bool
     */
    public function getExpressiveList($member_id, $page, $page_size)
    {
        $where = ['account_type' => 1, 'employee_id' => $member_id];

        //统计提现记录表中的已成功状态记录总数
        $data['cash_money'] = $this->where(['account_type' => 1, 'employee_id' => $member_id, 'status' => 3])->sum('total_money');
        //获取已提现记录列表
        if ($data['cash_money'] === false) {
            return false;
        }

        //统计数据为null,转换为数字类型数据
        $data['cash_money'] = $data['cash_money'] <= 0 ? 0 : $data['cash_money'];

        //获取提现记录
        $data['total'] = $this->where($where)->count();
        $data['list'] = $this->field('id,total_money,status,create_time,update_time')->where($where)->page($page, $page_size)->order('id desc')->select();
        if ($data['list'] === false) {
            return false;
        }

        //数据处理
        foreach ($data['list'] as $key => $datum) {
            if ($datum['status'] == 1) {
                $data['list'][$key]['datetime'] = $datum['create_time'];
            } else {
                $data['list'][$key]['datetime'] = $datum['update_time'];
            }
            //删除冗余数据
            unset($data['list'][$key]['create_time']);
            unset($data['list'][$key]['update_time']);
        }

        return $data;
    }

    /**
     * 获取提现数据判断提现条件
     * @param $member_id
     * @return bool
     */
    public function getExpreessiveData($member_id)
    {
        $where = ['account_type' => 1, 'employee_id' => $member_id, 'status' => 1, 'is_consume' => 1];
        $spreadRecordModel = D('SpreadRecord');
        $data['records'] = $spreadRecordModel->where($where)->getField('id', true);
        if ($data['records'] === false) {
            $this->error = '获取数据失败';
            return false;
        }

        //获取提现金额
        $data['money'] = $spreadRecordModel->where($where)->sum('money');
        if ($data['money'] === false) {
            $this->error = '获取数据失败';
            return false;
        }

        //格式化提现金额
        $data['money'] = !$data['money'] ? 0 : $data['money'];

        //获取能够提现的最低次数
        $expressive_number = C('EXPRESSIVE_NUMBER');
        $data['records_total'] = count($data['records']);

        //判断是否符合提现规则
        if ($data['records_total'] < $expressive_number) {
            $this->error = '提现条件不满足';
            return false;
        }

        //获取用户提现账户
        $data['account'] = M('member')->where(['id' => $member_id])->getField('alipay_account');
        if ($data['account'] == false) {
            $this->error = '您尚未设置提现账户';
            return false;
        }

        return $data;
    }

    /**
     * 插入提现数据
     * @param $member_id
     * @return bool
     */
    public function insertExpreessiveData($member_id)
    {
        //判断是否符合提现规则
        $data = $this->getExpreessiveData($member_id);
        if (!$data) {
            return false;
        }

        //将要插入的提现记录数据
        $expreessiveData = [
            'account_type' => 1,
            'employee_id' => $member_id,
            'total_money' => $data['money'],
            'create_time' => NOW_TIME,
            'status' => 1,
            'total_record' => $data['records_total'],
            'account' => $data['account'],
        ];

        //插入提现记录
        $this->startTrans();
        $expreessive_id = $this->add($expreessiveData);
        if (!$expreessive_id) {
            $this->rollback();
            $this->error = '申请提现失败';
            return false;
        }

        //插入提现记录与推广记录关联数据
        //提现关联数据组装
        $associated_data = [];
        foreach ($data['records'] as $record) {
            $associated_data[] = [
                'expressive_id' => $expreessive_id,
                'record_id' => $record,
            ];
        }
        $rs = M('SpreadRecordExpressive')->addAll($associated_data);
        if (!$rs) {
            $this->rollback();
            $this->error = '申请提现失败';
            return false;
        }

        //修改已归入提现记录的推广记录
        $updateRecordRes = M('spread_record')->where(['account_type' => 1, 'employee_id' => $member_id, 'id' => ['in', $data['records']]])->save(['status' => 2]);
        if ($updateRecordRes === false) {
            $this->rollback();
            $this->error = '申请提现失败';
            return false;
        }

        //提交事务
        $this->commit();
        return true;
    }


}