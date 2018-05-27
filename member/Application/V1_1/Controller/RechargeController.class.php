<?php
/**
 * FileName: RechargeController.class.php
 * User: Comos
 * Date: 2018/3/7 9:41
 */

namespace V1_1\Controller;

@date_default_timezone_set('PRC');

use Org\Util\JsSdkPay;
use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\String;
use Org\Util\Tools;

class RechargeController extends BaseController
{
    private $_payment = [1, 2, 3];      //支付类型payment 1余额支付 2微信支付 3支付宝 4银联支付
    private $_buyType = [1, 2, 3, 4];   //购买类型buy_type 1正常下单支付 2正常续酒下单支付 3拼吧支付 4拼吧续酒支付
    private $_payType = [1, 2];         //支付种类pay_type 1订单消费 2充值

    /**
     * 钱包充值支付下单
     */
    public function payment()
    {
        $version = I('post.version', '');           //接口版本号
        $client = I('post.client', '');
        $member_id = I('post.member_id', '');       //用户ID
        $pay_money = I('post.pay_money', '');       //支付金额
        $payment = I('post.payment', '');            //支付类型 1余额支付 2微信支付 3支付宝 4银联支付
        $trade_type = I('post.trade_type', '');     //微信发起支付的类型 JSAPI  APP
        $openid = I('post.openid', '');             //小程序的openid

        //校验传入参数
        if (!is_numeric($member_id) || !in_array($payment, $this->_payment) || !$version) {
            Response::error(ReturnCode::PARAM_INVALID, '请求参数不合法');
        }

        //验证终端
        if (!in_array($client, ['ios', 'android', 'xcx'])) {
            Response::error(ReturnCode::PARAM_INVALID, '请求终端不合法');
        }

        //当为微信支付时, 验证微信支付终端类型
        if ($payment == 2) {
            if (!in_array($trade_type, ['JSAPI', 'APP'])) {
                Response::error(ReturnCode::PARAM_INVALID, '未知支付请求类型');
            }
        }

        //验证支付金额是否相同
        $recharge_limit = C('RECHARGE_LIMIT');
        $moneys = array_keys($recharge_limit);
        if (!in_array($pay_money, $moneys)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '支付金额不合法');
        }

        //组装一个统配数据,各种判断类型集合
        $judgment_set = [
            'member_id' => $member_id,
            'trade_type' => $trade_type,
            'client' => $client,
            'pay_money' => $pay_money,
            'pay_type' => 2,    //附加数据中的支付种类 1订单消费 2充值
            'version' => $version
        ];

        //系统配置订单超时时间
        $order_overtime = C('ORDER_OVERTIME');

