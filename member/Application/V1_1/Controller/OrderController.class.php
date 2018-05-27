<?php
/**
 * FileName: OrderController.class.php
 * User: Comos
 * Date: 2017/8/30 11:18
 */


namespace V1_1\Controller;


use Org\Util\FibonacciRpcClient;
use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\Tools;
use Think\Log;

class OrderController extends BaseController
{

    private $_model;

    public function _initialize()
    {
        parent::_initialize();
        $this->_model = D('order');
    }

    /**
     * 用户普通订单列表
     */
    public function myOrderList()
    {
        $member_id = I('post.member_id', '');
        $page = I('post.page', 1);
        $pagesize = I('post.page_size', C('PAGE.PAGESIZE'));

        //判断数据输入是否合法
        if (!is_numeric($member_id) || !is_numeric($page) || !is_numeric($pagesize)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //自动修改订单状态请求
        //$time = time();
        //$post_data = ['sign' => AuthSign::getSign($time), 'timestamp' => $time];
        //Http::post(C('MEMBER_API_URL') . U('V1_1/Order/updateStatus'), '', $post_data);

        //实例化模型
        $orderModel = D('Order');
        //获取订单数据
        if (!$result = $orderModel->getMemberOrderList($member_id, $page, $pagesize)) {
            Response::error(ReturnCode::DB_READ_ERROR, $orderModel->getError());
        }

        Response::success($result);
    }


    /**
     * 获取订单信息详情
     */
    public function orderDetail()
    {
        $order_id = I('post.order_id', '');
        if (!is_numeric($order_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //获取订单数据
        $orderModel = D('order');
        $data = $orderModel->getorderDetail($order_id);
        if (!$data) {
            Response::error(ReturnCode::NOT_EXISTS, '订单不存在');
        }

        //输出订单二维码
        if ($data['status'] == 7) {
            if ($data['order_type'] != 3) {
                $data['order_qrcode'] = C('MEMBER_API_URL') . U('Home/Source/orderQrcode',
                        [
                            'order_id' => $data['id'],
                            'order_no' => $data['order_no'],
                            'order_type' => $data['order_type'],
                            'merchant_id' => $data['merchant_id'],
                        ]);
            } else {
                $data['order_qrcode'] = '';
            }

        } else {
            $data['order_qrcode'] = '';
        }

        //返回成功结果
        Response::success($data);
    }

    /**
     * 获取订单详情商品列表数据 v2.0
     * @param $order_id int 订单ID
     */
    public function goodsList()
    {
        $order_id = I('post.order_id', '');
        if (!is_numeric($order_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //订单ID对应的商品数量列表
        $order_goods = D('Order')->getGoodsListByOrderId($order_id);
        if (!$order_goods) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取订单商品失败');
        }

        Response::setSuccessMsg('请求订单商品数据成功');
        Response::success([
            'total' => count($order_goods),
            'list' => $order_goods
        ]);
    }


    /**
     * 向订单用户发送消息通知
     * @param $wx_openid string 微信openID
     * @param $param array 短信配置
     * @param $order array 订单信息
     */
    /*private function _sendNoticeToMember($param, $order)
    {
        date_default_timezone_set('PRC'); //默认时区
        //逾期模板消息
        $overdueDate = date('Y年m月d日') . '-' . date('Y年m月d日', strtotime('+' . $order['delayed'] . ' day', time()));
        //模板消息组装
        $temp_msg = [
            'touser' => $order['wx_openid'],
            'template_id' => '2CW2ytO6X9a0xcc-DV0tPk0E8mhJz_jNx9Scy_LLCiQ',
            'url' => "",
            'topcolor' => "#FF0000",
            "data" => [
                "first" => ["value" => "您好，您有一个卡座套餐未在规定时间内完成消费，已转为逾期保护状态。", "color" => "#002200"],
                "keyword1" => ["value" => $order['order_no'], "color" => "#ff6600"],
                "keyword2" => ["value" => $order['merchant_title'] . '卡座套餐, 预留卡座已释放', "color" => "#ff6600"],
                "keyword3" => ["value" => $overdueDate, "color" => "#ff6600"],
                "remark" => ["value" => "请于" . $order['delayed'] . "天逾期保护期内，重新预定卡座后消费此套餐，如有疑问请与酒吧联系。", "color" => "#002200"],
            ]
        ];

        //发送模板消息
        $send_rs = Tools::sendTmpMessage($order['wx_openid'], $temp_msg);
        if (!$send_rs) {
            $this->log->warn(date('Y-m-d H:i:s') . '||' . $order['order_no'] . '模板消息发送失败');
        }

        //发送短信提醒
        $sex = $order['contacts_sex'] == 1 ? '先生' : '女士';
        $sms_data = [
            'name' => $order['contacts_realname'] . $sex,
            'merchant' => $order['merchant_title'],
            'money' => $order['pay_price'],
            'day' => $order['delayed']
        ];
        $sms_rs = Tools::sendsms($order['contacts_tel'], $param['SEAT_OVERDUE_NOTICE'], $sms_data);
        if (!$sms_rs) {
            $this->log->warn('逾期通知短信发送失败, 电话号码为' . $order['contacts_tel']);
        }
    }*/


    /**
     * 创建卡座预定订单 v2.0
     */
    public function buySeatGoods()
    {
        $member_id = I('post.member_id', '');               //用户ID
        $merchant_id = I('post.merchant_id', '');           //商户ID
        $contacts_id = I('post.contacts_id', '');           //联系人ID
        $total_people = I('post.total_people', '');         //到店总人数
        $description = I('post.description', '');           //订单描述
        $arrives_time = I('post.arrives_time', '');         //到店日期
        $employee_id = I('post.employee_id', 0);            //员工ID
        $goods_seat_id = I('post.goods_seat_id', '');       //卡座商品ID
        $card_id = I('post.card_id', 0);                    //优惠券ID

        Tools::orderAllowedValid();     //下单时间限制判断
        $this->verifyBeanstalkd();  //检测beanstalkd是否运行正常

        //验证下单用户ID, 商品所属商户
        if (!is_numeric($member_id) || !is_numeric($merchant_id) || !is_numeric($goods_seat_id) || !is_numeric($total_people)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //判断填写到店人数是否合法
        if ($total_people > 20 || $total_people < 1) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '实际到店人数填写不正确');
        }

        //验证预约到店日期
        if (!preg_match('/^\d{4}(\-|\/|.)\d{1,2}\1\d{1,2}$/', $arrives_time)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '预约到店时间不正确');
        }

        //验证订单描述
        $description = Tools::filterEmoji($description);
        $description = str_replace('|', '', $description);
        if (!empty($description) && mb_strlen($description) > 100) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '订单描述不能超过100个字符');
        }

        //判断当前卡座是否被预定
        $date = date('Y-m-d', strtotime($arrives_time));
        $date = strtotime($date);   //精确到年月日的日期
        $rs = M('seat_lock')->field('goods_seat_id')->where(['goods_seat_id' => $goods_seat_id, 'merchant_id' => $merchant_id, 'arrives_time' => $date])->find();
        if ($rs) {
            //如果存在已被预定
            Response::error(ReturnCode::INVALID_REQUEST, '该卡座已被预定');
        }

        //获取商户
        $merchant = M('merchant')->field('begin_time,end_time')->where(['id' => $merchant_id])->find();
        $obegin_time = strtotime(date('Y-m-d', strtotime($arrives_time)) . ' ' . $merchant['begin_time']);
        if ($merchant['begin_time'] >= $merchant['end_time']) {
            $oend_time = strtotime(date('Y-m-d', strtotime($arrives_time)) . ' ' . $merchant['end_time']) + 86400;
        } else {
            $oend_time = strtotime(date('Y-m-d', strtotime($arrives_time)) . ' ' . $merchant['end_time']);
        }

        //如果员工ID存在就查询员工ID
        $employee = [];
        if ($employee_id) {
            //获取员工信息
            $employee_info = M('employee')->field('realname as employee_realname, avatar as employee_avatar, tel as employee_tel')
                ->where(['id' => $employee_id, 'merchant_id' => $merchant_id])
                ->find();
            if (!$employee_info) {
                Response::error(ReturnCode::DB_READ_ERROR, '请求员工数据失败');
            }

            //员工数据
            $employee['employee_id'] = $employee_id;
            $employee['employee_realname'] = $employee_info['employee_realname'];
            $employee['employee_tel'] = $employee_info['employee_tel'];
            $employee['employee_avatar'] = $employee_info['employee_avatar'];
        }

        //获取卡座商品数据
        $goods_seat_info = D('goods_seat')->where(['id' => $goods_seat_id, 'merchant_id' => $merchant_id])->find();
        if (!$goods_seat_info) {
            Response::error(ReturnCode::DB_READ_ERROR, '请求卡座数据失败');
        }

        //查询历史逾期卡套订单
        $relation_order = $this->_model->field('id,order_no')->where(['merchant_id' => $merchant_id, 'member_id' => $member_id, 'status' => 3, 'order_type' => 2])
            ->order('id desc')->find();
        if ($relation_order === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '请求数据失败');
        }

