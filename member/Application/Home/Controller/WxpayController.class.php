<?php

namespace Home\Controller;

use Org\Util\JsSdkPay;
use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\Sms;
use Org\Util\SocketPush;
use Org\Util\String;
use Org\Util\Tools;
use Org\Util\Wechat;
use Org\Util\AuthSign;
use Think\Controller;
use Think\Log;

class WxpayController extends Controller
{
    private $log;
    private $wechat;

    /**
     * redis连接初始化与日志记录器初始化
     * SocketController constructor.
     */
    public function _initialize()
    {
        //引入log4php类
        vendor('log4php.Logger');
        //加载log4php配置文件
        \Logger::configure(CONF_PATH . 'log4php.xml');
        //获取记录器
        $this->log = \Logger::getLogger('WxPay');
        //微信实例
        $this->wechat = new Wechat(C('WECHAT_OPTION'));
    }

    /**
     * 微信余额充值
     */
    public function payment()
    {
        //验证请求方式
        if (!IS_POST) {
            Response::error(ReturnCode::INVALID_REQUEST, '请求方式不被允许');
        }

        //验证签名MD5字符串
        $sign = I('post.sign', '');
        //时间戳
        $timestamp = I('post.timestamp', '');
        $rs = AuthSign::getAuthSign($sign, $timestamp);
        if (!$rs) {
            Response::error(ReturnCode::AUTH_ERROR, '签名校验失败');
        }

        //::::业务代码执行:::

        @date_default_timezone_set('PRC');
        $member_id = I('post.member_id', '');
        $pay_money = I('post.pay_money', '');
        $openid = I('post.openid', '');

        if (!($pay_money > 0) || !is_numeric($member_id) || empty($openid)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //微信支付配置数据
        $config = C('WXPAY_OPTION');

        //附加数据
        $attach['member_id'] = $member_id;
        $attach['pay_money'] = $pay_money;
        $attach['order_no'] = Tools::create_order_number(9);    //type为9代表充值
        $attach['pay_type'] = 2;   //附加数据中的支付种类    1订单消费 2充值

        $time_start = time();     //
        $time_expire = $time_start + 5 * 60;

        //验证支付金额是否相同
        $recharge_limit = C('RECHARGE_LIMIT');
        $moneys = array_keys($recharge_limit);
        if (!in_array($pay_money, $moneys)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '支付金额不合法');
        }

        //统一下单需要的数据
        $data['openid'] = $openid;     //小程序用户openid
        $data['appid'] = $config['APPID'];                                      //微信分配的小程序ID
        $data['mch_id'] = $config['MCH_ID'];                                    //微信支付分配的商户号
        $data['nonce_str'] = strtoupper(md5(String::randString(20)));       //随机字符串，不长于32位
        $data['body'] = '空瓶子钱包充值' . $pay_money . '元';                                              //商品描述,不能为空
        $data['attach'] = json_encode($attach);                                 //订单附加数据
        $data['out_trade_no'] = $attach['order_no'];                        //商户订单号
        $data['total_fee'] = $pay_money * 100;                    //订单总金额，单位为分
        $data['spbill_create_ip'] = $config['IP'];                              //APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。
        $data['time_start'] = date('YmdHis', $time_start);               //订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010
        $data['time_expire'] = date('YmdHis', $time_expire);             //订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010
        $data['notify_url'] = $config['NOTIFY_URL'];  //微信支付回调地址
        $data['trade_type'] = 'JSAPI';  //交易类型

        ksort($data);   //将参数以字典序排列
        $sign_str = urldecode(http_build_query($data)); //生成字典序字符串
        $stringSignTemp = $sign_str . '&key=' . $config['KEY'];
        $data['sign'] = strtoupper(md5($stringSignTemp));   //生成微信支付接口签名

        //将数据转化为xml
        $jssdk = new JsSdkPay();
        $xml = $jssdk->arrayToXml($data);   //将数组转化成xml
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $response = $jssdk->postXmlCurl($xml, $url);

        //微信服务器返回的数据
        $wxpay_data = $jssdk->xmlToArray($response);

        //将统一下单的数据记录到日志中
        $file_name = C('LOG_PATH') . 'wxpay_unifiedorder_' . date('Y_m_d') . '.log';
        Log::write('余额充值微信支付下单||' . date('Y-m-d H:i:s') . '||' . json_encode($data), 'INFO', '', $file_name);
        Log::write('余额充值微信下单结果||' . date('Y-m-d H:i:s') . '||' . json_encode($wxpay_data), 'INFO', '', $file_name);

        //判断微信下单的结果
        if ($wxpay_data['return_code'] == "SUCCESS" && $wxpay_data['result_code'] == 'SUCCESS') {
            $jsapi_data['appId'] = $wxpay_data['appid'];
            $jsapi_data['timeStamp'] = time();
            $jsapi_data['nonceStr'] = $wxpay_data['nonce_str'];
            $jsapi_data['package'] = "prepay_id=" . $wxpay_data['prepay_id'];
            $jsapi_data['signType'] = "MD5";

            //生成JSAPI签名
            ksort($jsapi_data);
            $paySign = urldecode(http_build_query($jsapi_data));
            $paySign = md5($paySign . '&key=' . $config['KEY']);
            $jsapi_data['paySign'] = strtoupper($paySign);

            //将数据返回给小程序调起支付
            Response::success($jsapi_data, ReturnCode::SUCCESS);
        } else {
            Response::error(ReturnCode::INVALID_REQUEST, '微信支付下单失败');
        }
    }


