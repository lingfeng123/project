<?php
/**
 * FileName: RefundController.class.php
 * User: Comos
 * Date: 2017/12/8 15:17
 */

namespace Admin\Controller;


use Org\Util\JPushNotify;
use Org\Util\JsSdkPay;
use Org\Util\String;
use Org\Util\Tools;
use Org\Util\YunpianSms;
use Think\Log;
use Think\Page;

class RefundController extends BaseController
{
    private $status = [
        '1' => '退款中',
        '2' => '退款成功',
        '3' => '退款异常',
        '4' => '退款关闭'
    ];

    /**
     * 微信退款数据列表
     */
    public function index($p = 1)
    {
        //关键字搜索
        $keywords = I('get.keywords', '');
        $status = I('get.status', '');
        $type = I('get.type', '');

        if ($keywords && $status) {
            $condition = [
                'a.order_no|a.out_refund_no|b.title|c.nickname' => ['like' => '%' . $keywords . '%'],
                'a.status' => $status
            ];
        } else if ($keywords && !$status) {
            $condition = [
                'a.order_no|a.out_refund_no|b.title|c.nickname' => ['like' => '%' . $keywords . '%'],
            ];
        } else if (!$keywords && $status) {
            $condition = [
                'a.status' => $status
            ];
        } else {
            $condition = 1;
        }

        $page = $p;
        $pageSize = C('PAGE.PAGESIZE');

        //查找所有的退款数据
        $count = M('refund')->alias('a')
            ->join('left join api_merchant b ON b.id = a.merchant_id')
            ->join('left join api_member c ON c.id = a.member_id')
            ->where($condition)->count();

        //分页查找数据
        $refund_list['list'] = M('refund')->alias('a')
            ->field('a.order_no,a.transaction_id,a.out_refund_no,a.total_fee,a.refund_fee,a.refund_desc,a.create_time,a.status,a.merchant_id,a.member_id,b.title,c.nickname ')
            ->join('left join api_merchant b ON b.id = a.merchant_id')
            ->join('left join api_member c ON c.id = a.member_id')
            ->where($condition)->page($page, $pageSize)->select();

        //执行分页操作
        $pages = new Page($count, $pageSize);
        $pages->setConfig('header', '共%TOTAL_ROW%条');
        $pages->setConfig('first', '首页');
        $pages->setConfig('last', '末页');
        $pages->setConfig('prev', '上一页');
        $pages->setConfig('next', '下一页');
        $pages->setConfig('theme', C('PAGE.THEME'));

        $refund_list['pageHtml'] = $pages->show();


        $this->assign('pageHtml', $refund_list['pageHtml']);
        $this->assign('refund', $refund_list['list']);
        $this->assign('status', $this->status);

        $this->display();
    }

    /**
     * 执行退款操作
     * @param $refund_type int 1微信退款 2余额退款
     * @param $order_no int  订单编号
     * @param $refund_fee string 输入的退款金额
     * @param $refund_desc string 退款备注
     */
    public function add()
    {
        if (IS_POST) {
            $refund_type = I('post.refund_type', '');
            $order_no = I('post.order_no', '');
            $refund_fee = I('post.refund_fee', '');
            $refund_desc = I('post.refund_desc', '');

            //验证退款类型
            if (!in_array($refund_type, [1, 2])) $this->error('退款类型不合法');

            //验证商户订单状态是否合法
            if (!is_numeric($order_no) && strlen($order_no) != 16) $this->error('商户订单编号不正确');

            //验证退款金额格式是否输入正确
            if (!preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $refund_fee)) $this->error('退款金额输入不合法');

            //退款备注验证
            if (mb_strlen($refund_desc) < 10) $this->error('退款备注太短');

            //获取订单数据
            $orderModel = D('order');
            $order_info = $orderModel->where(['order_no' => $order_no])->find();
            if (!$order_info) $this->error('没有找到符合条件的订单');

            //微信退款操作
            if ($refund_type == 1) {
                $this->_refundOperation($order_no, $refund_fee, $refund_desc, $order_info);
            }

            //执行余额退款
            if ($refund_type == 2) {
                $rs = $orderModel->refundOperation($order_info, $refund_desc);
                if (!$rs) {
                    $this->error('退款失败');
                } else {
                    $this->success('退款成功');
                }
            }

        }

