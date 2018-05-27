<?php

/**
 * FileName: MemberModel.class.php
 * User: Comos
 * Date: 2017/8/18 10:58
 */

namespace V1_1\Model;

use Org\Util\Response;
use Org\Util\String;
use Org\Util\Tools;
use Org\Util\YunpianSms;
use Think\Model;

class MemberModel extends Model
{
    /**
     * 写入用户数据
     * @param $data
     * @return array|bool
     */
    public function addMember($data)
    {
        $nickname = Tools::filterEmoji($data->nickName);
        $nickname = trim($nickname);
        $nickname = str_replace(' ', '', $nickname);
        if (!$nickname) {
            $nickname = '空瓶子用户' . mt_rand(0, 999);
        }

        if ($data->gender == 0) {
            $data->gender = 1;
        }
        //组装主表数据
        $memberData = [
            'nickname' => $nickname,
            'sex' => $data->gender,
            'avatar' => $data->avatarUrl,
            'status' => 1,
            'unionid' => $data->unionId,
            'created_time' => NOW_TIME,
            'updated_time' => NOW_TIME,
            'invite_code' => '',
            'level' => 1,
            'xcx_openid' => $data->openId,
        ];

        //开启事务
        $this->startTrans();
        //主表中插入用户数据api_member_capital
        $member_id = $this->add($memberData);
        if ($member_id === false) {
            $this->rollback();
            return false;
        }

        //获取推广码
        $invite_code = Tools::create_invite_code(C('INVITE_CODE_PREFIX.MEMBER'), $member_id);

        //设置用户推广码
        $invite_code_update = $this->where(['id' => $member_id])->save(['invite_code' => $invite_code]);
        if ($invite_code_update === false) {
            $this->rollback();
            return false;
        }

        //用户资金表api_member_capital
        $member_capital_data = [
            'member_id' => $member_id,
            'give_money' => 0.00,
            'recharge_money' => 0.00,
            'updated_time' => time(),
            'consume_money' => 0.00
        ];

        $MemberCapital = M('member_capital')->add($member_capital_data);
        if ($MemberCapital === false) {
            $this->rollback();
            return false;
        }

        //用户拼吧评论星数
        $bar = [
            'member_id' => $member_id,
            'total_star' => 0,
            'total_time' => 0,
            'average_star' => 0.0,
        ];
        $comment_bar = M('comment_barstar')->add($bar);
        if ($comment_bar === false) {
            $this->rollback();
            return false;
        }

        //提交事务
        $this->commit();

        //通用 - 新人注册券
        D('coupon')->newUserGetCard($member_id);

        //店铺 - 新人注册券
        D('coupon')->dianpuNewUserCard($member_id);

        //返回用户数据
        return $this->getMemberByUnionId($data->unionId);
    }


    /**
     * 根据unionid获取会员数据
     * @param $unionid
     * @return bool|\Think\Model
     */
    public function getMemberByUnionId($unionid)
    {
        //查询用户数据
        $user_data = $this->field('id as member_id,tel,nickname,realname,sex,avatar,coin,growth,invite_code,level,unionid,xcx_openid,wx_openid,is_auth')->where("status = 1 AND unionid = '{$unionid}'")->find();
        if ($user_data == false) {
            return false;
        }

        if ($user_data['sex'] == 1) {
            $user_data['sex'] = '男';
        } else {
            $user_data['sex'] = '女';
        }

        return $user_data;
    }

