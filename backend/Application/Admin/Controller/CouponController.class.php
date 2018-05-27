<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/11
 * Time: 20:15
 */

namespace Admin\Controller;

class CouponController extends BaseController
{
    private $type = [1 => '满减', 2 => '立减'];
    private $flag;
    private $status = [0 => '删除', 1 => '正常可领取', 2 => '不可领取'];   //卡券状态 0删除 1正常可领取 2不可领取
    private $is_sex = [0 => '无限制', 1 => '男', 2 => '女'];
    private $_model;

    public function _initialize()
    {
        $this->_model = D('Coupon');
        $this->flag = C('COUPON_FLAG');
        parent::_initialize();
    }

    private function getallmerchant()
    {
        $merchant = [['id' => 0, 'title' => '所有']];
        $merchant1 = M('merchant')->field('id,title')->order('id asc')->select();

        $merchant = array_merge($merchant, $merchant1);

        return $merchant;
    }

    /**
     * 优惠券展示列表
     */
    public function index()
    {
        $param = I('get.');
        //获取优惠券
        $couponModel = D('coupon');
        $data = $couponModel->getCardList($param);
        //数据展示
        $this->assign('list', $data['list']);
        $this->assign('type', $this->type);
        $this->assign('pageHtml', $data['pageHtml']);
        $this->assign('flag', $this->flag);
        $this->assign('status', $this->status);
        $this->assign('is_sex', $this->is_sex);
        $this->display();

    }

    /**
     * 添加优惠券
     */
    public function add()
    {
        if (IS_POST) {
            if ($this->_model->create() === false) {
                $this->error(get_error($this->_model), U('add'));
            }
            if ($this->_model->insert() === false) {
                $this->error(get_error($this->_model), U('add'));
            }

            $this->success('添加成功', U('index'));
        } else {

            //获取商户id和title
            $merchant = $this->getallmerchant();
            $this->assign('card_type', $this->type);
            $this->assign('merchant', $merchant);
            $this->assign('flag', $this->flag);
            $this->assign('merchant_type', C('COUPON_MERCHANT_TYPE'));
            $this->assign('goods_type', C('COUPON_GOODS_TYPE'));
            $this->display();
        }
    }

    /**
     * 修改优惠券
     */
    public function edit($id)
    {
        //获取数据
        $list = $this->_model->getOnelist($id);
        if (!$list) {
            $this->error('数据不存在');
        }
        if ($_POST) {
            if ($this->_model->create() === false) {
                $this->error(get_error($this->_model), U('add', ['id' => $id]));
            }
            if ($this->_model->modify($id) === false) {
                $this->error(get_error($this->_model), U('add', ['id' => $id]));
            }

            $this->success('修改成功', U('index'));
        } else {
            //获取商户id和title
            $merchant = $this->getallmerchant();
            $this->assign('list', $list);
            $this->assign('card_type', $this->type);
            $this->assign('merchant', $merchant);
            $this->assign('flag', $this->flag);
            $this->assign('merchant_type', C('COUPON_MERCHANT_TYPE'));
            $this->assign('goods_type', C('COUPON_GOODS_TYPE'));
            $this->display('add');
        }

    }

    /**
     * 卡券激活(禁用)
     */
    public function cardActivation($id, $status)
    {
        $couponModel = D('coupon');
        $res = $couponModel->activeCard($id, $status);
        if (!$res) {
            $this->error('封禁失败');
        }
        $this->success('操作成功', U('index'));

    }
}