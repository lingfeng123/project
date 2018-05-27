<?php
/**
 * FileName: WxpayController.class.php
 * User: Comos
 * Date: 2018/3/8 14:59
 */

namespace Home\Controller\V1_1;


use Home\Model\V1_1\BarMemberModel;
use Home\Model\V1_1\BarOrderModel;
use Home\Model\V1_1\OrderModel;
use Home\Model\V1_1\MemberCapitalModel;
use Org\Util\SocketPush;
use Org\Util\Tools;
use Org\Util\Wechat;
use Org\Util\YunpianSms;
use Think\Controller;
use Think\Log;

class WxpayController extends Controller
{

    private $wechat;
    private $_ypsms;    //云片短信实例
    private $_smsTpl;   //短信模板数据

    public function _initialize()
    {
        //微信实例
        $this->wechat = new Wechat(C('WECHAT_OPTION'));
        //实例云片短信类
        $this->_ypsms = new YunpianSms();
        //短信模板数据
        $this->_smsTpl = C('YUNPIAN');
    }

    /**
     * 支付回调公共处理
     * 购买类型buy_type 1正常下单支付 2正常续酒下单支付 3拼吧支付 4拼吧续酒支付
     * 支付种类pay_type 1订单消费 2充值
     * 客户端类型client ios,xcx,android
     * @param $wxpay_data array 支付回调数据
     * @param $attach array 支付回调的中的附加数据
     */
    public function publicWxpayNotify($wxpay_data, $attach)
    {
        //@@通讯结果为失败返回数据通知微信服务器 && 业务结果为fail,返回失败信息给微信服务器
        if ($wxpay_data['return_code'] == 'FAIL' || $wxpay_data['result_code'] == 'FAIL') {
            //记录微信支付结果日志
            Tools::writeLog($wxpay_data, C('LOG_PATH'), 'wxpay_xcx');
            Tools::responseXml('FAIL', 'RECEIVE NOTIFICATION SUCCESSFUL AND VERIFY FAILURE');
        }

        /*
         * 通知结果与业务结果都为SUCCESS时,处理业务逻辑
         */
        if ($wxpay_data['result_code'] == 'SUCCESS' && $wxpay_data['return_code'] == 'SUCCESS') {
            //以下是业务处理通知接收成功的处理代码
            //1订单消费 2充值 :::醒目标识
            switch ($attach['pay_type']) {
                case 1:
                    //订单消费
                    $this->consumerHandle($wxpay_data, $attach);
                    break;
                case 2:
                    //用户充值支付
                    $this->rechargeHandle($wxpay_data, $attach);

                    break;
                default:
                    //记录微信支付结果日志
                    Tools::writeLog($wxpay_data, C('LOG_PATH'), '', 'wxpay_recharge_notify');
                    //处理结果通知
                    Tools::responseXml('FAIL', 'FAIL TREATMENT');

            }
        }
    }

    /**
     * 订单消费数据处理方法
     * 购买类型 buy_type 1正常下单支付 2正常续酒下单支付 3拼吧支付 4拼吧续酒支付
     * @param $wxpay_data
     * @param $attach
     */
    private function consumerHandle($wxpay_data, $attach)
    {
        //判断订单类型 正常下单支付与正常续酒下单支付
        if (in_array($attach['buy_type'], [1, 2])) {
            $this->buyWineOrderHandle($wxpay_data, $attach);
        }

        //拼吧支付与拼吧续酒支付
        if (in_array($attach['buy_type'], [3, 4])) {
            $this->buyBarOrderHandle($wxpay_data, $attach);
        }
    }

    /**
     * 正常下单支付与正常续酒下单支付回调数据处理
     * @param $wxpay_data
     * @param $attach
     */
    private function buyWineOrderHandle($wxpay_data, $attach)
    {
        //转换yyyymmddhhiiss为时间戳;
        $time_end = Tools::strToTimestamp($wxpay_data['time_end']);
        $pay_data = [
            'appid' => $wxpay_data['appid'],
            'mch_id' => $wxpay_data['mch_id'],
            'trade_type' => $wxpay_data['trade_type'],
            'receipt_fee' => $wxpay_data['total_fee'] / 100,
            'trade_no' => $wxpay_data['transaction_id'],
            'end_time' => $time_end,
            'payment' => 2,
        ];

        //记录微信支付结果日志
        Tools::writeLog($wxpay_data, C('LOG_PATH'), '', 'wxpay_xcx');

        //线上订单支付处理方法
        $orderModel = new OrderModel();
        if ($order = $orderModel->orderPayResultProcessing($pay_data, $attach)) {
            $note = ['return_code' => 'SUCCESS', 'return_msg' => 'OK'];

            //推送socket消息给商户端用户
            $orderModel->pushMsgAndSms($this->_ypsms, $this->_smsTpl);

        } else {
            $note = ['return_code' => 'FAIL', 'return_msg' => 'FAIL TREATMENT'];
        }

        //处理结果通知
        Tools::responseXml(false, false, $note);
    }