    /**
     * 微信回调函数
     * @return bool
     */
    public function wxpayNotify()
    {
        $response = file_get_contents("php://input");

        $jssdk = new JsSdkPay();
        $wxpay_data = $jssdk->xmlToArray($response);

        //微信支付配置数据
        $config = C('WXPAY_OPTION');
        $sign_arr = $wxpay_data;
        unset($sign_arr['sign']);

        ksort($sign_arr);   //将参数以字典序排列
        $sign_str = urldecode(http_build_query($sign_arr)); //生成字典序字符串
        $stringSignTemp = $sign_str . '&key=' . $config['KEY'];
        $local_sign = strtoupper(md5($stringSignTemp));

        //file_put_contents('./b.txt', $local_sign . '||||' . $wxpay_data['sign'], FILE_APPEND);

        //验证接口签名
        if ($local_sign != $wxpay_data['sign']) {
            $reslut = $jssdk->arrayToXml([
                'return_code' => 'FAIL',
                'return_msg' => 'FAILURE OF INTERFACE SIGNATURE VERIFICATION',
            ]);
            header("Content-type:text/xml");
            exit($reslut);
        }

        //通讯结果为失败返回数据通知微信服务器 && 业务结果为fail,返回失败信息给微信服务器
        if ($wxpay_data['return_code'] == 'FAIL' || $wxpay_data['result_code'] == 'FAIL') {
            $reslut = $jssdk->arrayToXml([
                'return_code' => 'FAIL',
                'return_msg' => 'RECEIVE NOTIFICATION SUCCESSFUL AND VERIFY FAILURE',
            ]);

            //记录微信支付结果日志
            $file_name = C('LOG_PATH') . 'wxpay_notify_' . date('Y_m_d') . '.log';
            Log::write('余额充值微信支付下单||' . date('Y-m-d H:i:s') . '||' . json_encode($wxpay_data), 'INFO', '', $file_name);

            header("Content-type:text/xml");
            exit($reslut);
        }

        //通知结果与业务结果都为SUCCESS时,处理业务逻辑
        if ($wxpay_data['result_code'] == 'SUCCESS' && $wxpay_data['return_code'] == 'SUCCESS') {

            //支付时发送的附加数据
            $attach = json_decode($wxpay_data['attach'], true);

            //以下是业务处理通知接收成功的处理代码
            //TODO::1订单消费 2充值 :::醒目标识
            if ($attach['pay_type'] == 1) {
                //查询订单数据是否存在
                $order = D('order')->where(['order_no' => $attach['order_no']])->find();
                if (!$order) {
                    $reslut = $jssdk->arrayToXml(['return_code' => 'SUCCESS', 'return_msg' => 'THIS ORDER NOT EXESITE']);
                    header("Content-type:text/xml");
                    exit($reslut);
                }

                //判断是否已处理订单状态, 满足订单状态的状态不进行二次处理,直接返回已处理
                $status_array = [2, 3, 4, 5, 6, 7];
                if (in_array($order['status'], $status_array)) {
                    $note['return_code'] = 'SUCCESS';
                    $note['return_code'] = 'OK';
                    $reslut = $jssdk->arrayToXml($note);
                    header("Content-type:text/xml");
                    exit($reslut);
                }

                //::::::::::::::记录微信支付结果日志
                $file_name = C('LOG_PATH') . 'wxpay_notify_' . date('Y_m_d') . '.log';
                Log::write('微信支付消费订单||' . date('Y-m-d H:i:s') . '||' . json_encode($wxpay_data), 'INFO', '', $file_name);

                //线上订单支付处理方法
                $rs = $this->_onlineOrderPayResultProcessing($wxpay_data, $attach);
                if ($rs) {
                    $note['return_code'] = 'SUCCESS';
                    $note['return_code'] = 'OK';
                } else {
                    $note['return_code'] = 'FAIL';
                    $note['return_code'] = 'FAIL TREATMENT';
                }

                //向预订部推送socket消息
                $this->_pushSocketMessage($order, $attach);

                //处理结果通知
                $reslut = $jssdk->arrayToXml($note);
                header("Content-type:text/xml");
                exit($reslut);

            } elseif ($attach['pay_type'] == 2) {
                //查询订单状态
                $recharge_order = M('member_order')->where(['order_no' => $attach['order_no']])->find();
                if ($recharge_order && $recharge_order['status'] == 1) {
                    $note['return_code'] = 'SUCCESS';
                    $note['return_code'] = 'OK';
                    $reslut = $jssdk->arrayToXml($note);
                    header("Content-type:text/xml");
                    exit($reslut);
                }

                //::::::::::::::记录微信支付结果日志
                $file_name = C('LOG_PATH') . 'wxpay_notify_' . date('Y_m_d') . '.log';
                Log::write('充值微信支付下单||' . date('Y-m-d H:i:s') . '||' . json_encode($wxpay_data), 'INFO', '', $file_name);

                //用户充值支付处理方法
                $rs = $this->_userRechargePayResultProcessing($wxpay_data, $attach);
                if ($rs) {
                    $note['return_code'] = 'SUCCESS';
                    $note['return_code'] = 'OK';
                } else {
                    $note['return_code'] = 'FAIL';
                    $note['return_code'] = 'FAIL TREATMENT';
                }

                //处理结果通知
                $reslut = $jssdk->arrayToXml($note);
                header("Content-type:text/xml");
                exit($reslut);

            } else {

                //::::::::::::::记录微信支付结果日志
                $file_name = C('LOG_PATH') . 'wxpay_notify_' . date('Y_m_d') . '.log';
                Log::write('微信支付下单||' . date('Y-m-d H:i:s') . '||' . json_encode($wxpay_data), 'INFO', '', $file_name);

                //pay_type不等于1和2时
                $note['return_code'] = 'FAIL';
                $note['return_code'] = 'FAIL TREATMENT';

                //处理结果通知
                $reslut = $jssdk->arrayToXml($note);
                header("Content-type:text/xml");
                exit($reslut);
            }

        }
    }

