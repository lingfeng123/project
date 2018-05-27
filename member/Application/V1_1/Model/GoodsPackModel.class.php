<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace V1_1\Model;

use Org\Util\Tools;
use Think\Model;

class GoodsPackModel extends Model
{

    /**
     * 获取散套列表
     * @param $date 日期
     * @param $merchant_id 商户ID
     * @param $type 套餐类型
     * @return bool|int|mixed
     */
    /*public function getPackList($merchant_id, $date, $type)
    {
        //获取库存数量
        $merchant = D('merchant')->field('begin_time,sanpack_stock,kapack_stock')->where(['id' => $merchant_id])->find();
        if (!$merchant) {
            $this->error = '数据请求失败';
            return false;
        }
        //获取套餐库存
        $stock = $this->_validateStock($date, $merchant_id, $type, $merchant['sanpack_stock'], $merchant['kapack_stock']);
        if ($stock === false) {
            return false;
        }

        //获取套餐列表
        $list = $this->field('id,title,type,price,market_price,image,description,stock')->where(['merchant_id' => $merchant_id, 'type' => $type, 'status' => 1])->select();
        if ($list === false) {
            $this->error = '获取套餐失败';
            return false;
        }

        //组装数据
        $attachment_url = C('ATTACHMENT_URL');
        foreach ($list as $key => $value) {
            $list[$key]['id'] = (int)$list[$key]['id'];
            $list[$key]['type'] = (int)$list[$key]['type'];
            $list[$key]['stock'] = $stock;
            $list[$key]['image'] = $list[$key]['image'] ? $attachment_url . $list[$key]['image'] : '';
        }

        //返回数据结果
        return ['total' => count($list), 'list' => $list];
    }*/


    /**
     * 检测套餐库存是否充足
     * @param $date 日期
     * @param $merchant_id 商户ID
     * @param $type 套餐类型
     * @return bool|int|mixed
     */
    public function getPackStock($merchant_id, $member_id, $date, $type)
    {
        $merchant = D('merchant')->field('begin_time,sanpack_stock,kapack_stock')->where(['id' => $merchant_id])->find();
        if (!$merchant) {
            $this->error = '数据请求失败';
            return false;
        }

        $today_date = date('Y-m-d');    //用户请求接口时的系统日期
        $now_date = $today_date . ' ' . $merchant['begin_time'];    //商户今日营业开始时间
        $now_date = strtotime($now_date);   //商户今日营业时间时间戳

        //如果是购买的是散套, 限制营业时间开始后不允许购买
        if ($type == 1) {
            if (time() > $now_date && $date == $today_date) {
                $this->error = '已到营业时间,暂不支持购买';
                return false;
            }
        }

        //购买散套
        if ($type == 1) {
            $user_where = ['member_id' => $member_id, 'order_type' => 3, 'status' => ['IN', [1, 2, 7]], 'top_order_id' => 0];
            $where = ['member_id' => $member_id, 'merchant_id' => $merchant_id, 'order_type' => 3, 'status' => ['IN', [1, 2, 7]], 'top_order_id' => 0];
        }

        //如果是购买卡套
        if ($type == 2) {
            $user_where = ['member_id' => $member_id, 'order_type' => ['IN', [1, 2]], 'status' => ['IN', [1, 2, 3, 7]]];
            $where = ['member_id' => $member_id, 'merchant_id' => $merchant_id, 'order_type' => ['IN', [1, 2]], 'status' => ['IN', [1, 2, 3, 7]]];
        }

        //检测全局的订单,查询用户在所有商户的订单
        $user_order_info = D('order')->field('id,status,created_time,arrives_time')->where($user_where)->select();
        if ($user_order_info) {
            foreach ($user_order_info as $value) {
                $user_arrives_time = date('Y-m-d', $value['arrives_time']);
                if ($user_arrives_time == $date) {
                    //如果最后一个符合条件的订单的创建日期不是今日
                    $this->error = '当日您不可再购买该酒吧套餐';
                    return false;
                }
            }
        }

        //查询是否存在符合条件的数据
        $order_info = D('order')->field('id,status,order_type,created_time,arrives_time')->where($where)->order('created_time desc')->select();
        if ($order_info === false) {
            $this->error = '数据请求失败';
            return false;
        }

        //查询数据是否存在 update 2017-11-29 17:19:47
        if ($order_info) {
            foreach ($order_info as $order_item) {
                //获取订单创建日期
                $arrives_time = date('Y-m-d', $order_item['arrives_time']);

                if ($arrives_time == $date) {
                    //如果最后一个符合条件的订单的创建日期不是今日
                    $this->error = '当日您不可再购买该酒吧套餐';
                    return false;
                }

                //如果订单状态为3
                if ($order_item['status'] == 3) {
                    $this->error = '您有逾期卡座套餐未消费,请消费后再购买';
                    return false;
                }

                //判断是否当日已预定卡座
                if ($order_item['order_type'] == 1 && in_array($order_item['status'], [1, 2, 7]) && $arrives_time == $date) {
                    $this->error = '您当日已预定该酒吧卡座不能再购买卡座套餐';
                    return false;
                }
            }
        }


        //获取库存数据
        $stock = $this->_validateStock($date, $merchant_id, $type, $merchant['sanpack_stock'], $merchant['kapack_stock']);
        if (!$stock || $stock <= 0) {
            $this->error = '库存不足,无法购买';
            return false;
        }

        return true;
    }

