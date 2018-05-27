<?php
/**
 * FileName: OrderModel.class.php
 * User: Comos
 * Date: 2017/8/22 18:59
 */

namespace V1_1\Model;


use Org\Util\AbolishException;
use Org\Util\CancelException;
use Org\Util\OverdueException;
use Org\Util\Tools;
use Think\Model;

@date_default_timezone_set("PRC");

class OrderModel extends Model
{
    /**
     * 订单列表
     * @param $member_id
     * @param $page
     * @param $pagesize
     * @return bool
     */
    public function getMemberOrderList($member_id, $page, $pagesize)
    {
        //统计所有订单
        $where = ['api_order.member_id' => $member_id, 'is_xu' => 0, 'is_bar' => 0];
        $orders['total'] = $this->join('api_merchant ON api_order.merchant_id = api_merchant.id', 'LEFT')
            ->where($where)->count();

        //查询用户所有订单
        $list = $this->field('api_order.id,
        api_order.order_no,
        api_merchant.id as merchant_id,
        api_merchant.title as merchant_title,
        api_merchant.logo,
        api_merchant.tel,
        api_merchant.begin_time,
        api_order.pay_price,
        api_order.contacts_realname,
        api_order.contacts_tel,
        api_order.contacts_sex,
        api_order.status,
        api_order.order_type,
        api_order.is_evaluate as is_comment,
        from_unixtime(arrives_time, "%Y-%m-%d") as arrives_time,
        api_order.incr_time, 
        api_order.created_time, 
        api_order.obegin_time, 
        api_order.oend_time, 
        from_unixtime(api_order.created_time) as format_time,
        (select count(id) from api_order as A where A.top_order_id = api_order.id) winecount
        ')
            ->join('api_merchant ON api_order.merchant_id = api_merchant.id', 'LEFT')
            ->where($where)
            ->order(['api_order.created_time' => 'desc', 'api_order.id' => 'desc'])
            ->page($page, $pagesize)
            ->select();

        //判断数据获取状态
        if ($orders === false) {
            $this->error = '获取订单数据失败';
            return false;
        }

        //处理数据
        $attachment_url = C('ATTACHMENT_URL');
        foreach ($list as $key => $value) {
            //订单状态为2时才存在incr_time字段
            if ($value['status'] != 7) {
                unset($list[$key]['incr_time']);
            } else {
                $list[$key]['incr_time'] = (int)$list[$key]['incr_time'];
            }
            //默认按钮不显示
            $list[$key]['button_display'] = 0;

            //订单状态为完成时才存在is_comment字段
            if ($value['status'] != 4) {
                unset($list[$key]['is_comment']);
            } else {
                $list[$key]['is_comment'] = (int)$list[$key]['is_comment'];

                //卡座套餐, 优惠套餐可续酒按钮展示
                if (in_array($value['order_type'], [2, 3])) {
                    //判断续酒按钮是否显示
                    $merchant = M('merchant')->field('begin_time,end_time')->where(['id' => $value['merchant_id']])->find();
                    $a_time = str_replace(':', '', $merchant['begin_time']);
                    $b_time = str_replace(':', '', $merchant['end_time']);

                    //判断该订单是否已经超过了营业时间(订单只能在当天的营业范围内才能续酒)
                    if ($a_time >= $b_time) {
                        //截止格式化时间
                        $laytime = date('Y-m-d', strtotime('+ 1 day', strtotime($value['arrives_time']))) . ' ' . $merchant['end_time'];
                    } else {
                        $laytime = $value['arrives_time'] . ' ' . $merchant['end_time'];
                    }

                    //起点时间戳
                    $start_time = strtotime($value['arrives_time'] . ' ' . $merchant['begin_time']);
                    //计算提前时间点
                    $start_time = $start_time - C('EARLY_COMPLETION_TIME');

                    //转换时间戳
                    $laytime = strtotime($laytime);
                    $now_time = time();
                    if ($now_time <= $laytime && $now_time >= $start_time) {
                        $list[$key]['button_display'] = 1;
                    }
                }
            }

            //遍历转换数据类型
            $list[$key]['id'] = (int)$list[$key]['id'];
            $list[$key]['merchant_id'] = (int)$list[$key]['merchant_id'];
            $list[$key]['status'] = (int)$list[$key]['status'];
            $list[$key]['order_type'] = (int)$list[$key]['order_type'];
            $list[$key]['created_time'] = (int)$list[$key]['created_time'];
            $list[$key]['logo'] = $list[$key]['logo'] ? $attachment_url . $list[$key]['logo'] : '';
        }

        $orders['list'] = $list;

        //转换数据类型
        $orders['total'] = (int)$orders['total'];
        return $orders;
    }


    /**
     * 根据订单号获取订单数据
     * @param $order_id
     * @return mixed
     */
    public function getorderDetail($order_id)
    {
        $fields = "id, order_no,
            merchant_id,
            member_id, 
            contacts_realname, 
            contacts_tel, 
            contacts_sex, 
            total_price,
            pay_price,
            purchase_price,
            discount_money,
            status,
            order_type,
            payment,
            description,
            arrives_time,
            incr_time,
            employee_id,
            employee_realname,
            employee_avatar,
            employee_tel,
            cancel_reason,
            card_id,
            is_bar,
            created_time,
            obegin_time,
            oend_time,
            from_unixtime(created_time) as format_time, 
            relation_order_no,
            relation_order_id,
            is_evaluate as is_comment";
        $order = $this->field($fields)->find($order_id);
        if (!$order) {
            $this->error = '获取订单失败';
            return false;
        }

        $order['merchant'] = M('Merchant')->field('title as merchant_title,logo,tel as merchant_tel, begin_time,end_time')
            ->where(['id' => $order['merchant_id']])->find();
        if (!$order['merchant']) {
            $this->error = '获取商户失败';
            return false;
        }

        $attachment_url = C('ATTACHMENT_URL');
        $order['merchant']['logo'] = $order['merchant']['logo'] ? $attachment_url . $order['merchant']['logo'] : '';
        $order['merchant']['begin_time'] = Tools::formatTimeStr($order['merchant']['begin_time']);
        $order['merchant']['end_time'] = Tools::formatTimeStr($order['merchant']['end_time']);

        if ($order['card_id'] != 0) {
            $order['deductible'] = M('coupon')->where(['id' => $order['card_id']])->getField('deductible');
        } else {
            $order['deductible'] = '0.00';
        }


        switch ($order['order_type']) {
            case 0:

                //单品酒水
                $goods = $this->getpackAndSingleGoods($order['order_no']);
                $order['goods_pack'] = $this->addImageUrl($attachment_url, $goods);
                $order['pack_price'] = $this ->getOrderPackPrice($goods);

                unset($order['employee_id']);
                unset($order['employee_realname']);
                unset($order['employee_avatar']);
                unset($order['employee_tel']);

                break;
            case 1:

                //卡座
                $seat = $this->getSeatGoods($order['order_no']);
                $order['goods_seat'] = $seat;
                //逾期订单
                if ($order['relation_order_no']) {
                    $goods = $this->getpackAndSingleGoods($order['relation_order_no']);
                    $order['overdue_goods'] = $this->addImageUrl($attachment_url, $goods);
                }

                //订单员工头像
                $order['employee_avatar'] = $order['employee_avatar'] ? $attachment_url . $order['employee_avatar'] : '';

                break;
            case 2:

                //卡套
                $goods = $this->getpackAndSingleGoods($order['order_no']);
                $order['goods_pack'] = $this->addImageUrl($attachment_url, $goods);
                $order['pack_price'] = $this ->getOrderPackPrice($goods);

                $seat = $this->getSeatGoods($order['order_no']);
                $order['goods_seat'] = $seat;
                //获取会员逾期时间
                $order['overdue_day'] = M('member')->join('api_member_privilege ON api_member_privilege.level = api_member.level')
                    ->where(['api_member.id' => $order['member_id']])->getField('api_member_privilege.delayed');

                //订单员工头像
                $order['employee_tel'] = $order['employee_tel'] ? $order['employee_tel'] : '';
                $order['employee_avatar'] = $order['employee_avatar'] ? $attachment_url . $order['employee_avatar'] : '';

                break;
            case 3:

                //散套
                $goods = $this->getpackAndSingleGoods($order['order_no']);
                $order['goods_pack'] = $this->addImageUrl($attachment_url, $goods);
                $order['pack_price'] = $this ->getOrderPackPrice($goods);

                //删除员工数据
                unset($order['employee_id']);
                unset($order['employee_realname']);
                unset($order['employee_avatar']);
                unset($order['employee_tel']);

                break;
        }

        if($order['order_type']==1){
            $order['Platform'] = $order['total_price'] - $order['pay_price'];
        }else{
            //计算平台优惠
            $order['Platform'] = $order['total_price'] - $order['pack_price'];
        }


        switch ($order['order_type']) {
            case 2:
                if (!$seat) {
                    $order['goods_seat'] = (object)[];
                } else {
                    $order['goods_seat'] = $seat;
                }
                break;
        }

        $order_overtime = C('ORDER_OVERTIME');
        $order_total_time = $order['created_time'] + $order_overtime;
        if ($order_total_time > time()) {
            $order['have_time'] = $order_total_time - time();
        } else {
            $order['have_time'] = 0;
        }

        //转换数据类型
        $order['id'] = (int)$order['id'];
        $order['merchant_id'] = (int)$order['merchant_id'];
        $order['member_id'] = (int)$order['member_id'];
        $order['contacts_sex'] = (int)$order['contacts_sex'];
        $order['status'] = (int)$order['status'];
        $order['order_type'] = (int)$order['order_type'];
        $order['payment'] = (int)$order['payment'];
        $order['incr_time'] = (int)$order['incr_time'];
        $order['is_comment'] = (int)$order['is_comment'];
        $order['card_id'] = (int)$order['card_id'];

        return $order;
    }

    private function getOrderPackPrice($goods)
    {
        $pack_price = 0.00;
        foreach ($goods as $good){
            $pack_price +=$good['pack_price'] * $good['pack_amount'];
        }
        return $pack_price;
    }



    /**
     * 获取套餐和单品酒水商品
     * @param $order_no
     * @return mixed
     */
    private function getpackAndSingleGoods($order_no)
    {
        $fields = "goods_pack_id as pack_id, title as pack_title, price as pack_price, image as pack_image, amount as pack_amount, goods_type, market_price, pack_description";
        $goods = M('order_pack')->field($fields)->where(['order_no' => $order_no])->select();
        return $goods;
    }

    /**
     * 获取卡座商品数据
     * @param $order_no
     * @return mixed
     */
    private function getSeatGoods($order_no)
    {
        $fields = "goods_seat_id as seat_id, max_people, floor_price, set_price, total_people, seat_number";
        $goods = M('order_seat')->field($fields)->where(['order_no' => $order_no])->find();
        return $goods;
    }

    /**
     * 添加商品图片前缀
     * @param $attachment_url
     * @param $goods
     */
    private function addImageUrl($attachment_url, $goods)
    {
        if (isset($goods)) {
            foreach ($goods as $key => $good) {
                $goods[$key]['pack_image'] = empty($goods[$key]['pack_image']) ? '' : $attachment_url . $goods[$key]['pack_image'];
            }
        }

        return $goods;
    }

    /**
     * 根据订单号获取订单详情
     * @param $order_id
     * @return mixed
     */
    /*public function getOrderInfoByOrderNumber($order_id)
    {
        //获取订单数据
        $order_info = $this->field('id,
            order_no,
            merchant_id,
            member_id, 
            contacts_realname, 
            contacts_tel, 
            contacts_sex, 
            total_price,
            pay_price,
            purchase_price,
            discount_money,
            status,
            order_type,
            payment,
            description,
            arrives_time,
            incr_time,
            employee_id,
            employee_realname,
            employee_avatar,
            employee_tel,
            cancel_reason,
            card_id,
            created_time,
            from_unixtime(created_time) as format_time, 
            from_unixtime(updated_time) as updated_time,
            relation_order_no,
            is_evaluate')
            ->where(['order_id' => $order_id])
            ->find();

        if (!$order_info) {
            $this->error = '获取订单数据失败';
            return false;
        }

        //格式化到店时间
        $order_info['arrives_time'] = date('Y-m-d', $order_info['arrives_time']);

        //获取订单商户数据
        $merchant_info = M('Merchant')->field('title as merchant_title,logo,tel as merchant_tel, begin_time,end_time')->where(['id' => $order_info['merchant_id']])->find();
        if (!$merchant_info) {
            $this->error = '获取商户数据失败';
            return false;
        }

        //组装图片地址
        $attachment_url = C('ATTACHMENT_URL');
        $merchant_info['logo'] = $merchant_info['logo'] ? $attachment_url . $merchant_info['logo'] : 0;

        //根据order_type获取套餐详情
        switch ($order_info['order_type']) {
            case 0:


                break;
            case 1:

                //卡座订单
                $goods_info = M('OrderSeat')->field('goods_seat_id,	title,max_people,floor_price,set_price,total_people,seat_number')
                    ->where(['order_no' => $order_info['order_no']])
                    ->find();

                //订单员工头像
                $order_info['employee_avatar'] = $order_info['employee_avatar'] ? $attachment_url . $order_info['employee_avatar'] : '';

                //判断是否存在逾期卡套
                if (!empty($order_info['relation_order_no'])) {

                    //查询关联卡套信息
                    $order_info['overdue_order'] = M('OrderPack')->field('order_no, title as pack_title,image as pack_image,price as pack_price')
                        ->where(['order_no' => $order_info['relation_order_no']])
                        ->select();

                    if (!$order_info['overdue_order']) {
                        $this->error = '获取逾期卡套订单失败';
                        return false;
                    }

                    foreach ($order_info['overdue_order'] as $key => $value) {
                        //组装套餐图片地址
                        $order_info['overdue_order'][$key]['pack_image'] = !empty($order_info['overdue_order'][$key]['pack_image']) ? $attachment_url . $order_info['overdue_order'][$key]['pack_image'] : '';;
                    }
                }

                break;
            case 2:

                //卡套

                //删除员工数据
                if (!$order_info['employee_id']) {
                    unset($order_info['employee_id']);
                    unset($order_info['employee_realname']);
                    unset($order_info['employee_avatar']);
                    unset($order_info['employee_tel']);
                } else {
                    $order_info['employee_avatar'] = $order_info['employee_avatar'] ? $attachment_url . $order_info['employee_avatar'] : '';
                }

                $goods_info = M('order_pack')->field('goods_pack_id,title,price as goods_price, image,pack_description')
                    ->where(['order_no' => $order_info['order_no']])
                    ->select();

                if (!$goods_info) return false;

                foreach ($goods_info as $index => $good_info) {
                    //组装图片地址
                    $goods_info[$index]['image'] = !empty($goods_info[$index]['image']) ? $attachment_url . $goods_info[$index]['image'] : '';
                }

                //获取卡套关联的卡座编号信息
                $seat_number = M('order_seat')->where(['order_no' => $order_info['order_no']])->getField('seat_number');
                $goods_info['seat_number'] = $seat_number ? $seat_number : '';

                //获取会员逾期时间
                $order_info['overdue_day'] = D('member')->join('api_member_privilege ON api_member_privilege.level = api_member.level')
                    ->where(['api_member.id' => $order_info['member_id']])
                    ->getField('api_member_privilege.delayed');
                if (!$order_info['overdue_day']) return false;

                break;
            case 3:

                //散套
                //删除员工数据
                unset($order_info['employee_id']);
                unset($order_info['employee_realname']);
                unset($order_info['employee_avatar']);
                unset($order_info['employee_tel']);

                $goods_info = M('OrderPack')->field('goods_pack_id,title,price as goods_price, image,pack_description')
                    ->where(['order_no' => $order_info['order_no']])
                    ->select();
                if (!$goods_info) return false;

                foreach ($goods_info as $index => $good_info) {
                    //组装图片地址
                    $goods_info[$index]['image'] = !empty($goods_info[$index]['image']) ? $attachment_url . $goods_info[$index]['image'] : '';
                }

                break;
        }

        //判断查询结果
        if (!$goods_info) {
            $this->error = '查询订单商品数据失败';
            return false;
        }

        //组合数据
        $order_info['goods_info'] = $goods_info;
        $order_info = array_merge($order_info, $merchant_info);

        //获取订单评价状态
        if ($order_info['status'] == 4) {
            $order_info['is_comment'] = (int)$order_info['is_evaluate'];
            unset($order_info['is_evaluate']);
        }

        //转换数据类型
        $order_info['id'] = (int)$order_info['id'];
        $order_info['merchant_id'] = (int)$order_info['merchant_id'];
        $order_info['member_id'] = (int)$order_info['member_id'];
        $order_info['contacts_sex'] = (int)$order_info['contacts_sex'];
        $order_info['status'] = (int)$order_info['status'];
        $order_info['order_type'] = (int)$order_info['order_type'];
        $order_info['payment'] = (int)$order_info['payment'];
        $order_info['incr_time'] = (int)$order_info['incr_time'];

        //判断订单类型转换数据
        if ($order_info['order_type'] == 1) {
            $order_info['employee_id'] = (int)$order_info['employee_id'];
            $order_info['goods_seat_id'] = (int)$order_info['goods_seat_id'];
        } else {
            $order_info['goods_pack_id'] = (int)$order_info['goods_pack_id'];
        }

        return $order_info;
    }*/


    /**
     * 根据订单ID获取订单商品数据
     * @param $order_id int 订单ID
     */
    public function getGoodsListByOrderId($order_id)
    {
        //查询数据库订单商品数据
        $data = M('order_pack')->field('order_id,goods_pack_id as goods_id,title,amount,price,image,pack_description,purchase_price,market_price')
            ->where(['order_id' => $order_id])
            ->order('goods_type asc')
            ->select();
        if ($data === false) {
            return false;
        }

        $attachment_url = C('ATTACHMENT_URL');
        //组装图片地址前缀
        foreach ($data as $key => $datum) {
            $data[$key]['image'] = $data[$key]['image'] ? $attachment_url . $data[$key]['image'] : '';
        }

        return $data;
    }

    /**
     * 程序自动更改已取消，已逾期，已作废订单状态
     */
    public function updateOrderStatus()
    {
        //查询订单状态为已支付的订单与商户信息
        $orders = $this
            ->field('api_order.id, 
            api_order.merchant_id, 
            api_order.member_id, 
            api_order.order_no, 
            api_order.status, 
            api_order.top_order_id, 
            api_order.contacts_realname, 
            api_order.contacts_tel, 
            api_order.contacts_sex, 
            api_order.order_type, 
            api_order.pay_price, 
            api_order.arrives_time, 
            api_order.incr_time, 
            api_order.created_time, 
            api_order.updated_time, 
            api_order.card_id, 
            api_merchant.title as merchant_title,
            api_merchant.begin_time, 
            api_merchant.delay_time, 
            api_merchant.end_time, 
            api_member.wx_openid, 
            api_member_privilege.delayed')
            ->join("api_merchant ON api_order.merchant_id = api_merchant.id")
            ->join("api_member ON api_order.member_id = api_member.id")
            ->join("api_member_privilege ON api_member.level = api_member_privilege.level")
            ->where(['api_order.status' => ['in', [1, 3, 7]], 'api_order.is_bar' => 0])//查询订单状态为1未支付,3已逾期,7已接单的订单数据
            ->order('api_order.id asc')
            ->select();

        $overdue_orders_id = [];   //符合逾期标准的订单ID号
        $cancel_orders_id = [];    //符合作废标准的订单ID号
        $abolish_order_id = []; //符合30分钟未支付的订单ID
        $overdue_orders = [];   //符合逾期标准的订单

        $overdue_orders_order_no = [];   //符合逾期标准的订单编号
        $cancel_orders_order_no = [];    //符合作废标准的订单编号

        //遍历判断订单状态
        foreach ($orders as $v) {

            //更改超过30分钟未支付的订单状态为已取消
            if ($v['status'] == 1) {
                $abolish_time = $v['created_time'] + C('ORDER_OVERTIME');   //订单超时时间
                if (time() > $abolish_time) {
                    $abolish_order_id[] = $v['id'];
                    if ($v['card_id']) {
                        M('coupon_member')->where(['member_id' => $v['member_id'], 'card_id' => $v['card_id']])->save(['card_status' => 0]);
                    }
                }
            }

            //卡套逾期操作 => 已接单|卡套
            if ($v['status'] == 7 && $v['order_type'] == 2) {
                $overdue_time = $this->_formatToTime($v['arrives_time'], $v['end_time']);       //逾期时间的时间戳
                if ($v['end_time'] <= $v['begin_time']) {
                    $overdue_time = $overdue_time + 24 * 60 * 60;   //小于等于营业起始时间则为第二日, 时间戳加一天
                }

                //判断当前订单状态是否逾期
                if (time() > $overdue_time) {
                    $overdue_orders_id[] = $v['id']; //将订单ID保存到数组中
                    $overdue_orders_order_no[] = $v['order_no'];    //将订单编号保存到数组中
                    if ($v['delayed']) {
                        $overdue_orders[] = $v;
                    }
                }
            }

            //卡套逾期作废操作 => 已逾期|卡套
            if ($v['status'] == 3 && $v['order_type'] == 2) {
                //获取订单更改为逾期的更新时间
                $format_arrives_time = $v['updated_time'] + $v['delayed'] * 24 * 60 * 60;
                //可逾期总时间小于当前时间,记录作废订单ID
                if (time() > $format_arrives_time) {
                    $cancel_orders_id[] = $v['id'];
                    $cancel_orders_order_no[] = $v['order_no']; //将作废编号保存到数组中
                }
            }

            //获取订单到店消费时间
            $arrives_time = $this->_formatToTime($v['arrives_time'], $v['begin_time']);

            //散套作废操作 => 已结单|散套
            if ($v['status'] == 7 && $v['order_type'] == 3 && $v['top_order_id'] == 0) {

                //将订单作废时间推迟指定分钟数
                $arrives_time = $arrives_time + C('FINISH_DELAY_TIME');
                //判断是否当前时间已经超过作废时间
                if (time() > $arrives_time) {
                    $cancel_orders_id[] = $v['id'];    //将作废订单ID记录
                    $cancel_orders_order_no[] = $v['order_no']; //将作废编号保存到数组中
                }
            }

            //卡座预定作废操作 => 已接单|卡座
            if ($v['status'] == 7 && $v['order_type'] == 1) {
                //判断延期时间是否设置
                if ($v['incr_time']) {
                    $arrives_time = $arrives_time + $v['incr_time'] * 60 + C('FINISH_DELAY_TIME');  //加上作废时间推迟指定分钟数
                } else {
                    $arrives_time = $arrives_time + C('FINISH_DELAY_TIME');  //加上作废时间推迟指定分钟数
                }

                //将作废订单ID记录
                if ($arrives_time < time()) {
                    $cancel_orders_id[] = $v['id'];
                    $cancel_orders_order_no[] = $v['order_no']; //将作废编号保存到数组中
                    $this->releaseSeat($v['order_no']);
                }
            }
        }

        //将已过期订单状态更改为已取消
        if ($abolish_order_id) {
            $abolish_rs = $this->where(['id' => ['in', $abolish_order_id]])->save(['status' => 0, 'updated_time' => time()]);
            if ($abolish_rs === false) {
                throw  new AbolishException('已过期订单状态更改失败');
            }
        }

        //将已逾期订单设置为逾期状态
        if ($overdue_orders_id) {
            //执行更新订单状态sql语句
            $overdue_rs = $this->where(['id' => ['in', $overdue_orders_id]])->save(['status' => 3, 'updated_time' => time()]);
            //删除订单相关消息数据
            M('message_employee')->where(['order_no' => ['in', $overdue_orders_order_no]])->delete();
            if ($overdue_rs === false) {
                throw  new OverdueException('已逾期订单设置为逾期状态失败');
            }
        }

        //将已过期未消费订单状态修改为已作废状态
        if ($cancel_orders_id) {
            $cancel_rs = $this->where(['id' => ['in', $cancel_orders_id]])->save(['status' => 5, 'updated_time' => time()]);
            //删除订单相关消息数据
            M('message_employee')->where(['order_no' => ['in', $cancel_orders_order_no]])->delete();
            if ($cancel_rs === false) {
                throw  new CancelException('已过期未消费订单状态修改失败');
            }
        }

        //返回逾期订单数据
        return $overdue_orders;
    }

    /**
     * 释放卡座(根据订单编号释放)
     * @param $order_no
     * @return bool
     */
    private function releaseSeat($order_no)
    {
        //释放指定日期卡座
        $rs = M('seat_lock')->where(['order_no' => $order_no])->delete();
        if ($rs === false) {
            $this->error = '释放卡座失败';
            return false;
        }
        return true;
    }

    /**
     * 已过期未支付订单状态修改
     * @param $order_info int 订单数据
     * @param $order_no string 订单编号
     * @return bool 返回值
     */
    public function _expiredOrderChangeStatus($order_info)
    {
        $now_time = time();     //当前时间戳
        $expiration_time = $order_info['created_time'] + C('ORDER_OVERTIME');   //订单规定超时取消时间
        //订单未过期
        if ($expiration_time > $now_time) return true;
        //判断订单类型
        $time = time();
        $rsa = $this->where(['id' => $order_info['id']])->save(['status' => 0, 'updated_time' => $time]);
        //判断是否存在card_id
        if ($order_info['card_id']) {
            M('coupon_member')->where(['member_id' => $order_info['member_id'], 'card_id' => $order_info['card_id']])->save(['card_status' => 0]);
        }
        if ($rsa === false) return false;
    }

    /**
     * 已逾期订单状态修改
     * @param $order_info array 订单数据
     * @param $order_no string 订单号
     * @return bool 返回值
     */
    public function _overdueOrderChangeStatus($order_info)
    {
        //获取当前订单的商户营业时间
        $merchant = D('merchant')->field('begin_time, end_time')->where(['id' => $order_info['merchant_id']])->find();

        //判断是否为第二日
        $overdue_time = $this->_formatToTime($order_info['arrives_time'], $merchant['end_time']);       //逾期时间的时间戳
        //延时时间
        if ($merchant['end_time'] <= $merchant['begin_time']) {
            $overdue_time = $overdue_time + 24 * 60 * 60;   //小于等于营业起始时间则为第二日, 时间戳加一天
        }

        //判断当前订单状态是否逾期
        if (time() > $overdue_time) {
            $rsa = $this->where(['id' => $order_info['id']])->save(['status' => 3, 'updated_time' => time()]);
            if ($rsa === false) return false;
            //删除订单相关消息数据
            M('message_employee')->where(['order_no' => $order_info['order_no']])->delete();
            return true;
        }

        return false;
    }

    /**
     * 单个订单::::修改已作废订单状态
     * @param $order_info array 订单数据
     * @return bool 返回值
     */
    public function _cancelOrderChangeStatus($order_info)
    {
        $order_id = null;

        //获取当前订单的商户营业时间
        $merchant = D('merchant')->field('begin_time, end_time')->where(['id' => $order_info['merchant_id']])->find();
        //开始计时时间
        $starttime = $this->_formatToTime($order_info['arrives_time'], $merchant['begin_time']);

        //卡套逾期作废操作 => 订单状态：已逾期, 订单类型：卡套
        if ($order_info['status'] == 3 && $order_info['order_type'] == 1 && $order_info['top_order_id'] == 0) {
            //获取当前用户的可逾期期限 单位:天
            $delayed = D('member')->join('api_member_privilege ON api_member.level = api_member_privilege,level', 'LEFT')
                ->where(['member_id' => $order_info['member_id']])
                ->getField('delayed');

            //订单作废的截止时间
            $cancel_time = $order_info['updated_time'] + $delayed * 24 * 60 * 60;
            //可逾期总时间小于当前时间,记录作废订单ID
            if (time() > $cancel_time) {
                $order_id = $order_info['id'];
            }
        }

        //散套作废操作 => 订单状态：已接单,订单类型：散套
        if ($order_info['status'] == 7 && $order_info['order_type'] == 3 && $order_info['top_order_id'] == 0) {
            //加上延迟作废的时间
            $arrives_time = $starttime + C('FINISH_DELAY_TIME');
            if (time() > $arrives_time) {
                $order_id = $order_info['id'];
            }
        }

        //卡座预定作废操作 => 订单状态：已接单, 订单类型：卡座
        if ($order_info['status'] == 7 && $order_info['order_type'] == 1) {
            //判断延期时间是否设置
            if ($order_info['incr_time']) {
                $arrives_time = $starttime + $order_info['incr_time'] * 60;
            } else {
                $arrives_time = $starttime;
            }
            //加上延迟作废的时间
            $arrives_time = $arrives_time + C('FINISH_DELAY_TIME');
            //将作废订单ID记录
            if ($arrives_time < time()) {
                $order_id = $order_info['id'];
            }
        }
        //修改订单状态为已作废
        if ($order_id) {
            $rsa = $this->where(['id' => $order_id])->save(['status' => 5, 'updated_time' => time()]);
            //删除订单相关消息数据
            M('message_employee')->where(['order_no' => $order_info['order_no']])->delete();
            if ($rsa === false) return false;
        }

        return true;
    }


    /**
     * 根据父级订单ID获取续酒订单列表与续酒订单对应的商品数据
     */
    public function getXuWineOrderList($order_id, $page, $pagesize)
    {
        //根据主订单ID查询续酒订单
        $where = ['top_order_id' => $order_id];

        //统计总续酒订单数
        $total = $this->where($where)->count();
        $fields = "id as order_id, order_no, total_price, pay_price, discount_money, status, order_type, payment, description, desk_number, created_time";
        $list = $this->field($fields)->where($where)->page($page, $pagesize)->order('id desc')->select();
        if ($list === false) {
            return false;
        }

        $order_ids = array_map('array_shift', $list);
        if(!empty($order_ids)){
            $goods_list = M('order_pack')->field('order_id,goods_pack_id, title,amount,price,image,pack_description,market_price,goods_type')
                ->where(['order_id' => ['in', $order_ids]])
                ->select();

            $goods_filter_list = [];
            $attachment_url = C('ATTACHMENT_URL');
            foreach ($order_ids as $f_order_id) {
                foreach ($goods_list as $item) {
                    if ($f_order_id == $item['order_id']) {
                        $item['image'] = $item['image'] ? $attachment_url . $item['image'] : '';
                        $goods_filter_list[$f_order_id][] = $item;
                    }
                }
            }

            //组装列表数据
            $list = array_map(function ($single) use ($goods_filter_list) {
                $goods_list = $goods_filter_list[$single['order_id']];
                //二维数组排序
                $single['goods_list'] = Tools::multiArraySort($goods_list, 'goods_type');
                return $single;
            }, $list);
        }

        $order_overtime = C('ORDER_OVERTIME');
        foreach ($list as $key => $order_item) {
            $order_total_time = $order_item['created_time'] + $order_overtime;
            if ($order_total_time > time()) {
                $list[$key]['have_time'] = $order_total_time - time();
            } else {
                $list[$key]['have_time'] = 0;
            }

            //计算平台优惠
            $list[$key]['Platform'] = $order_item['total_price'] - $order_item['pay_price'];
        }

        return ['total' => $total, 'list' => $list];
    }


    /**
     * 获取格式化时间
     * @param $his_time string 时分秒时间格式 00:00:00
     * @param $format_date string  年月日日期格式 2017-05-21
     * @return false|int    时间戳
     */
    private function _formatToTime($format_date, $his_time)
    {
        $format_date = date('Y-m-d', $format_date);
        $format_time = $format_date . ' ' . $his_time;
        return strtotime($format_time);
    }

}