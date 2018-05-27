<?php
/**
 * FileName: CommentMerchantModel.class.php
 * User: Comos
 * Date: 2017/8/22 16:28
 */

namespace V1_1\Model;


use Org\Util\Tools;
use Think\Model;

class CommentMerchantModel extends Model
{

    /**
     * 根据商户ID获取商户评价
     * @param $merchant_id  int 商户ID
     * @param $page int  当前页码
     * @param $page_size    每页显示数量
     */
    public function getMerchantListByMerchantId($merchant_id, $page, $page_size)
    {
        //查询条件
        $where = [
            'merchant_id' => $merchant_id,
            'api_comment_merchant.status' => 1
        ];

        //获取记录总数
        $list['total'] = $this->where($where)->count();
        //根据分页查询数据
        $fields = "api_comment_merchant.id as comment_id, member_id, FROM_UNIXTIME(api_comment_merchant.created_time) as created_time, average,avatar,nickname,sex, age,content";
        $comments = $this->field($fields)
            ->join(' api_member ON api_comment_merchant.member_id = api_member.id', 'LEFT')
            ->where($where)
            ->order('created_time desc')
            ->page($page, $page_size)
            ->select();
        //判断查询结果是否为true
        if ($comments === false) return false;

        foreach ($comments as $key =>$comment){
            if (!preg_match('/^(http|https)/ius', $comment['avatar'])) {
                $comments[$key]['avatar'] = C('attachment_url') . $comment['avatar'];
            }

            $comments[$key]['age'] = Tools::calculateAge($comment['age']);
        }

        //返回商户评论数据
        $list['list'] = $comments;
        return $list;
    }

    /**
     * 查询订单对应的评价信息
     */
    public function findOrderCommentInfo($member_id, $order_id)
    {
        //获取订单信息
        $orderInfo = D('Order')->field('id, order_no, member_id, order_type, merchant_id, employee_id, employee_avatar, employee_realname')
            ->where(['member_id' => $member_id, 'id' => $order_id])
            ->find();

        if ($orderInfo === false) {
            $this->error = '符合条件的订单不存在';
            return false;
        }

        //获取酒吧名称
        $merchantInfo = D('Merchant')->field('title, logo')->find($orderInfo['merchant_id']);
        if ($merchantInfo === false) {
            $this->error = '获取酒吧名称失败';
            return false;
        }

        $attachment_url = C('ATTACHMENT_URL');
        //拼装数据
        $comments['merchant_title'] = $merchantInfo['title'];
        $comments['merchant_id'] = $orderInfo['merchant_id'];
        $comments['logo'] = $attachment_url . $merchantInfo['logo'];
        $comments['order_no'] = $orderInfo['order_no'];
        $comments['order_id'] = $orderInfo['id'];

        //获取当前用户对订单的评分
        $merchantStarInfo = M('CommentMerchant')->field('environment, atmosphere, service, content')->where(['order_no' => $orderInfo['order_no']])->find();
        if (!$merchantStarInfo) {
            $this->error = '订单尚未被评价';
            return false;
        }

        //拼装数据
        foreach ($merchantStarInfo as $key => $value) {
            $comments[$key] = $value;
        }

        //判断关联员工是否存在
        if ($orderInfo['order_type'] == 1) {
            //获取对服务员的评分
            $employee_star = M('CommentEmployee')->where(['order_id' => $order_id])->getField('star');
            if ($employee_star === false) {
                $this->error = '获取订单评价失败';
                return false;
            }
            //拼装数据
            $comments['employee_star'] = $employee_star;
            $comments['employee_avatar'] = $attachment_url . $orderInfo['employee_avatar'];
            $comments['employee_realname'] = $orderInfo['employee_realname'];
        }

        //返回查询数据
        return $comments;
    }

    /**
     * 插入用户评价内容
     */
    public function insertCommentData($data)
    {
        //开启事务
        $this->startTrans();

        //添加商户评价表数据
        if (!$this->_addCommentMerchant($data)) {
            $this->rollback();
            $this->error = '添加商户评价失败';
            return false;
        }
        //修改商户评价统计表
        if (!$this->_updateCommentMchstar($data)) {
            $this->rollback();
            $this->error = '修改商户评价统计失败';
            return false;
        }

        //判断是否存在员工ID,员工ID为true时才执行写入数据库操作
        if ($data['employee_id']) {
            //添加员工评价表数据
            if (!$this->_addCommentEmployee($data)) {
                $this->rollback();
                $this->error = '添加员工评价失败';
                return false;
            }
            //修改员工评价统计表数据
            if (!$this->_updateCommentEmpstar($data)) {
                $this->rollback();
                $this->error = '修改员工评价统计失败';
                return false;
            }
        }

        //修改订单评价状态
        $rs = D('order')->where(['id' => $data['order_id']])->save(['is_evaluate' => 1]);
        if ($rs === false) {
            $this->rollback();
            $this->error = '评价失败';
            return false;
        }

        //提交事务
        $this->commit();
        return true;
    }