    /**
     * 获取库存数据
     * @param $date 日期
     * @param $merchant_id 商户ID
     * @param $type 套餐类型
     * @return bool|int|mixed
     */
    private function _validateStock($date, $merchant_id, $type, $sanpack_stock, $kapack_stock)
    {
        //格式化时间
        $date_int = (int)date('Ymd', strtotime($date));    //字符串日期 2017-10-10
        $date_now = (int)date('Ymd', time());
        if ($date_int < $date_now) {
            $this->error = '不能选择今天之前的日期';
            return false;
        }

        //获取商户的最大预定周期时间
        $preordain_cycle = D('merchant')->where(['id' => $merchant_id])->getField('preordain_cycle');
        $preordain_cycle_second = $preordain_cycle * 24 * 60 * 60;
        //支持的最大选择时间
        $max_second = strtotime(date('Y-m-d', time())) + $preordain_cycle_second;
        $send_second = strtotime($date);
        if ($max_second < $send_second) {
            $this->error = '日期超出预定周期';
            return false;
        }
    }


    /**
     * 获取订单续酒套餐与单品酒水列表 v2.0
     * @param $merchant_id int 商户ID
     * @param $page int 当前页码
     * @param $goods_type int 商品类型
     * @param $pagesize int 每页显示数量
     * @return array|bool
     */
    public function getWineGoodsPack($merchant_id, $page, $goods_type, $pagesize, $order_id, $member_id)
    {
        //兼容1.2处理
        if ($order_id > 0) {

            //获取主订单的到店时间
            $date = M('order')->where(['id' => $order_id])->getField('arrives_time');
            if (!$date) {
                return false;
            }

            $date = date('Ymd', $date);
            $son_sql = "(select `price` from `api_goods_price` where `date` = '{$date}' AND `goods_id` = api_goods_pack.id) as price";
            $fields = "id, merchant_id, title, type, {$son_sql}, image, description, xu_stock as stock, api_goods_pack.price as case_price, market_price, purchase_price";

        } else {
            $fields = "id, merchant_id, title, type, price, image, description, xu_stock as stock, market_price, purchase_price";
        }


        //根据商品类型获取商品数据
        $where = ['type' => $goods_type, 'merchant_id' => $merchant_id, 'status' => 1];

        //统计总记录数
        $count = $this->where($where)->count();
        //查询具体数据
        $rows = $this->field($fields)->where($where)->page($page, $pagesize)->order('created_time desc')->select();
        if ($rows === false || $count == false) {
            return false;
        }

        //判断member_id是否存在,兼容1.2
        if ($member_id) {
            //获取用户性别
            $sex = M('member')->where(['id' => $member_id])->getField('sex');   //获取用户信息

            //获取所有还未使用和未过期的卡券
            $cards = M('coupon')->field('api_coupon.*,api_coupon_member.card_status,api_coupon_member.get_time,api_coupon_member.member_id')
                ->join('left join api_coupon_member ON api_coupon_member.card_id=api_coupon.id')
                ->where(['member_id' => $member_id, 'card_status' => 0])
                ->select();
        }

        //拼接图片的完整地址
        $attachment_url = C('ATTACHMENT_URL');
        foreach ($rows as $key => $row) {
            if ($row['type'] == 3 && is_null($row['price'])) {
                $rows[$key]['price'] = $row['case_price'];
            }

            if ($row['type'] != 3 && is_null($row['price'])) {
                unset($rows[$key]);
                continue;
            }

            if ($row['stock'] < 1) {
                unset($rows[$key]);
                continue;
            }
            $rows[$key]['image'] = $row['image'] ? $attachment_url . $row['image'] : '';

            //判断member_id是否存在,兼容1.2
            if ($member_id) {
                //获取最大优惠券
                $cardArr = D('coupon')->getGoodsListUseFulCards($merchant_id, $row, $sex, $cards);

                $money = $rows[$key]['price'] - $cardArr['deductible'];
                $rows[$key]['different_price'] = $money > 0 ? $money : '0.00';
                $rows[$key]['different_price'] = Tools::formatMoney($rows[$key]['different_price']);

                $rows[$key]['card_id'] = $cardArr['card_id'];
                $rows[$key]['deductible'] = $cardArr['deductible'];
            }
        }

        $rows = array_values($rows);
        return $return = ['total' => $count, 'list' => $rows];
    }

