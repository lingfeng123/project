<?php
/**
 * FileName: MemberBlacklistModel.class.php
 * User: Comos
 * Date: 2018/2/24 17:44
 */

namespace V1_1\Model;


use Org\Util\Tools;
use Think\Model;

class MemberBlacklistModel extends Model
{

    /**
     * 根据当前用户ID获取当前用户的黑名单记录
     * @param $member_id int 当前用户的ID
     */
    public function getBlackListData($member_id, $page, $pagesize)
    {
        $where = ['member_id' => $member_id];
        $join_where = 'api_member ON api_member.id = api_member_blacklist.black_member_id';
        $count = $this->where($where)->join($join_where)->count();
        $data = $this->field('api_member_blacklist.id, nickname, sex, avatar, age, api_member.id as black_member_id, api_member_blacklist.created_time')
            ->join($join_where)
            ->where($where)
            ->order('api_member_blacklist.created_time desc')
            ->page($page, $pagesize)
            ->select();
        if ($count === false || $data === false) {
            return false;
        }

        //计算用户年龄
        foreach ($data as $key => $datum) {
            $data[$key]['age'] = Tools::calculateAge($data[$key]['age']);

            //判断头像URL中是否是微信头像地址
            if (!preg_match('/^(http|https)/ius', $data[$key]['avatar'])) {
                $attachment_url = C('ATTACHMENT_URL');
                $data[$key]['avatar'] = $data[$key]['avatar'] ? $attachment_url . $data[$key]['avatar'] : '';
            }
        }

        return ['total' => $count, 'list' => $data];
    }

    /**
     * 添加用户的黑名单数据
     * @param $member_id int 当前登录用户ID
     * @param $black_member_id int 需要加入黑名单的用户ID
     * @return bool 返回值
     */
    public function addBlackUser($member_id, $black_member_id)
    {
        $where = ['member_id' => $member_id, 'black_member_id' => $black_member_id];
        if ($this->where($where)->find()) {
            $this->error = '黑名单记录已存在';
            return false;
        };

        //写入黑名单数据数据
        $where['created_time'] = time();
        $res = $this->add($where);
        if (!$res) {
            $this->error = '添加黑名单失败';
            return false;
        }

        return true;
    }
}