    /**
     * 获取用户会员级别 / 获取累计消费金额 / 获取会员基础数据
     * @param $member_id int 用户ID
     * @return mixed
     */
    public function getMemberVipInfos($member_id)
    {
        $vipInfo = M()->query("
SELECT api_member.id as member_id, api_member.coin as member_coin,is_auth,age,sex,signature,image, avatar, nickname,is_edit_sex,api_member_privilege.*, consume_money, (give_money + recharge_money) as money ,tel
FROM `api_member` 
JOIN `api_member_privilege` ON api_member.level = api_member_privilege.level 
JOIN `api_member_capital` ON api_member.id = api_member_capital.member_id 
WHERE api_member.id = {$member_id}");

        $vipInfo = $vipInfo[0];
        unset($vipInfo['id']);

        //计算会员年龄
        $vipInfo['age'] = Tools::calculateAge($vipInfo['age']);

        $attachment_url = C('attachment_url');
        //图片地址组装
        if ($vipInfo['image']) {
            $vipInfo['image'] = Tools::albumsFormat($vipInfo['image'], '|', $attachment_url);

        } else {
            $vipInfo['image'] = [];
        }

        //检测是否为微信头像
        if (!preg_match('/^(http|https)/ius', $vipInfo['avatar'])) {
            $vipInfo['avatar'] = C('attachment_url') . $vipInfo['avatar'];
        }

        //是否设置了支付密码
        $password = M('member_capital')->where(['member_id' => $member_id])->getField('password');
        if ($password) {
            $vipInfo['is_payment_pwd'] = 1;
        } else {
            $vipInfo['is_payment_pwd'] = 0;
        }

        //剩余可用优惠券数量
        $vipInfo['overdue_card'] = M('coupon_member')->where(['member_id' => $member_id, 'card_status' => 0])->count();

        //获取邀请好友获取的奖励金额
        $where = ['account_type'=>1,'employee_id'=>$member_id,'status'=>1,'is_consume'=>1];
        $reward_money = M('spread_record')->where($where)->sum('money');
        if($reward_money === null){
            $reward_money = '0.00';
        }
        $vipInfo['reward_money'] = $reward_money;

        return $vipInfo;
    }


    /**
     * 获取用户下一会员等级需要消费的总额度
     * @param $level
     * @return mixed
     */
    public function getMemberNextLevelMoney($level)
    {
        $quota = M('MemberPrivilege')->field('quota,title as next_vip_title')->where(['level' => $level])->find();
        return $quota;
    }


    /**
     * 设置用户的支付密码
     * @param $member_id
     * @param $password
     * @return bool
     */
    public function setMemberPayPasswordById($member_id, $password)
    {
        /*if ($password != $repassword) {
            $this->error = '两次密码不一致';
            return false;
        }*/
        //获取密码加密字符串
        $salt = String::randString(6);
        //获取加密后的密码
        $password = Tools::salt_mcrypt($password, $salt);
        //修改数据库
        $result = M('MemberCapital')->where(['member_id' => $member_id])->save(['password' => $password, 'salt' => $salt]);
        if (!$result) {
            $this->error = '设置支付密码失败';
            return false;
        }
        return true;
    }


    /**
     * 获取用户密码设置信息
     * @param $member_id
     * @param $password
     * @return bool|mixed
     */
    public function getMemberCapitalInfoByMemberId($member_id, $password)
    {
        $data = M('MemberCapital')->field('password, salt')->find($member_id);
        if ($data === false) {
            return false;
        }

        //加密密码并比对
        $password = Tools::salt_mcrypt($password, $data['salt']);
        if ($password !== $data['password']) {
            return false;
        }
        //比对成功
        return $data;
    }


    /**
     * 根据电话号码查询用户数据
     * @param $tel int 电话号码
     * @return bool|mixed
     */
    public function getMemberDataByTel($tel)
    {
        //根据电话号码查询用户数据
        $fileds = "api_member.id,
        api_member.tel,
        api_member.password,
        api_member.salt,
        api_member.nickname,
        api_member.sex,
        api_member.avatar,
        api_member.coin,
        api_member.status,
        api_member.invite_code,
        api_member.level,
        api_member_privilege.title,
        api_member.is_auth,
        api_member_capital.consume_money,
        (api_member_capital.give_money + api_member_capital.recharge_money) as money";
        $data = $this->field($fileds)
            ->join('api_member_capital ON api_member_capital.member_id = api_member.id', 'left')
            ->join('api_member_privilege ON api_member_privilege.level = api_member.level', 'left')
            ->where(['tel' => $tel])->find();
        if (!$data) {
            return false;
        }

        return $data;
    }


    /**
     * 用户注册接口
     * @param $tel int  手机号
     * @param $password  string  密码
     * @return array|bool
     */
    public function register($tel, $password,$code)
    {
        //验证手机号是否存在数据库中
        $member = $this->field('id,tel')->where(['tel' => $tel])->find();
        if ($member) {
            $this->error = '该手机号已经存在,请直接登录';
            return false;
        }

        $yunpian = new YunpianSms();
        //验证验证码是否正确
        if ($yunpian->valiCode($tel, $code) === false) {
            $this->error ='验证码输入不正确';
        };

        //不存在执行新增数据(生成6位数的随机盐)
        $salt = String::randString(6);
        $pwd = Tools::salt_mcrypt($password, $salt);

        //开启事务
        $this->startTrans();
        //执行新增
        $data = [
            'tel' => $tel,
            'password' => $pwd,
            'salt' => $salt,
            'status' => 1,
            'level' => 1,
            'created_time' => time(),
            'updated_time' => time(),
        ];
        $member_id = $this->add($data);
        if (!$member_id) {
            $this->error = '注册失败1';
            $this->rollback();
            return false;
        }

        //获取推广码
        $invite_code = Tools::create_invite_code(C('INVITE_CODE_PREFIX.MEMBER'), $member_id);

        //设置用户推广码
        $invite_code_update = $this->where(['id' => $member_id])->save(['invite_code' => $invite_code]);
        if ($invite_code_update === false) {
            $this->rollback();
            return false;
        }

        //用户资金表api_member_capital
        $member_capital_data = [
            'member_id' => $member_id,
            'give_money' => 0.00,
            'recharge_money' => 0.00,
            'updated_time' => NOW_TIME,
            'consume_money' => 0.00
        ];

        $MemberCapital = M('MemberCapital')->add($member_capital_data);
        if ($MemberCapital === false) {
            $this->error = '注册失败2';
            $this->rollback();
            return false;
        }

        //用户拼吧评论星数
        $bar = [
            'member_id' => $member_id,
            'total_star' => 0,
            'total_time' => 0,
            'average_star' => 0,
        ];
        $comment_bar = D('comment_barstar')->add($bar);
        if ($comment_bar === false) {
            $this->error = '注册失败3';
            $this->rollback();
            return false;
        }

        $this->commit();

        //通用 - 新人注册券
        D('coupon')->newUserGetCard($member_id);

        //店铺 - 新人注册券
        D('coupon')->dianpuNewUserCard($member_id);

        //将member_id返回
        $data['member_id'] = $member_id;
        return $data;
    }

    /**
     * 重新设置用户密码
     * @param tel int member's mobile
     * @param password string new password
     * @return bool
     */
    public function setMemberPwd($tel, $password)
    {
        //首先判定该手机用户是否注册
        $member = $this->where(['tel' => $tel])->find();
        if (!$member) {
            $this->error = '该手机号尚未注册,请先注册';
            return false;
        }

        $pwd = Tools::salt_mcrypt($password, $member['salt']);
        //修改用户的密码
        $res = $this->where(['id' => $member['id']])->save(['password' => $pwd]);
        if ($res === false) {
            $this->error = '重置密码失败';
            return false;
        }
        return true;
    }


    /**
     * 验证性别是否已修改过
     * @param $member_id int 用户ID
     * @return mixed 1修改过 0没修改过
     */
    public function valiSexIsUpdated($member_id)
    {
        return $this->where(['id' => $member_id])->getField('is_edit_sex');
    }
}