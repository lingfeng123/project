<?php
/**
 * FileName: RefundRecordController.class.php
 * User: Comos
 * Date: 2018/4/9 16:13
 */

namespace Admin\Controller;


use Think\Page;

class RefundRecordController extends BaseController
{
    protected $pay_type = [1 => '订单消费', 2 => '充值'];
    protected $buy_type = [1 => '正常下单支付', 2 => '正常续酒下单支付', 3 => '拼吧支付', 4 => '拼吧续酒支付'];
    protected $trade_status = [1 => '退款中', 2 => '退款成功'];

    /**
     * 支付记录
     */
    public function index($p = 1, $keywords = '', $buy_type = '', $trade_status = '', $pay_type = '')
    {
        $RefundRecordModel = M('refund_record');

        $pagesize = C('PAGE.PAGESIZE');
        if (!empty($keywords)) {
            $where['order_no'] = $keywords;
        }
        if (!empty($buy_type)) {
            $where['buy_type'] = $buy_type;
        }
        if (!empty($trade_status)) {
            $where['trade_status'] = $trade_status;
        }
        if (!empty($pay_type)) {
            $where['pay_type'] = $pay_type;
        }

        $total = $RefundRecordModel->field('api_refund_record.*, api_member.nickname, api_merchant.title as merchant_title')
            ->join('api_member ON api_member.id = api_refund_record.member_id')
            ->join('api_merchant ON api_merchant.id = api_refund_record.merchant_id')
            ->where($where)
            ->count();

        $list = $RefundRecordModel->field('api_refund_record.*, api_member.nickname, api_merchant.title as merchant_title')
            ->join('api_member ON api_member.id = api_refund_record.member_id')
            ->join('api_merchant ON api_merchant.id = api_refund_record.merchant_id')
            ->where($where)
            ->order('id desc')
            ->page($p, $pagesize)
            ->select();

        if ($list === false || $total === false) {
            $this->error('查询数据失败');
        }

        $pages = new Page($total, $pagesize);
        $pages->setConfig('header', '共%TOTAL_ROW%条');
        $pages->setConfig('first', '首页');
        $pages->setConfig('last', '末页');
        $pages->setConfig('prev', '上一页');
        $pages->setConfig('next', '下一页');
        $pages->setConfig('theme', C('PAGE.THEME'));

        $pageHtml = $pages->show();

        $this->assign('list', $list);
        $this->assign('pageHtml', $pageHtml);
        $this->assign('pay_type', $this->pay_type);
        $this->assign('buy_type', $this->buy_type);
        $this->assign('trade_status', $this->trade_status);
        $this->display();
    }
}