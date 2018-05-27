<?php
/**
 * FileName: OrderController.class.php
 * User: Comos
 * Date: 2017/10/17 16:17
 */

namespace Admin\Controller;


use Org\Util\JPushNotify;
use Org\Util\Tools;
use Think\Log;

class OrderController extends BaseController
{
    private $_model;

    //订单状态
    public $order_status = [
        0 => '已取消',
        1 => '未支付',
        2 => '待接单',
        3 => '已逾期',
        4 => '已完成',
        5 => '已作废',
        6 => '已拒绝',
        7 => '已接单'
    ];

    //结算状态
    public $settlement_status = [
        0 => '未结算',
        1 => '已结算',
    ];

    //支付方式
    public $payment = [
        1 => '余额支付',
        2 => '微信支付',
        3 => '支付宝',
        4 => '银联支付',
    ];

    //订单类型
    public $order_type = [
        0 => '单品酒水',
        1 => '卡座预定',
        2 => '卡座套餐',
        3 => '优惠套餐',
    ];

    //性别
    public $sex = [
        1 => '男',
        2 => '女',
    ];

    //订单操作类型
    public $operation = [
        1 => '逾期操作',
        2 => '延期操作',
        3 => '完成操作',
        4 => '预订部接单',
        5 => '预订部拒单'
    ];

    //禁止退款的订单状态
    public $disallowStatus = [0, 1, 4, 6];
    public $successStatus = [3, 5, 7];

    public function _initialize()
    {
        $this->_model = D('Order');
        parent::_initialize();
    }

    /**
     * 订单列表
     * @param int $p
     */
    public function index($p = 1)
    {
        $parma = I('get.', '');
        $list = $this->_model->getOrderList($p, $parma);
        if (!$list) {
            $this->error('获取列表失败');
        }

        //分配数据
        $this->assign('list', $list['list']);
        $this->assign('pageHtml', $list['pageHtml']);
        $this->assign('money', $list['money']);
        $this->assign('order_status', $this->order_status);
        $this->assign('sex', $this->sex);
        $this->assign('order_type', $this->order_type);
        $this->assign('payment', $this->payment);
        $this->assign('settlement_status', $this->settlement_status);
        $this->assign('disallowStatus', $this->disallowStatus);
        $this->assign('successStatus', $this->successStatus);
        $this->assign('cancellation_reasons', C("CANCELLATION_REASONS"));
        $this->display();
    }


    /**
     * 查询订单详情
     * @param $id
     */
    public function detail($id)
    {
        //查询订单详情
        $order = $this->_model->where(['id' => $id])->find();
        if (!$order) {
            $this->error('订单不存在');
        }

        //查询卡券数据
        if ($order['card_id']) {
            //查询卡券数据
            $card = M('coupon')->field('id,card_name,deductible')->where(['id' => $order['card_id']])->find();
            $this->assign('card', $card);
        }

        //查询逾期订单数据
        if ($order['relation_order_no']) {
            $relation_order = $this->_model->where(['order_no' => $order['relation_order_no']])->find();
            $this->assign('relation_order', $relation_order);
        }

        //查询商户信息
        $merchant = M('merchant')->where(['id' => $order['merchant_id']])->find();
        //查询用户信息
        $member = M('member')->where(['id' => $order['member_id']])->find();
        //查询套餐
        $pack = M('order_pack')->where(['order_id' => $id])->select();
        //查询卡座
        $seat = M('order_seat')->where(['order_id' => $id])->find();
        //查询操作记录
        $operation = M('employee_operation')->where(['order_id' => $id])->order('updated_time desc')->select();
        //查询订单操作记录
        $adminOperate = M('order_operate_record')->field('api_order_operate_record.*, username, nickname')
            ->join('api_user ON api_order_operate_record.user_id = api_user.id')
            ->where(['order_id' => $id])
            ->order('id desc')
            ->select();

        //分配数据
        $this->assign('order', $order);
        $this->assign('merchant', $merchant);
        $this->assign('member', $member);
        $this->assign('pack', $pack);
        $this->assign('seat', $seat);
        $this->assign('operation', $operation);
        $this->assign('adminOperate', $adminOperate);

        $this->assign('attachment_url', C('ATTACHMENT_URL'));
        $this->assign('order_status', $this->order_status);
        $this->assign('sex', $this->sex);
        $this->assign('order_type', $this->order_type);
        $this->assign('payment', $this->payment);
        $this->assign('settlement_status', $this->settlement_status);
        $this->assign('employee_operation', $this->operation);

        $this->display('detail');
    }

