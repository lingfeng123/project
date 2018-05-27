<?php
/**
 * FileName: RenewWineController.class.php
 * User: Comos
 * Date: 2017/12/21 11:15
 */

namespace V1_1\Controller;


use Org\Util\AuthSign;
use Org\Util\FibonacciRpcClient;
use Org\Util\Http;
use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\Tools;
use Think\Log;

class RenewController extends BaseController
{

    /**
     * 获取订单续酒套餐列表
     */
    public function winePackList()
    {
        $version = I('post.version', '');
        $order_id = I('post.order_id', 0);
        $member_id = I('post.member_id', 0);
        $merchant_id = I('post.merchant_id', '');
        $goods_type = I('goods_type', '');
        $page = I('post.page', 1);
        $pagesize = I('post.page_size', C("PAGE.PAGESIZE"));

        //判断当前商户ID是否传入合法
        if (!is_numeric($merchant_id) || !is_numeric($page) || !is_numeric($pagesize) || !is_numeric($order_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //判断订单类型
        if (!in_array($goods_type, [1, 2, 3])) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '商品类型不合法');
        }

        //查询当前商户当天的所有套餐
        $list = D('goods_pack')->getWineGoodsPack($merchant_id, $page, $goods_type, $pagesize, $order_id, $member_id);
        if ($list === false) {
            Response::error(ReturnCode::DATA_EXISTS, '获取套餐列表数据失败');
        }

        Response::setSuccessMsg('续酒商品获取成功');
        Response::success($list);
    }


    /**
     * 验证是否能否续酒
     */
    public function checkWineTime()
    {
        $order_id = I('post.order_id', '');
        $merchant_id = I('post.merchant_id', '');

        if (!is_numeric($order_id) && !is_numeric($merchant_id)) Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        $orderInfo = M('order')->field('id,order_no,order_type,status,arrives_time,updated_time')->where(['id' => $order_id, 'status' => 4])->find();
        if (empty($orderInfo)) {
            Response::error(ReturnCode::DATA_EXISTS, '订单不存在,无法续酒');
        }
        $wineOrder = M('order')->where(['merchant_id' => $merchant_id, 'status' => 1, 'top_order_id' => $order_id])->getField('id');
        if ($wineOrder) {
            Response::error(ReturnCode::INVALID_REQUEST, '您存在未支付的续酒订单');
        }

        $wineOrder1 = M('order')->where(['merchant_id' => $merchant_id, 'status' => 7, 'top_order_id' => $order_id])->getField('id');
        if ($wineOrder1) {
            Response::error(ReturnCode::INVALID_REQUEST, '您还有未完成的续酒订单，无法续酒');
        }

        $this->_verifyXuTime($orderInfo, $merchant_id);

        Response::success();
    }

    /**
     * 验证续酒时间是否合法
     * @param $orderInfo
     * @param $merchant_id
     */
    private function _verifyXuTime($orderInfo, $merchant_id)
    {
        //获取商户的最晚营业时间
        $merchant = M('merchant')->field('begin_time,end_time')->where(['id' => $merchant_id])->find();
        $a_time = str_replace(':', '', $merchant['begin_time']);
        $b_time = str_replace(':', '', $merchant['end_time']);

        //判断该订单是否已经超过了营业时间(订单只能在当天的营业范围内才能续酒)
        if ($a_time >= $b_time) {
            //截止格式化时间
            $laytime = date('Y-m-d', strtotime('+ 1 day', $orderInfo['arrives_time'])) . ' ' . $merchant['end_time'];
        } else {
            $laytime = date('Y-m-d', $orderInfo['arrives_time']) . ' ' . $merchant['end_time'];
        }

        //起点时间戳
        $start_time = strtotime(date('Y-m-d', $orderInfo['arrives_time']) . ' ' . $merchant['begin_time']);
        //计算提前时间点
        $start_time = $start_time - C('EARLY_COMPLETION_TIME');

        //转换时间戳
        $laytime = strtotime($laytime);
        $now_time = time();
        if ($now_time >= $laytime && $now_time <= $start_time) {
            Response::error(ReturnCode::INVALID, '订单已过期，不支持续酒。');
        }
    }