    /**
     * 线上订单支付结果处理
     * @param $wxpay_data
     * @param $attach
     */
    private function _onlineOrderPayResultProcessing($wxpay_data, $attach)
    {
        //查询订单数据
        $fields = "
        api_merchant.title as merchant_name,
        api_merchant.begin_time,
        api_merchant.tel as merchant_tel,
        api_merchant.begin_time,
        api_merchant.end_time,
        ";
        $fields .= "
        api_order.id,
        api_order.order_no,
        api_order.merchant_id,
        api_order.member_id,
        api_order.contacts_realname,
        api_order.contacts_tel,
        api_order.contacts_sex,
        api_order.total_price,
        api_order.pay_price,
        api_order.discount_money,
        api_order.status,
        api_order.order_type,
        api_order.payment,
        api_order.arrives_time,
        api_order.incr_time,
        api_order.created_time,
        api_order.employee_id,
        api_order.employee_realname,
        api_order.employee_tel,
        api_order.updated_time,
        api_order.relation_order_no,
        ";
        $fields .= "
        api_order_seat.goods_seat_id,
        api_order_seat.seat_number,
        ";
        $fields .= "
        api_order_pack.goods_pack_id,
        api_order_pack.title as pack_title
        ";
        $order_info = M('order')->field($fields)
            ->join('api_order_pack ON api_order_pack.order_no = api_order.order_no', 'left')
            ->join('api_order_seat ON api_order_seat.order_no = api_order.order_no', 'left')
            ->join('api_merchant ON api_order.merchant_id = api_merchant.id', 'left')
            ->where(['api_order.order_no' => $attach['order_no']])
            ->find();

        $order = [];
        foreach ($order_info as $key => $value) {
            if ($value == null) {
                $value = '';
            }
            $order[$key] = $value;
        }
        $order_info = $order;
        unset($order);

        //判断金额是否相等
        /*$total_fee = (string)($wxpay_data['total_fee'] / 100);
        $pay_price = (string)$order_info['pay_price'];
        if ($total_fee != $pay_price) {
            $this->log->info('支付金额与订单金额不一致' . $attach['order_no']);
            return true;
        }*/
        $orderModel=D('order');
        //修改添加数据
        $rs = $orderModel->updateOrderStatus($order_info, $wxpay_data, $attach);
        if ($rs === false) {
            $this->payerror_log($order_info,$wxpay_data,$orderModel);
            return false;
        }
        return true;
    }

