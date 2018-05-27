<?php
/**
 * FileName: LoginController.php
 * User: Comos
 * Date: 2018/1/18 9:45
 */

namespace V1_1\Controller;


use Org\Util\AuthSign;
use Org\Util\JPushNotify;
use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\Tools;
use Org\Util\YunpianSms;

class LoginController extends BaseController
{
    private $memberModel;
    private $_redis;

    public function _initialize()
    {
        parent::_initialize();

        //实例model
        $this->memberModel = D('member');

        //创建redis连接
        $this->_redis = new \Redis();
        if (!$res = $this->_redis->connect(C('REDIS_CONFIG.HOSTNAME'), C('REDIS_CONFIG.PORT'))) {
            Response::error(ReturnCode::INVALID_REQUEST, 'Cache server connection failed');
        }

        //redis连接密码
        $this->_redis->auth(C('REDIS_CONFIG.PASSWORD'));
    }

    /**
     * 生成用户登录TOKEN
     */
    private function _accessToken($uid)
    {
        //获取一个新的token
        $accessToken = AuthSign::getUserToken('mem_');
        $accessToken = strtoupper($accessToken);
        //设置token过期时间
        //uid => token
        $result = $this->_redis->setex('member_' . $uid, 604800, $accessToken);
        if ($result === false) {
            return false;
        }
        //返回token
        return $accessToken;
    }

    /**
     * App用户登录
     * @param tel int 电话号码
     * @param password string 密码
     *  e10adc3949ba59abbe56e057f20f883e  123456
     */
    public function login()
    {
        $tel = I('post.tel', '');
        $password = I('post.password', '');
        $login_type = I('post.login_type', 1);
        $sms_code = I('post.sms_code', '');
        $regid = I('post.registration_id', '');

        //验证电话号码
        if (!preg_match("/^1[345789]\d{9}$/", $tel)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '电话号码不合法');
        }

        //根据电话号码查询用户
        $data = $this->memberModel->getMemberDataByTel($tel);
        if (!$data) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '账号不存在');
        }

        $vipInfo = $this->memberModel->getMemberVipInfos($data['id']);
        if (!$vipInfo) {
            Response::error(ReturnCode::NOT_FOUND, '个人信息获取失败');
        }

        //获取距离下一等级的消费额度差额
        $level = $vipInfo['level'] + 1;

        //判断下一等级是否大于最大会员等级
        $next_level = $this->memberModel->getMemberNextLevelMoney($level);
        if ($next_level === false) {
            Response::error(ReturnCode::NOT_FOUND, '数据请求失败');
        }

        if (!$next_level) {
            $vipInfo['next_vip_title'] = '';
            $vipInfo['diff_money'] = 0;
        } else {
            //计算差额
            $vipInfo['next_vip_title'] = $next_level['next_vip_title'];
            $diff_money = $next_level['quota'] - $vipInfo['consume_money'];
            $vipInfo['diff_money'] = $diff_money;
        }

        if ($data['status'] == 0) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '账号已封禁, 请联系客服处理!');
        }

        //login_type =1 表示验证码登录
        if ($login_type == 1) {
            //获取手机对应的验证码(redis)
            $smsCode = $this->_redis->get($tel);

            if (empty($sms_code)) {
                Response::error(ReturnCode::INVALID, '短信验证码不能为空');
            }

            if ($smsCode != $sms_code) {
                Response::error(ReturnCode::INVALID, '验证码不正确');
            }
        } else {
            //密码加密算法
            $password = Tools::salt_mcrypt($password, $data['salt']);
            if ($data['password'] != $password) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '密码不正确');
            }
        }


        //删除多余数据
        unset($data['password']);
        unset($data['salt']);

        //获取会员的uid
        $data['uid'] = $vipInfo['uid'] = AuthSign::getUserUid($data['id'], C('ACCOUNT_TYPE.MEMBER'));
        $vipInfo['kpzkf_phone'] = C('KPZKF_PHONE');

        //获取token
        $vipInfo['token'] = $this->_accessToken($data['uid']);
        if (!$vipInfo['token']) {
            Response::error(ReturnCode::INVALID_REQUEST, '获取accessToken失败');
        }

        //推送ID参数不为空
        if (!empty($regid)) {
            //写入绑定极光推送ID
            $this->registeridBind($data['id'], $regid);
        }

        Response::setSuccessMsg('登录成功');
        Response::success($vipInfo);
    }

    /**
     * 极光推送用户绑定关系处理 v2.0
     * @param $member_id
     * @param $regid
     */
    private function registeridBind($member_id, $regid)
    {
        //修改用户与registerID的关联
        $rs = $this->_redis->hSet('kpz_app_member_ids', $member_id, $regid);
        if ($rs === false) {
            Response::error(ReturnCode::INVALID_REQUEST, 'notify cache failed');
        }

        JPushNotify::setAlias($regid, $member_id);
    }


    /**
     * 退出登录
     */
    public function logout()
    {
        $regid = I('post.registration_id', '');
        $member_id = I('post.member_id', '');
        if (empty($regid) || !is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        $memberName = 'kpz_app_member_ids';     //member_id => regid
        //$registerName = 'kpz_app_member_regid'; //regid => member_id

        //删除用户regid关联
        $this->_redis->hDel($memberName, $member_id);
        //$this->_redis->hDel($registerName, $regid);

        JPushNotify::delAlias($member_id);
        Response::setSuccessMsg('退出成功');
        Response::success();
    }


    /**
     * 新用户注册
     * @param  tel string 用户手机号
     * @param  code  string  验证码
     * @param  password string  密码
     */
    public function register()
    {
        $tel = I('post.tel', '');
        $code = I('post.code', '');
        $password = I('post.password', '');

        //首先验证电话号码是否合法
        if (!preg_match("/^1[345789]\d{9}$/", $tel)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '电话号码不合法');
        }


        $member_model = D('member');
        $rs = $member_model->register($tel, $password,$code);
        if ($rs === false) {
            Response::error(ReturnCode::DB_SAVE_ERROR, $member_model->getError());
        }
        Response::setSuccessMsg('注册成功');
        Response::success($rs);
    }

    /**
     * 忘记密码,重置密码
     * @param tel int 用户手机号
     * @param code string  手机验证码
     * @param newpassword string  新密码
     * @param renewpassword string  确认新密码
     */
    public function forgetPwd()
    {
        $tel = I('post.tel', '');
        $code = I('post.code', '');
        $newpassword = I('post.newpassword', '');
        $renewpassword = I('post.renewpassword', '');
        //判断手机号码是否合法
        if (!preg_match("/^1[345789]\d{9}$/", $tel)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '电话号码不合法');
        }
        //验证码新密码和确认密码是否一致
        if ($newpassword !== $renewpassword) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '两次密码不一致,请重新输入');
        }
        //验证码是否合法
        $yunpian = new YunpianSms();
        //验证验证码是否正确
        $YRS = $yunpian->valiCode($tel, $code);
        if ($YRS === false) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '验证码输入不正确');
        };

        $member_model = D('member');
        $rs = $member_model->setMemberPwd($tel, $newpassword);
        if ($rs === false) {
            Response::error(ReturnCode::DB_SAVE_ERROR, '重置密码失败');
        }

        Response::setSuccessMsg('密码重置成功');
        Response::success();

    }

}