        //渲染视图
        $this->display();
    }

    /**
     * 微信退款操作
     * @param $order_no int 商户订单号
     * @param $refund_fee_input float 输入的退款金额
     * @param $refund_desc string 退款备注
     * @return bool
     */
    private function _refundOperation($order_no, $refund_fee_input, $refund_desc, $order_info)
    {
        @date_default_timezone_set('PRC');

        //查询是否已存在退款记录
        $refund_val = M('refund')->field('id')->where(['order_no' => $order_no])->find();
        if ($refund_val) {
            $this->error('该订单已执行了退款');
        }

        //获取微信支付日志记录
        $wxpay_log = M('paylog_wxpay')->field('transaction_id, member_id,appid,mch_id,openid,total_fee,pay_type')
            ->where(['pay_type' => 1, 'order_no' => $order_no])->find();
        if (!$wxpay_log) {
            $this->error('获取该订单支付记录失败');
        }

        $total_fee = $wxpay_log['total_fee'] * 100;
        $refund_fee_input = $refund_fee_input * 100;

        //验证输入金额是否合法
        if ($total_fee < $refund_fee_input) {
            $this->error('退款金额不能大于实际支付金额');
        }

        //微信支付配置数据
        $config = C('WXPAY_OPTION');

        //微信退款请求数据
        $data['appid'] = $config['APPID'];                                      //微信分配的小程序ID
        $data['mch_id'] = $config['MCH_ID'];                                    //微信支付分配的商户号
        $data['nonce_str'] = strtoupper(md5(String::randString(20)));       //随机字符串，不长于32位
        $data['transaction_id'] = $wxpay_log['transaction_id'];
        $data['out_refund_no'] = Tools::refund_number();                       //商户退款编号
        $data['total_fee'] = $total_fee;                                              //商品描述,不能为空
        $data['refund_fee'] = $total_fee;                                              //商品描述,不能为空
        $data['op_user_id'] = $config['MCH_ID'];                                              //商品描述,不能为空

        ksort($data);   //将参数以字典序排列
        $sign_str = urldecode(http_build_query($data)); //生成字典序字符串
        $stringSignTemp = $sign_str . '&key=' . $config['KEY'];
        $data['sign'] = strtoupper(md5($stringSignTemp));

        //将数据转化为xml
        $jssdk = new JsSdkPay();
        $xml = $jssdk->arrayToXml($data);   //将数组转化成xml
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $response = $jssdk->postSSLXml($url, $xml);
        $fileName = 'manual_refund_' . date('Y-m-d') . '.log';

        //微信服务器返回的数据
        $wx_tk = $jssdk->xmlToArray($response);

        //记录日志
        Log::write('发起微信退款||' . date('Y-m-d H:i:s') . '||' . json_encode($data), Log::WARN, '', $fileName);
        Log::write('微信退款数据||' . date('Y-m-d H:i:s') . '||' . json_encode($wx_tk), Log::WARN, '', $fileName);

        //写入微信退款数据
        if ($wx_tk['return_code'] == "SUCCESS" && $wx_tk['result_code'] == 'SUCCESS') {
            //执行数据库操作
            if (!$rs = D('order')->wxRefundUpdateData($wx_tk, $order_info, $refund_desc)) {
                $this->error('发起退款失败1');
            }
            $this->success('发起退款成功', U('Refund/index'));

        } else {
            //记录日志
            Log::write('发起退款失败||' . date('Y-m-d H:i:s') . '||' . json_encode($wx_tk), Log::WARN, '', $fileName);
            $this->error('发起退款失败2', U('Refund/index'));
        }
    }


    /**
     * 取消订单并退款
     */
    public function cancelOrder($order_id, $cancel_reason)
    {
        if (!is_numeric($order_id) || empty($cancel_reason)) {
            $this->ajaxError('请求参数不合法');
        }

        if (!isset($this->uid) && empty($this->uid)) {
            $this->ajaxError('用户未登录,无法操作');
        }

        //获取订单数据
        $order_info = M('order')->where(['id' => $order_id])->find();
        if (!$order_info) {
            $this->ajaxError('获取订单失败');
        }

        if (in_array($order_info['status'], [0, 1, 4, 6])) {
            $this->ajaxError('该订单无法执行退款');
        }

        //判断支付金额是否为0
        if ($order_info['pay_price'] == 0) {
            $order_info['payment'] = 1;
        }

        $refundModel = D('refundRecord');

        //执行退款操作 余额支付退款操作
        switch ($order_info['payment']) {
            case 1:

                //站内余额支付
                if (!$rs = $refundModel->refundOperation($order_info, $this->uid, $cancel_reason)) {
                    $this->ajaxError($refundModel->getError());
                }

                break;
            case 2:

                $pay_record = $refundModel->getPaymentRecord($order_info['order_no']);
                if(!$pay_record){
                    $this->ajaxError('获取订单支付记录失败');
                }

                //微信支付退款
                if (!$refundModel->wxRefund($order_info, $this->uid, $cancel_reason,$pay_record)) {
                    $this->ajaxError($refundModel->getError());
                }

                break;
            case 0:

                //拼吧退款
                if (!$refundModel->refundBarMember($order_info, $this->uid, $cancel_reason)) {
                    $this->ajaxError($refundModel->getError());
                }

                break;
            case 3:

                $pay_record = $refundModel->getPaymentRecord($order_info['order_no']);
                if(!$pay_record){
                    $this->ajaxError('获取订单支付记录失败');
                }

                //支付宝退款
                if (!$refundModel->aliPayRefund($order_info, $this->uid, $cancel_reason)) {
                    $this->ajaxError($refundModel->getError());
                }

                break;
        }

        //删除消息
        M('message_employee')->where(['order_no' => $order_info['order_no']])->delete();

        //推送通知和发送短信
        $this->sendMessage($order_info, $cancel_reason);

        //成功返回数据
        $this->ajaxSuccess('取消订单并退款成功');
    }

    /**
     * 推送通知和发送短信
     */
    private function sendMessage($order_info, $cancel_reason)
    {
        $yunpian = new YunpianSms();
        $tpl_config = C('YUNPIAN');
        $template_id = $tpl_config['judan'];

        $merchant = M('merchant')->field('title')->find($order_info['merchant_id']);    //获得商户数据

        //推送jpush消息
        if ($order_info['is_bar'] == 1) {

            //拼吧循环推送消息
            $bar_members = M('bar_member')->field('api_bar_member.member_id,api_bar_member.bar_id,api_member.nickname,api_member.sex,api_member.tel')
                ->join('left join api_bar_order ON api_bar_order.bar_id = api_bar_member.bar_id')
                ->join('left join api_member ON api_member.id = api_bar_member.member_id')
                ->where(['api_bar_order.order_id' => $order_info['id']])
                ->select();

            foreach ($bar_members as $bar_member) {
                $message = [
                    '#name#' => $bar_member['nickname'] . $this->_sex[$bar_member['sex']],
                    '#product#' => $merchant['title'] . "的拼吧订单",
                    '#reason#' => $cancel_reason
                ];
                $msg_title = '您参与的拼吧，因' . $cancel_reason . '被商家拒绝';
                $yunpian->tplSingleSend($bar_member['tel'], $template_id, $message);

                JPushNotify::toAliasNotify(
                    $bar_member['member_id'],
                    [
                        'alert' => $msg_title,
                        'title' => '拼吧订单被拒绝',
                        'extras' => [
                            'msg_type' => 'bar',  //system order bar
                            'title' => $msg_title,
                            'content' => '',
                            'icon' => '',
                            'order_id' => $bar_member['bar_id']
                        ]
                    ]);
            }

        } else {

            $product = $msg_title = '';
            //正常下单的拒绝推送消息
            switch ($order_info['order_type']) {
                case 1:

                    //卡座订单
                    $product = $merchant['title'] . '卡座';
                    $msg_title = '您购买的卡座订单，因' . $cancel_reason . '被商家拒绝';

                    break;
                case 2:
                    //卡套订单
                    $product = $merchant['title'] . $order_info['pay_price'] . '卡座套餐';
                    $msg_title = '您购买的卡套订单，因' . $cancel_reason . '被商家拒绝';

                    break;
                case 3:

                    //散套订单
                    $product = $merchant['title'] . $order_info['pay_price'] . '优惠套餐';
                    $msg_title = '您购买的散套订单，因' . $cancel_reason . '被商家拒绝';

                    break;
                case 0:

                    //散套订单
                    $product = $merchant['title'] . $order_info['pay_price'] . '单品酒水';
                    $msg_title = '您购买的散套订单，因' . $cancel_reason . '被商家拒绝';

                    break;
            }

            //给用户发送拒绝短信
            $message = [
                '#name#' => '用户',
                '#product#' => $product,
                '#reason#' => $cancel_reason
            ];
            $yunpian->tplSingleSend($order_info['contacts_tel'], $template_id, $message);

            //给用户推送消息
            JPushNotify::toAliasNotify($order_info['member_id'], [
                'alert' => $msg_title,
                'title' => '订单已拒绝',
                'extras' => [
                    'msg_type' => 'order',  //system order bar
                    'title' => '订单已拒绝',
                    'content' => $msg_title,
                    'icon' => C('MEMBER_API_URL') . '/Public/images/message/refusetoorder.png',
                    'order_id' => $order_info['id']
                ]
            ]);
        }
    }
}