        //判断支付类型
        switch ($payment) {
            case 2:

                //获取微信支付超时时间起止时间点
                $time_start = time();
                $time_expire = $time_start + $order_overtime;

                //微信小程序支付
                if ($trade_type == 'JSAPI') {
                    //验证小程序的openID是否传入
                    if (!$openid) Response::error(ReturnCode::PARAM_WRONGFUL, 'openID获取失败,无法发起支付');
                    //调用小程序支付统一下单方法
                    $this->_xcxWechatPayOrder($time_start, $time_expire, $judgment_set, $openid);
                }

                //微信APP支付
                if ($trade_type == 'APP') {
                    //调用APP支付下单方法
                    $this->_appWechatPayOrder($time_start, $time_expire, $judgment_set);
                }

                break;
            case 3:
                //支付宝支付
                $this->_alipayPayOrder($order_overtime, $judgment_set);

                break;
        }
    }

    /**
     * 微信小程序充值发起支付
     * @param $time_start int 订单开始时间
     * @param $time_expire int 订单截止时间
     * @param $judgment_set array 附加数据
     * @param $openid string 用户openid
     */
    private function _xcxWechatPayOrder($time_start, $time_expire, $judgment_set, $openid)
    {
        $config = C('WXPAY_OPTION');

        //附加数据
        $attach = $judgment_set;
        $attach['order_no'] = Tools::create_order_number(9);    //type为9代表充值

        $unifiedorderData = [
            'openid' => $openid,                                              //小程序用户openid
            'appid' => $config['APPID'],                                      //微信分配的小程序ID
            'mch_id' => $config['MCH_ID'],                                    //微信支付分配的商户号
            'nonce_str' => strtoupper(md5(String::randString(20))),      //随机字符串，不长于32位
            'body' => '空瓶子钱包充值' . $judgment_set['pay_money'] . '元',    //商品描述,不能为空
            'attach' => http_build_query($attach),                            //订单附加数据
            'out_trade_no' => $attach['order_no'],                            //商户订单号
            'total_fee' => $judgment_set['pay_money'] * 100,                  //订单总金额，单位为分
            'spbill_create_ip' => $config['IP'],                              //APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。
            'time_start' => date('YmdHis', $time_start),              //订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010
            'time_expire' => date('YmdHis', $time_expire),            //订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010
            'notify_url' => $config['NOTIFY_URL'],                            //微信支付回调地址
            'trade_type' => 'JSAPI',                                          //交易类型
        ];
        $unifiedorderData['sign'] = Tools::getWxPaySign($unifiedorderData, $config['KEY']);

        //将数据转化为xml
        $jssdk = new JsSdkPay();
        //请求微信支付下单接口获取下单结果
        $response = $jssdk->postXmlCurl(Tools::arrayToXml($unifiedorderData), 'https://api.mch.weixin.qq.com/pay/unifiedorder');
        //将返回数据xml->array
        $wxpay_data = Tools::xmlToArray($response);

        //将统一下单的数据记录到日志中
        Tools::writeLog($wxpay_data, C('LOG_PATH'), '', 'wxpay_recharge');

        //判断微信下单的结果
        if ($wxpay_data['return_code'] == "SUCCESS" && $wxpay_data['result_code'] == 'SUCCESS') {
            $jsapi_data = [
                'appId' => $wxpay_data['appid'],
                'timeStamp' => time(),
                'nonceStr' => $wxpay_data['nonce_str'],
                'package' => "prepay_id=" . $wxpay_data['prepay_id'],
                'signType' => "MD5",
            ];

            $jsapi_data['paySign'] = Tools::getWxPaySign($jsapi_data, $config['KEY']);
            Response::setSuccessMsg('小程序充值下单成功');
            Response::success($jsapi_data, ReturnCode::SUCCESS);
        } else {
            Response::error(ReturnCode::INVALID_REQUEST, '充值下单失败');
        }
    }

    /**
     * 微信APP充值发起支付
     * @param $time_start int 订单开始时间
     * @param $time_expire int 订单截止时间
     * @param $judgment_set array 附加数据
     */
    private function _appWechatPayOrder($time_start, $time_expire, $judgment_set)
    {
        //微信APP支付配置数据
        $config = C('APP_WXPAY_OPTION');

        //附加数据
        $attach = $judgment_set;
        $attach['order_no'] = Tools::create_order_number(9);    //type为9代表充值

        //统一下单需要的数据
        $unifiedorderData = [
            'appid' => $config['APPID'],                                      //微信分配的小程序ID
            'mch_id' => $config['MCH_ID'],                                    //微信支付分配的商户号
            'nonce_str' => strtoupper(md5(String::randString(20))),      //随机字符串，不长于32位
            'body' => '空瓶子钱包充值' . $judgment_set['pay_money'] . '元',    //商品描述,不能为空
            'attach' => http_build_query($attach),                            //订单附加数据
            'out_trade_no' => $attach['order_no'],                            //商户订单号
            'total_fee' => $judgment_set['pay_money'] * 100,                  //订单总金额，单位为分
            'spbill_create_ip' => $config['IP'],                              //APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。
            'time_start' => date('YmdHis', $time_start),              //订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010
            'time_expire' => date('YmdHis', $time_expire),            //订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010
            'notify_url' => $config['NOTIFY_URL'],                            //微信支付回调地址
            'trade_type' => 'APP',                                          //交易类型
        ];

        //获取接口参数签名
        $unifiedorderData['sign'] = Tools::getWxPaySign($unifiedorderData, $config['KEY']);

        //将数据转化为xml
        $jssdk = new JsSdkPay();
        //请求微信支付下单接口获取下单结果
        $response = $jssdk->postXmlCurl($jssdk->arrayToXml($unifiedorderData), 'https://api.mch.weixin.qq.com/pay/unifiedorder');
        //将返回数据xml->array
        $wxpay_data = $jssdk->xmlToArray($response);

        //将统一下单的数据记录到日志中
        Tools::writeLog($wxpay_data, C('LOG_PATH'), '', 'wxpay_recharge');

        //判断微信下单的结果
        if ($wxpay_data['return_code'] == "SUCCESS" && $wxpay_data['result_code'] == 'SUCCESS') {

            $app_data = [
                'appid' => $wxpay_data['appid'],
                'partnerid' => $wxpay_data['mch_id'],
                'timestamp' => time(),
                'noncestr' => $wxpay_data['nonce_str'],
                'prepayid' => $wxpay_data['prepay_id'],
                'package' => "Sign=WXPay",
            ];
            //生成JSAPI签名
            $app_data['sign'] = Tools::getWxPaySign($app_data, $config['KEY']);

            //将数据返回给小程序调起支付
            Response::setSuccessMsg('充值下单成功');
            Response::success($app_data, ReturnCode::SUCCESS);
        } else {
            Response::error(ReturnCode::INVALID_REQUEST, '充值下单失败');
        }
    }

    /**
     * 支付宝
     * @param $time_expire int 订单截止时间
     * @param $judgment_set array 附加数据
     */
    private function _alipayPayOrder($time_expire, $judgment_set)
    {
        //将秒转换为分钟
        $time_expire = $time_expire / 60;

        //载入类文件
        vendor('alipay.AopClient');
        vendor('alipay.request.AlipayTradeAppPayRequest');

        //获取支付宝支付配置信息
        $alipayConfig = C('alipay');

        //附加数据
        $attach = $judgment_set;
        $attach['order_no'] = Tools::create_order_number(9);    //type为9代表充值

        //实例
        $aop = new \AopClient;
        $aop->gatewayUrl = $alipayConfig['gate_way_url'];
        $aop->appId = $alipayConfig['app_id'];
        $aop->rsaPrivateKey = $alipayConfig['rsa_private_key'];
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->signType = $alipayConfig['sign_type'];
        $aop->alipayrsaPublicKey = $alipayConfig['alipay_rsa_public_key'];

        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new \AlipayTradeAppPayRequest();

        //SDK已经封装掉了公共参数，这里只需要传入业务参数
        $cztime = date('Y-m-d H:i:s');
        $bizcontent = [
            'body' => "空瓶子钱包余额充值, " . $cztime . "充值" . $judgment_set['pay_money'] . "元", //对一笔交易的具体描述信息。如果是多种商品，请将商品描述字符串累加传给body。
            'subject' => "空瓶子商品订单-" . $attach['order_no'],                                     //商品的标题/交易标题/订单标题/订单关键字等。
            'out_trade_no' => $attach['order_no'],                                                   //商户网站唯一订单号
            'timeout_express' => $time_expire . 'm',                                               //该笔订单允许的最晚付款时间，逾期将关闭交易。
            'total_amount' => $judgment_set['pay_money'],                                   //订单总金额，单位为元，精确到小数点后两位，取值范围[0.01,100000000]
            'product_code' => "QUICK_MSECURITY_PAY",                                       //销售产品码，商家和支付宝签约的产品码，为固定值QUICK_MSECURITY_PAY
            'passback_params' => urlencode(http_build_query($attach)),                     //公用回传参数，支付宝会在异步通知时将该参数原样返回。本参数必须进行UrlEncode之后才可以发送给支付宝
        ];

        $request->setNotifyUrl($alipayConfig['notify_url']);
        $request->setBizContent(json_encode($bizcontent));

        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);

        Response::setSuccessMsg('支付宝下单成功');
        Response::success(['alipay_trade' => $response]);
    }
}