    /**
     * 钱包充值数据处理方法
     * @param $wxpay_data
     * @param $attach
     */
    private function rechargeHandle($wxpay_data, $attach)
    {
        //转换yyyymmddhhiiss为时间戳;
        $time_end = Tools::strToTimestamp($wxpay_data['time_end']);
        $pay_data = [
            'appid' => $wxpay_data['appid'],
            'mch_id' => $wxpay_data['mch_id'],
            'trade_type' => $wxpay_data['trade_type'],
            'receipt_fee' => $wxpay_data['total_fee'] / 100,
            'trade_no' => $wxpay_data['transaction_id'],
            'end_time' => $time_end,
            'payment' => 2,
        ];

        $MemberCapitalModel = new MemberCapitalModel();
        //查询订单状态
        if ($MemberCapitalModel->findOrderStatus($attach)) {
            Tools::responseXml('SUCCESS', 'OK');
        }

        //记录微信支付结果日志
        Tools::writeLog($wxpay_data, C('LOG_PATH'), '', 'wxpay_recharge_notify');

        //用户充值支付处理方法
        $recharge_limit = C('RECHARGE_LIMIT');

        //总充值金额大于阀值关闭赠送
        $give_money = M('member_capital')->sum('give_money');
        if ($give_money >= C('SYS_MAX_GIVE_MONEY')) {
            $recharge_limit = array_map(function () {
                return 0;
            }, $recharge_limit);
        }

        //充值金额转换为元
        $recharge_money = $pay_data['receipt_fee'];
        $pay_data['receipt_fee'] = Tools::formatMoney($recharge_money);
        $total_fee = (string)$pay_data['receipt_fee'];

        //获取充值金额与赠送金额
        $give_money = $recharge_limit[$total_fee];

        $count = M('member_order')->where(['member_id' => $attach['member_id'], 'recharge_money' => $recharge_money])->count();
        if ($count > 0) {
            $give_money = 0;
        }

        //写入充值相关数据
        $rs = $MemberCapitalModel->createRechargePayData($pay_data, $attach, $give_money);
        if ($rs) {
            $note = ['return_code' => 'SUCCESS', 'return_msg' => 'OK'];
        } else {
            $note = ['return_code' => 'FAIL', 'return_msg' => 'FAIL TREATMENT'];

            //调用发短信的接口给后台管理人员发送报警短信  【空瓶子】用户#name#在进行#operate#时发生#wrong#异常；订单编号: #code#；请及时查看并处理异常！
            $tpl_value = [
                '#name#' => 'ID为' . $attach['member_id'],
                '#operate#' => '余额充值',
                '#wrong#' => '微信支付',
                '#code#' => $attach['order_no'],
            ];
            $this->_ypsms->tplSingleSend(C('ADMIN_PHONE'), $this->_smsTpl['baojing'], $tpl_value);
        }

        //处理结果通知
        Tools::responseXml('', '', $note);
    }

    /**
     * 拼吧/拼吧续酒微信支付回调
     * @param $wxpay_data
     * @param $attach
     */
    private function buyBarOrderHandle($wxpay_data, $attach)
    {
        $time_end = Tools::strToTimestamp($wxpay_data['time_end']);
        $pay_data = [
            'appid' => $wxpay_data['appid'],
            'mch_id' => $wxpay_data['mch_id'],
            'trade_type' => $wxpay_data['trade_type'],
            'receipt_fee' => $wxpay_data['total_fee'] / 100,
            'trade_no' => $wxpay_data['transaction_id'],
            'end_time' => $time_end,
            'payment' => 2,
        ];

        $barOrderModel = new BarMemberModel();
        $barMemberStatus = M('bar_member')->field('pay_status')->where(['id' => $attach['order_id']])->find();
        if (!$barMemberStatus) {
            Log::write(M('bar_member')->getLastSql());
            $note = ['return_code' => 'FAIL', 'return_msg' => 'FAIL TREATMENT'];
            Tools::responseXml(false, false, $note);
        }

        //如果订单已处理
        if (!in_array($barMemberStatus['pay_status'], [0, 1])) {

            $note = ['return_code' => 'SUCCESS', 'return_msg' => 'OK'];
            Tools::responseXml(false, false, $note);

        }

        //执行回调操作
        $bar_order_rs = $barOrderModel->barOrderHandle($pay_data, $attach);
        Log::write('pay_result: ' . json_encode($bar_order_rs) . '::::' . $barOrderModel->getError());

        if ($bar_order_rs) {
            $note = ['return_code' => 'SUCCESS', 'return_msg' => 'OK'];
            $resultData = $barOrderModel->callbackData;
            if (is_array($resultData)) {
                $barOrderModel->employee_socket_message($resultData, $attach['buy_type']);
            }

        } else {
            $note = ['return_code' => 'FAIL', 'return_msg' => 'FAIL TREATMENT'];
        }

        //返回处理结果通知
        Tools::responseXml(false, false, $note);
    }
}