    /**
     * 用户充值支付结果处理
     * @param $wxpay_data
     * @param $attach
     */
    private function _userRechargePayResultProcessing($wxpay_data, $attach)
    {
        //支付赠送金额
        $recharge_limit = C('RECHARGE_LIMIT');
        //充值金额转换为元
        $total_fee = (string)($wxpay_data['total_fee'] / 100);
        $wxpay_data['total_fee'] = $wxpay_data['cash_fee'] = $total_fee;

        //获取充值金额与赠送金额
        $give_money = $recharge_limit[$total_fee];
        $cModel=D('MemberCapital');
        //写入充值相关数据
        $rs=$cModel->createRechargeOrderData($wxpay_data, $attach, $give_money);
        if($rs === false){
            $this->recharge_error_log($attach,$wxpay_data,$cModel);
            return false;
        }
        return true;
    }

    /**
     * 向预订部推送消息通知
     * @param $merchant_id  int 商户ID
     * @param $order_no int 订单号
     */
    private function _pushSocketMessage($order, $attach)
    {
        $orderType=[1=>'卡座',2=>'卡座套餐',3=>'优惠套餐'];
        $sex=[1=>'先生',2=>'女士'];
        //获取短信模板
        $param = C("ALIDAYU.TEMPLATECODE");
        if ($attach['buy_type'] == 1) {
            //获取所有预订部的员工ID
            $yudingbu_permission = C('YUDINGBU_PERMISSION');
            $employee_ids = M('EmployeeJobPermission')->distinct(true)
                ->join("api_employee_andjob ON api_employee_andjob.job_id = api_employee_job_permission.job_id")
                ->where(['permission_id' => ['IN', $yudingbu_permission], 'api_employee_job_permission.merchant_id' => $order['merchant_id']])
                ->getField('employee_id', true);

            if ($employee_ids) {
                try {
                    //向预订部推送socket消息 ::socket::
                    $socketPush = new SocketPush();
                    $socketPush->pushOrderSocketMessage($employee_ids, 3, (string)$order['order_no']);
                } catch (\Exception $exception) {
                    //记录日志
                    Log::write($exception, Log::WARN);
                }

                //给预订部员工发送新支付订单提醒 TODO:: 正式上线需开启
                //顾客${name}购买了${product}，请及时处理订单。
//                try {
//                    foreach ($employee_ids as $employee_id){
//                        //根据employee——id找到对应的电话号码
//                        $tel=M('employee')->where(['id'=>$employee_id])->getField('tel');
//                        $sms_data = [
//                            'name' => $order['contacts_realname'].$sex[$order['contacts_sex']],
//                            'product'=> $orderType[$order['order_type']] .'订单'
//                        ];
//                        //发送短信
//                        if (!$this->_sendNotifySms($tel, $param['SUBEMLPOYEE'], $sms_data)) {
//                            throw new \Exception('推送预订部员工通知消息发送失败, 电话号码为' . $tel);
//                        }
//                    }
//
//                } catch (\Exception $exception) {
//                    Log::write($exception, Log::WARN);
//                }
            }

        } elseif ($attach['buy_type'] == 2) {
            try {
                //向预订部推送socket消息 ::socket::
                $socketPush = new SocketPush();
                $socketPush->pushOrderSocketMessage($order['employee_id'], 3, (string)$order['order_no']);
            } catch (\Exception $exception) {
                //记录日志
                Log::write($exception, Log::WARN);
            }

            //给指定员工发送新支付订单消息
            try {
                $tel=$order['employee_tel'];
                $sms_data = [
                    'name' => $order['contacts_realname'].$sex[$order['contacts_sex']],
                    'product'=> '续酒订单'
                ];
                //发送短信
                if (!$this->_sendNotifySms($tel, $param['SUBEMLPOYEE'], $sms_data)) {
                    throw new \Exception('续酒订单成功员工通知消息发送失败, 电话号码为' . $tel);
                }
            } catch (\Exception $exception) {
                Log::write($exception, Log::WARN);
                Response::success();
            }
        }
    }