    /**
     * @author jiangling
     * 查看订单信息
     * @param $id  int  订单id
     */
    public function edit($id)
    {
        $orderDetail = M('order')->alias('a')
            ->field('a.order_no,a.id,a.order_type,a.status,a.arrives_time,a.contacts_realname,a.contacts_tel,b.title,a.settlement_status')
            ->join('left join api_merchant b ON b.id = a.merchant_id')
            ->where(['a.id' => $id])->find();

        $this->assign('list', $orderDetail);
        $this->assign('order_status', $this->order_status);
        $this->assign('order_type', $this->order_type);
        $this->assign('settlement_status', $this->settlement_status);
        $this->display();

    }

    /**
     * @author jiangling
     * 修改订单信息
     * @param $id   int  订单id
     * @param status  int  0 取消 1 未支付,2 已支付3 已逾期,4 已完成 5 作废 6 拒绝 7 已接单
     * @param settlement_status  int  1 已结算 0 表示未结算
     */
    public function modify()
    {
        $order_array = [0, 1, 6];
        $status = I('post.order_status');
        $id = I('post.order_id');
        $settlement_status = I('post.settlement_status');

        if (!is_numeric($id)) {
            die('参数不合法');
        }
        $orderModel = M('order');
        //查询订单状态
        $orderInfo = $orderModel->field('order_no,status,order_type,settlement_status,relation_order_no,purchase_price,arrives_time')->where(['id' => $id])->find();

        M()->startTrans();
        //判断订单状态和当前的修改的订单状态不一致的时候(表示需要修改结算)
        if ($status != $orderInfo['status']) {
            if (in_array($status, $order_array)) {
                M()->rollback();
                $this->error($this->order_status['$status'] . '订单不能被修改');
            }

            //判断该订单是否存在逾期卡套
            if (!empty($orderInfo['relation_order_no'])) {
                $dr1 = $orderModel->where(['order_no' => $orderInfo['relation_order_no']])->save(['status' => 5, 'updated_time' => time()]);
                $order_nos[] = $orderInfo['relation_order_no'];
                if (!$dr1) {
                    M()->rollback();
                    $this->error('修改订单1状态失败');
                }
            }

            $dr = $orderModel->save(['id' => $id, 'status' => $status, 'updated_time' => time()]);
            if (!$dr) {
                M()->rollback();
                $this->error('修改订单2状态失败');
            }
        }
        //结算修改
        if ($settlement_status != $orderInfo['settlement_status']) {
            //判断该订单是否结算
            if ($orderInfo['settlement_status'] != 1) {
                M()->rollback();
                return $this->error('订单未结算,请您在财务结算处结算');
            }
            //修改订单结算状态
            $sr = $orderModel->save(['id' => $id, 'settlement_status' => $settlement_status]);
            if (!$sr) {
                M()->rollback();
                $this->error('结算状态修改失败');
            }
            $fsrData = M('finance_recode')->field('id,finance_id')->where(['order_no' => $orderInfo['order_no']])->find();
            //删除结算订单列表的订单数据
            $fsr = M('finance_recode')->where(['id' => $fsrData['id']])->delete();
            if (!$fsr) {
                M()->rollback();
                $this->error('删除结算订单记录失败');
            }
            //修改结算表中的金额数据
            $fr1 = M('finance')->field('order_total,money')->where(['id' => $fsrData['finance_id']])->find();
            $fr2 = M('finance')->where(['id' => $fsrData['finance_id']])->save(['order_total' => ($fr1['order_total'] - 1), 'money' => ($fr1['money'] - $orderInfo['purchase_price'])]);
            if (!$fr2) {
                M()->rollback();
                $this->error('更新结算表中的数据失败');
            }
        }
        M()->commit();
        $this->success('修改成功', U('order/index'));
    }

