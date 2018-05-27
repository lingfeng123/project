<?php
/**
 * FileName: UnifiedOrderController.class.php
 * title: 各种支付统一下单控制器
 * User: Comos
 * Date: 2018/2/23 10:58
 * description:
 * 购买类型 buy_type: 1正常下单支付 2正常续酒下单支付 3拼吧支付 4拼吧续酒支付
 * 订单类型 order_tpye: 订单类型参照数据库字段设计
 * 是否拼吧 is_bar: 根据数据库字段设计
 * 是否续酒 is_xu: 根据数据库字段设计
 * 支付类别 pay_type: 1订单消费(包括了各种线上商品购买的订单) 2充值
 */

namespace V1_1\Controller;

@date_default_timezone_set('PRC');

use Org\Util\JPushNotify;
use Org\Util\JsSdkPay;
use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\SocketPush;
use Org\Util\String;
use Org\Util\Tools;
use Org\Util\YunpianSms;
use Think\Log;

class UnifiedOrderController extends BaseController
{
    private $_ypsms;    //云片短信实例
    private $_smsTpl;   //短信模板数据
    private $_payment = [1, 2, 3];      //支付类型payment 1余额支付 2微信支付 3支付宝 4银联支付
    private $_buyType = [1, 2, 3, 4];   //购买类型buy_type 1正常下单支付 2正常续酒下单支付 3拼吧支付 4拼吧续酒支付
    private $_payType = [1, 2];         //消费类型pay_type 1订单消费 2充值

    public function _initialize()
    {
        parent::_initialize();
        //实例云片短信类
        $this->_ypsms = new YunpianSms();
        //短信模板数据
        $this->_smsTpl = C('YUNPIAN');
    }

