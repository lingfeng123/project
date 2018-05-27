<?php
/**
 * FileName: MemberController.class.php
 * User: Comos
 * Date: 2017/8/22 19:15
 */

namespace Admin\Controller;

use Think\Controller;
use Think\Page;

class MemberController extends BaseController
{

    /**
     * 获取注册用户列表
     */
    public function index($p = 1)
    {
        $parma = I('get.', '');

        $memberModel = D('Member');
        $data = $memberModel->getMemberListByWhere($parma, $p);

        $this->assign('member', $data['list']);
        $this->assign('pageHtml', $data['pageHtml']);
        //渲染视图
        $this->display();
    }


    /**
     * 查看指定用户资料
     */
    public function show($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            $this->error('输入参数非法', U('index'));
        }
        $memberModel = D('Member');
        $memberInfo = $memberModel->getMemberInfoById($id);

        //分配数据
        $this->assign('detail', $memberInfo);
        //根据ID查询用户数据
        $this->display();
    }

    /**
     * 封禁,恢复会员
     */
    public function isclosure($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            $this->error('输入参数非法', U('index'));
        }

        $memberModel = D('Member');
        $memberInfo = $memberModel->closureMember($id);
        if ($memberInfo === false) {
            $this->error(get_error($memberModel), U('index'));
        }

        //成功提示
        $this->success('操作成功', U('index'));
    }

    /**
     * 用户联系人列表
     */
    public function constractList($id, $p = 1, $keyword = '')
    {

        if (empty($id) || !is_numeric($id)) {
            $this->error('输入参数非法', U('index'));
        }

        //判断该用户是否存在
        $member_data = M('member')->where(['id' => $id])->find();
        if (empty($member_data)) {
            $this->error('用户不存在', U('index'));
        }

        if ($keyword) {
            $where = ['realname|sex|tel' => ['like', "%" . $keyword . "%"]];
            $condition['_complex'] = $where;
        }
        $condition = ['member_id' => $id];

        $page_size = C('PAGE.PAGESIZE');
        $page = $p;

        //获取用户的联系人
        $_cmodel = M('member_contacts');
        $con_data = $_cmodel->where($condition)->page($page, $page_size)->select();

        $count = $_cmodel->where(['member_id' => $id])->count();

        $pages = new Page($count, $page_size);
        $pages->setConfig('header', '共%TOTAL_ROW%条');
        $pages->setConfig('first', '首页');
        $pages->setConfig('last', '末页');
        $pages->setConfig('prev', '上一页');
        $pages->setConfig('next', '下一页');
        $pages->setConfig('theme', C('PAGE.THEME'));

        $pageHtml = $pages->show();
        $this->assign('pageHtml', $pageHtml);
        $this->assign('link', $con_data);
        $this->display('constractList');
    }


    /**
     * 用户消费记录列表
     */
    public function consumeList($id, $p = 1)
    {
        $member_id = $id;
        $type = 1;
        $page = $p;
        $page_size = C('PAGE.PAGESIZE');

        if (empty($member_id) || !is_numeric($member_id)) {
            $this->error('输入参数非法', U('index'));
        }

        //获取用户的消费记录
        $consume_list = $this->_memberConsumeRecharge($type, $member_id, $page, $page_size);
        //获取用户的总消费额度
        $total_consume = M('member_capital')->field('consume_money')->where(['member_id' => $member_id])->find();

        $data = [
            'total_consume' => $total_consume['consume_money'],
            'list' => $consume_list['list']
        ];

        $this->assign('ConsumeList', $data);
        $this->assign('pageHtml', $consume_list['pageHtml']);

        $this->display('consumeList');

    }

    /**
     * 用户退款记录表
     */
    public function refundList($id, $p = 1)
    {
        $member_id = $id;
        $type = 3;
        $page = $p;
        $page_size = C('PAGE.PAGESIZE');

        if (empty($member_id) || !is_numeric($member_id)) {
            $this->error('输入参数非法', U('index'));
        }

        //获取用户的消费记录
        $refund_list = $this->_memberConsumeRecharge($type, $member_id, $page, $page_size);

        $this->assign('pageHtml', $refund_list['pageHtml']);
        $this->assign('refund', $refund_list['list']);

        $this->display('refundList');
    }

    /**
     * 用户充值列表
     */
    public function rechargeList($id, $p = 1)
    {
        $member_id = $id;
        $type = 2;
        $page = $p;
        $page_size = C('PAGE.PAGESIZE');

        if (empty($member_id) || !is_numeric($member_id) && empty($type)) {
            $this->error('输入参数非法', U('index'));
        }

        $data = $this->_memberConsumeRecharge($type, $member_id, $page, $page_size);

        $this->assign('recharge_list', $data['list']);
        $this->assign('pageHtml', $data['pageHtml']);

        $this->display('rechargeList');
    }

    /**
     * 交易记录
     */
    private function _memberConsumeRecharge($type, $member_id, $page, $page_size)
    {
        //判断该用户是否存在
        $member_data = M('member')->where(['id' => $member_id])->find();
        if (empty($member_data)) {
            $this->error('用户不存在', U('index'));
        }
        $condition = ['type' => $type, 'member_id' => $member_id];
        $model = M('member_record');
        //查询记录
        $data['list'] = $model->where($condition)->page($page, $page_size)->select();
        $count = $model->where($condition)->count();

        $pages = new Page($count, $page_size);
        $pages->setConfig('header', '共%TOTAL_ROW%条');
        $pages->setConfig('first', '首页');
        $pages->setConfig('last', '末页');
        $pages->setConfig('prev', '上一页');
        $pages->setConfig('next', '下一页');
        $pages->setConfig('theme', C('PAGE.THEME'));

        $data['pageHtml'] = $pages->show();
        if (empty($data)) {
            $this->error('用户交易记录不存在', U('index'));
        }
        return $data;
    }

    /**
     * 充值订单列表
     */
    public function orderList($id, $p = 1)
    {
        $member_id = $id;
        $page = $p;
        $page_size = C('PAGE.PAGESIZE');

        if (empty($member_id) && is_numeric($member_id)) {
            $this->error('参数传入错误', U('index'));
        }
        $condition = array('member_id' => $member_id);

        $rechageOrderData = M('member_order')->where($condition)->page($page, $page_size)->select();

        $count = M('member_order')->where($condition)->count();

        $pages = new Page($count, $page_size);
        $pages->setConfig('header', '共%TOTAL_ROW%条');
        $pages->setConfig('first', '首页');
        $pages->setConfig('last', '末页');
        $pages->setConfig('prev', '上一页');
        $pages->setConfig('next', '下一页');
        $pages->setConfig('theme', C('PAGE.THEME'));

        $pageHtml = $pages->show();

        $this->assign('orderdata', $rechageOrderData);

        $this->assign('pageHtml', $pageHtml);
        $this->display('rechargeOrderList');
    }
}