    /**
     * 添加商户评价表数据
     */
    private function _addCommentMerchant($data)
    {
        //商户评价表
        $average = sprintf("%.1f", ($data['environment'] + $data['atmosphere'] + $data['service']) / 3);
        $CommentMerchantData = [
            'order_no' => $data['order_no'],
            'merchant_id' => $data['merchant_id'],
            'member_id' => $data['member_id'],
            'order_id' => $data['order_id'],
            'environment' => $data['environment'],
            'atmosphere' => $data['atmosphere'],
            'service' => $data['service'],
            'content' => $data['content'],
            'average' => $average,
            'created_time' => NOW_TIME,
        ];
        $CommentMerchantModel = D('CommentMerchant');
        $CommentMerchantResult = $CommentMerchantModel->add($CommentMerchantData);
        if ($CommentMerchantResult === false) {
            return false;
        }
        //执行成功返回true
        return true;
    }

    /**
     * 添加员工评价表数据
     * @param $data
     * @return bool
     */
    private function _addCommentEmployee($data)
    {
        //员工评价表
        $CommentEmployeeData = [
            'order_no' => $data['order_no'],
            'order_id' => $data['order_id'],
            'member_id' => $data['member_id'],
            'employee_id' => $data['employee_id'],
            'star' => $data['star'],
            'created_time' => time(),
        ];
        $CommentEmployeeResult = M('CommentEmployee')->add($CommentEmployeeData);
        if ($CommentEmployeeResult === false) {
            return false;
        }
        return true;
    }

    /**
     * 修改商户统计表中的数据
     * @param $data
     * @return bool
     */
    private function _updateCommentMchstar($data)
    {
        //查询商户记录是否存在
        $MchstarData = M('CommentMchstar')->where(['merchant_id' => $data['merchant_id']])->find();

        if ($MchstarData) {
            //计算各个评分等级总星星
            $environment_star = $MchstarData['environment_star'] + $data['environment'];
            $atmosphere_star = $MchstarData['atmosphere_star'] + $data['atmosphere'];
            $service_star = $MchstarData['service_star'] + $data['service'];
            $amount = $MchstarData['amount'] + 1;
            //计算总平均分
            $average = sprintf("%.1f", (($environment_star + $atmosphere_star + $service_star) / $amount) / 3);

            //修改数据组装
            $CommentMchstarData = [
                'merchant_id' => $data['merchant_id'],
                'environment_star' => $environment_star,
                'atmosphere_star' => $atmosphere_star,
                'service_star' => $service_star,
                'amount' => $amount,
                'average' => $average
            ];
            //修改数据
            $CommentMchstarReslut = M('CommentMchstar')->where(['merchant_id' => $data['merchant_id']])->save($CommentMchstarData);
            if ($CommentMchstarReslut === false) {
                return false;
            }
            //执行成功返回true
            return true;
        }

        //查询数据失败
        return false;
    }

    /**
     * 修改员工评价统计表数据
     * @param $data
     * @return bool
     */
    private function _updateCommentEmpstar($data)
    {
        //根据员工ID查询员工数据
        $oldCommentEmpstar = M('CommentEmpstar')->where(['employee_id' => $data['employee_id']])->find();
        if ($oldCommentEmpstar) {
            $stars = $oldCommentEmpstar['star'] + $data['star'];
            $amount = $oldCommentEmpstar['amount'] + 1;
            $average = sprintf("%.1f", $stars / $amount);
            $CommentEmpstarData = [
                'employee_id' => $data['employee_id'],
                'star' => $stars,
                'amount' => $amount,
                'average' => $average
            ];
            //修改员工评价统计表中的数据
            $CommentEmpstarRelsult = M('CommentEmpstar')->where(['employee_id' => $data['employee_id']])->save($CommentEmpstarData);
            if ($CommentEmpstarRelsult === false) {
                return false;
            }
            return true;
        }

        //查询数据失败
        return false;
    }

}