    /**
     * 向指定用户发送短信通知
     * @param $tel int 联系人手机号码
     * @param $template_id  string 短信模板编号
     * @param $data array 短信变量内容
     * @return bool
     */
    private function _sendNotifySms($tel, $template_id, array $data)
    {
        //加载阿里大鱼短信扩展
        vendor('autoload');
        //获取阿里大鱼配置项
        $params = C("ALIDAYU");
        $sms = new Sms($params['ACCESSKEYID'], $params['ACCESSKEYSECRET']);

        $outid = date('Ymdhis', time());

        //执行发送短信
        $response = $sms->sendSms(
            $params['SIGNNAME'], // 短信签名
            $template_id, // 短信模板编号
            $tel, // 短信接收者电话号码
            $data, // 短信模板中字段的值
            $outid  //发送短信流水号
        );

        //判断发送短信是否成功
        if (strtoupper($response->Code) !== 'OK') {
            return false;
        }

        //短信发送成功
        return true;
    }


    /**
     * 微信退款回调
     */
    public function wxRefund()
    {
        $response = file_get_contents("php://input");
        //file_put_contents('./a.txt', $response, FILE_APPEND);die;
        $jssdk = new JsSdkPay();
        $arr = $jssdk->xmlToArray($response);

        //微信支付配置数据
        $config = C('WXPAY_OPTION');
        //判断接口数据是否请求成功,如果成功执行以下代码
        if ($arr['return_code'] == 'SUCCESS') {

            //将微信数据解密
            $refund_xml = $jssdk->decryptAES($arr['req_info'], $config['KEY']);

            //解密失败返回结果
            if (!$refund_xml) {
                exit("<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[DECRYPT FAIL]]></return_msg></xml>");
            }
            //将解密后的数据转换为数组
            $refund_arr = $jssdk->xmlToArray($refund_xml);
            $fileName = C('LOG_PATH') . 'refund_' . date('Y-m-d') . '.log';
            Log::write('退款回调||' . date('Y-m-d H:i:s') . '||' . json_encode($refund_arr), Log::INFO, '', $fileName);

            //判断退款是否成功
            if ($refund_arr['refund_status'] == 'SUCCESS') {

                //查询是否存在记录且已是退款成功状态
                $refund_one = M('refund')->where(['transaction_id' => $refund_arr['transaction_id']])->find();
                if ($refund_one['status'] == 2) {
                    //直接返回成功结果
                    exit("<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>");
                }

                //判断退款金额是否与服务端存储的金额相等
                $refund_arr['refund_fee'] = ($refund_arr['refund_fee'] / 100);
                $diff_number = abs($refund_one['refund_fee'] - $refund_arr['refund_fee']);

                //设置默认退款状态为2 成功
                $status = 2;
                if ($diff_number != 0 || $diff_number != '0.00' || $diff_number != 0.00) {
                    $status = 5;    //金额不相等,设置退款状态为5 金额不对等
                }

                //修改退款订单状态
                $rs = M('refund')->where(['status' => 1, 'transaction_id' => $refund_arr['transaction_id']])->save(['status' => $status, 'updated_time' => time()]);
                if ($rs === false) {
                    //执行SQL失败,返回fail重新接收数据并执行
                    $return_code = "FAIL";
                    $return_msg = "update refund fail";
                } else {
                    //执行成功,修改状态完成, 返回success
                    $return_code = "SUCCESS";
                    $return_msg = "OK";
                }

            } else {

                //退款未成功的其他错误类型
                $status = 0;
                switch ($refund_arr['refund_status']) {
                    //SUCCESS-退款成功  CHANGE-退款异常   REFUNDCLOSE—退款关闭
                    case 'CHANGE':
                        $status = 3;
                        break;
                    case 'REFUNDCLOSE':
                        $status = 4;
                        break;
                }

                //根据微信生成的订单号修改退款订单状态
                $rs = M('refund')->where(['status' => 1, 'transaction_id' => $refund_arr['transaction_id']])->save(['status' => $status, 'updated_time' => time()]);
                if ($rs === false) {
                    //执行SQL失败,返回fail重新接收数据并执行
                    $return_code = "FAIL";
                    $return_msg = "update refund fail";
                } else {
                    //执行成功,修改状态完成, 返回success
                    $return_code = "SUCCESS";
                    $return_msg = "OK";
                }


            }

        } else {
            $return_code = "SUCCESS";
            $return_msg = "Failure of interface data processing";
        }

        //输出退款结果
        exit("<xml><return_code><![CDATA[{$return_code}]]></return_code><return_msg><![CDATA[{$return_msg}]]></return_msg></xml>");
    }