    /**
     * 支付接口请求入口 / 微信支付与余额支付 / 提交订单支付(去支付)
     * (正常购买下单或续酒下单发起支付请求接口都是调用本接口, 没有单独的平台划分,根据传入的参数类型区分支付平台和支付类型等)
     * @param $order_no int 订单号
     * @param $member_id int 用户ID
     * @param $pay_money string 支付金额
     * @param $payment int 支付类型
     * @param $buy_type int 支付类型
     */
    public function payment()
    {
        $version = I('post.version', '');           //接口版本号
        $client = I('post.client', '');             //客户端类型 xcx, ios, android
        $order_id = I('post.order_id', '');         //订单ID或拼吧订单ID
        $buy_type = I('post.buy_type', '');         //购买类型 buy_type: 1正常下单支付 2正常续酒下单支付 3拼吧支付 4拼吧续酒支付
        $member_id = I('post.member_id', '');       //用户ID
        $pay_money = I('post.pay_money', '');       //支付金额
        $payment = I('post.payment', 2);            //支付类型 1余额支付 2微信支付 3支付宝 4银联支付
        $pay_type = I('post.pay_type', 1);          //附加数据中的支付种类 1订单消费 2充值
        $trade_type = I('post.trade_type', '');     //微信发起支付的类型 JSAPI  APP
        $openid = I('post.openid', '');             //小程序的openid
        $pay_password = I('post.pay_password', ''); //余额支付密码

        //校验传入参数
        if (!is_numeric($order_id) || !is_numeric($member_id) || !in_array($payment, $this->_payment) || !in_array($buy_type, $this->_buyType) || !in_array($pay_type, $this->_payType) || !$version) {
            Response::error(ReturnCode::PARAM_INVALID, '支付请求参数不合法');
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

        //获取订单数据
        if (in_array($buy_type, [1, 2])) {

            //正常购物车下单/续酒支付
            $order = M('order')->where(['id' => $order_id, 'status' => 1])->find();
            if (!$order) {
                Response::error(ReturnCode::INVALID_REQUEST, '未找到符合条件的订单');
            }

        } elseif (in_array($buy_type, [3, 4])) {

            //拼吧下单/续酒支付
            $fields = "api_bar_member.id, api_bar_member.member_id,api_bar_member.pay_price, bar_type, pay_no as order_no, order_type, pay_status, api_bar_member.created_time";
            $order = M('bar_member')
                ->field($fields)
                ->join('api_bar on api_bar.id = api_bar_member.bar_id')
                ->where(['api_bar_member.id' => $order_id, 'api_bar.bar_status' => 1, 'api_bar_member.pay_status' => 1])
                ->find();

            if (!$order) {
                Response::error(ReturnCode::INVALID_REQUEST, '未找到符合条件的订单');
            }

            //附加参数bar_type
            $bar_type = $order['bar_type'];
        }

        //判断下单时间
        $created_time = $order['created_time'];
        $expired_time = $created_time + C('ORDER_OVERTIME');
        $diff_time = $expired_time - time();

        //如果订单创建时间小于当前时间并且当前时间与订单创建时间相差
        if ($expired_time < time() || $diff_time < 3) {
            Response::error(ReturnCode::INVALID_REQUEST, '订单已超时或支付时间不足');
        }

        //微信支付的订单开始与结束时间
        $time_start = date('YmdHis', $created_time);
        $time_expire = date('YmdHis', $expired_time);

        //如果价格不为空,判断价格是否与数据库中订单一致
        $vali_pay_money = $pay_money;
        $vali_pay_price = $order['pay_price'];
        if ($vali_pay_money != $vali_pay_price) {
            Response::error(ReturnCode::NUMBER_MATCH_ERROR, '支付金额不匹配');
        }

        //判断订单价格
        if ($vali_pay_price == 0) $payment = 1;

        //组装一个统配数据,各种判断类型集合
        $judgment_set = [
            'buy_type' => $buy_type,
            'member_id' => $member_id,
            'pay_type' => $pay_type,
            'trade_type' => $trade_type,
            'client' => $client,
            'version' => $version,
        ];
        //如果bar_type存在则将数据加到统配数据中
        if (isset($bar_type)) {
            $judgment_set['bar_type'] = $bar_type;
        }

        /**
         * 判断用户使用的支付方式
         * 支付类型: 1余额支付 2微信支付 3支付宝 4银联支付
         */
        switch ($payment) {
            case 1:
                //站内余额支付
                //获取用户信息
                $result = D('Member')->getMemberCapitalInfoByMemberId($member_id, $pay_password);
                if (!$result) {
                    Response::error(ReturnCode::PARAM_WRONGFUL, '支付密码不正确');
                }

                $this->_balancePayment($order, $buy_type, $judgment_set);
                break;
            case 2:

                //微信小程序支付
                if ($trade_type == 'JSAPI') {
                    //验证小程序的openID是否传入
                    if (!$openid) {
                        Response::error(ReturnCode::PARAM_WRONGFUL, 'openID获取失败,无法发起支付');
                    }

                    //调用小程序支付统一下单方法
                    $this->_xcxWechatPayOrder($order, $time_start, $time_expire, $judgment_set, $openid);
                }

                //微信APP支付
                if ($trade_type == 'APP') {
                    //调用APP支付下单方法
                    $this->_appWechatPayOrder($order, $time_start, $time_expire, $judgment_set);
                }

                break;
            case 3:

                //支付宝支付
                $time_expire = $expired_time / 60;
                $this->_alipayPayOrder($order, $time_expire, $judgment_set);

                break;
            default:
                Response::error(ReturnCode::INVALID, '未知支付请求');
        }
    }

    /**
     * 站内余额支付
     * @param $order_info array 订单数据
     * @param $buy_type int 购买类型
     * @param $judgment_set array 统配数据
     */
    private function _balancePayment($order_info, $buy_type, $judgment_set)
    {
        //订单应支付金额
        $pay_price = $order_info['pay_price'];

        //查询api_member_capital表中用户原始金额数据
        $member_capital = M('member_capital')->where(['member_id' => $order_info['member_id']])->find();
        if ($member_capital === false) {
            Response::error(ReturnCode::INVALID_REQUEST, '支付失败, 请求数据错误');
        }

        //用户原来的赠送金额   和  变动前的赠送余额
        $before_give_money = $give_money = $member_capital['give_money'];
        //用户原来的充值金额  和   变动前的充值金额
        $before_recharge_money = $recharge_money = $member_capital['recharge_money'];    //充值金额

        //判断余额是否充足
        if (($give_money + $recharge_money) < $pay_price) {
            Response::error(ReturnCode::INVALID_REQUEST, '您的余额不足,请及时充值');
        }

        //先用充值金额支付应支付订单金额得到剩下未支付金额
        $charge_money = $recharge_money - $pay_price;

        if ($charge_money < 0) {

            //扣除充值金额后剩下的未支付金额小于0
            $recharge_money = 0;
            $charge_money = abs($charge_money); //将数据转换成正整数
            $give_money = $give_money - $charge_money;

            //变动后的金额
            $after_give_money = $give_money;
            $after_recharge_money = $recharge_money;
        } else {
            $recharge_money = $charge_money;

            //变动后的金额
            $after_give_money = $give_money;
            $after_recharge_money = $recharge_money;
        }

        //写入支付完成后的数据
        $member_record_model = D('member_record');
        $res = $member_record_model->insertPayInfo(
            $give_money,
            $recharge_money,
            $before_give_money,
            $before_recharge_money,
            $after_give_money,
            $after_recharge_money,
            $order_info,
            $judgment_set);
        if (!$res) {
            Response::error(ReturnCode::INVALID_REQUEST, '支付失败');
        }

        $sex = [1 => '先生', 2 => '女士'];

        //购物车普通购买支付发送socket消息
        if ($buy_type == 1) {
            //向预订部推送消息 获取所有预订部的员工ID
            $yudingbu_permission = C('YUDINGBU_PERMISSION');
            $employee_ids = M('EmployeeJobPermission')
                ->distinct(true)
                ->join("api_employee_andjob ON api_employee_andjob.job_id = api_employee_job_permission.job_id")
                ->where(['api_employee_job_permission.permission_id' => ['IN', $yudingbu_permission], 'api_employee_job_permission.merchant_id' => $order_info['merchant_id']])
                ->getField('employee_id', true);

            //循环推送通知  预订部员工ID存在时才发送socket消息
            // ::socket::
            if ($employee_ids) {
                try {
                    $socketPush = new SocketPush();
                    $socketPush->pushOrderSocketMessage($employee_ids, 3, $order_info['order_no']);
                } catch (\Exception $exception) {
                    //记录日志
                    Log::write($exception, Log::WARN);
                }

                /**
                 * 给预订部员工发送新支付订单提醒
                 * 【空瓶子】顾客#name#购买了#product#，请及时处理订单。
                 * TODO:: 正式上线需开启,测试阶段不需要
                 */
                $orderType = [1 => '卡座', 2 => '卡座套餐', 3 => '优惠套餐'];
                $tpl_value = [
                    '#name#' => $order_info['contacts_realname'] . $sex[$order_info['contacts_sex']],
                    '#product#' => $orderType[$order_info['order_type']] . '订单'
                ];

                //获取所有员工的电话号码
                $employee_tels = M('employee')->where(['id' => ['in', $employee_ids]])->getField('tel', true);
                foreach ($employee_tels as $employee_tel) {
                    //获取短信模板发送短信
                    $rs = $this->_ypsms->tplSingleSend($employee_tel, $this->_smsTpl['daijiedan'], $tpl_value);
                    //记录失败日志
                    if (!$rs) Log::write($this->_ypsms->errMsg, Log::NOTICE);
                }
            }

        }

        /**
         * 续酒订单支付完成发送短信消息
         */
        if ($buy_type == 2 && $order_info['employee_id']) {

            try {
                $socketPush = new SocketPush();
                $socketPush->pushOrderSocketMessage($order_info['employee_id'], 3, $order_info['order_no']);
            } catch (\Exception $exception) {
                //记录日志
                Log::write($exception, Log::WARN);
            }

            //使用云片发送短信给指定员工发送新支付订单消息
            if ($order_info['employee_tel']) {
                $tpl_value = [
                    "#name#" => $order_info['contacts_realname'] . $sex[$order_info['contacts_sex']],
                    "#product#" => '续酒订单'
                ];

                //获取短信模板发送短信
                $rs = $this->_ypsms->tplSingleSend($order_info['employee_tel'], $this->_smsTpl['daijiedan'], $tpl_value);
                //记录失败日志
                if (!$rs) Log::write($this->_ypsms->errMsg, Log::NOTICE);
            }

        }

        $nickname = M('member')->where(['id' => $judgment_set['member_id']])->getField('nickname');
        $extData = $member_record_model->extData;   //拼吧的扩展数据

        //正常购买短信
        if ($buy_type == 1) {
            if ($order_info['order_type'] == 3 || $order_info['order_type'] == 0) {
                $this->sendSmsToMember($order_info);
            }
        }

        //正常拼吧推送消息内容
        if ($buy_type == 3) {
            $title = '您参与的拼吧人数已满, 拼吧成功！';
            $title_single = $nickname . '参与了您的拼吧。';
            $sms_title = '拼吧';
            $this->barNoftify($extData, $title, $title_single, $buy_type);
        }

        //拼吧续酒推送消息内容
        if ($buy_type == 4) {
            $title = '您参与的拼吧续酒人数已满, 续酒成功！';
            $title_single = $nickname . '参与了您的拼吧续酒。';
            $sms_title = '拼吧续酒';
            $this->barNoftify($extData, $title, $title_single, $buy_type);
        }

        /**
         * 给用户发送余额提醒
         */
        //获取用户绑定手机
        $tel = D('member')->field('tel,nickname,sex')->where(['id' => $order_info['member_id']])->find();
        if ($tel['tel']) {
            //【空瓶子】尊敬的#name#，您已于#time#消费#paymoney#元，当前余额#totalmoney#元，感谢您的使用。
            $tpl_value = [
                '#name#' => $tel['nickname'] . $sex[$tel['sex']],
                '#time#' => date('Y年m月d日 H时i分', time()),
                '#paymoney#' => $order_info['pay_price'],
                '#totalmoney#' => $give_money + $recharge_money,
            ];

            //获取短信模板发送短信
            $rs = $this->_ypsms->tplSingleSend($tel['tel'], $this->_smsTpl['yuexiaofei'], $tpl_value);
            //记录失败日志
            if (!$rs) Log::write($this->_ypsms->errMsg, Log::NOTICE);
        }

        //支付成功
        Response::setSuccessMsg('支付成功');
        Response::success();
    }

    /**
     * 给用户发送订单接单成功短信提醒
     */
    private function sendSmsToMember($order_info)
    {
        $merchant = M('merchant')->field('province,city,area,address')->where(['id' => $order_info['merchant_id']])->find();
        $orderType = [1 => '卡座', 2 => '卡座套餐', 3 => '优惠套餐', 0 => '单品酒水'];
        $tpl_value = [
            '#product#' => $orderType[$order_info['order_type']],
            '#time#' => date('Y年m月d日 H时i分', $order_info['obegin_time']),
            '#address#' => $merchant['province'] . $merchant['city'] . $merchant['area'] . $merchant['address'],
        ];
        $rs = $this->_ypsms->tplSingleSend($order_info['contacts_tel'], $this->_smsTpl['santaojiedan'], $tpl_value);
        if (!$rs) Log::write($this->_ypsms->errMsg, Log::NOTICE);
    }

    /**
     * 推送jpush消息给用户端
     * @param $extData
     * @param $title
     * @param $title_single
     * @param $buy_type
     */
    private function barNoftify($extData, $title, $title_single, $buy_type)
    {
        $message = [
            'alert' => '点击查看详情',
            'title' => '',
            'extras' => [
                'msg_type' => 'bar',  //system order bar
                'title' => '',
                'content' => '',
                'icon' => '',
                'order_id' => $extData['bar_id']
            ]
        ];

        if ($extData['success']) {
            $message['title'] = $message['extras']['title'] = $title;
            foreach ($extData['members'] as $extDatum) {
                JPushNotify::toAliasNotify($extDatum['member_id'], $message);
            }
        }

        $message['title'] = $message['extras']['title'] = $title_single;
        JPushNotify::toAliasNotify($extData['member_id'], $message);

        //拼吧存在有用户时才发送短信通知
        if ($extData['bar_info'] && $extData['success'] && $buy_type == 3) {
            $memberTels = [];
            foreach ($extData['members'] as $member) {
                $memberTels[] = $member['tel'];
            }

            $orderType = [1 => '卡座', 2 => '卡座套餐', 3 => '优惠套餐', 0 => '单品酒水'];

            if ($extData['bar_info']['bar_type'] == 1) {
                if ($extData['bar_info']['order_type'] == 3 || $extData['bar_info']['order_type'] == 0) {
                    //【空瓶子】尊敬的用户您好，您参与的#product#拼吧，商户已接单，请于#time#前到店消费 , 如有疑问，请联系空瓶子客服#telphone#。
                    $tpl_value = [
                        '#product#' => '由' . $extData['bar_info']['contacts_realname'] . '发起的' . $orderType[$extData['bar_info']['order_type']],
                        '#time#' => date('Y年m月d日H时i分', $extData['bar_info']['obegin_time']),
                        '#telphone#' => C('KPZKF_PHONE'),
                    ];
                    $tpl = $this->_smsTpl['pinsanok'];
                } else {
                    $tpl_value = [
                        '#name#' => $extData['bar_info']['contacts_realname'],
                    ];
                    $tpl = $this->_smsTpl['pinman'];
                }
            } else {
                $tpl_value = [
                    '#product#' => '由' . $extData['bar_info']['contacts_realname'] . '发起的派对',
                    '#time#' => date('Y年m月d日H时i分', $extData['bar_info']['obegin_time']),
                    '#telphone#' => C('KPZKF_PHONE'),
                ];
                $tpl = $this->_smsTpl['pinsanok'];
            }
            $this->_ypsms->tplBatchSend($memberTels, $tpl, $tpl_value);
        }
    }


    /**
     * 微信小程序微信支付下单
     * @param $order_info array 订单数据
     * @param $time_start int 起点时间
     * @param $time_expire int 过期时间
     * @param $judgment_set array 判定数据
     * @param $openid string 小程序用户的openid
     */
    private function _xcxWechatPayOrder($order_info, $time_start, $time_expire, $judgment_set, $openid)
    {
        //微信支付配置数据
        $config = C('WXPAY_OPTION');

        //附加数据
        $attach = [
            'order_no' => $order_info['order_no'],
            'order_id' => $order_info['id'],
            'buy_type' => $judgment_set['buy_type'],    //购买类型
            'order_type' => $order_info['order_type'],
            'pay_type' => $judgment_set['pay_type'],     //附加数据中的支付种类   1订单消费 2充值
            'member_id' => $judgment_set['member_id'],
            'version' => $judgment_set['version'],
            //'trade_type' => $judgment_set['trade_type'],
            //'client' => $judgment_set['client'],
        ];

        //拼吧订单添加额外数据
        if (in_array($judgment_set['buy_type'], [3, 4]) && isset($judgment_set['bar_type'])) {
            $attach['bar_type'] = $judgment_set['bar_type'];
        }

        //统一下单需要的数据
        $data = [
            'openid' => $openid,                                              //小程序用户openid
            'appid' => $config['APPID'],                                      //微信分配的小程序ID
            'mch_id' => $config['MCH_ID'],                                    //微信支付分配的商户号
            'nonce_str' => strtoupper(md5(String::randString(20))),      //随机字符串，不长于32位
            'body' => "空瓶子购物订单-" . $order_info['order_no'],             //商品描述,不能为空
            'attach' => http_build_query($attach),                            //订单附加数据
//            'out_trade_no' => $order_info['order_no'] . mt_rand(100, 999),    //商户订单号
            'out_trade_no' => $order_info['order_no'],    //商户订单号
            'total_fee' => $order_info['pay_price'] * 100,                    //订单总金额，单位为分
            'spbill_create_ip' => $config['IP'],                              //APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。
            'time_start' => $time_start,                                      //订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010
            'time_expire' => $time_expire,                                    //订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010
            'notify_url' => $config['NOTIFY_URL'],                            //微信支付回调地址
            'trade_type' => 'JSAPI',                                          //交易类型
        ];

        $data['sign'] = Tools::getWxPaySign($data, $config['KEY']);

        //将数据转化为xml
        $jssdk = new JsSdkPay();
        $response = $jssdk->postXmlCurl($jssdk->arrayToXml($data), 'https://api.mch.weixin.qq.com/pay/unifiedorder');

        //微信服务器返回的数据转换为数组
        $wxpay_data = $jssdk->xmlToArray($response);

        //将统一下单的数据记录到日志中
        Tools::writeLog($data, C('LOG_PATH'), '', 'wxpay_xcx');
        Tools::writeLog($wxpay_data, C('LOG_PATH'), '', 'wxpay_xcx');

        //判断微信下单的结果
        if ($wxpay_data['return_code'] == "SUCCESS" && $wxpay_data['result_code'] == 'SUCCESS') {

            $jsapi_data = [
                'appId' => $wxpay_data['appid'],
                'timeStamp' => time(),
                'nonceStr' => $wxpay_data['nonce_str'],
                'package' => "prepay_id=" . $wxpay_data['prepay_id'],
                'signType' => "MD5",
            ];

            //生成jsapi签名
            $jsapi_data['paySign'] = Tools::getWxPaySign($jsapi_data, $config['KEY']);

            //将数据返回给小程序调起支付
            Response::success($jsapi_data, ReturnCode::SUCCESS);
        } else {
            Response::error(ReturnCode::INVALID_REQUEST, '微信支付下单失败' . json_encode($wxpay_data));
        }
    }


    /**
     * 微信开放平台APP微信支付下单
     * @param $order_info array 订单数据
     * @param $time_start int 起点时间
     * @param $time_expire int 过期时间
     * @param $judgment_set array 判定数据
     */
    private function _appWechatPayOrder($order_info, $time_start, $time_expire, $judgment_set)
    {
        //微信支付配置数据
        $config = C('APP_WXPAY_OPTION');

        //附加数据
        $attach = [
            'order_no' => $order_info['order_no'],
            'order_id' => $order_info['id'],
            'buy_type' => $judgment_set['buy_type'],    //购买类型
            'order_type' => $order_info['order_type'],
            'pay_type' => $judgment_set['pay_type'],     //附加数据中的支付种类   1订单消费 2充值
//            'trade_type' => $judgment_set['trade_type'],
//            'client' => $judgment_set['client'],
            'version' => $judgment_set['version'],
            'member_id' => $judgment_set['member_id'],
        ];

        //拼吧订单添加额外数据
        if (in_array($judgment_set['buy_type'], [3, 4]) && isset($judgment_set['bar_type'])) {
            $attach['bar_type'] = $judgment_set['bar_type'];
        }

        //统一下单需要的数据
        $data = [
            'appid' => $config['APPID'],                                 //微信分配的小程序ID
            'mch_id' => $config['MCH_ID'],                                    //微信支付分配的商户号
            'nonce_str' => strtoupper(md5(String::randString(20))),      //随机字符串，不长于32位
            'body' => "空瓶子购物订单-" . $order_info['order_no'],             //商品描述,不能为空
            'attach' => http_build_query($attach),                                 //订单附加数据
//            'out_trade_no' => $order_info['order_no'] . mt_rand(100, 999),    //商户订单号
            'out_trade_no' => $order_info['order_no'],    //商户订单号
            'total_fee' => $order_info['pay_price'] * 100,                    //订单总金额，单位为分
            'spbill_create_ip' => $config['IP'],                              //APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。
            'time_start' => $time_start,                                      //订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010
            'time_expire' => $time_expire,                                    //订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010
            'notify_url' => $config['NOTIFY_URL'],                            //微信支付回调地址
            'trade_type' => 'APP',                                            //交易类型
        ];

        //生成APP签名
        $data['sign'] = Tools::getWxPaySign($data, $config['KEY']);

        //将数据转化为xml
        $jssdk = new JsSdkPay();
        $response = $jssdk->postXmlCurl($jssdk->arrayToXml($data), 'https://api.mch.weixin.qq.com/pay/unifiedorder');

        //微信服务器返回的数据转换为数组
        $wxpay_data = $jssdk->xmlToArray($response);

        //将统一下单的数据记录到日志中
        Tools::writeLog($data, C('LOG_PATH'), '', 'wxpay_app');
        Tools::writeLog($wxpay_data, C('LOG_PATH'), '', 'wxpay_app');

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

            //生成签名
            $app_data['sign'] = Tools::getWxPaySign($app_data, $config['KEY']);

            //将数据返回给APP调起支付
            Response::success($app_data, ReturnCode::SUCCESS);
        } else {
            Response::error(ReturnCode::INVALID_REQUEST, '微信支付下单失败');
        }
    }