    /**
     * 2.0验证库存的新方法
     */
    public function checkWinePackStock()
    {
        $order_id = I('order_id', '');
        $goods_ids = I('post.goods_ids','');
        $client = I('post.client','');

        if($client == 'xcx'){
            $goods_ids = explode(',',$goods_ids);
        }

        if(!is_array($goods_ids) && !is_numeric($order_id)){
            Response::error(ReturnCode::PARAM_INVALID,'请求参数不正确');
        }

        $order = M('order')->field('merchant_id,arrives_time')->where(['id'=>$order_id])->find();
        if($order ===false){
            Response::error(ReturnCode::DATA_EXISTS,'主订单不存在');
        }

        $wineOrder = M('order')->where(['merchant_id' => $order['merchant_id'], 'status' => 1, 'top_order_id' => $order_id])->getField('id');
        if ($wineOrder) {
            Response::error(ReturnCode::INVALID_REQUEST, '您存在未支付的续酒订单');
        }

        $wineOrder1 = M('order')->where(['merchant_id' => $order['merchant_id'], 'status' => 7, 'top_order_id' => $order_id])->getField('id');
        if ($wineOrder1) {
            Response::error(ReturnCode::INVALID_REQUEST, '您还有未完成的续酒订单，无法续酒');
        }

        $this->_verifyXuTime($order, $order['merchant_id']);

        $good_pack_num = [];
        foreach ($goods_ids as $item){
            $good_pack_ids = explode('=',$item);
            $good_pack_num[$good_pack_ids[0]] =$good_pack_ids[1];
        }

        $good_ids = array_keys($good_pack_num);

        $goods = M('goods_pack')->where(['id' => ['in', $good_ids], 'status' => 1])->select();
        if (!$goods) {
            Response::error(ReturnCode::DATA_EXISTS, '未找到符合要求的商品');
        }

        foreach ($goods as $key => $good) {

            if($good['xu_stock'] == 0){
                Response::error(ReturnCode::INVALID, $good['title'].'商品已售罄');
            }
            // 验证拼吧续酒商品库存是否充足
            $good_diff = $good['xu_stock'] -$good_pack_num[$good['id']];

            if($good_diff < 0){
                Response::error(ReturnCode::INVALID, $good['title'].'商品库存不足');
            }
        }

        Response::success();
    }


