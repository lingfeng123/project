<?php
/**
 * FileName: MemberAuthRecordModel.class.php
 * User: Comos
 * Date: 2018/2/26 10:56
 */

namespace V1_1\Model;


use Think\Model;

class MemberAuthRecordModel extends Model
{

    /**
     * 添加新派对大使认证数据
     * @param $member_id int 当前登录用户ID
     * @param $realname string 真实姓名
     * @param $tel int 手机号码
     */
    public function addAuthData($member_id, $realname, $contacts_tel)
    {
        $time = time();
        $insert_data = [
            'member_id' => $member_id,
            'realname' => $realname,
            'contacts_tel' => $contacts_tel,
            'created_time' => $time,
            'updated_time' => $time,
        ];

        $res = $this->add($insert_data);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * 根据联系方式获取一条记录
     * @param $contacts_tel int 电话号码
     * @return bool
     */
    public function findRecord($where)
    {
        $res = $this->field('id')->where($where)->find();
        if ($res) {
            return true;
        }

        return false;
    }
}