    /**
     * 支付宝统一下单发起支付请求数据
     * @param $order array 订单数据
     * @param $time_expire  int  失效时间
     * @param $judgment_set array 统配数据
     */
    private function _alipayPayOrder($order, $time_expire, $judgment_set)
    {
        //载入类文件
        vendor('alipay.AopClient');
        vendor('alipay.request.AlipayTradeAppPayRequest');

        //获取支付宝支付配置信息
        $alipayConfig = C('alipay');

        //实例
        $aop = new \AopClient;
        $aop->gatewayUrl = $alipayConfig['gate_way_url'];
        $aop->appId = $alipayConfig['app_id'];
        $aop->rsaPrivateKey = $alipayConfig['rsa_private_key'];
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->signType = $alipayConfig['sign_type'];
        $aop->alipayrsaPublicKey = $alipayConfig['alipay_rsa_public_key'];

        //下单数据
        $passback_params = [
            'buy_type' => $judgment_set['buy_type'],
            'pay_type' => $judgment_set['pay_type'],
            'order_id' => $order['id'],
            'order_no' => $order['order_no'],
            'order_type' => $order['order_type'],
            'trade_type' => $judgment_set['trade_type'],
            'client' => $judgment_set['client'],
            'version' => $judgment_set['version'],
            'member_id' => $judgment_set['member_id'],
        ];

        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new \AlipayTradeAppPayRequest();

        //SDK已经封装掉了公共参数，这里只需要传入业务参数
        $body = "空瓶子平台" . $order['pay_price'] . "元商品订单, 订单编号:" . $order['order_no'] . ', 下单时间:' . date('Y-m-d H:i:s', $order['created_time']);
        $bizcontent = [
            'body' => $body,     //对一笔交易的具体描述信息。如果是多种商品，请将商品描述字符串累加传给body。
            'subject' => "空瓶子商品订单-" . $order['order_no'],     //商品的标题/交易标题/订单标题/订单关键字等。
            'out_trade_no' => $order['order_no'],     //商户网站唯一订单号
            'timeout_express' => $time_expire . 'm',     //该笔订单允许的最晚付款时间，逾期将关闭交易。
            'total_amount' => $order['pay_price'],       //订单总金额，单位为元，精确到小数点后两位，取值范围[0.01,100000000]
            'product_code' => "QUICK_MSECURITY_PAY",    //销售产品码，商家和支付宝签约的产品码，为固定值QUICK_MSECURITY_PAY
            'passback_params' => urlencode(http_build_query($passback_params)),     //公用回传参数，如果请求时传递了该参数，则返回给商户时会回传该参数。支付宝会在异步通知时将该参数原样返回。本参数必须进行UrlEncode之后才可以发送给支付宝
        ];

        $request->setNotifyUrl($alipayConfig['notify_url']);
        $request->setBizContent(json_encode($bizcontent));

        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);