        //获得当前会员的免预定金特权数据
        $member_card = D('member')->field('free_seat')->join('api_member_privilege ON api_member.level = api_member_privilege.level')
            ->where(['api_member.id' => $member_id])->find();
        if ($member_card === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '请求数据失败');
        }

        $pay_price = $goods_seat_info['set_price'];
        $discount_money = 0;
        //判断是否有免预定金特权
        if ($member_card['free_seat']) {
            $discount_money = $goods_seat_info['set_price'];
            $pay_price = 0;
        }

        //如果支付金额为0的时候,结算金额也为0
        if ($pay_price === 0) {
            $purchase_price = 0;
        } else {
            //获取当前卡座订单扣取手续费后的金额(给商户结算的最终金额)
            $charge_money = $goods_seat_info['set_price'] * C('SERVICE_CHARGE');
            $charge_money = substr(sprintf("%.3f", $charge_money), 0, -1);  //金额舍去小数点后2位
            $purchase_price = $goods_seat_info['set_price'] - $charge_money;
        }

        //如果存在优惠券
        /*if ($card_id) {
            $rs = $this->checkCard($card_id, $pay_price, $member_id, $merchant_id);
            if ($rs !== true) {
                Response::error(ReturnCode::INVALID_REQUEST, $rs);
            }
            //计算支付金额和折扣金额
            $card = M('coupon')->field('card_type,deductible,high_amount')->where(['id' => $card_id, 'status' => 1])->find();
            if ($card && $pay_price != 0) {
                $pay_price = $pay_price - $card['deductible'];
                $discount_money = $discount_money + $card['deductible'];
            }
        }*/

        //获取联系人数据
        $contacts = M('member_contacts')->field('realname,sex,tel,member_id')->find($contacts_id);
        if (!$contacts) {
            Response::error(ReturnCode::DB_READ_ERROR, '联系人不存在');
        }

        //组装数据
        $order = [
            'merchant_id' => $merchant_id,
            'member_id' => $member_id,
            'contacts_realname' => $contacts['realname'],
            'contacts_tel' => $contacts['tel'],
            'contacts_sex' => $contacts['sex'],
            'total_price' => $goods_seat_info['set_price'],
            'pay_price' => $pay_price,
            'purchase_price' => $purchase_price,
            'discount_money' => $discount_money,
            'status' => 1,
            'order_type' => 1,
            'description' => $description,
            'arrives_time' => $date,
            'relation_order_no' => $relation_order['order_no'] ? $relation_order['order_no'] : 0,
            'relation_order_id' => $relation_order['id'] ? $relation_order['id'] : 0,
            'card_id' => $card_id,
            'is_bar' => 0,
            'obegin_time' => $obegin_time,
            'oend_time' => $oend_time,
        ];
        $order = array_merge($order, $employee);
        $goods = [
            'merchant_id' => $merchant_id,
            'member_id' => $member_id,
            'goods_seat_id' => $goods_seat_id,
            'title' => $goods_seat_info['title'],
            'max_people' => $goods_seat_info['max_people'],
            'floor_price' => $goods_seat_info['floor_price'],
            'floor' => $goods_seat_info['floor'],
            'set_price' => $goods_seat_info['set_price'],
            'total_people' => $total_people,
            'seat_number' => $goods_seat_info['seat_number'],
        ];

        //订单数据
        $data = [
            'version' => 'v1.1',  //api接口版本号(项目开发版本)
            'buy_type' => 1,  //购买类型 1正常下单 2续酒下单 3拼吧下单 4拼吧续酒
            'order' => $order,
            'goods' => $goods,
        ];
        $data = json_encode($data);

        //RPC应答
        $option = C('RABBITMQ_OPTION');
        $fibonacci_rpc = new FibonacciRpcClient($option);
        $response = $fibonacci_rpc->call($data);

        $message = json_decode($response, true);
        if ($message['code'] == 200) {
            Response::success($message['data']);
        } else {
            Response::error(ReturnCode::INVALID_REQUEST, $message['msg']);
        }
    }

    /**
     * 检查beanstalkd是否运行正常
     */
    private function verifyBeanstalkd()
    {
        //检查beanstalkd是否运行正常
        $beanConfig = C('BEANS_OPTIONS');
        $status = Tools::beanstalkStats($beanConfig['TUBE_NAME'][0]);
        if (!$status) {
            Log::write('beanstalkd server Crashed', Log::ERR);
            Response::error(ReturnCode::INVALID_REQUEST, '当前服务不可用');
        }
    }

    /**
     * 购物车购买商品 v2.0
     * @param $member_id int    用户ID
     * @param $merchant_id int  商户ID
     * @param $goods_ids array  商品ID数组 商品ID => 商品数量
     * @param $contacts_id int  联系人ID
     * @param $description string  订单描述
     * @param $arrives_time string  到店日期
     * @param $card_id int  卡券ID
     */
    public function buyGoods()
    {
        //接收用户下单数据
        $client = I('post.client', '');                   //用户ID
        $member_id = I('post.member_id', '');                   //用户ID
        $merchant_id = I('post.merchant_id', '');               //商户ID
        $goods_ids = I('post.goods_ids', '');                   //商品ID数组
        $contacts_id = I('post.contacts_id', '');               //联系人ID
        $description = I('post.description', '');               //订单描述
        $arrives_time = I('post.arrives_time', '');             //到店日期
        $card_id = I('post.card_id', '');                       //优惠券ID

        Tools::orderAllowedValid();     //下单时间限制判断
        $this->verifyBeanstalkd();  //检测beanstalkd是否运行正常

        if ($client == 'xcx' && !is_array($goods_ids)) {
            $goods_ids = explode(',', $goods_ids);
        }

        //验证下单各种ID合法性
        if (!is_numeric($member_id) || !is_numeric($merchant_id) || !is_array($goods_ids) || !is_numeric($contacts_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //判断商品数据
        if (!$goods_ids) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请至少购买一件商品');
        }

        $goods_new_arr = [];
        foreach ($goods_ids as $goods_id) {
            $goods_arr = explode('=', $goods_id);
            $goods_id = $goods_arr[0];
            $number = $goods_arr[1];
            $goods_new_arr[$goods_id] = $number;

            //验证商品ID是否合法
            if (!is_numeric($goods_id) || $goods_id < 1 || !is_numeric($number) || $number < 1) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '商品数据不合法');
            }
        }

        //重新赋值
        $goods_ids = $goods_new_arr;

        //验证商品ID是否合法
        /*foreach ($goods_ids as $goods_id => $number) {
            if (!is_numeric($goods_id) || $goods_id < 1 || !is_numeric($number) || $number < 1) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '商品数据不合法');
            }
        }*/

        //验证订单描述
        $description = Tools::filterEmoji($description);
        $description = str_replace('|', '', $description);
        if (!empty($description) && strlen($description) > 100) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '订单备注不能超过100个字符');
        }

        //验证预约到店日期
        if (!preg_match('/^\d{4}(\-|\/|.)\d{1,2}\1\d{1,2}$/', $arrives_time)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请正确选择到店日期');
        }

        /* 获取各种商品数据 */
        //商品类型: 1散客套餐 2卡座套餐 3 单点酒水
        $goods_id_array = array_keys($goods_ids);
        $goods_pack_model = D('goods_pack');

        $date = date('Ymd', strtotime($arrives_time));
        $son_sql = "(select `price` from `api_goods_price` where `date` = '{$date}' AND `goods_id` = api_goods_pack.id) as price";
        $goods = $goods_pack_model->field("id, merchant_id, title, type, {$son_sql}, stock, xu_stock, api_goods_pack.price as case_price, market_price, purchase_price")
            ->where(['id' => ['in', $goods_id_array], 'merchant_id' => $merchant_id])
            ->select();
        if (!$goods) {
            Response::error(ReturnCode::DB_READ_ERROR, '未找到符合条件的商品');
        }

        //价格赋值
        foreach ($goods as $skey => $sgood) {
            if ($sgood['type'] == 3 && is_null($sgood['price'])) {
                $goods[$skey]['price'] = $sgood['case_price'];
            }

            if ($sgood['type'] != 3 && is_null($sgood['price'])) {
                Response::error(ReturnCode::DB_READ_ERROR, $sgood['title'] . ' 暂不支持购买');
            }
        }

        //获取商户的每日库存
        $merchant = D('merchant')->field('sanpack_stock,kapack_stock,begin_time,end_time')->where(['id' => $merchant_id])->find();
        if (!$merchant) {
            $this->error = '获取商户失败';
            return false;
        }

        $obegin_time = strtotime(date('Y-m-d', strtotime($arrives_time)) . ' ' . $merchant['begin_time']);
        if ($merchant['begin_time'] >= $merchant['end_time']) {
            $oend_time = strtotime(date('Y-m-d', strtotime($arrives_time)) . ' ' . $merchant['end_time']) + 86400;
        } else {
            $oend_time = strtotime(date('Y-m-d', strtotime($arrives_time)) . ' ' . $merchant['end_time']);
        }


        //查询每日库存表中已售套餐数据
        $date = date('Ymd', strtotime($arrives_time));
        $goods_sales_stock = M('goods_pack_stock')->where(['date' => $date, 'merchant_id' => $merchant_id])->getField('goods_id,day_sales');

        $pack_total = 0;    //套餐总数
        $pay_price = 0.00;  //总应支付金额
        $market_price = 0.00;   //总市场价格
        $purchase_price = 0.00; //总结算价格
        $every_day_stock = 0;   //每日库存量
        $order_type = 0;    //订单类型 0为纯单品酒水 1卡座 2卡套 3散套
        $pack_goods = [];   //套餐商品存储数组
        $single_goods = []; //单品酒水存储数组
        foreach ($goods as $key => $good) {
            //判断是否非酒水商品
            if ($good['type'] != 3) {
                $pack_total += 1;

                //判断订单类型
                switch ($good['type']) {
                    case 1:
                        $order_type = 3;
                        $every_day_stock = $merchant['sanpack_stock'];
                        break;
                    case 2:
                        $order_type = 2;
                        $every_day_stock = $merchant['kapack_stock'];
                        break;
                }

                //判断套餐商品是否购买数量大于了1件
                if ($goods_ids[$good['id']] != 1) {
                    Response::error(ReturnCode::DB_READ_ERROR, '每个套餐只能购买一件');
                }

                //计算剩余库存
                $sold_number = isset($goods_sales_stock[$good['id']]) ? $goods_sales_stock[$good['id']] : 0;
                $surplus_stock = $goods_pack_model->calculateNowStock($every_day_stock, $good['stock'], $sold_number);
                if ($surplus_stock < 1) {
                    Response::error(ReturnCode::DB_READ_ERROR, '该商品已售馨');
                }

                //套餐商品的购买数量
                $pack_goods[] = [
                    'id' => $good['id'],
                    'amount' => $goods_ids[$good['id']]
                ];

                //判断单品酒水商品
            } elseif ($good['type'] == 3) {
                if ($good['stock'] < $goods_ids[$good['id']]) {
                    Response::error(ReturnCode::DB_READ_ERROR, '商品库存不足');
                }

                $single_goods[$good['id']] = $goods_ids[$good['id']];
            }

            //判断是否选择了多个套餐
            if ($pack_total > 1) Response::error(ReturnCode::DB_READ_ERROR, '同时只能购买一个类型套餐');

            //计算各项总价
            $pay_price += $good['price'] * $goods_ids[$good['id']];
            $market_price += $good['market_price'] * $goods_ids[$good['id']];
            $purchase_price += $good['purchase_price'] * $goods_ids[$good['id']];
        }

        //优惠结果总价
        $discount_money = $market_price - $pay_price;

        //如果存在优惠券
        if ($card_id) {
            $couponModel = D('coupon');
            if (!$couponModel->checkCardIsUseful($card_id, $member_id, $pay_price, $merchant_id, $goods)) {
                Response::error(ReturnCode::NOT_EXISTS, $couponModel->getError());
            }
            //计算支付金额和折扣金额
            $card = $couponModel->field('card_type,deductible,high_amount')->where(['id' => $card_id, 'status' => 1])->find();
            if ($card) {
                $pay_price = $pay_price - $card['deductible'];
                $discount_money = $market_price - $pay_price;
                if ($pay_price <= 0) {
                    $discount_money = $market_price;
                }
            }
        }

        //获取联系人数据
        $contacts = M('member_contacts')->field('realname,sex,tel,member_id')->find($contacts_id);
        if (!$contacts) {
            Response::error(ReturnCode::DB_READ_ERROR, '联系人不存在');
        }


        //订单基本信息
        $data['version'] = 'v1.1';  //api接口版本号(项目开发版本)
        $data['buy_type'] = 1;  //购买类型 1正常下单 2续酒下单
        $data['order']['merchant_id'] = $merchant_id;
        $data['order']['member_id'] = $member_id;
        $data['order']['contacts_realname'] = $contacts['realname'];
        $data['order']['contacts_tel'] = $contacts['tel'];
        $data['order']['contacts_sex'] = $contacts['sex'];
        $data['order']['total_price'] = $market_price;
        $data['order']['pay_price'] = $pay_price > 0 ? $pay_price : 0;
        $data['order']['purchase_price'] = $purchase_price;
        $data['order']['discount_money'] = $discount_money > 0 ? $discount_money : 0;
        $data['order']['status'] = 1;
        $data['order']['settlement_status'] = 0;
        $data['order']['order_type'] = $order_type;
        $data['order']['description'] = $description;
        $data['order']['arrives_time'] = strtotime($arrives_time);
        $data['order']['card_id'] = $card_id ? $card_id : 0;
        $data['order']['is_xu'] = 0;        //是否为续酒订单
        $data['order']['is_bar'] = 0;        //是否为拼吧订单
        $data['order']['obegin_time'] = $obegin_time;        //是否为拼吧订单
        $data['order']['oend_time'] = $oend_time;        //是否为拼吧订单

        //订单商品数据
        $data['goods'] = ['pack_goods' => $pack_goods, 'single_goods' => $single_goods];

        //调用消息队列生成订单
        $data = json_encode($data);
        $option = C('RABBITMQ_OPTION');
        $fibonacci_rpc = new FibonacciRpcClient($option);
        $response = $fibonacci_rpc->call($data);

        $message = json_decode($response, true);
        if ($message['code'] == 200) {
            Response::setSuccessMsg('普通购买下单成功');
            Response::success($message['data']);
        } else {
            Response::error(ReturnCode::INVALID_REQUEST, $message['msg']);
        }
    }


    /**
     * 检查该时间是否可以正常的购买商品
     */
    public function check_buy_auth()
    {
        $merchant_id = I('post.merchant_id', 0);
        $member_id = I('post.member_id', 0);
        $arrives_time = I('post.date', 0);
        $goods_ids = I('post.goods_ids', '');
        $client = I('post.client', '');

        if ($client == 'xcx') {
            $goods_ids = explode(',', $goods_ids);
        }

        //验证数据合法性
        if (!is_array($goods_ids) || !is_numeric($merchant_id) || !is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        Tools::orderAllowedValid();     //下单时间限制判断 TODO :: 感觉这里有点问题，开放购买过了第二天凌晨就买不了了

        $fields = M('merchant')->field('sanpack_stock,kapack_stock,preordain_cycle,begin_time,end_time,open_buy')->where(['id' => $merchant_id])->find();
        if ($fields === false) {
            Response::error(ReturnCode::NOT_FOUND, '商户数据不存在');
        }

        //获取商户的最大预定周期时间
        $preordain_cycle = $fields['preordain_cycle'];
        $preordain_cycle_second = $preordain_cycle * 24 * 60 * 60;

        //支持的最大选择时间
        $max_second = strtotime(date('Y-m-d', time())) + $preordain_cycle_second;
        $send_second = strtotime($arrives_time);
        if ($max_second < $send_second) {
            $this->error = '日期超出预定周期';
            return false;
        }

        //首先查找商品库存是否正常
        $good_ids = [];
        foreach ($goods_ids as $goods_id) {
            $goods_pack = explode('=', $goods_id);
            $good_ids[$goods_pack[0]] = $goods_pack[1];
        }

        $goods_pack_ids = array_keys($good_ids);

        //获取所有商品信息
        $goods = M('goods_pack')->where(['id' => ['in', $goods_pack_ids], 'status' => 1])->select();
        if (!$goods) {
            Response::error(ReturnCode::DATA_EXISTS, '未找到符合要求的商品');
        }

        $date = date('Ymd', strtotime($arrives_time));
        $goods_sales_stock = M('goods_pack_stock')->where(['date' => $date, 'merchant_id' => $merchant_id])->getField('goods_id,day_sales');

        $pack_total = 0;
        foreach ($goods as $key => $good) {

            if ($good['type'] != 3) {
                $pack_total += 1;
                switch ($good['type']) {
                    case 1:
                        $every_day_stock = $fields['sanpack_stock'];
                        if ($fields['open_buy'] == 0) {
                            $start_time = strtotime($arrives_time . ' ' . $fields['begin_time']);
                            if ($start_time < time()) {
                                Response::error(ReturnCode::PARAM_INVALID, '该酒吧暂不支持营业时间之内购买当天优惠套餐');
                            }
                        }

                        $where = ['member_id' => $member_id, 'merchant_id' => $merchant_id, 'order_type' => 3, 'status' => ['IN', [1, 2, 7]], 'top_order_id' => 0, 'is_bar' => 0];

                        break;
                    case 2:
                        $where = ['member_id' => $member_id, 'merchant_id' => $merchant_id, 'order_type' => ['IN', [1, 2]], 'status' => ['IN', [1, 2, 3, 7]], 'is_bar' => 0];

                        $every_day_stock = $fields['kapack_stock'];
                        break;
                }

                //判断套餐商品是否购买数量大于了1件
                if ($good_ids[$good['id']] != 1) {
                    Response::error(ReturnCode::DB_READ_ERROR, '每个套餐只能购买一件1');
                }

                //计算剩余库存
                $sold_number = isset($goods_sales_stock[$good['id']]) ? $goods_sales_stock[$good['id']] : 0;
                $surplus_stock = D('goods_pack')->calculateNowStock($every_day_stock, $good['stock'], $sold_number);
                if ($surplus_stock < 1) {
                    Response::error(ReturnCode::DB_READ_ERROR, $good['title'] . '该商品已售馨');
                }

                //判断单品酒水商品
            } elseif ($good['type'] == 3) {
                if ($good['stock'] < $good_ids[$good['id']]) {
                    Response::error(ReturnCode::DB_READ_ERROR, $good['title'] . '商品库存不足');
                }
            }

            if ($pack_total > 1) {
                Response::error(ReturnCode::DB_READ_ERROR, '套餐只能购买一件');
            }
        }

        //验证是否存在未完成的订单
        $orderInfos = M('order')->field('id,status,created_time,arrives_time,order_type')->where($where)->select();
        if ($orderInfos === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '数据请求失败');
        }
        foreach ($orderInfos as $orderInfo) {

            if (strtotime($arrives_time) == $orderInfo['arrives_time']) {
                Response::error(ReturnCode::DB_READ_ERROR, '您当日已存在未完成的套餐,暂不能购买该商户的今日的套餐');
            }

            if ($orderInfo['order_type'] == 2 && $orderInfo['status'] == 3) {
                Response::error(ReturnCode::DB_READ_ERROR, '您有逾期卡座套餐未消费,请消费后再购买');
            }

            if ($orderInfo['order_type'] == 1 && in_array($orderInfo['status'], [1, 2, 7])) {
                Response::error(ReturnCode::DB_READ_ERROR, '您当日已预定该酒吧卡座不能再购买卡座套餐');
            }
        }

        Response::success();
    }

}