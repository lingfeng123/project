<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/17
 * Time: 9:44
 */

namespace Admin\Controller;


use Think\Page;

class WxpayLogController extends BaseController
{

    //微信支付日志记录  api_paylog_wxpay
    private $_model;

    public function _initialize()
    {
        parent::_initialize();
        $this->_model = D('paylogWxpay');
    }

    /**
     * 获取微信支付的日志记录列表
     * @param int $p int 页码
     * @param string $keywords string  关键字
     */
    public function index($p = 1, $keywords = '')
    {
        $page = $p;
        $page_size = C('PAGE.PAGESIZE');
        if ($keywords) {
            $condition = ['a.transaction_id | a.order_no | b.nickname' => ['like', "%" . $keywords . "%"]];
        }

        $paylog = $this->_model->alias('a')
            ->field('a.*,b.nickname')
            ->join('api_member b ON b.id=a.member_id')
            ->where($condition)->page($page, $page_size)->select();

        $count = $this->_model->alias('a')
            ->field('a.*,b.nickname,c.title')
            ->join('api_member b ON b.id=a.member_id')
            ->join('api_merchant c ON c.id=a.merchant_id')
            ->where($condition)->page($page, $page_size)->count();

        $pages = new Page($count, $page_size);
        $pages->setConfig('header', '共%TOTAL_ROW%条');
        $pages->setConfig('first', '首页');
        $pages->setConfig('last', '末页');
        $pages->setConfig('prev', '上一页');
        $pages->setConfig('next', '下一页');
        $pages->setConfig('theme', C('PAGE.THEME'));

        $pageHtml = $pages->show();

        $this->assign('paylog', $paylog);
        $this->assign('pageHtml', $pageHtml);
        $this->display();

    }


}