    /**
     * 获取订单续酒套餐列表(有) v2.0 2018年2月10日11:09:07
     * @param $merchant_id
     * @param $page
     * @param $good_type
     * @param $pagesize
     * @return array|bool
     */
    /*public function getWineGoodsPackV2($merchant_id, $page, $good_type, $pagesize)
    {
        //获取商户对应的套餐
        $where = ['merchant_id' => $merchant_id, 'type' => $good_type, 'status' => 1];
        $count = $this->where($where)->count();
        $lists = $this->field('id as goods_pack_id,xu_stock,merchant_id,title as pack_title,type,price,image as pack_image,description as pack_description,market_price,purchase_price')
            ->where($where)
            ->page($page, $pagesize)
            ->select();

        //如果数据不存在
        if ($lists === false || $count === false) {
            return false;
        }

        //拼接图片的完整地址
        $attachment_url = C('ATTACHMENT_URL');
        foreach ($lists as $key => $list) {
            if ($list['xu_stock'] < 1) {
                unset($lists[$key]);
                continue;
            }
            $lists[$key]['pack_image'] = $list['pack_image'] ? $attachment_url . $list['pack_image'] : '';
        }

        $lists = array_values($lists);
        return $return = ['total' => $count, 'list' => $lists];
    }*/

    /**
     * 获取套餐套餐商品列表 v2.0
     * @param $merchant_id int 商户ID
     * @param $date string 日期 2018-10-10
     * @param $type int 1散套 2卡套 3单品酒水
     * @return array
     */
    public function getPackGoodsList($merchant_id, $date, $type, $member_id)
    {
        //查询商户每日设定库存
        $merchant = D('merchant')->field('begin_time,sanpack_stock,kapack_stock')->where(['id' => $merchant_id])->find();
        $every_day_stock = 0;
        if ($type == 1) {
            $every_day_stock = $merchant['sanpack_stock'];
        } elseif ($type == 2) {
            $every_day_stock = $merchant['kapack_stock'];
        }

        //查询每日库存表中已售套餐数据
        $in_time = strtotime($date);
        $date = date('Ymd', $in_time);
        $goods_pack_stock = M('goods_pack_stock')->where(['date' => $date, 'merchant_id' => $merchant_id, 'type' => $type])->getField('goods_id,day_sales');

        //查询套餐商品 2018年5月8日18:16:02 每日一价
        $son_sql = "(select `price` from `api_goods_price` where `date` = '{$date}' AND `goods_id` = api_goods_pack.id) as price";
        $pack_goods_data = M('goods_pack')->field("id, title, type, {$son_sql}, market_price, image, description, stock")
            ->where(['type' => $type, 'merchant_id' => $merchant_id, 'status' => 1])
            ->order('id desc')
            ->select();

        //判断member_id是否存在
        if ($member_id) {
            //获取用户性别
            $sex = M('member')->where(['id' => $member_id])->getField('sex');   //获取用户信息

            //获取所有还未使用和未过期的卡券
            $cards = M('coupon')->field('api_coupon.*,api_coupon_member.card_status,api_coupon_member.get_time,api_coupon_member.member_id')
                ->join('left join api_coupon_member ON api_coupon_member.card_id=api_coupon.id')
                ->where(['member_id' => $member_id, 'card_status' => 0])
                ->select();
        }

        //遍历数据组装库存
        $attachment_url = C('ATTACHMENT_URL');
        foreach ($pack_goods_data as $key => $value) {
            //判断每日价格是否设置
            if (is_null($pack_goods_data[$key]['price'])) {
                unset($pack_goods_data[$key]);
                continue;
            }

            $pack_goods_data[$key]['id'] = (int)$pack_goods_data[$key]['id'];
            $pack_goods_data[$key]['type'] = (int)$pack_goods_data[$key]['type'];

            //验证库存记录是否存在
            $sold_number = isset($goods_pack_stock[$pack_goods_data[$key]['id']]) ? $goods_pack_stock[$pack_goods_data[$key]['id']] : 0;

            //调用库存计算公共方法计算每日实际可售库存
            $stock = $this->calculateNowStock($every_day_stock, $pack_goods_data[$key]['stock'], $sold_number);

            if ($stock < 1) {
                unset($pack_goods_data[$key]);
                continue;
            }

            $pack_goods_data[$key]['stock'] = (int)$stock;
            $pack_goods_data[$key]['date'] = $in_time;
            $pack_goods_data[$key]['image'] = $pack_goods_data[$key]['image'] ? $attachment_url . $pack_goods_data[$key]['image'] : '';

            //判断member_id是否存在
            if ($member_id) {
                //获取最大优惠券
                $cardArr = D('coupon')->getGoodsListUseFulCards($merchant_id, $value, $sex, $cards);

                $money = $pack_goods_data[$key]['price'] - $cardArr['deductible'];
                $pack_goods_data[$key]['different_price'] = $money > 0 ? $money : '0.00';
                $pack_goods_data[$key]['different_price'] = Tools::formatMoney($pack_goods_data[$key]['different_price']);

                $pack_goods_data[$key]['card_id'] = $cardArr['card_id'];
                $pack_goods_data[$key]['deductible'] = $cardArr['deductible'];
            }
        }

        $pack_goods_data = array_values($pack_goods_data);
        return ['total' => count($pack_goods_data), 'list' => $pack_goods_data];
    }

