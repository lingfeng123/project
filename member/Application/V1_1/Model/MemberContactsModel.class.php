<?php
/**
 * FileName: MemberContactsModel.class.php
 * User: Comos
 * Date: 2017/8/23 14:48
 */

namespace V1_1\Model;


use Think\Model;

class MemberContactsModel extends Model
{

    /**
     * 添加新联系人数据
     * @param $data
     * @return mixed
     */
    public function createNewContact($data){
        $member_id = $data['member_id'];
        //查询用户是否添加过联系人
        $res = $this->where(['member_id' => $member_id])->find();
        if (!$res){
            //没有添加过联系人,设置is_default为1
            $data['is_default'] = 1;
        }
        return $this->add($data);
    }


    /**
     * 判断联系人号码是否已存在
     * @param $tel  int 电话号码
     * @param $member_id  int  用户ID
     * @return bool
     */
    public function verifyTelExist($tel, $member_id){
        //查询此注册用户手机号码是否已添加过
        return $this->where(['member_id' => $member_id, 'tel' => $tel])->find();
    }
}