    /**
     * 点击购买的时候验证库存是否充足 v1.0
     * 2.0中此方法已废弃
     */
    public function checkWinePack()
    {
        $merchant_id = I('post.merchant_id', '');
        $order_id = I('order_id', '');
        $pack_type = I('pack_type', '');
        $version = I('post.version', '');

        //判断当前商户ID是否传入合法
        if (!is_numeric($merchant_id) || !is_numeric($order_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //查询该订单是否是已经完成的是否存在
        $orderInfo = M('order')->field('id,order_no,order_type,status,arrives_time,member_id,updated_time')->where(['id' => $order_id, 'status' => 4])->find();
        if (empty($orderInfo)) {
            Response::error(ReturnCode::NOT_EXISTS, '关联订单不存在');
        }

        //判断是否存在未支付的续酒订单
        $wineOrder = M('order')->where(['merchant_id' => $merchant_id, 'status' => 1, 'top_order_id' => $order_id])->getField('id');
        if ($wineOrder) {
            Response::error(ReturnCode::DB_READ_ERROR, '您存在未支付的续酒订单');
        }

        $wineOrder1 = M('order')->where(['merchant_id' => $merchant_id, 'status' => 7, 'top_order_id' => $order_id])->getField('id');
        if ($wineOrder1) {
            Response::error(ReturnCode::INVALID_REQUEST, '您还有未完成的续酒订单，无法续酒');
        }

        //验证续酒时间是否合法
        $this->_verifyXuTime($orderInfo, $merchant_id);

        //格式化订单完成更新时间
        $date = date('Ymd');
        //检查续酒库存是否充足
        $stock = M('goods_pack_stock')->where(['merchant_id' => $merchant_id, 'date' => $date, 'type' => $pack_type])->getField('wine_stock');
        if ($stock === null) {
            $san_wine_stock = M('merchant')->where(['id' => $merchant_id])->getField('san_wine_stock');
            $stock = $san_wine_stock;
        }
        if ($stock === 0) {
            Response::error(ReturnCode::INVALID_REQUEST, '该套餐已售罄');
        }

        Response::success();
    }


    /**
     * 续酒订单列表
     */
    public function orderList()
    {
        $order_id = I('post.order_id', '');
        $page = I('post.page', 1);
        $pagesize = I('post.page_size', C('PAGE.PAGESIZE'));
        $version = I('post.version', '');

        //检查传入数据是否合法
        if (!is_numeric($order_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //自动修改订单状态请求
        /*$time = time();
        $post_data = ['sign' => AuthSign::getSign($time), 'timestamp' => $time];
        Http::post(C('MEMBER_API_URL') . U('V1_1/Order/updateStatus'), '', $post_data);*/

        $orderModel = D('order');
        $list = $orderModel->getXuWineOrderList($order_id, $page, $pagesize);
        if (!$list) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取续酒订单数据失败');
        }

        Response::setSuccessMsg('获取续酒订单成功');
        Response::success($list);
    }

    /**
     * 续酒下单
     */
    public function createWineOrder()
    {
        //接收用户下单数据
        $client = I('post.client', '');
        $order_id = I('post.order_id', '');                 //父级订单号
        $description = I('post.description', '');           //订单描述
        $desk_number = I('post.desk_number', '');           //桌号
        $card_id = I('post.card_id', 0);                   //优惠券ID
        $goods_ids = I('post.goods_ids', '');               //商品ID数组

        //检查beanstalkd是否运行正常
        $beanConfig = C('BEANS_OPTIONS');
        $status = Tools::beanstalkStats($beanConfig['TUBE_NAME'][0]);
        if (!$status) {
            Log::write('beanstalkd server Crashed', Log::ERR);
            Response::error(ReturnCode::INVALID_REQUEST, '当前服务不可用');
        }

        if ($client == 'xcx' && !is_array($goods_ids)) {
            $goods_ids = explode(',', $goods_ids);
        }

        //商品所属商户
        if (!is_numeric($order_id) || (!empty($card_id) && !is_numeric($card_id)) || !is_array($goods_ids) || !$goods_ids) {
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

        $goods_ids = $goods_new_arr;

        //验证订单描述
        $description = Tools::filterEmoji($description);
        $description = str_replace('|', '', $description);
        if (mb_strlen($description) > 100) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '续酒备注不能超过100个字符');
        }

        //查询续酒关联的父级订单数据
        $fields = 'id,order_no,merchant_id,member_id,contacts_realname,contacts_tel,contacts_sex,order_type,employee_id,employee_realname,employee_avatar,employee_tel,arrives_time';
        $main_order = D('order')->field($fields)->find($order_id);
        if (!$main_order) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取订单失败');
        }

        //员工ID不存在,查询操作记录表中的员工信息
        if (!$main_order['employee_id']) {
            //查询订单对应的员工信息
            $main_order['employee_id'] = M('employee_operation')
                ->where(['order_no' => $main_order['order_no'], 'merchant_id' => $main_order['merchant_id'], 'type' => 3])
                ->getField('employee_id');
            if (!$main_order['employee_id']) {
                $main_order['employee_realname'] = '';
                $main_order['employee_avatar'] = '';
                $main_order['employee_tel'] = 0;
                $main_order['employee_id'] = 0;
            } else {
                //获取员工信息
                $employee = D('employee')->field('realname,avatar,tel')->where(['id' => $main_order['employee_id']])->find();
                $main_order['employee_realname'] = $employee['realname'];
                $main_order['employee_avatar'] = $employee['avatar'];
                $main_order['employee_tel'] = $employee['tel'];
            }
        }

        //获取商品数据 商品类型: 1散客套餐 2卡座套餐 3 单点酒水
        $goods_id_array = array_keys($goods_ids);
        $goods_pack_model = D('goods_pack');

        $goods_price_date = date('Ymd', $main_order['arrives_time']);
        $son_sql = "(select `price` from `api_goods_price` where `date` = '{$goods_price_date}' AND `goods_id` = api_goods_pack.id) as price";
        $goods = $goods_pack_model->field("id,merchant_id,type,{$son_sql},stock, api_goods_pack.price as case_price, xu_stock,market_price,purchase_price")
            ->where(['id' => ['in', $goods_id_array], 'merchant_id' => $main_order['merchant_id']])
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
                Response::error(ReturnCode::DB_READ_ERROR, '商品暂不支持购买');
            }
        }

