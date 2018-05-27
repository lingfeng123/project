<?php
/**
 * FileName: MemberModel.class.php
 * User: Comos
 * Date: 2017/8/22 19:16
 */

namespace Admin\Model;


use Think\Model;
use Think\Page;

class MemberModel extends Model
{

    /**
     * 根据条件获取注册会员数据
     * @param $parma
     * @param $page
     * @return bool
     */
    public function getMemberListByWhere($parma, $page)
    {
        $where = '';
        if (isset($parma['sex']) && $parma['sex'] != '') {
            $where['api_member.sex'] = $parma['sex'];
        }

        if (isset($parma['status']) && $parma['status'] != '') {
            $where['api_member.status'] = $parma['status'];
        }

        if (isset($parma['is_auth']) && $parma['is_auth'] != '') {
            $where['api_member.is_auth'] = $parma['is_auth'];
        }

        //判断是否绑定手机
        if (isset($parma['bind_tel']) && $parma['bind_tel'] != '') {
            if ($parma['bind_tel'] == 1) {
                $where['tel'] = ['neq', 0];
            } else {
                $where['tel'] = ['eq', 0];
            }
        }

        //查询条件
        if (isset($parma['keywords']) && !empty($parma['keywords'])) {
            $where['tel'] = array('like', '%' . $parma['keywords'] . '%');
            $where['nickname'] = array('like', '%' . $parma['keywords'] . '%');
            $where['_logic'] = 'or';
        }

        //时间范围
        if (isset($parma['start_time']) && !empty($parma['start_time']) && isset($parma['stop_time']) && !empty($parma['stop_time'])) {
            $parma['start_time'] = htmlspecialchars_decode($parma['start_time']);
            $parma['stop_time'] = htmlspecialchars_decode($parma['stop_time']);

            $start_time = strtotime($parma['start_time']);
            $stop_time = strtotime($parma['stop_time']);

            $where['api_member.created_time'] = [['EGT', $start_time], ['ELT', $stop_time]];
        }

        $pageSize = C('PAGE.PAGESIZE');

        //统计查询条件总条数
        $count = $this->join('api_member_privilege ON api_member.level = api_member_privilege.level')
            ->join('api_member_capital ON api_member_capital.member_id = api_member.id')
            ->where($where)
            ->count();

        //执行查询
        $fields = "api_member.id, tel,nickname,sex,avatar,api_member.coin,status,from_unixtime(created_time) as created_time,api_member.level, title as level_name,consume_money,give_money,recharge_money,is_auth";
        $data['list'] = $this->field($fields)
            ->join('api_member_privilege ON api_member.level = api_member_privilege.level')
            ->join('api_member_capital ON api_member_capital.member_id = api_member.id')
            ->where($where)->page($page, $pageSize)->order("id desc")->select();
        if ($data['list'] === false) {
            return false;
        }

        $pages = new Page($count, $pageSize);
        $pages->setConfig('header', '共%TOTAL_ROW%条');
        $pages->setConfig('first', '首页');
        $pages->setConfig('last', '末页');
        $pages->setConfig('prev', '上一页');
        $pages->setConfig('next', '下一页');
        $pages->setConfig('theme', C('PAGE.THEME'));

        $data['pageHtml'] = urldecode($pages->show());

        return $data;
    }


    /**
     * 获取注册用户信息
     * @param $id   int  用户ID
     * @return bool|mixed   成功返回数据,失败返回false
     */
    public function getMemberInfoById($id)
    {
        $fields = "id,tel,nickname,sex,avatar,coin,status,from_unixtime(created_time) as created_time,from_unixtime(updated_time) as updated_time,invite_code,level";
        $memberInfo = $this->field($fields)->find($id);
        if ($memberInfo == false) {
            $this->error = '获取用户信息失败';
            return false;
        }

        //获取用户会员等级名称
        $vipModel = M('MemberPrivilege');
        $vip_data = $vipModel->getField('level,title');
        $memberInfo['level_name'] = $vip_data[$memberInfo['level']];

        //数据处理
        if ($memberInfo['sex'] == 1) {
            $memberInfo['sex'] = '男';
        } else {
            $memberInfo['sex'] = '女';
        }
        return $memberInfo;
    }


    /**
     * 修改用户账号状态
     * @param $id int 用户ID
     * @return bool 执行结果
     */
    public function closureMember($id)
    {
        //根据用户ID查询对应用户账号状态
        $info = $this->field('status')->find($id);
        if (!$info) {
            $this->error = '查无此人';
            return false;
        }
        //更改用户状态值
        $status = $info['status'] == 1 ? 0 : 1;
        //修改用户数据
        $rs = $this->save(['id' => $id, 'status' => $status]);
        //判断执行sql执行结果,返回状态
        if ($rs === false) {
            $this->error = '修改状态失败';
            return false;
        }
        return true;
    }
}