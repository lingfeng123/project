<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/11
 * Time: 20:25
 */

namespace Admin\Model;


use Think\Model;
use Think\Page;

class CouponModel extends Model
{
    //自动验证
    protected $_validate = [
        ['card_name', '1,10', '优惠券名字不能超过10个字', self::MUST_VALIDATE, 'length'],
        ['card_type', '1,2', '优惠方式选择不正确', self::MUST_VALIDATE, 'in'],
        ['deductible', '/^[0-9]+(\.[0-9]{0,2})?$/', '抵扣金额不合法', self::MUST_VALIDATE, 'regex'],
        ['high_amount', '/^[0-9]+(\.[0-9]{0,2})?$/', '最低消费金额不合法', self::MUST_VALIDATE, 'regex'],
        ['status', '0,1,2', '优惠券状态选择不合法', self::MUST_VALIDATE, 'in'],
        ['total', 'number', '优惠券数量只能是数字', self::MUST_VALIDATE],
        ['marks', '1,18', '优惠券描述1-18', self::MUST_VALIDATE, 'length'],
    ];


    /**
     * 获取卡券列表
     */
    public function getCardList($param)
    {
        $page = $param['p'] ? $param['p'] : 1;
        $pagesize = C('PAGE.PAGESIZE');

        $condition = 1;
        //获取数据列表展示
        $list = $this->field('api_coupon.*,api_merchant.title')
            ->join('left join api_merchant  ON  api_coupon.merchant_id = api_merchant.id ')
            ->where($condition)
            ->page($page, $pagesize)
            ->order('id desc')
            ->select();

        //获取分页总数条数
        $count = $this->join('left join api_merchant ON api_merchant.id = api_coupon.merchant_id')->where($condition)->count();

        //执行分页操作
        $pages = new Page($count, $pagesize);
        $pages->setConfig('header', '共%TOTAL_ROW%条');
        $pages->setConfig('first', '首页');
        $pages->setConfig('last', '末页');
        $pages->setConfig('prev', '上一页');
        $pages->setConfig('next', '下一页');
        $pages->setConfig('theme', C('PAGE.THEME'));

        $data['pageHtml'] = $pages->show();

        //拼装数据
        $data['list'] = $list;
        return $data;
    }

    /**
     * 添加卡券
     */
    public function insert()
    {
        $data = $this->data();

        $data['high_amount'] = $data['high_amount'] ? $data['high_amount'] : 0;

        $data['start_time'] = $data['start_time'] ? strtotime($data['start_time']) : 0;
        $data['end_time'] = $data['end_time'] ? strtotime($data['end_time']) : 0;

        $data['get_start_time'] = $data['get_start_time'] ? strtotime($data['get_start_time']) : 0;
        $data['get_end_time'] = $data['get_end_time'] ? strtotime($data['get_end_time']) : 0;

        $data['effective_time'] = $data['effective_time'] ? $data['effective_time'] : 0;
        $data['attach_time'] = $data['attach_time'] ? strtotime($data['attach_time']) : 0;

        $data['total'] = $data['total'] ? $data['total'] : 0;
        $data['created_time'] = time();

        if (!$res = $this->add($data)) {
            $this->error = '添加优惠券失败';
            return false;
        }
        return true;
    }

    /**
     * 修改卡券
     */
    public function modify($id)
    {
        $data = $this->data();
        $data['id'] = $id;
        $data['high_amount'] = $data['high_amount'] ? $data['high_amount'] : 0;

        $data['start_time'] = $data['start_time'] ? strtotime($data['start_time']) : 0;
        $data['end_time'] = $data['end_time'] ? strtotime($data['end_time']) : 0;

        $data['get_start_time'] = $data['get_start_time'] ? strtotime($data['get_start_time']) : 0;
        $data['get_end_time'] = $data['get_end_time'] ? strtotime($data['get_end_time']) : 0;

        $data['effective_time'] = $data['effective_time'] ? $data['effective_time'] : 0;
        $data['attach_time'] = $data['attach_time'] ? strtotime($data['attach_time']) : 0;

        $data['total'] = $data['total'] ? $data['total'] : 0;

        $rs = $this->save($data);
        if ($rs === false) {
            return false;
        }

        return true;
    }

    public function getOnelist($id)
    {
        $list = $this->find($id);
        return $list;
    }

    /**
     * delete  cardID all field from coupon table
     */
    public function deleteCard($id)
    {
        $this->startTrans();

        //删除对应的优惠券表中数据
        $res1 = $this->where('id = d%', $id)->delete();
        if ($res1 === false) {
            $this->error = '删除优惠券表数据失败';
            $this->rollback();
            return false;
        }

        //删除对应用户卡券
        $res2 = D('member_coupon')->where(['card_id' => $id])->delete();

        if ($res2 === false) {
            $this->error = '删除用户优惠券表数据失败';
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;

    }

    /**
     * active cardID status from coupon table
     */
    public function activeCard($id, $type)
    {
        $res1 = $this->where(['id' => $id])->save(['status' => $type]);
        if ($res1 === false) {
            $this->error = '修改卡券状态失败';
            return false;
        }
        return true;
    }

    private function createCardNumber()
    {
        @date_default_timezone_set("PRC");
        $str = 'QWERTYUIOPASDFGHJKLZXCVBNM1234567890_!@qwertyuiopasdfghjklzxcvbnm';
        $length = 8;
        $count = strlen($str) - 1;
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $str[mt_rand(0, $count)];
        }
        $time = microtime();
        $time_arr = explode(' ', $time);
        $timestr = substr($time_arr[0], 2);
        $string .= (string)$timestr;
        return MD5($string);
    }

}