        $pay_price = 0.00;      //总应支付金额
        $market_price = 0.00;   //总市场价格
        $purchase_price = 0.00; //总结算价格
        $order_type = $main_order['order_type'];        //订单类型 0为纯单品酒水 1卡座 2卡套 3散套 (续酒跟着主订单的order_type走)
        $goods_send = [];       //商品ID => 商品购买数量
        foreach ($goods as $key => $good) {
            //判断库存是否充足
            if ($good['xu_stock'] < $goods_ids[$good['id']]) {
                Response::error(ReturnCode::DB_READ_ERROR, '商品库存不足');
            }

            //计算各项总价
            $pay_price += $good['price'] * $goods_ids[$good['id']];
            $market_price += $good['market_price'] * $goods_ids[$good['id']];
            $purchase_price += $good['purchase_price'] * $goods_ids[$good['id']];

            //套餐商品的购买数量
            $goods_send[$good['id']] = $goods_ids[$good['id']];
        }

        //优惠结果总价
        $discount_money = $market_price - $pay_price;

        //如果存在优惠券
        if ($card_id) {
            $couponModel = D('coupon');
            if (!$couponModel->checkCardIsUseful($card_id, $main_order['member_id'], $pay_price, $main_order['merchant_id'], $goods)) {
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

        //订单基本信息
        $data['version'] = 'v1.1';  //api接口版本号(项目开发版本)
        $data['buy_type'] = 2;  //购买类型 1正常 2续酒
        $data['order']['merchant_id'] = $main_order['merchant_id'];
        $data['order']['member_id'] = $main_order['member_id'];
        $data['order']['contacts_realname'] = $main_order['contacts_realname'];
        $data['order']['contacts_tel'] = $main_order['contacts_tel'];
        $data['order']['contacts_sex'] = $main_order['contacts_sex'];
        $data['order']['total_price'] = $market_price;    //市场总价
        $data['order']['pay_price'] = $pay_price > 0 ? $pay_price : 0;     //实付金额
        $data['order']['purchase_price'] = $purchase_price;  //结算价格
        $data['order']['discount_money'] = $discount_money > 0 ? $discount_money : 0;    //优惠金额
        $data['order']['status'] = 1;    //订单状态
        $data['order']['settlement_status'] = 0;    //结算状态
        $data['order']['order_type'] = $order_type;  //订单类型
        $data['order']['arrives_time'] = $main_order['arrives_time'];
        $data['order']['employee_id'] = $main_order['employee_id'];
        $data['order']['employee_realname'] = $main_order['employee_realname'];
        $data['order']['employee_avatar'] = $main_order['employee_avatar'];
        $data['order']['employee_tel'] = $main_order['employee_tel'];
        $data['order']['description'] = $description; //订单备注
        $data['order']['desk_number'] = $desk_number; //桌号
        $data['order']['top_order_id'] = $order_id;    //父级订单ID
        $data['order']['card_id'] = $card_id; //优惠券ID
        $data['order']['is_xu'] = 1;        //是否为续酒订单
        $data['order']['is_bar'] = 0;        //是否为拼吧订单

        //订单商品数据
        $data['goods'] = $goods_send;

        //调用消息队列生成订单
        $data = json_encode($data);
        //Log::write($data, Log::INFO);

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
}