    /**
     * 支付完成后毁掉处理失败
     * @param $order  array() 订单数据
     * @param $wxpay_data  array() 微信支付后服务器返回数据
     * @param $model     object  数据模型
     */
    private function payerror_log($order,$wxpay_data,$model){
        //支付成功后,后台数据处理问题报警
        $errlog=M('payerror_log')->where(['order_id'=>$order['id']])->find();

        //该订单报警日志存在,就新增一条,并发送短信验证
        if(!$errlog){
            $errdata=[
                'order_id'=>$order['id'],
                'order_no'=>$order['order_no'],
                'merchant_id'=>$order['merchant_id'],
                'merchant_pay_no'=>$wxpay_data['transaction_id'],
                'member_id'=>$order['member_id'],
                'pay_price'=>$order['pay_price'],
                'createtime'=> time(),
                'desc '=>$model->getError(),
                'pay_type '=>1,
            ];
            $erorr_res=M('payerror_log')->add($errdata);
            //调用发短信的接口(给后台管理人员
            $param = C("ALIDAYU.TEMPLATECODE");

            if($order['order_type'] == 1){
                $string=$order['seat_number'];
            }else{
                $string=$order['pack_title'];
            }
            //给指定员工发送新支付订单消息
            //顾客${name},购买${product}，支付失败，订单编号: ${code}，请前往查看处理。
            try {
                $tel=C('ADMIN_PHONE');
                $sms_data = [
                    'name' => $order['contacts_realname'],
                    'product'=> $order['merchant_name'].$string,
                    'code' => $order['order_no']
                ];
                //发送短信
                if (! $this->_sendNotifySms($tel,$param['ADMIN_NOTICE'],$sms_data)) {
                    throw new \Exception('微信支付后台处理失败开发人员通知消息发送失败, 电话号码为' . $tel);
                }
            } catch (\Exception $exception) {
                Log::write($exception, Log::WARN);
            }
        }
    }


    private function recharge_error_log($attach,$wxpay_data,$cModel){
        //支付成功后,后台数据处理问题报警
        $errlog=M('payerror_log')->where(['order_no'=>$attach['order_no']])->find();
        $errormsg=$cModel->getError();
        //该订单报警日志存在,就新增一条,并发送短信验证
        if(!$errlog){
            $errdata=[
                'order_id'=> 0,
                'order_no'=>$attach['order_no'],
                'merchant_id'=> 0,
                'merchant_pay_no'=>$wxpay_data['transaction_id'],
                'member_id'=>$attach['member_id'],
                'pay_price'=>$wxpay_data['total_fee'],
                'createtime'=>time(),
                'desc'=> $errormsg,
                'pay_type'=>1,
            ];
            $erorr_res=M('payerror_log')->add($errdata);
            //调用发短信的接口(给后台管理人员
            $param = C("ALIDAYU.TEMPLATECODE");
            //获取会员姓名
            $member_name=M('member')->where(['id'=>$attach['member_id']])->getField('nickname');
            //给指定员工发送新支付订单消息
            //顾客${name},余额充值，支付失败，订单编号:${code},请前往查看处理。
            try {
                $tel=C('ADMIN_PHONE');
                $sms_data = [
                    'name' => $member_name ,
                    'code' => $attach['order_no'],
                ];
                //发送短信
                if (! $this->_sendNotifySms($tel,$param['ADMIN_RECHARGE'],$sms_data)) {
                    throw new \Exception('微信支付后台处理失败开发人员通知消息发送失败, 电话号码为' . $tel);
                }
            } catch (\Exception $exception) {
                Log::write($exception, Log::WARN);
            }
        }
    }

}