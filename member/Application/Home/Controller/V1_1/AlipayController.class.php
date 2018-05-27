<?php
/**
 * FileName: AlipayController.class.php
 * User: Comos
 * Date: 2018/2/22 18:12
 */

namespace Home\Controller\V1_1;


use Home\Model\V1_1\BarMemberModel;
use Home\Model\V1_1\MemberCapitalModel;
use Home\Model\V1_1\OrderModel;
use Org\Util\Tools;
use Org\Util\Wechat;
use Org\Util\YunpianSms;
use Think\Controller;
use Think\Log;

class AlipayController extends Controller
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
     * 支付种类pay_type 1订单消费 2充值
     * 客户端类型client ios,xcx,android
     * @param $alipay_data array 支付回调数据
     * @param $attach array 附加数据
     */
    public function publicAlipayNotify($alipay_data, $attach)
    {
        //订单消费
        if ($attach['pay_type'] == 1) {
            $this->consumerHandle($alipay_data, $attach);
        }

        //钱包余额充值
        if ($attach['pay_type'] == 2) {
            $this->rechargeHandle($alipay_data, $attach);
        }
    }

    /**
     * 订单消费数据处理方法
     * 购买类型 buy_type 1正常下单支付 2正常续酒下单支付 3拼吧支付 4拼吧续酒支付
     * @param $alipay_data
     * @param $attach
     */
    private function consumerHandle($alipay_data, $attach)
    {
        //判断订单类型 正常下单支付与正常续酒下单支付
        if (in_array($attach['buy_type'], [1, 2])) {
            $this->buyWineOrderHandle($alipay_data, $attach);
        }

        //拼吧支付与拼吧续酒支付
        if (in_array($attach['buy_type'], [3, 4])) {
            $this->buyBarOrderHandle($alipay_data, $attach);
        }
    }

    /**
     * 正常下单支付与正常续酒下单支付回调数据处理
     * @param $alipay_data
     * @param $attach
     */
    private function buyWineOrderHandle($alipay_data, $attach)
    {
        $pay_data = [
            'appid' => $alipay_data['app_id'],
            'mch_id' => $alipay_data['seller_id'],
            'trade_type' => 'APP',
            'receipt_fee' => $alipay_data['receipt_amount'],
            'trade_no' => $alipay_data['trade_no'],
            'end_time' => strtotime($alipay_data['gmt_payment']),
            'payment' => 3,
        ];

        //记录微信支付结果日志
        Tools::writeLog($alipay_data, C('LOG_PATH'), '', 'alipay_app');

        //线上订单支付处理方法
        $orderModel = new OrderModel();
        if ($order = $orderModel->orderPayResultProcessing($pay_data, $attach)) {
            $str = 'success';
        } else {
            $str = 'fail';
        }

        //推送socket消息给商户端用户
        $orderModel->pushMsgAndSms($this->_ypsms, $this->_smsTpl);

        //处理结果通知
        exit($str);
    }

    /**
     * 钱包充值数据处理方法
     * @param $alipay_data
     * @param $attach
     */
    private function rechargeHandle($alipay_data, $attach)
    {
        $pay_data = [
            'appid' => $alipay_data['app_id'],
            'mch_id' => $alipay_data['seller_id'],
            'trade_type' => 'APP',
            'receipt_fee' => $alipay_data['receipt_amount'],
            'trade_no' => $alipay_data['trade_no'],
            'end_time' => strtotime($alipay_data['gmt_payment']),
            'payment' => 3,
        ];

        $MemberCapitalModel = new MemberCapitalModel();
        //查询订单状态
        if ($MemberCapitalModel->findOrderStatus($attach)) exit('success');

        //记录支付宝支付结果日志
        Tools::writeLog($alipay_data, C('LOG_PATH'), '', 'alipay_recharge_notify');

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
        $receipt_fee = (string)$pay_data['receipt_fee'];

        //获取充值金额与赠送金额
        $give_money = $recharge_limit[$receipt_fee];
        $count = M('member_order')->where(['member_id' => $attach['member_id'], 'recharge_money' => $recharge_money])->count();
        if ($count > 0) {
            $give_money = 0;
        }

        //写入充值相关数据
        if ($MemberCapitalModel->createRechargePayData($pay_data, $attach, $give_money)) {
            /*try {
                $tel = M('member')->where(['id' => $attach['member_id']])->getField('tel');
                if ($tel) {
                    $this->_ypsms->tplSingleSend(
                        C('ADMIN_PHONE'),
                        $this->_smsTpl['chongzhiok'],
                        [
                            '#time#' => date('Y年m月d日 H:i:s'),
                            '#recharge_money#' => $recharge_money,
                            '#give_money#' => '首充赠送' . $give_money . '元，',
                            '#total_money#' => $MemberCapitalModel->totalMoney,
                        ]
                    );
                }
            } catch (\Exception $exception) {
                Log::write($exception);
            }*/
            exit('success');
        } else {
            //调用发短信的接口给后台管理人员发送报警短信  【空瓶子】用户#name#在进行#operate#时发生#wrong#异常；订单编号: #code#；请及时查看并处理异常！
            $tpl_value = [
                '#name#' => 'ID为' . $attach['member_id'],
                '#operate#' => '余额充值',
                '#wrong#' => '支付宝支付',
                '#code#' => $attach['order_no'],
            ];
            $this->_ypsms->tplSingleSend(C('ADMIN_PHONE'), $this->_smsTpl['baojing'], $tpl_value);
            exit('fail');
        }
    }

    private function buyBarOrderHandle($alipay_data, $attach)
    {
        $pay_data = [
            'appid' => $alipay_data['app_id'],
            'mch_id' => $alipay_data['seller_id'],
            'trade_type' => 'APP',
            'receipt_fee' => $alipay_data['receipt_amount'],
            'trade_no' => $alipay_data['trade_no'],
            'end_time' => strtotime($alipay_data['gmt_payment']),
            'payment' => 3,
        ];

        //记录微信支付结果日志
        Tools::writeLog($alipay_data, C('LOG_PATH'), '', 'alipay_app');

        $barOrderModel = new BarMemberModel();
        $barMemberStatus = M('bar_member')->field('pay_status')->where(['id' => $attach['order_id']])->find();
        if (!$barMemberStatus) {
            Log::write(M('bar_member')->getLastSql());
            $note = ['return_code' => 'FAIL', 'return_msg' => 'FAIL TREATMENT'];
            Tools::responseXml(false, false, $note);
        }

        //如果订单已处理
        if (!in_array($barMemberStatus['pay_status'], [0, 1])) {
            exit('success');
        }

        //执行回调操作
        $bar_order_rs = $barOrderModel->barOrderHandle($pay_data, $attach);
        Log::write('pay_result: ' . json_encode($bar_order_rs) . '::::' . $barOrderModel->getError());

        if ($bar_order_rs) {
            $note = 'success';
            $resultData = $barOrderModel->callbackData;
            if (is_array($resultData)) {
                $barOrderModel->employee_socket_message($resultData, $attach['buy_type']);
            }

        } else {
            $note = 'fail';
        }

        exit($note);
    }
}