        Response::setSuccessMsg('支付宝下单成功');
        Response::success(['alipay_trade' => $response]);
    }


    /**
     * 支付成功界面的提示文字
     * @var array
     */
    protected $tipsContent = array(
        //支付成功界面
        'PAY_SUCCESS' => array(
            'PAY_KATAO' => '您已成功购买卡座套餐，本套餐须在#keyword1#到酒吧消费，若未到店消费，将根据您的会员级别为您保留一定的天数，在保留期内可预定卡座后去消费该套餐，保留期结束后还未消费，则将作废不会退还您费用。',
            'PAY_SANTAO' => '您已成功购买优惠套餐，本套餐须在#keyword1#到酒吧消费，若未到店消费，将逾期作废不会退还您费用。',
            'PAY_KAZUO' => '您已成功预定卡座，须在#keyword1#到酒吧消费，若未到店消费，将逾期作废不会退还您预订金。',
            'PAY_DANPIN' => '您已成功购买酒水，请按时去消费。',
            'PAY_PINBA' => '您已成功支付拼吧，参与人数满后即拼吧成功。',
            'PAY_PINXU' => '您支付成功，待所有人都支付后即续酒成功',
            'PAY_XU' => '恭喜您续酒成功,酒吧工作人员将把酒水呈给您',
        ),
    );

    /**
     * 获取订单当前状态
     */
    public function paymentResult()
    {
        $order_id = I('post.order_id');
        $buy_type = I('post.buy_type');

        if (!is_numeric($order_id) || !is_numeric($buy_type)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        $data = [];
        if (in_array($buy_type, [1, 2])) {
            $data = $this->paymentResultOrder($order_id);
        } else if (in_array($buy_type, [3, 4])) {
            $data = $this->paymentResultBar($order_id, $buy_type);
        }

        if ($data == false) {
            Response::error(ReturnCode::DATA_EXISTS, '订单未找到');
        }

        Response::setSuccessMsg('获取订单状态成功');
        Response::success($data);
    }


    /**
     * 获取订单当前的状态
     */
    private function paymentResultOrder($order_id, $buy_type)
    {
        //判断查询结果
        $order = D('order')->field('order_type, api_order.status, api_order.arrives_time, begin_time, end_time')
            ->join('api_merchant ON api_merchant.id = api_order.merchant_id')
            ->where(['api_order.id' => $order_id])
            ->find();
        if (!$order) {
            return false;
        }

        //判断订单状态是否在指定订单状态内
        $status_array = [2, 3, 4, 5, 6, 7];
        if (in_array($order['status'], $status_array)) {
            $pay_status = 1;
        } else {
            $pay_status = 0;
        }

        $tips = $this->tipsContent['PAY_SUCCESS'];
        $date = date('Y年m月d日', $order['arrives_time']);
        $str = $date . Tools::formatTimeStr($order['begin_time']) . '-' . Tools::formatTimeStr($order['end_time']);

        $order_tips = '';
        if ($buy_type == 1) {
            switch ($order['order_type']) {
                case 0:
                    //单品酒水
                    $order_tips = $tips['PAY_DANPIN'];
                    break;
                case 1:
                    //卡座
                    $order_tips = str_replace('#keyword1#', $str, $tips['PAY_KAZUO']);
                    break;
                case 2:
                    //卡套
                    $order_tips = str_replace('#keyword1#', $str, $tips['PAY_KATAO']);
                    break;
                case 3:
                    //散套
                    $order_tips = str_replace('#keyword1#', $str, $tips['PAY_SANTAO']);
                    break;
            }

        } else if ($buy_type == 2) {
            $order_tips = $tips['PAY_XU'];
        }

        return ['pay_status' => $pay_status, 'order_tips' => $order_tips];
    }

    /**
     * 拼吧订单当前状态
     * @param $order_id
     * @param $buy_type
     * @return array|bool
     */
    private function paymentResultBar($order_id, $buy_type)
    {

        //判断查询结果
        $order = M('bar_member')->field('pay_status, api_bar.arrives_time, order_type, begin_time, end_time')
            ->join('api_bar ON api_bar.id = api_bar_member.bar_id')
            ->join('api_merchant ON api_merchant.id = api_bar.merchant_id')
            ->where(['api_bar_member.id' => $order_id])
            ->find();

        if (!$order) {
            return false;
        }

        //判断订单状态是否为已支付
        if (!in_array($order['pay_status'], [0, 1])) {
            $pay_status = 1;
        } else {
            $pay_status = 0;
        }

        $tips = $this->tipsContent['PAY_SUCCESS'];
//        $date = date('Y年m月d日', $order['arrives_time']);
//        $str = $date . Tools::formatTimeStr($order['begin_time']) . '-' . Tools::formatTimeStr($order['end_time']);

        if ($buy_type == 3) {
            $order_tips = $tips['PAY_PINBA'];
        } else if ($buy_type == 4) {
            $order_tips = $tips['PAY_PINXU'];
        }


        return ['pay_status' => $pay_status, 'order_tips' => $order_tips];
    }


    /**
     * 订单支付页面订单数据
     * @param $order_id int 订单ID
     * @param buy_type int 购买类型
     */
    public function payshow()
    {
        $order_id = I('post.order_id', '');
        $buy_type = I('post.buy_type', '');
        if (!is_numeric($order_id) || !in_array($buy_type, [1, 2, 3, 4])) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //根据buy_type类型查询订单系统
        if (in_array($buy_type, [1, 2])) {
            //普通购买订单
            $order = M('order')->field('id,pay_price,created_time')->where(['id' => $order_id])->find();
            if (!$order) Response::error(ReturnCode::DB_READ_ERROR, '请求数据失败');
        } else {
            $order = M('bar_member')->field('id,pay_price,created_time')->where(['id' => $order_id])->find();
            if (!$order) Response::error(ReturnCode::DB_READ_ERROR, '请求数据失败', $order);
        }

        $have_time = $order['created_time'] + C('ORDER_OVERTIME') - time();
        $have_time = $have_time > 0 ? $have_time : 0;
        $data = [
            'order_id' => $order['id'],
            'pay_money' => $order['pay_price'],
            'have_time' => $have_time,
            'buy_type' => $buy_type,
        ];

        Response::success($data);
    }
}