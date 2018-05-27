<?php
/**
 * FileName: KcoinRecordModel.class.php
 * User: Comos
 * Date: 2018/2/28 18:11
 */

namespace V1_1\Model;


use Think\Model;

class KcoinRecordModel extends Model
{

    /**
     * K币交易记录
     * @param $member_id int 当前登录用户的ID
     * @param $page int 当前页码
     * @param $pagesize int 每页显示数量
     */
    public function getCoinList($member_id, $page, $pagesize)
    {
        $count = $this->where(['member_id' => $member_id])->count();
        $list = $this->where(['member_id' => $member_id])->page($page, $pagesize)->select();
        if ($count === false || $list === false) {
            return false;
        }

        return [
            'total' => $count,
            'list' => $list
        ];
    }
}