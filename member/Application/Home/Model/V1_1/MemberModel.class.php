<?php

namespace Home\Model\V1_1;

use Org\Util\String;
use Org\Util\Tools;
use Think\Model;

class MemberModel extends Model
{
    /**
     * H5页面新注册会员写入资料到数据库
     * @param $data
     * @return bool
     */
    public function addMemberByEmployeeInviteCode($data)
    {

        $salt = String::randString(6);
        $password = Tools::salt_mcrypt($data['password'], $salt);

        //开启事务
        $this->startTrans();
        if ($data['source'] == 'wechat') {
            /*
             * 微信入口注册
             */
            $nickname = Tools::filterEmoji($data['nickname']);
            $memeber_data = [
                'wx_openid' => $data['wx_openid'],
                'status' => 1,
                'level' => 1,
                'growth' => 0,
                'coin' => 0,
                'created_time' => time(),
                'unionid' => $data['unionid'],
                'nickname' => $nickname,
                'sex' => $data['sex'],
                'avatar' => $data['avatar'],
                'tel' => $data['tel'],
                'password' => $password,
                'salt' => $salt
            ];
        } else {
            /**
             * H5其他注册入口
             */
            $nickname = Tools::filterEmoji($data['nickname']);
            $memeber_data = [
                'wx_openid' => $data['wx_openid'],
                'status' => 1,
                'level' => 1,
                'growth' => 0,
                'coin' => 0,
                'created_time' => time(),
                'unionid' => $data['unionid'],
                'nickname' => $nickname,
                'sex' => $data['sex'],
                'avatar' => $data['avatar'],
                'tel' => $data['tel'],
                'password' => $password,
                'salt' => $salt
            ];
        }

        //判断是否存在推广码,存在推广码才添加推广码数据
        if ($data['invite_code']) {
            $memeber_data['promoter_code'] = $data['invite_code'];
        }

        //写入数据到数据库
        if (!$member_id = $this->add($memeber_data)) {
            $this->error = '001';
            $this->rollback();
            return false;
        }

        //获取用户的推广码
        $invite_code = Tools::create_invite_code(C('INVITE_CODE_PREFIX.MEMBER'), $member_id);
        $res = $this->where(['id' => $member_id])->save(['invite_code' => $invite_code, 'updated_time' => time()]);
        if ($res === false) {
            $this->error = '002';
            $this->rollback();
            return false;
        }

        //根据推广码查询用户数据
        $prefix = substr($data['invite_code'], 0, 1);
        if ($prefix == 1) {
            //v1.1 & v2.0
            //如果前缀为1就是用户端推广
            $invite_member_id = $this->where(['invite_code' => $data['invite_code']])->getField('id');
            if (!$invite_member_id) {
                $this->error = '003';
                $this->rollback();
                return false;
            }

            //写入会员推广数据
            $spread_data = [
                'employee_id' => $invite_member_id,
                'account_type' => 1,
                'member_id' => $member_id,
                'money' => 0,
                'reg_time' => time(),
                'is_consume' => 0,
            ];
            $spread_res = M('spread_record')->add($spread_data);
            if ($spread_res === false) {
                $this->error = '004';
                $this->rollback();
                return false;
            }


        } elseif ($prefix == 2) {
            //商户端推广
            $employee_id = M('employee')->where(['invite_code' => $data['invite_code']])->getField('id');
            if (!$employee_id) {
                $this->error = '推广码不存在-005';
                $this->rollback();
                return false;
            }

            //写入员工推广记录
            $spread_data = [
                'employee_id' => $employee_id,
                'account_type' => 2,
                'member_id' => $member_id,
                'money' => 0,
                'reg_time' => time(),
                'is_consume' => 0,
            ];
            $spread_res = M('spread_record')->add($spread_data);
            if ($spread_res === false) {
                $this->error = '006';
                $this->rollback();
                return false;
            }
        } elseif ($prefix == 3) {
            //推广码参数前缀为3,则为线下推广二维码渠道,无需查询与关联推广用户

        }

        //用户资金表api_member_capital
        $member_capital_data = [
            'member_id' => $member_id,
            'give_money' => 0.00,
            'recharge_money' => 0.00,
            'updated_time' => time(),
            'consume_money' => 0.00
        ];

        $MemberCapital = M('MemberCapital')->add($member_capital_data);
        if ($MemberCapital === false) {
            $this->error = '007';
            $this->rollback();
            return false;
        }

        //查询优惠券表中包含新人字符的数据
        $card_id = M('coupon')->where(['card_name' => ['like', '%新人%']])->order('id asc')->getField('id');
        if (!$card_id) {
            $this->error = '008';
            $this->rollback();
            return false;
        }

        //写入优惠券数据
        $res = M('coupon_member')->add(['get_time' => time(), 'card_id' => $card_id, 'member_id' => $member_id]);
        if (!$res) {
            $this->error = '009';
            $this->rollback();
            return false;
        }

        //提交事务
        $this->commit();
        return true;
    }
}
