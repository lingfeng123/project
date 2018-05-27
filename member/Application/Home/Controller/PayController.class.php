<?php
/**
 * FileName: PayController.class.php
 * User: Comos
 * Date: 2018/2/22 17:41
 */

namespace Home\Controller;


use Home\Controller\V1_1\WxpayController;
use Home\Controller\V1_1\AlipayController;
use Org\Util\Tools;
use Think\Controller;
use Think\Log;

//支付回调不能进行请求方式校验,否则接收不到数据
class PayController extends Controller
{
    /**
     * 付款类型: 充值1 正常购买2  正常续酒3  拼吧购买4 拼吧续酒5 ....
     * 订单类型: 散套 卡套 单品 卡座
     * 微信支付类型: xcx 小程序支付 app APP微信支付
     */


    /**
     * 微信小程序支付回调地址
     */
    public function wxXcxNotify()
    {
        $this->wxpayJudge('xcx');
    }

    /**
     * 微信APP端支付回调地址
     */
    public function wxAppNotify()
    {
        $this->wxpayJudge('app');
    }

    /**
     * 微信支付判断处理
     * @param $wxpay_type_name string 支付终端类型
     */
    private function wxpayJudge($wxpay_type_name)
    {
        $response = file_get_contents("php://input");
        $wxpay_data = Tools::xmlToArray($response);

        //判断支付商户号配置信息
        $config = [];
        $logName = '';
        if ($wxpay_type_name == 'xcx') {
            $config = C('WXPAY_OPTION');
            $logName = 'wxpay_xcx_notify';
        } elseif ($wxpay_type_name == 'app') {
            $config = C('APP_WXPAY_OPTION');
            $logName = 'wxpay_app_notify';
        }
        //签名要使用的数据
        $sign_arr = $wxpay_data;

        unset($sign_arr['sign']);
        //获取签名字符串
        $signStr = Tools::getWxPaySign($sign_arr, $config['KEY']);
        //写入回调日志
        Tools::writeLog($wxpay_data, C('LOG_PATH'), 'ERR', $logName);

        //验证接口签名
        if ($signStr != $wxpay_data['sign']) {
            Tools::responseXml('FAIL', 'FAILURE OF INTERFACE SIGNATURE VERIFICATION');
        }

        //附加数据
        parse_str($wxpay_data['attach'], $attach);

        //根据接口版本处理不同回调数据
        switch ($attach['version']) {
            case 'v1.1':
                //执行1.1 & 1.2 & 2.0的数据处理
                $wxpayController = new WxpayController();
                $wxpayController->publicWxpayNotify($wxpay_data, $attach);
                break;
            default:
                //1.0的回调处理
                if ($wxpay_type_name == 'xcx') {
                    $firstController = new \Home\Controller\WxpayController();
                    $firstController->wxpayNotify();
                }
        }
    }


    /**
     * 支付宝支付回调地址
     */
    public function alipayNotify()
    {
        //接收回调数据
        $alipay_data = $_POST;

        //载入sdk
        vendor('alipay.AopClient');

        //加载配置
        $alipayConfig = C('alipay');
        $aop = new \AopClient;
        $aop->alipayrsaPublicKey = $alipayConfig['alipay_rsa_public_key'];
        //校验签名
        $flag = $aop->rsaCheckV1($alipay_data, NULL, "RSA2");
        if ($flag == true) {

            //1,校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
            if ($alipay_data['seller_id'] != $alipayConfig['seller_id']) {
                exit('fail: seller_id different');
            }

            //2,验证app_id是否为该商户本身。
            if ($alipay_data['app_id'] != $alipayConfig['app_id']) {
                exit('fail: app_id different');
            }

            //3,只有交易通知状态为TRADE_SUCCESS或TRADE_FINISHED时，支付宝才会认定为买家付款成功。
            if ($alipay_data['trade_status'] != 'TRADE_SUCCESS' && $alipay_data['trade_status'] != 'TRADE_FINISHED') {
                exit('fail: The transaction has not been completed');
            }

            //1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号
            //2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额）

            //解析附加数据
            $passback_params = urldecode($alipay_data['passback_params']);
            parse_str($passback_params, $attach);

            //根据接口版本处理不同回调数据
            try {
                switch ($attach['version']) {
                    case 'v1.1':
                        //执行1.1 & 1.2 & 2.0的数据处理
                        $alipayController = new AlipayController();
                        $alipayController->publicAlipayNotify($alipay_data, $attach);
                        break;
                }
            } catch (\Exception $exception) {
                Log::write($exception, Log::ERR);
            }

        } else {
            //验证签名失败,写入错误日志
            Log::write('validate sign fail: ' . json_encode($alipay_data), Log::ERR);
            exit('fail: validate sign fail');
        }
    }

}