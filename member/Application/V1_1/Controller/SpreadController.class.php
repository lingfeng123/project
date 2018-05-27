<?php
/**
 * FileName: SpreadExpressiveController.class.php
 * User: Comos
 * Date: 2017/9/18 16:44
 */

namespace V1_1\Controller;

use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\Tools;
use Org\Util\Wechat;

class SpreadController extends BaseController
{

    /**
     * 获取会员邀请推广收益
     */
    public function promotion()
    {
        $member_id = I('post.member_id', '');
        $client = I('post.client', '');

        if (!is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不正确');
        }

        $spreadRecordModel = D('spread_record');

        //获取用户未提现推广收益
        $total_money = $spreadRecordModel->where(['account_type' => 1, 'employee_id' => $member_id, 'status' => 1])->sum('money');
        if ($total_money === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取推广收益失败');
        }

        //用户未提现推广总收益
        $total_money = $total_money <= 0 ? '0.00' : $total_money;

        //获取已推广总人数
        $data['my_promotion'] = $spreadRecordModel->where(['account_type' => 1, 'employee_id' => $member_id])->count();
        if ($data['my_promotion'] === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取推广人数失败');
        }

        //获取提现账户
        $member_data = D('member')->field('invite_code,alipay_account')->find($member_id);
        if (!$member_data) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取推广码失败');
        }

        $member_api_url = C('MEMBER_API_URL');
        //入口标识
        $flag = 'member_' . $client;

        //注册地址
        $url = "{$member_api_url}/wechat/register?&rsv_idx=2&ie=utf-8&rsv_enter=0&rsv_sug3=6&rsv_sug1=3&referrer=h5&flag={$flag}&invite_code={$member_data['invite_code']}";

        //获取推广二维码
        $qrcode = U('Home/Source/userqrcode', ['url' => $url], false, true);

        //2017-10-20 11:34:48修改可提现金额为总收益金额
        $data['total_money'] = $total_money < 0 ? '0.00' : $total_money;    //总收益额度
        $data['promotion_quota'] = C('PROMOTION_QUOTA');     //获取单次推广的奖励金额
        $data['alipay_account'] = $member_data['alipay_account'];
        $data['spread_rule'] = $member_api_url . '/html/v1.1/spreadrule/index.html';
        $data['qrcode_url'] = $qrcode;
        $data['app_logo'] = $member_api_url . "/Public/images/logo.png";
        $data['share_url'] = $url;

        //获取推广内容
        $contents = C('SHARE_CONTENT');
        $count = count($contents);
        $contents = $contents[mt_rand(0, $count - 1)];
        $data['title'] = $contents['title'];
        $data['intro'] = $contents['intro'];

        //返回数据
        Response::success($data);
    }


    /**
     * 我的邀请(邀请成功注册的用户)
     */
    public function registerList()
    {
        $member_id = I('post.member_id', '');
        $page = I('post.page', 1);
        $page_size = I('post.page_size', C('PAGE.PAGESIZE'));

        if (!is_numeric($member_id) || !is_numeric($page) || !is_numeric($page_size)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不正确');
        }

        $spreadRecordModel = D('spread_record');
        $data = $spreadRecordModel->getSpreadRecordList($member_id, $page, $page_size);
        if ($data === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '请求数据失败');
        }

        //返回结果
        Response::success($data);
    }


    /**
     * 奖励明细
     */
    public function proceeds()
    {
        $member_id = I('post.member_id', '');
        $page = I('post.page', 1);
        $page_size = I('post.page_size', C('PAGE.PAGESIZE'));

        if (!is_numeric($member_id) || !is_numeric($page) || !is_numeric($page_size)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不正确');
        }

        $spreadRecordModel = D('spread_record');
        $proceeds = $spreadRecordModel->getProceedsList($member_id, $page, $page_size);
        if ($proceeds === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '请求数据失败');
        }

        //输出结果
        Response::success($proceeds);
    }


    /**
     * 申请提现
     */
    public function expressive()
    {
        $member_id = I('post.member_id', '');
        if (!is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不正确');
        }

        $spreadExpressiveModel = D('spread_expressive');
        $data = D('spread_expressive')->insertExpreessiveData($member_id);
        if ($data === false) {
            Response::error(ReturnCode::DB_READ_ERROR, $spreadExpressiveModel->getError());
        }

        //提现成功提示
        Response::setSuccessMsg('申请提现成功');
        Response::success();
    }


    /**
     * 提现明细
     */
    public function cashRecord()
    {
        $member_id = I('post.member_id', '');
        $page = I('post.page', 1);
        $page_size = I('post.page_size', C('PAGE.PAGESIZE'));

        if (!is_numeric($member_id) || !is_numeric($page) || !is_numeric($page_size)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不正确');
        }

        $spreadExpressiveModel = D('spread_expressive');
        $data = $spreadExpressiveModel->getExpressiveList($member_id, $page, $page_size);
        if ($data === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取数据失败');
        }

        Response::success($data);
    }


    /**
     * 获取提现账户
     */
    public function getAccount()
    {
        $member_id = I('post.member_id', '');
        if (!is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        $account = D('member')->where(['id' => $member_id])->getField('alipay_account');
        if ($account === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取提现账户失败');
        }

        //返回账户数据
        Response::success(['alipay_account' => $account]);
    }


    /**
     * 设置提现账户
     */
    public function setAccount()
    {
        $member_id = I('post.member_id', '');
        $alipay_account = I('post.alipay_account', '');
        $password = I('post.password', '');

        //数据校验
        if (empty($alipay_account) || !is_numeric($member_id) || strlen($password) != 32) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //修改提现账户
        $memberModel = D('member');

        //验证密码是否正确
        $member = $memberModel->field('password,salt')->find($member_id);
        if (!$member) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '用户不存在');
        }

        //验证密码
        $password = Tools::salt_mcrypt($password, $member['salt']);
        if ($member['password'] != $password) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '登录密码不正确');
        }

        //设置提现账户
        $res = $memberModel->where(['id' => $member_id])->save(['alipay_account' => $alipay_account]);
        if ($res === false) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '设置提现账户失败');
        }

        //返回结果
        Response::setSuccessMsg('设置提现账户成功');
        Response::success();
    }

}