    /**
     * 计算当前商品实际库存
     * @param $every_day_stock int 每日销售限制库存数量
     * @param $goods_stock int 商品自身实际库存数量
     * @param $sold_number int 商品每日已销售数量
     * @return mixed
     */
    public function calculateNowStock($every_day_stock, $goods_stock, $sold_number)
    {
        //每日库存与当日该套餐销售总数作减法运算,若值小于0, 当日可售库存为0, 如果大于0 当日可售库存为number值
        $number = $every_day_stock - $sold_number;
        if ($number > 0) {
            //得到的number值再与商品实际剩余总库存进行对比
            //若商品总库存小于number则商品实际可售库存为商品剩余中库存, 反之商品实际可售库存为number值
            $total_stock = $goods_stock > 0 ? $goods_stock : 0;
            if ($total_stock > $number) {
                $stock = $number;
            } else {
                $stock = $total_stock;
            }
        } else {
            $this->error = '商品已售罄';
            $stock = 0;
        }

        return $stock;
    }

    /**
     * 验证库存是否充足()    v1.2
     * @param $date string 日期
     * @param $goods_id int 商品ID
     * @param $merchant_id int 商户ID
     * @param $member_id int 用户ID
     * @return bool|int
     * todo::这里可能会要一个参数进行控制是否检验单个商品的库存
     */
    public function getGoodsStockBoolean($date, $goods_id, $merchant_id, $member_id)
    {
        $merchant = D('merchant')->field('begin_time,sanpack_stock,kapack_stock,preordain_cycle,open_buy')->where(['id' => $merchant_id])->find();
        if (!$merchant) {
            $this->error = '获取商户失败';
            return false;
        }

        //根据商品ID获取商品类型
        if (!$goods_info = M('goods_pack')->field('id,stock,type')->where(['id' => $goods_id])->find()) {
            $this->error = '商品获取失败';
            return false;
        }

        //商品类型
        $type = $goods_info['type'];

        //用户请求接口时的系统日期
        $today_date = date('Y-m-d');
        //商户今日营业开始时间
        $now_date = $today_date . ' ' . $merchant['begin_time'];
        //商户今日营业时间时间戳
        $now_date = strtotime($now_date);

        //如果是购买的是散套, 限制营业时间开始后不允许购买
        if ($type == 1 && $merchant['open_buy'] == 0) {
            if (time() > $now_date && $date == $today_date) {
                $this->error = '已到营业时间,暂不支持购买';
                return false;
            }
        }

        //购买散套
        if ($type == 1) {
            $user_where = ['member_id' => $member_id, 'order_type' => 3, 'status' => ['IN', [1, 2, 7]], 'top_order_id' => 0, 'is_bar' => 0];
            $where = ['member_id' => $member_id, 'merchant_id' => $merchant_id, 'order_type' => 3, 'status' => ['IN', [1, 2, 7]], 'top_order_id' => 0, 'is_bar' => 0];
        }

        //如果是购买卡套
        if ($type == 2) {
            $user_where = ['member_id' => $member_id, 'order_type' => ['IN', [1, 2]], 'status' => ['IN', [1, 2, 3, 7]], 'is_bar' => 0];
            $where = ['member_id' => $member_id, 'merchant_id' => $merchant_id, 'order_type' => ['IN', [1, 2]], 'status' => ['IN', [1, 2, 3, 7]], 'is_bar' => 0];
        }

        //检测全局的订单,查询用户在所有商户的订单
        $user_order_info = D('order')->field('id,status,created_time,arrives_time')->where($user_where)->select();
        if ($user_order_info) {
            foreach ($user_order_info as $value) {
                $user_arrives_time = date('Y-m-d', $value['arrives_time']);
                if ($user_arrives_time == $date) {
                    //如果最后一个符合条件的订单的创建日期不是今日
                    $this->error = '当日您不可再购买该酒吧套餐';
                    return false;
                }
            }
        }

        //查询是否存在符合条件的数据
        $order_info = D('order')->field('id,status,order_type,created_time,arrives_time')->where($where)->order('created_time desc')->select();
        if ($order_info === false) {
            $this->error = '数据请求失败';
            return false;
        }

        //查询数据是否存在 update 2017-11-29 17:19:47
        if ($order_info) {
            foreach ($order_info as $order_item) {
                //获取订单创建日期
                $arrives_time = date('Y-m-d', $order_item['arrives_time']);

                if ($arrives_time == $date) {
                    //如果最后一个符合条件的订单的创建日期不是今日
                    $this->error = '当日您不可再购买该酒吧套餐';
                    return false;
                }

                //如果订单状态为3
                if ($order_item['status'] == 3) {
                    $this->error = '您有逾期卡座套餐未消费,请消费后再购买';
                    return false;
                }

                //判断是否当日已预定卡座
                if ($order_item['order_type'] == 1 && in_array($order_item['status'], [1, 2, 7]) && $arrives_time == $date) {
                    $this->error = '您当日已预定该酒吧卡座不能再购买卡座套餐';
                    return false;
                }
            }
        }


        //格式化时间
        $date_int = (int)date('Ymd', strtotime($date));    //字符串日期 2017-10-10
        $date_now = (int)date('Ymd', time());
        if ($date_int < $date_now) {
            $this->error = '不能选择今天之前的日期';
            return false;
        }

        //获取商户的最大预定周期时间
        $preordain_cycle = $merchant['preordain_cycle'];
        $preordain_cycle_second = $preordain_cycle * 24 * 60 * 60;

        //支持的最大选择时间
        $max_second = strtotime(date('Y-m-d', time())) + $preordain_cycle_second;
        $send_second = strtotime($date);
        if ($max_second < $send_second) {
            $this->error = '日期超出预定周期';
            return false;
        }

        //验证库存是否充足
        $every_day_stock = 0;
        if ($type == 1) {
            $every_day_stock = $merchant['sanpack_stock'];
        } elseif ($type == 2) {
            $every_day_stock = $merchant['kapack_stock'];
        }

        //查询每日库存表中已售套餐数据
        $date = date('Ymd', strtotime($date));
        $day_sales = M('goods_pack_stock')->where(['date' => $date, 'goods_id' => $goods_id])->getField('day_sales');
        $sold_number = $day_sales ? $day_sales : 0;

        //调用库存计算公共方法计算每日实际可售库存
        return $this->calculateNowStock($every_day_stock, $goods_info['stock'], $sold_number);
    }


    /**
     * 获取单品酒水数据
     * @param $merchant_id int 商户ID
     * @return array
     */
    public function getSingleWineData($merchant_id)
    {
        $goods_info = $this->field('id,merchant_id,title,type,price,image,description,stock,market_price')
            ->where(['merchant_id' => $merchant_id, 'status' => 1, 'type' => 3])
            ->order('id desc')
            ->select();

        if ($goods_info === false) {
            return false;
        }

        //遍历数据组装库存
        $attachment_url = C('ATTACHMENT_URL');
        foreach ($goods_info as $key => $goods) {

            if ($goods['stock'] < 1) {
                unset($goods_info[$key]);
                continue;
            }

            $goods_info[$key]['id'] = (int)$goods_info[$key]['id'];
            $goods_info[$key]['type'] = (int)$goods_info[$key]['type'];
            $goods_info[$key]['stock'] = (int)$goods_info[$key]['stock'];
            $goods_info[$key]['image'] = $goods_info[$key]['image'] ? $attachment_url . $goods_info[$key]['image'] : '';
        }

        $goods_info = array_values($goods_info);
        return ['total' => count($goods_info), 'list' => $goods_info];
    }

}
