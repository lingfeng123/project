<?php

/**
 * FileName: MerchantCustomerModel.class.php
 * User: Comos
 * Date: 2017/9/20 16:17
 */

namespace Home\Model;

use Think\Model;

class MerchantCustomerModel extends Model {

    /**
     * 查询当前手机是否存在用户
     * @param $customer_tel
     * @return mixed
     */
    public function findCustomer($customer_tel) {
        return $this->field('customer_name, customer_sex, customer_tel, grosses')->where(['customer_tel' => $customer_tel])->find();
    }

    /**
     * 根据电话号码修改联系人信息
     * $data = [
     * 'merchant_id' => 1,
     * 'customer_name' => '张三',
     * 'customer_sex' => 1,
     * 'customer_tel' => 13566243654,
     * 'grosses' => 300.00,
     * ];
     */
    public function addOrEditCustomerByTel($data) {
        $customer_tel = $data['customer_tel'];
        $merchant_id = $data['merchant_id'];

        //查询当前是否存在联系人
        $customer = $this->findCustomer($customer_tel);
        if (!$customer) {
            //执行新增操作
            $data['created_time'] = NOW_TIME;
            //写入数据
            if (!$this->add($data)) {
                $this->error = $this->getDbError();
                return false;
            }
        } else {
            //执行修改操作
            unset($data['customer_tel']);

            //判断姓名是否相同
            if ($customer['customer_name'] == $data['customer_name']) {
                unset($data['customer_name']);
            }
            //判断性别是否相同
            if ($customer['customer_sex'] == $data['customer_sex']) {
                unset($data['customer_sex']);
            }

            //消费总额
            $data['grosses'] = $customer['grosses'] + $data['grosses'];
            //修改数据
            if ($this->where(['customer_tel' => $customer_tel, 'merchant_id' => $merchant_id])->save($data) === false) {
                $this->error = $this->getDbError();
                return false;
            }
        }

        return true;
    }

    /**
     * 修改客户的到店次数
     */
    public function updateCustomerComeNumber($contacts_tel) {
        $where = ['customer_tel' => $contacts_tel];
        //取得原始值
        $come_number = $this->where($where)->getField('come_number');
        if ($come_number === false) {
            return false;
        }
        //修改值
        $rs = $this->where($where)->save(['come_number' => $come_number + 1, 'last_time' => NOW_TIME]);
        if ($rs === false) {
            return false;
        }

        //成功返回
        return true;
    }

    /**
     * 获取客户列表
     */
    public function getCustomerList($merchant_id, $type, $page, $pagesize, $orderby) {
        $where = ['merchant_id' => $merchant_id];   //查询条件
        $count = $this->where($where)->count(); //获取记录总数

        switch ($type) {
            case 1;
                //查询最近消费客户列表
                $count = $this->where($where)->count();
                $list = $this->field('id,customer_name,customer_sex,customer_tel,is_tab,last_time')->where($where)->page($page, $pagesize)
                                ->order('last_time desc, id desc')->select();
                break;

            case 2;
                $list = $this->field('id,customer_name,customer_sex,customer_tel,is_tab,come_number')->where($where)->page($page, $pagesize)
                                ->order('come_number desc, id desc')->select();
                break;

            case 3;
                $list = $this->field('id,customer_name,customer_sex,customer_tel,is_tab,grosses')->where($where)->page($page, $pagesize)
                                ->order('grosses ' . $orderby . ', id desc')->select();
                break;
        }

        //判断查询是否成功
        if ($list === false || $count === false) {
            return false;
        }

        //数据转换
        if (isset($list[0]['last_time'])) {
            foreach ($list as $key => $datum) {
                $list[$key]['last_time'] = date('Y-m-d H:i', $datum['last_time']);
            }
        }

        //返回数据结果
        return [
            'total' => $count,
            'list' => $list,
        ];
    }

    /**
     * 根据搜索条件获取数据
     */
    public function getCustomerSearchList($merchant_id, $keyword, $max_money, $min_money, $datetime, $is_tab, $page, $pagesize) {
        //组装搜索条件
        $where = ['merchant_id' => $merchant_id];
        if (!empty($keyword)) {
            $where['customer_name'] = ['LIKE', '%' . $keyword . '%'];
        }

        //判断金额搜索区间
        if (!empty($min_money) && !empty($max_money)) {
            $where['grosses'] = ['BETWEEN', [$min_money, $max_money]];
        }

        //判断是否存在时间
        if (!empty($datetime)) {
            $datetime = date('Y-m-d', strtotime('-' . $datetime . 'day'));
            $datetime = strtotime($datetime);
            $where['last_time'] = ['GT', $datetime];
        }

        //是否是重要客户
        if (!empty($is_tab)) {
            $where['is_tab'] = ['GT', $datetime];
        }

        //获取符合条件的数据
        $count = $this->where($where)->count();
        $list = $this->field('id, customer_name, customer_sex, customer_tel, is_tab, last_time')->where($where)->page($page, $pagesize)->order('last_time desc')->select();

        //查询数据条件
        if ($count === false || $list === false) {
            return false;
        }

        //返回数据
        return [
            'total' => (int) $count,
            'list' => $list,
        ];
    }

}