    /**
     * 订单完成操作
     */
    public function complete($id)
    {
        //实例模型
        $orderModel = D('Order');

        //从数据库中获取获取订单与用户信息
        $orderAndMember = M('order')->field('api_order.member_id,
        api_order.id,
        api_order.order_no,
        api_order.employee_id,
        api_order.merchant_id,
        api_order.order_type,
        api_member.level,
        api_order.status, 
        api_order.is_bar, 
        api_order.is_xu, 
        wx_openid, 
        api_member.tel,
        contacts_tel,
        contacts_sex,
        contacts_realname,
        api_order.pay_price,
        api_order.created_time,
        api_order.relation_order_no,
        api_member_privilege.delayed,
        api_order.arrives_time,
        api_merchant.title as merchant_title')
            ->join('api_member ON api_member.id = api_order.member_id')
            ->join('api_member_privilege ON api_member.level = api_member_privilege.level')
            ->join('api_merchant ON api_merchant.id = api_order.merchant_id')
            ->where(['api_order.id' => $id])
            ->find();

        //判断数据是否读取成功
        if (!$orderAndMember) {
            $this->error = '获取订单失败';
            return false;
        }

        //判断订单状态是否允许执行  订单状态为7:已接单时
        //0已取消 1未支付 2待接单(已支付) 3已逾期 4完成 5已作废 6已拒绝 7已接单
        if (in_array($orderAndMember['status'], [0, 1, 2, 4, 6])) {
            $this->error('订单状态不允许');
        }

        try {
            //修改订单状态
            if (!$res = $orderModel->markSuccessStatus($orderAndMember, $this->uid)) {
                $this->error($orderModel->getError());
            }

            /**
             * 推送消息给用户端
             */
            //是否为拼吧订单判断
            if ($orderAndMember['is_bar'] == 1) {
                //拼吧订单消息推送

                $bar_id = M('bar_order')->where(['order_id' => $orderAndMember['id']])->getField('bar_id');
                $memberIds = M('bar_member')->field("member_id")->where(['pay_status' => 2, 'bar_id' => $bar_id])->select();

                if ($orderAndMember['is_xu'] == 1) {
                    $alert = '您参与的拼吧续酒订单已消费完成';
                    $title = '订单已完成通知';
                } else {
                    $alert = '您参与的拼吧订单已消费完成';
                    $title = '订单已完成通知';
                }

                foreach ($memberIds as $memberId) {
                    $message = [
                        'alert' => $alert,
                        'title' => $title,
                        'extras' => [
                            'msg_type' => 'bar',  //system order bar
                            'title' => $title,
                            'content' => $alert,
                            'icon' => C('MEMBER_API_URL') . '/Public/images/message/acceptorders.png',
                            'order_id' => $bar_id
                        ]
                    ];
                    JPushNotify::toAliasNotify($memberId, $message);
                }
            } else {
                //普通订单消息推送

                //判断是否是续酒订单,给用户推送续酒完成的短信
                if ($orderAndMember['is_xu'] == 1) {
                    $alert = '您的续酒' . $this->order_type[$orderAndMember['order_type']] . '订单已消费完成';
                    $title = '订单已完成通知';
                } else {
                    $alert = '您的' . $this->order_type[$orderAndMember['order_type']] . '订单已消费完成';
                    $title = '订单已完成通知';
                }

                //推送消息
                JPushNotify::toAliasNotify($orderAndMember['member_id'], [
                    'alert' => $alert,
                    'title' => $title,
                    'extras' => [
                        'msg_type' => 'order',  //system order bar
                        'title' => $title,
                        'content' => $alert,
                        'icon' => C('MEMBER_API_URL') . '/Public/images/message/acceptorders.png',
                        'order_id' => $orderAndMember['id']
                    ]
                ]);
            }

        } catch (\Exception $exception) {
            Log::write($exception);
            $this->error($exception->getMessage());
        }

        //修改订单状态成功
        $http_referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : false;
        if ($http_referer) {
            redirect($_SERVER['HTTP_REFERER'], 0);
        } else {
            $this->redirect('Order/index');
        }
    }

    /**
     * 新订单通知
     */
    public function neworder()
    {
        $lastTime = S('kpz_new_order_time');
        $lastTime = $lastTime ? $lastTime : 0;
        $where = [
            'status' => ['IN', [2, 7]],
            'created_time' => ['GT', $lastTime]
        ];
        $count = M('order')->where($where)->count();
        S('kpz_new_order_time', time());
        $this->ajaxReturn(['total' => $count, 'code' => 1]);
    }
}