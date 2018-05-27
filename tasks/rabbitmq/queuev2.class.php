<?php

/**
 * FileName: queueCreateOrder.class.php
 * User: Comos
 * Date: 2018/3/15 18:11
 */
//require_once __DIR__ . '/../common.php';
require_once 'base.class.php';

class queuev2 extends base
{

    /**
     * 创建卡座订单 v2.0
     * @param $data array 订单数据
     * @return array|object|string
     */
    public static function buidSeatOrder($data)
    {
        try {
            //建立PDO数据库连接
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_DBNAME, DB_USERNAME, DB_PASSWORD);
            $pdo->query('set names utf8;');
        } catch (PDOException $e) {
            return self::response(self::DB_CONNECT_FAIL, '数据库连接失败' . $e->getMessage());
        }

        try {
            $goods = $data['goods'];
            $order = $data['order'];

            //订单创建时间与最后修改时间
            $created_time = $updated_time = time();

            /**
             * 验证是否符合购买条件
             * update 2017年12月26日11:00:40
             */
            $arrives_time = strtotime(date('Y-m-d', $order['arrives_time']));
            $today_stock = $pdo->query("SELECT COUNT(`id`) as total FROM `api_order` WHERE `arrives_time` = '{$arrives_time}' AND `member_id` = {$order['member_id']} AND `merchant_id` = '{$order['merchant_id']}' AND `order_type` = 1 AND `status` IN (1, 2, 7) limit 1");
            $stock = $today_stock->fetch(PDO::FETCH_ASSOC);
            if ($stock['total'] > 0) return self::response(self::INVALID_REQUEST, '1000-您有未完成卡座订单');

            //获取订单号
            $order_no = self::_createOrderNumber();

            //开始事务
            $pdo->beginTransaction();

            //判断员工ID是否存在
            if (isset($order['employee_id']) && !empty($order['employee_id'])) {
                //写入订单主表SQL 有客户经理
                $order_add = $pdo->prepare("INSERT INTO `api_order` (`order_no`, `merchant_id`, `member_id`, `contacts_realname`, `contacts_tel`, `contacts_sex`, `total_price`, `pay_price`, `purchase_price`, `discount_money`, `status`, `settlement_status`, `order_type`, `description`, `arrives_time`, `employee_id`, `employee_realname`, `employee_tel`, `employee_avatar`, `created_time`, `updated_time`, `relation_order_no`, `relation_order_id`,`card_id`,`obegin_time`,`oend_time`) VALUES (:order_no, :merchant_id, :member_id, :contacts_realname, :contacts_tel, :contacts_sex, :total_price, :pay_price, :purchase_price, :discount_money, :status, :settlement_status, :order_type, :description, :arrives_time, :employee_id, :employee_realname, :employee_tel, :employee_avatar, :created_time, :updated_time, :relation_order_no, :relation_order_id,:card_id,:obegin_time,:oend_time)");
                //绑定参数
                $order_main_data = [
                    ':order_no' => $order_no,
                    ':merchant_id' => $order['merchant_id'],
                    ':member_id' => $order['member_id'],
                    ':contacts_realname' => $order['contacts_realname'],
                    ':contacts_tel' => $order['contacts_tel'],
                    ':contacts_sex' => $order['contacts_sex'],
                    ':total_price' => $order['total_price'],
                    ':pay_price' => $order['pay_price'],
                    ':purchase_price' => $order['purchase_price'],
                    ':discount_money' => $order['discount_money'],
                    ':status' => $order['status'],
                    ':settlement_status' => 0,
                    ':order_type' => $order['order_type'],
                    ':description' => $order['description'],
                    ':arrives_time' => $order['arrives_time'],
                    ':employee_id' => $order['employee_id'],
                    ':employee_realname' => $order['employee_realname'],
                    ':employee_tel' => $order['employee_tel'],
                    ':employee_avatar' => $order['employee_avatar'],
                    ':created_time' => $created_time,
                    ':updated_time' => $updated_time,
                    ':relation_order_no' => $order['relation_order_no'],
                    ':relation_order_id' => $order['relation_order_id'],
                    ':card_id' => $order['card_id'] ? $order['card_id'] : 0,
                    ':obegin_time' => $order['obegin_time'],
                    ':oend_time' => $order['oend_time'],
                ];
                $order_add = $order_add->execute($order_main_data);

            } else {
                //写入订单主表SQL 无客户经理
                $order_add = $pdo->prepare("INSERT INTO `api_order` (`order_no`, `merchant_id`, `member_id`, `contacts_realname`, `contacts_tel`, `contacts_sex`, `total_price`, `pay_price`, `purchase_price`, `discount_money`, `status`, `settlement_status`, `order_type`, `description`, `arrives_time`, `created_time`, `updated_time`, `relation_order_no`, `relation_order_id`,`card_id`,`obegin_time`,`oend_time`) VALUES (:order_no, :merchant_id, :member_id, :contacts_realname, :contacts_tel, :contacts_sex, :total_price, :pay_price, :purchase_price, :discount_money, :status, :settlement_status, :order_type, :description, :arrives_time, :created_time, :updated_time, :relation_order_no, :relation_order_id,:card_id,:obegin_time,:oend_time)");

                $order_main_data = [
                    ':order_no' => $order_no,
                    ':merchant_id' => $order['merchant_id'],
                    ':member_id' => $order['member_id'],
                    ':contacts_realname' => $order['contacts_realname'],
                    ':contacts_tel' => $order['contacts_tel'],
                    ':contacts_sex' => $order['contacts_sex'],
                    ':total_price' => $order['total_price'],
                    ':pay_price' => $order['pay_price'],
                    ':purchase_price' => $order['purchase_price'],
                    ':discount_money' => $order['discount_money'],
                    ':status' => $order['status'],
                    ':settlement_status' => 0,
                    ':order_type' => $order['order_type'],
                    ':description' => $order['description'],
                    ':arrives_time' => $order['arrives_time'],
                    ':created_time' => $created_time,
                    ':updated_time' => $updated_time,
                    ':relation_order_no' => $order['relation_order_no'],
                    ':relation_order_id' => $order['relation_order_id'],
                    ':card_id' => $order['card_id'] ? $order['card_id'] : 0,
                    ':obegin_time' => $order['obegin_time'],
                    ':oend_time' => $order['oend_time'],
                ];
                //绑定参数
                $order_add = $order_add->execute($order_main_data);
            }

            //执行语句结果
            if (!$order_add) {
                $pdo->rollBack();
                return self::response(self::DB_SAVE_ERROR, '创建订单失败');
            }

            //获取插入订单的ID
            $last_order_id = $pdo->lastInsertId();

            //创建订单卡座商品表数据
            $seat_add = $pdo->prepare("INSERT INTO `api_order_seat` (`order_no`, `goods_seat_id`, `title`, `max_people`, `floor_price`, `set_price`, `total_people`, `merchant_id`, `member_id`, `seat_number`,`order_id`) VALUES (:order_no, :goods_seat_id, :title, :max_people, :floor_price, :set_price, :total_people, :merchant_id, :member_id, :seat_number,:order_id)");

            $order_goods_data = [
                ':order_no' => $order_no,
                ':goods_seat_id' => $goods['goods_seat_id'],
                ':title' => $goods['title'],
                ':max_people' => $goods['max_people'],
                ':floor_price' => $goods['floor_price'],
                ':set_price' => $goods['set_price'],
                ':total_people' => $goods['total_people'],
                ':merchant_id' => $goods['merchant_id'],
                ':member_id' => $goods['member_id'],
                ':seat_number' => $goods['seat_number'],
                ':order_id' => $last_order_id,
            ];

            //执行SQL语句
            $seat_add = $seat_add->execute($order_goods_data);

            //执行语句结果
            if (!$seat_add) {
                $pdo->rollBack();
                return self::response(self::DB_SAVE_ERROR, '卡座订单信息写入失败');
            }

            //锁定卡座当日库存
            $seat_lock = $pdo->prepare("INSERT INTO `api_seat_lock` (`seat_number`, `order_no`, `arrives_time`, `floor`, `goods_seat_id`, `merchant_id`) VALUES (:seat_number, :order_no, :arrives_time, :floor, :goods_seat_id, :merchant_id)");
            //TODO::此处无需保存订单ID到每日卡座锁定释放表
            $seat_lock = $seat_lock->execute([
                'seat_number' => $goods['seat_number'],
                'order_no' => $order_no,
                'arrives_time' => $order['arrives_time'],
                'floor' => $goods['floor'],
                'goods_seat_id' => $goods['goods_seat_id'],
                'merchant_id' => $goods['merchant_id']
            ]);

            //执行语句结果
            if (!$seat_lock) {
                $pdo->rollBack();
                return self::response(self::DB_SAVE_ERROR, '锁定卡座失败');
            }

            //优惠券变为已使用
            if ($order['card_id']) {
                $card_rs = $pdo->exec("UPDATE `api_coupon_member` SET `card_status`= 1 WHERE `card_id` = '{$order['card_id']}' AND `member_id` ='{$order['member_id']}'");
                //执行结果
                if ($card_rs === false) {
                    $pdo->rollBack();
                    return self::response(self::DB_SAVE_ERROR, '优惠券状态更新失败');
                }
            }

            //延时队列
            $Pheanstalk = new Pheanstalk\Pheanstalk($GLOBALS['CONFIG']['BEANS_OPTIONS']['HOST']);
            $tube_name = $GLOBALS['CONFIG']['BEANS_OPTIONS']['TUBE_NAME'][0];
            $delayed_data = [
                'version' => 'v1.1',
                'order_id' => $last_order_id,
                'order_no' => $order_no,
                'buy_type' => 1,    //1普通下单 2普通续酒 3拼吧下单 4拼吧续酒
                'exc_type' => 1,    //执行类型 1订单取消 2订单作废 3订单逾期
            ];
            $Pheanstalk->putInTube($tube_name, json_encode($delayed_data), 0, $GLOBALS['CONFIG']['ORDER_OVERTIME']);

            //提交事务,完成订单创建
            $pdo->commit();

            return self::response(self::SUCCESS, '下单成功',
                [
                    'order_id' => $last_order_id,
                    'order_no' => $order_no,
                    'buy_type' => 1,
                    'pay_money' => $order['pay_price']
                ]);

        } catch (PDOException $e) {
            file_put_contents('./log/' . date('Y-m-d') . 'log', date('Y-m-d H:i:s') . '||' . $e->getMessage() . "\n", FILE_APPEND);
            return self::response(self::INVALID_REQUEST, '请求失败');
        }
    }

    /**
     * 购物车商品订单 卡套/散套/单品酒水   v2.0
     * @param $data array 订单数据
     * @return array|object|string
     */
    public static function buildGoodsOrder($data)
    {
        try {
            //建立PDO数据库连接
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_DBNAME, DB_USERNAME, DB_PASSWORD);
            $pdo->query('set names utf8;');
        } catch (PDOException $e) {
            return self::response(self::DB_CONNECT_FAIL, '数据库连接失败');
        }

        $order_no = self::_createOrderNumber();     //获取订单号
        $order = $data['order'];
        //套餐商品 单个
        $pack_goods = isset($data['goods']['pack_goods'][0]) ? $data['goods']['pack_goods'][0] : [];
        //单品酒水 多个
        $single_goods = $data['goods']['single_goods'];

        try {
            //订单创建时间与最后修改时间
            $created_time = $updated_time = time();

            //到店日期格式化为yyyymmdd格式
            $date_int = (int)date('Ymd', $order['arrives_time']);

            //order_type 订单类型 2卡套订单 3散套订单
            //pack_type 商品类型 1散客套餐 2卡座套餐 3单品酒水
            switch ($order['order_type']) {
                case 2:
                    $pack_type = 2;
                    break;
                case 3:
                    $pack_type = 1;
                    break;
                default:
                    $pack_type = 3;
            }

            //验证是否满足下单条件
            $arrives_time = strtotime(date('Y-m-d', $order['arrives_time']));   //到店时间

            //商品表数据预存储
            $order_goods_table_data = [];

            $pack_add_stock_record_sql = null;
            $pack_stock_change_sql = null;
            $single_add_stock_record_sql = [];
            $single_stock_change_sql = [];
            $add_single_goods_record_sql = null;
            /**
             * 验证是否存在套餐商品购买 当套餐商品存在时才执行套餐的各种判断
             */
            if ($pack_goods) {
                //sept:1, 验证是否有存在 未支付/逾期/待接单/已接单的 订单, 有则当日不允许再购买
                if ($order['order_type'] == 2) {

                    //卡套订单查询验证
                    $pdo_sql = $pdo->query("SELECT COUNT(`id`) as total FROM `api_order` WHERE `arrives_time` = '{$arrives_time}' AND `member_id` = {$order['member_id']} AND `merchant_id` = '{$order['merchant_id']}' AND `order_type` = 2 AND `status` IN (1, 2, 3, 7) AND `is_bar` = 0 limit 1");
                    $stock = $pdo_sql->fetch(PDO::FETCH_ASSOC);
                    if ($stock['total'] > 0) return self::response(self::INVALID_REQUEST, '您有未完成的卡座套餐订单,请先完成后再购买新套餐1');

                } elseif ($order['order_type'] == 3) {

                    //散套订单查询验证
                    $pdo_sql = $pdo->query("SELECT COUNT(`id`) as total FROM `api_order` WHERE `arrives_time` = '{$arrives_time}' AND `member_id` = {$order['member_id']} AND `merchant_id` = '{$order['merchant_id']}' AND `order_type` = 3 AND `status` IN (1, 2, 7) AND `is_bar` = 0 limit 1");
                    $stock = $pdo_sql->fetch(PDO::FETCH_ASSOC);
                    if ($stock['total'] > 0) return self::response(self::INVALID_REQUEST, '您有未完成的优惠套餐订单,请先完成后再购买新套餐');

                }

                //获取商户卡套散套每日销售限量库存
                $pdo_sql = $pdo->query("SELECT `sanpack_stock`, `kapack_stock` FROM `api_merchant` WHERE `id` = '{$order['merchant_id']}' limit 1");
                $merchant_stock = $pdo_sql->fetch(PDO::FETCH_ASSOC);
                if (!$merchant_stock) {
                    return self::response(self::DB_SAVE_ERROR, '套餐库存获取出错');
                }

                //获取当前套餐商品信息与总库存
                $pdo_sql = $pdo->query("SELECT * FROM `api_goods_pack` WHERE `id` = '{$pack_goods['id']}' AND `status` = 1 limit 1");
                $pack_goods_stock = $pdo_sql->fetch(PDO::FETCH_ASSOC);
                if (!$pack_goods_stock) {
                    return self::response(self::DB_SAVE_ERROR, '该套餐已下架');
                }

                //2018年5月8日18:06:08 获取当日设置价格
                $show_date = date('Ymd', $order['arrives_time']);
                $pdo_sql = $pdo->query("SELECT `price` from `api_goods_price` WHERE `goods_id` = '{$pack_goods['id']}' AND `date` = {$show_date} limit 1");
                $todayPrice = $pdo_sql->fetch(PDO::FETCH_ASSOC);
                if (is_null($todayPrice['price'])) {
                    return self::response(self::DB_SAVE_ERROR, '此套餐暂时不支持购买');
                }
                $pack_goods_stock['price'] = $todayPrice['price'];

                //判断该套餐是否售馨
                if ($pack_goods_stock['stock'] < 1) {
                    return self::response(self::DB_READ_ERROR, '预购套餐已售馨');
                }

                //查询套餐当日库存记录是否存在
                $pdo_sql = $pdo->query("SELECT * FROM `api_goods_pack_stock` WHERE `date` = '{$date_int}' AND `goods_id` = '{$pack_goods['id']}' limit 1");
                $thisday_stock = $pdo_sql->fetch(PDO::FETCH_ASSOC);

                //判断套餐库存是否存在
                if ($thisday_stock) {
                    //商品类型 1散客套餐 2卡座套餐 3单品酒水
                    if ($pack_type == 1) {
                        //散套
                        $total_stock = $merchant_stock['sanpack_stock'];
                    } elseif ($pack_type == 2) {
                        //卡套
                        $total_stock = $merchant_stock['kapack_stock'];
                    }

                    //库存记录已存在,判断当日销售总数是否达到每日销售限制数量
                    if ($thisday_stock['day_sales'] >= $total_stock) {
                        return self::response(self::DB_READ_ERROR, '预购套餐已售馨');
                    }

                    //修改当日库存
                    $pack_add_stock_record_sql = "UPDATE `api_goods_pack_stock` SET day_sales = day_sales + 1 WHERE `date` = '{$date_int}' AND `goods_id` = '{$pack_goods['id']}'";
                    /*$result = $pdo->exec("UPDATE `api_goods_pack_stock` SET day_sales = day_sales + 1 WHERE `date` = '{$date_int}' AND `goods_id` = '{$pack_goods['id']}'");
                    if ($result === false) {
                        $pdo->rollBack();
                        return self::response(self::DB_READ_ERROR, '下单失败');
                    }*/


                } else {
                    //库存记录不存在,新增记录
                    $pack_add_stock_record_sql = "INSERT INTO `api_goods_pack_stock` (`merchant_id`, `goods_id`, `date`, `type`, `day_sales`) VALUES ('{$order['merchant_id']}', '{$pack_goods['id']}', '{$date_int}', '{$pack_type}', '{$pack_goods['amount']}')";

                    /*$result = $pdo->exec("INSERT INTO `api_goods_pack_stock` (`merchant_id`, `goods_id`, `date`, `type`, `day_sales`) VALUES ('{$order['merchant_id']}', '{$pack_goods['id']}', '{$date_int}', '{$pack_type}', '{$pack_goods['amount']}')");
                    if (!$result) {
                        $pdo->rollBack();
                        return self::response(self::DB_SAVE_ERROR, '创建订单失败');
                    }*/
                }

                //执行扣减套餐库存
                $pack_stock_change_sql = "UPDATE `api_goods_pack` SET `stock`= `stock`- {$pack_goods['amount']} WHERE `id`='{$pack_goods['id']}'";
                /*$goods_pack_rs = $pdo->exec("UPDATE `api_goods_pack` SET `stock`= `stock`- {$pack_goods['amount']} WHERE `id`='{$pack_goods['id']}'");
                if ($goods_pack_rs === false) {
                    $pdo->rollBack();
                    return self::response(self::DB_SAVE_ERROR, '1005-库存扣除失败');
                }*/

                //需要转换为字符串的写入数据
                $order_goods_table_data[] = [
                    'order_no' => $order_no,
                    'goods_pack_id' => $pack_goods_stock['id'],
                    'title' => $pack_goods_stock['title'],
                    'amount' => $pack_goods['amount'],
                    'price' => $pack_goods_stock['price'],
                    'image' => $pack_goods_stock['image'],
                    'merchant_id' => $order['merchant_id'],
                    'member_id' => $order['member_id'],
                    'pack_description' => $pack_goods_stock['description'],
                    'purchase_price' => $order['purchase_price'],
                    'market_price' => $pack_goods_stock['market_price'],
                    'goods_type' => $pack_goods_stock['type']
                ];
            }

            /**
             * 验证是否购买了单品酒水
             */
            if ($single_goods) {
                //获取单品酒水商品id
                $single_goods_keys = array_keys($single_goods);
                $single_goods_ids_str = implode(',', $single_goods_keys);

                //查询该单品酒水是否已在每日库存中存在记录
                $pdo_sql = $pdo->query("SELECT `goods_id`, `day_sales` FROM `api_goods_pack_stock` WHERE `goods_id` IN ({$single_goods_ids_str}) AND `date` = '{$date_int}'");
                $goods_everyday_total = [];     //每日销售记录表中的数据 ['商品ID' => '已售数量']
                while ($r = $pdo_sql->fetch(PDO::FETCH_ASSOC)) {
                    $goods_everyday_total[$r['goods_id']] = $r['day_sales'];
                }

                //将查询到的数据保存起来
                $add_single_goods_arr = [];
                $pdo_sql = $pdo->query("SELECT * FROM `api_goods_pack` WHERE `id` IN ({$single_goods_ids_str})");
                while ($r = $pdo_sql->fetch(PDO::FETCH_ASSOC)) {

                    //需要转换为字符串的写入数据
                    $order_goods_table_data[] = [
                        'order_no' => $order_no,
                        'goods_pack_id' => $r['id'],
                        'title' => $r['title'],
                        'amount' => $single_goods[$r['id']],
                        'price' => $r['price'],
                        'image' => $r['image'],
                        'merchant_id' => $order['merchant_id'],
                        'member_id' => $order['member_id'],
                        'pack_description' => $r['description'],
                        'purchase_price' => $r['purchase_price'],
                        'market_price' => $r['market_price'],
                        'goods_type' => $r['type']
                    ];

                    //验证是否存在记录
                    if (isset($goods_everyday_total[$r['id']])) {
                        //存在,修改数据
                        $single_add_stock_record_sql[] = "UPDATE `api_goods_pack_stock` SET `day_sales` = `day_sales` + '{$single_goods[$r['id']]}' WHERE `date` = '{$date_int}' AND `goods_id` = '{$r['id']}'";
                        /*$rs = $pdo->exec("UPDATE `api_goods_pack_stock` SET `day_sales` = `day_sales` + '{$single_goods[$r['id']]}' WHERE `date` = '{$date_int}' AND `goods_id` = '{$r['id']}'");
                        if ($rs === false) {
                            $pdo->rollBack();
                            return self::response(self::DB_READ_ERROR, '记录销售数量失败');
                        }*/
                    } else {
                        $add_single_goods_arr[] = "('{$order['merchant_id']}', '{$r['id']}', '{$date_int}', 3, '{$single_goods[$r['id']]}')";
                    }

                    //执行单点酒水的商品的库存扣减
                    $single_stock_change_sql[] = "UPDATE `api_goods_pack` SET `stock`= `stock`- '{$single_goods[$r['id']]}' WHERE `id`='{$r['id']}'";
                    /*$goods_pack_rs = $pdo->exec("UPDATE `api_goods_pack` SET `stock`= `stock`- '{$single_goods[$r['id']]}' WHERE `id`='{$r['id']}'");
                    if ($goods_pack_rs === false) {
                        $pdo->rollBack();
                        return self::response(self::DB_SAVE_ERROR, '库存扣除失败');
                    }*/
                }

                //新增数据,如果存在未新增数据才执行以下代码
                if ($add_single_goods_arr) {
                    $add_single_goods_string = implode(',', $add_single_goods_arr);
                    $add_single_goods_record_sql = "INSERT INTO `api_goods_pack_stock` (`merchant_id`, `goods_id`, `date`, `type`, `day_sales`) VALUES {$add_single_goods_string}";
                    /*$rs = $pdo->exec("INSERT INTO `api_goods_pack_stock` (`merchant_id`, `goods_id`, `date`, `type`, `day_sales`) VALUES {$add_single_goods_string}");
                    //判断是否执行成功
                    if ($rs === false) {
                        $pdo->rollBack();
                        return self::response(self::DB_READ_ERROR, '记录销售数量失败');
                    }*/
                }
            }

            //开启事务
            $pdo->beginTransaction();

            //修改套餐当日库存记录
            if (!is_null($pack_add_stock_record_sql)) {
                $result = $pdo->exec($pack_add_stock_record_sql);
                if ($result === false) {
                    $pdo->rollBack();
                    return self::response(self::DB_READ_ERROR, '下单失败');
                }
            }

            //新增套餐当日库存记录
            if (!is_null($pack_stock_change_sql)) {
                $result = $pdo->exec($pack_stock_change_sql);
                if ($result === false) {
                    $pdo->rollBack();
                    return self::response(self::DB_READ_ERROR, '下单失败');
                }
            }

            //修改单品酒水当日库存记录
            if ($single_add_stock_record_sql) {
                foreach ($single_add_stock_record_sql as $item_sql) {
                    $result = $pdo->exec($item_sql);
                    if ($result === false) {
                        $pdo->rollBack();
                        return self::response(self::DB_READ_ERROR, '下单失败');
                    }
                }
            }

            //执行单点酒水的商品的库存扣减
            if ($single_stock_change_sql) {
                foreach ($single_stock_change_sql as $single_sql) {
                    $result = $pdo->exec($single_sql);
                    if ($result === false) {
                        $pdo->rollBack();
                        return self::response(self::DB_READ_ERROR, '下单失败');
                    }
                }
            }

            //新增单品酒水当日库存记录
            if (!is_null($add_single_goods_record_sql)) {
                $result = $pdo->exec($add_single_goods_record_sql);
                if ($result === false) {
                    $pdo->rollBack();
                    return self::response(self::DB_READ_ERROR, '下单失败');
                }
            }

            /**
             * 创建商品主订单表数据
             */
            $order_add = $pdo->prepare("INSERT INTO `api_order` (`order_no`, `merchant_id`, `member_id`, `contacts_realname`, `contacts_tel`, `contacts_sex`, `total_price`, `pay_price`, `purchase_price`, `discount_money`, `status`, `settlement_status`, `order_type`, `description`, `arrives_time`, `created_time`, `updated_time`,`card_id`,`obegin_time`,`oend_time`) VALUES (:order_no, :merchant_id, :member_id, :contacts_realname, :contacts_tel, :contacts_sex, :total_price, :pay_price, :purchase_price, :discount_money, :status, :settlement_status, :order_type, :description, :arrives_time, :created_time, :updated_time ,:card_id,:obegin_time,:oend_time)");
            //绑定参数
            $pre_data = [
                ':order_no' => $order_no,
                ':merchant_id' => $order['merchant_id'],
                ':member_id' => $order['member_id'],
                ':contacts_realname' => $order['contacts_realname'],
                ':contacts_tel' => $order['contacts_tel'],
                ':contacts_sex' => $order['contacts_sex'],
                ':total_price' => $order['total_price'],
                ':pay_price' => $order['pay_price'],
                ':purchase_price' => $order['purchase_price'],
                ':discount_money' => $order['discount_money'],
                ':status' => $order['status'],
                ':settlement_status' => $order['settlement_status'],
                ':order_type' => $order['order_type'],
                ':description' => $order['description'],
                ':arrives_time' => $order['arrives_time'],
                ':created_time' => $created_time,
                ':updated_time' => $updated_time,
                ':card_id' => $order['card_id'] ? $order['card_id'] : 0,
                ':obegin_time' => $order['obegin_time'],
                ':oend_time' => $order['oend_time'],
            ];
            //执行语句结果
            if (!$order_add = $order_add->execute($pre_data)) {
                $pdo->rollBack();
                return self::response(self::DB_SAVE_ERROR, '订单创建失败');
            }

            //获取订单ID
            $last_order_id = $pdo->lastInsertId();

            //写入商品数据到订单商品表
            if ($order_goods_table_data) {
                //订单商品表数据添加
                foreach ($order_goods_table_data as $item) {
                    $pack_prepare = $pdo->prepare("INSERT INTO `api_order_pack` (`order_no`, `goods_pack_id`, `title`, `amount`, `price`, `image`, `merchant_id`, `member_id`, `pack_description`,`purchase_price`, `market_price`, `goods_type`, `order_id`) VALUES (:order_no, :goods_pack_id, :title, :amount, :price, :image, :merchant_id, :member_id, :pack_description,:purchase_price, :market_price, :goods_type, :order_id)");
                    $goodsArr = [
                        ":order_no" => $item['order_no'],
                        ":goods_pack_id" => $item['goods_pack_id'],
                        ":title" => $item['title'],
                        ":amount" => $item['amount'],
                        ":price" => $item['price'],
                        ":image" => $item['image'],
                        ":merchant_id" => $item['merchant_id'],
                        ":member_id" => $item['member_id'],
                        ":pack_description" => $item['pack_description'],
                        ":purchase_price" => $item['purchase_price'],
                        ":market_price" => $item['market_price'],
                        ":goods_type" => $item['goods_type'],
                        ":order_id" => $last_order_id
                    ];
                    //执行语句结果
                    if (!$order_add = $pack_prepare->execute($goodsArr)) {
                        $pdo->rollBack();
                        return self::response(self::DB_SAVE_ERROR, '商品存储失败');
                    }
                }
            }

            //优惠券变为已使用
            if ($order['card_id']) {
                $card_rs = $pdo->exec("UPDATE `api_coupon_member` SET `card_status` = 1 WHERE `card_id` = '{$order['card_id']}' AND `member_id` = '{$order['member_id']}'");
                //执行结果
                if ($card_rs === false) {
                    $pdo->rollBack();
                    return self::response(self::DB_SAVE_ERROR, '优惠券状态更新失败');
                }
            }

            //提交订单事务
            $pdo->commit();

            //延时队列
            $Pheanstalk = new Pheanstalk\Pheanstalk($GLOBALS['CONFIG']['BEANS_OPTIONS']['HOST']);
            $tube_name = $GLOBALS['CONFIG']['BEANS_OPTIONS']['TUBE_NAME'][0];
            $delayed_data = [
                'version' => 'v1.1',
                'order_id' => $last_order_id,
                'order_no' => $order_no,
                'buy_type' => 1,    //1普通下单 2普通续酒 3拼吧下单 4拼吧续酒
                'exc_type' => 1,    //执行类型 1订单取消 2订单作废 3订单逾期
            ];
            $Pheanstalk->putInTube($tube_name, json_encode($delayed_data), 0, $GLOBALS['CONFIG']['ORDER_OVERTIME']);

            //创建订单成功
            return self::response(self::SUCCESS, '下单成功',
                [
                    'order_id' => $last_order_id,
                    'order_no' => $order_no,
                    'buy_type' => 1,
                    'pay_money' => $order['pay_price'],
                ]);

        } catch (PDOException $e) {
            file_put_contents('./log/' . date('Y-m-d') . 'log', date('Y-m-d H:i:s') . '||' . $e->getMessage() . "\n", FILE_APPEND);
            return self::response(self::INVALID_REQUEST, '请求失败');
        }
    }

    /**
     * 购物车续酒下单 卡套/散套/单品酒水   v2.0
     * @param $data array 续酒订单数据
     * @return array|object|string
     */
    public static function buildMultiRenewOrder($data)
    {
        try {
            //建立PDO数据库连接
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_DBNAME, DB_USERNAME, DB_PASSWORD);
            $pdo->query('set names utf8;');
        } catch (PDOException $e) {
            return self::response(self::DB_CONNECT_FAIL, '数据库连接失败');
        }

        //获取订单号
        $order_no = self::_createOrderNumber();
        $order = $data['order'];
        //商品列表数据
        $single_goods = $data['goods'];

        try {

            //检查是否存在未支付订单
            $today_stock = $pdo->query("SELECT COUNT(`id`) as total FROM `api_order` WHERE `merchant_id` = '{$order['merchant_id']}' AND `member_id` = {$order['member_id']} AND `status` IN(1, 7) AND `top_order_id` <> 0 AND `is_bar` = 0 limit 1");
            $stock = $today_stock->fetch(PDO::FETCH_ASSOC);
            if ($stock['total'] > 0) {
                return self::response(self::INVALID_REQUEST, '您有待完成续酒订单,请完成后再购买');
            }

            //订单创建时间与最后修改时间
            $created_time = $updated_time = time();

            //到店日期格式化为yyyymmdd格式
            $date_int = (int)date('Ymd', $order['arrives_time']);

            //获取单品酒水商品id
            $single_goods_keys = array_keys($single_goods);
            $single_goods_ids_str = implode(',', $single_goods_keys);

            //查询该商品是否存在每日销售记录
            $pdo_sql = $pdo->query("SELECT `goods_id`, `xu_day_sales` FROM `api_goods_pack_stock` WHERE `goods_id` IN ({$single_goods_ids_str}) AND `date` = '$date_int'");
            $goods_everyday_total = [];     //每日销售记录表中的数据 ['商品ID' => '已售数量']
            while ($r = $pdo_sql->fetch(PDO::FETCH_ASSOC)) {
                $goods_everyday_total[$r['goods_id']] = $r['xu_day_sales'];
            }

            //将查询到的数据保存起来
            $order_goods_table_data = [];     //将要写入订单商品表的数据

            $goods_stock_update_sql = [];   //修改每日库存SQL
            $goods_stock_decrease_sql = []; //减去商品库存SQL
            $goods_pack_stock_add_sql = null; //新增每日库存SQL

            $add_single_goods_arr = []; //每日销售记录表中需要增加的商品销售数量数据

            $pdo_sql = $pdo->query("SELECT `id`,`merchant_id`,`title`,`type`,(select `price` from `api_goods_price` where `date` = '{$date_int}' AND `goods_id` = api_goods_pack.id) as price,`image`,`description`,`created_time`,`stock`,`xu_stock`,`status`,api_goods_pack.price as case_price,`market_price`,`purchase_price` FROM `api_goods_pack` WHERE `id` IN ({$single_goods_ids_str})");
            while ($r = $pdo_sql->fetch(PDO::FETCH_ASSOC)) {

                if ($r['type'] == 3 && is_null($r['price'])) {
                    $r['price'] = $r['case_price'];
                }

                if ($r['type'] != 3 && is_null($r['price'])) {
                    return self::response(self::DB_READ_ERROR, '商品暂不支持购买');
                }

                //该商品购买数量
                $amount = $single_goods[$r['id']];
                //判断商品库存是否充足
                if ($r['xu_stock'] < $amount) {
                    return self::response(self::NOT_STOCK, '商品已售馨');
                }

                //验证库存记录是否存在
                if (isset($goods_everyday_total[$r['id']])) {
                    //存在,修改数据
                    $goods_stock_update_sql[] = "UPDATE `api_goods_pack_stock` SET `xu_day_sales` = `xu_day_sales` + {$amount} WHERE `date` = '{$date_int}' AND `goods_id` = '{$r['id']}'";
                } else {
                    $add_single_goods_arr[] = "('{$order['merchant_id']}', '{$r['id']}', '{$date_int}', '{$r['type']}', '{$amount}')";
                }

                //执行续酒库存的扣减
                $goods_stock_decrease_sql[] = "UPDATE `api_goods_pack` SET `xu_stock`= `xu_stock`-{$amount} WHERE `id`='{$r['id']}'";

                //需要转换为字符串的写入数据
                $order_goods_table_data[] = [
                    'order_no' => $order_no,      //订单ID
                    'goods_pack_id' => $r['id'],       //商品ID
                    'title' => $r['title'],    //商品标题
                    'amount' => $amount,        //购买数量
                    'price' => $r['price'],    //商品价格
                    'image' => $r['image'],    //商品图片
                    'merchant_id' => $order['merchant_id'],  //商户ID
                    'member_id' => $order['member_id'],    //用户ID
                    'pack_description' => $r['description'],      //商品描述
                    'purchase_price' => $r['purchase_price'],   //结算价格
                    'market_price' => $r['market_price'],     //市场价格
                    'goods_type' => $r['type']              //商品类型
                ];
            }

            /**
             * 新增数据,如果存在未新增数据才执行以下代码
             */
            if ($add_single_goods_arr) {
                $add_single_goods_string = implode(',', $add_single_goods_arr);
                $goods_pack_stock_add_sql = "INSERT INTO `api_goods_pack_stock` (`merchant_id`, `goods_id`, `date`, `type`, `xu_day_sales`) VALUES {$add_single_goods_string}";
            }

            //开启事务
            $pdo->beginTransaction();

            //修改每日库存SQL
            if (count($goods_stock_update_sql) > 0) {
                foreach ($goods_stock_update_sql as $item_sql) {
                    //判断是否执行成功
                    if ($pdo->exec($item_sql) === false) {
                        $pdo->rollBack();
                        return self::response(self::DB_READ_ERROR, '库存记录失败');
                    }
                }
            }

            //减去商品库存SQL
            if (count($goods_stock_decrease_sql) > 0) {
                foreach ($goods_stock_decrease_sql as $item_sql) {
                    //判断是否执行成功
                    if ($pdo->exec($item_sql) === false) {
                        $pdo->rollBack();
                        return self::response(self::DB_READ_ERROR, '扣减库存失败');
                    }
                }
            }

            //新增每日库存SQL
            if (!is_null($goods_pack_stock_add_sql)) {
                if ($pdo->exec($goods_pack_stock_add_sql) === false) {
                    $pdo->rollBack();
                    return self::response(self::DB_READ_ERROR, '增加库存记录失败');
                }
            }

            /**
             * 创建续酒主订单数据
             */
            $order_add = $pdo->prepare("INSERT INTO `api_order` (`order_no`, `merchant_id`, `member_id`, `contacts_realname`, `contacts_tel`, `contacts_sex`, `employee_id`, `employee_realname`, `employee_avatar`, `employee_tel`, `total_price`, `pay_price`, `purchase_price`, `discount_money`, `status`, `settlement_status`, `order_type`, `description`, `arrives_time`, `created_time`, `updated_time`, `top_order_id`, `desk_number`, `card_id`, `is_xu`, `is_bar`) VALUES (:order_no, :merchant_id, :member_id, :contacts_realname, :contacts_tel, :contacts_sex, :employee_id, :employee_realname, :employee_avatar, :employee_tel, :total_price, :pay_price, :purchase_price, :discount_money, :status, :settlement_status, :order_type, :description, :arrives_time, :created_time, :updated_time, :top_order_id, :desk_number, :card_id, :is_xu, :is_bar)");
            //绑定参数
            $pre_data = [
                ':order_no' => $order_no,
                ':merchant_id' => $order['merchant_id'],

                //用户数据
                ':member_id' => $order['member_id'],
                ':contacts_realname' => $order['contacts_realname'],
                ':contacts_tel' => $order['contacts_tel'],
                ':contacts_sex' => $order['contacts_sex'],

                //员工数据
                ':employee_id' => $order['employee_id'],
                ':employee_realname' => $order['employee_realname'],
                ':employee_avatar' => $order['employee_avatar'],
                ':employee_tel' => $order['employee_tel'],

                //金额数据
                ':total_price' => $order['total_price'],
                ':pay_price' => $order['pay_price'],
                ':purchase_price' => $order['purchase_price'],
                ':discount_money' => $order['discount_money'],

                //订单状态数据
                ':status' => $order['status'],
                ':settlement_status' => $order['settlement_status'],

                ':order_type' => $order['order_type'],
                ':description' => $order['description'],

                //订单时间数据
                ':arrives_time' => $order['arrives_time'],
                ':created_time' => $created_time,
                ':updated_time' => $updated_time,

                //其他数据
                ':top_order_id' => $order['top_order_id'],
                ':desk_number' => $order['desk_number'],
                ':card_id' => $order['card_id'] ? $order['card_id'] : 0,    //优惠券
                ':is_xu' => $order['is_xu'],    //是否续酒订单
                ':is_bar' => $order['is_bar'],  //是否拼吧订单
            ];
            //执行语句结果
            if (!$order_add = $order_add->execute($pre_data)) {
                $pdo->rollBack();
                return self::response(self::DB_SAVE_ERROR, '订单创建失败');
            }

            //获取订单ID
            $last_order_id = $pdo->lastInsertId();

            //订单商品表数据添加
            foreach ($order_goods_table_data as $goods_table_datum) {
                $goods_add = $pdo->prepare("INSERT INTO `api_order_pack` (`order_no`, `goods_pack_id`, `title`, `amount`, `price`, `image`, `merchant_id`, `member_id`, `pack_description`,`purchase_price`, `market_price`, `goods_type`, `order_id`) VALUES (:order_no, :goods_pack_id, :title, :amount, :price, :image, :merchant_id, :member_id, :pack_description, :purchase_price, :market_price, :goods_type, :order_id)");
                $goods_arr = [
                    ':order_no' => $goods_table_datum['order_no'],      //订单ID
                    ':goods_pack_id' => $goods_table_datum['goods_pack_id'],       //商品ID
                    ':title' => $goods_table_datum['title'],    //商品标题
                    ':amount' => $goods_table_datum['amount'],        //购买数量
                    ':price' => $goods_table_datum['price'],    //商品价格
                    ':image' => $goods_table_datum['image'],    //商品图片
                    ':merchant_id' => $goods_table_datum['merchant_id'],  //商户ID
                    ':member_id' => $goods_table_datum['member_id'],    //用户ID
                    ':pack_description' => $goods_table_datum['pack_description'],      //商品描述
                    ':purchase_price' => $goods_table_datum['purchase_price'],   //结算价格
                    ':market_price' => $goods_table_datum['market_price'],     //市场价格
                    ':goods_type' => $goods_table_datum['goods_type'],           //商品类型
                    ':order_id' => $last_order_id
                ];
                if (!$res = $goods_add->execute($goods_arr)) {
                    $pdo->rollBack();
                    return self::response(self::DB_SAVE_ERROR, '下单失败');
                }
            }

            //优惠券变为已使用
            if ($order['card_id']) {
                $card_rs = $pdo->exec("UPDATE `api_coupon_member` SET `card_status` = 1 WHERE `card_id` = '{$order['card_id']}' AND `member_id` = '{$order['member_id']}'");
                //执行结果
                if ($card_rs === false) {
                    $pdo->rollBack();
                    return self::response(self::DB_SAVE_ERROR, '优惠券使用失败');
                }
            }

            //提交订单事务
            $pdo->commit();

            //延时队列
            $Pheanstalk = new Pheanstalk\Pheanstalk($GLOBALS['CONFIG']['BEANS_OPTIONS']['HOST']);
            $tube_name = $GLOBALS['CONFIG']['BEANS_OPTIONS']['TUBE_NAME'][0];
            $delayed_data = [
                'version' => 'v1.1',
                'order_id' => $last_order_id,
                'order_no' => $order_no,
                'buy_type' => 2,    //1普通下单 2普通续酒 3拼吧下单 4拼吧续酒
                'exc_type' => 1,    //执行类型 1订单取消 2订单作废 3订单逾期
            ];
            $Pheanstalk->putInTube($tube_name, json_encode($delayed_data), 0, $GLOBALS['CONFIG']['ORDER_OVERTIME']);

            //创建订单成功
            return self::response(self::SUCCESS, '下单成功',
                [
                    'order_id' => $last_order_id,
                    'order_no' => $order_no,
                    'buy_type' => 2,
                    'pay_money' => $order['pay_price'],
                ]);

        } catch (PDOException $e) {
            file_put_contents('./log/' . date('Y-m-d') . 'log', date('Y-m-d H:i:s') . '||' . $e->getMessage() . "\n", FILE_APPEND);
            return self::response(self::INVALID_REQUEST, '请求失败');
        }
    }

    /**
     * 拼吧下单 version 2.0
     * @param $data array
     * @return array|object|string
     */
    public static function buidBarOrder($data)
    {
        try {
            //建立PDO数据库连接
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_DBNAME, DB_USERNAME, DB_PASSWORD);
            $pdo->query('set names utf8;');
        } catch (PDOException $e) {
            return self::response(self::DB_CONNECT_FAIL, '数据库连接失败' . $e->getMessage());
        }

        $order = $data['order'];
        //套餐商品 单个
        $pack_goods = isset($data['goods']['pack_goods'][0]) ? $data['goods']['pack_goods'][0] : [];
        //单品酒水 多个
        $single_goods = $data['goods']['single_goods'];

        try {
            //拼吧订单编号
            $bar_no = self::create_order_number(3);

            $created_time = $updated_time = time();
            $date_int = date('Ymd', $order['arrives_time']);

            //开启事务
            $pdo->beginTransaction();

            //当bar_type 1 酒局  2 派对
            if ($order['bar_type'] == 1) {
                //查看是否存在未处理的酒局
                $undeal_sql = $pdo->query("SELECT `id` FROM `api_bar` WHERE `bar_status`= 1 AND `arrives_time` = '{$order['arrives_time']}' AND `member_id` = '{$order['member_id']}' AND `bar_type` = 1");
                $undeal_bar = $undeal_sql->fetch(PDO::FETCH_ASSOC);
                if ($undeal_bar) {
                    return self::response(self::INVALID_REQUEST, '你当日存在未完成的酒局');
                }

                //拼了 套餐信息
                if ($pack_goods) {
                    //根据商品ID查商品信息(商品总库存)
                    $son_sql = "(select `price` from `api_goods_price` where `date` = '{$date_int}' AND `goods_id` = api_goods_pack.id) as price";
                    $goods = $pdo->query("SELECT `id`,`stock`,`type`,`title`,{$son_sql},`image`,`description`,`market_price`,`purchase_price` FROM `api_goods_pack` WHERE `id`='{$pack_goods['id']}' limit 1");
                    $goods_stock = $goods->fetch(PDO::FETCH_ASSOC);
                    if ($goods_stock['stock'] < 1) {
                        return self::response(self::INVALID_REQUEST, '该套餐已售罄');
                    }

                    //判断该套餐是否有设置价格
                    if (is_null($goods_stock['price'])) {
                        return self::response(self::INVALID_REQUEST, '此套餐暂不支持购买');
                    }

                    //查看每日套餐库存数(每日允许售卖库存)预售库存
                    $daily_stock = $pdo->query("SELECT `sanpack_stock`,`kapack_stock` FROM `api_merchant` WHERE `id`='{$order['merchant_id']}' limit 1");
                    $day_stock = $daily_stock->fetch(PDO::FETCH_ASSOC);

                    //首先判断库存是否足够(当日商品已销售数量)
                    $good_pack = $pdo->query("SELECT `day_sales` FROM `api_goods_pack_stock` WHERE `goods_id`= '{$pack_goods['id']}' AND `date`='$date_int' limit 1");
                    $sales = $good_pack->fetch(PDO::FETCH_ASSOC);

                    //如果是卡套,就和卡套的比较(日库存剩余)
                    if ($goods_stock['type'] == 2) {
                        $yu_stock = $day_stock['kapack_stock'] - $sales['day_sales'];
                    } else if ($goods_stock['type'] == 1) {
                        $yu_stock = $day_stock['sanpack_stock'] - $sales['day_sales'];
                    }

                    if ($yu_stock > 0) {
                        if ($goods_stock['stock'] >= $yu_stock) {
                            $stock = $yu_stock;
                        } else {
                            $stock = $goods_stock['stock'];
                        }
                    } else {
                        $stock = 0;
                    }

                    //判断如果库存为0了,表示库存不足,
                    if ($stock == 0) {
                        return self::response(self::INVALID_REQUEST, '1006-该套餐的库存不足,请选择其他套餐');
                    }

                    if ($sales) {
                        //更新每日销量
                        $pack_stock = $pdo->exec("UPDATE `api_goods_pack_stock` SET `day_sales` = `day_sales` + 1 WHERE `goods_id`='{$goods_stock['id']}' AND `date`='$date_int'");
                        if ($pack_stock === false) {
                            $pdo->rollBack();
                            return self::response(self::DB_SAVE_ERROR, '1005-库存扣除失败');
                        }
                    } else {
                        $result = $pdo->exec("INSERT INTO `api_goods_pack_stock` (`merchant_id`, `goods_id`, `date`, `type`, `day_sales`) VALUES ('{$order['merchant_id']}', '{$goods_stock['id']}', '$date_int', '{$goods_stock['type']}', 1)");
                        if ($result === false) {
                            $pdo->rollBack();
                            return self::response(self::DB_SAVE_ERROR, '1005-库存扣除失败');
                        }
                    }

                    //扣减商品表中的总库存数
                    $goods_pack_rs = $pdo->exec("UPDATE `api_goods_pack` SET `stock`= `stock`- 1 WHERE `id`= '{$goods_stock['id']}'");
                    if ($goods_pack_rs === false) {
                        $pdo->rollBack();
                        return self::response(self::DB_SAVE_ERROR, '1005-库存扣除失败1');
                    }
                }
                //如果拼吧存在单品酒水
                if ($single_goods) {
                    //暂时不计算
                }
            }

            //派对直接创建订单
            $order_add = $pdo->prepare("INSERT INTO `api_bar` (`bar_no`, `bar_type`, `bar_theme`, `cost_type`, `man_number`, `woman_number`,`average_cost`, `merchant_id`, `member_id`, `contacts_realname`, `contacts_tel`, `contacts_sex`, `total_price`, `pay_price`, `purchase_price`, `bar_status`,`order_type`, `description`,`arrives_time`,`created_time`,`updated_time`,`top_bar_id`,`is_xu`,`obegin_time`,`oend_time`,`is_join`) VALUES (:bar_no, :bar_type,:bar_theme,:cost_type,:man_number,:woman_number,:average_cost,:merchant_id, :member_id, :contacts_realname, :contacts_tel, :contacts_sex, :total_price, :pay_price, :purchase_price, :bar_status, :order_type, :description, :arrives_time, :created_time, :updated_time, :top_bar_id ,:is_xu,:obegin_time,:oend_time,:is_join)");
            //绑定参数
            $order_add = $order_add->execute([
                ':bar_no' => $bar_no,
                ':bar_type' => $order['bar_type'],
                ':bar_theme' => $order['bar_theme'],
                ':cost_type' => $order['cost_type'],
                ':man_number' => $order['man_number'],
                ':woman_number' => $order['woman_number'],
                ':average_cost' => $order['average_cost'],
                ':merchant_id' => $order['merchant_id'],
                ':member_id' => $order['member_id'],
                ':contacts_realname' => $order['realname'],
                ':contacts_tel' => $order['tel'],
                ':contacts_sex' => $order['sex'],
                ':total_price' => $order['total_price'],
                ':pay_price' => $order['pay_price'],
                ':purchase_price' => $order['purchase_price'],
                ':bar_status' => 1,
                ':order_type' => $order['order_type'],
                ':description' => $order['description'],
                ':arrives_time' => $order['arrives_time'],
                ':created_time' => $created_time,
                ':updated_time' => $updated_time,
                ':top_bar_id' => 0,
                ':is_xu' => 0,
                ':obegin_time' => $order['obegin_time'],
                ':oend_time' => $order['oend_time'],
                ':is_join' => $order['is_join'],
            ]);

            if ($order_add === false) {
                $pdo->rollBack();
                return self::response(self::DB_SAVE_ERROR, '1005-订单创建失败1');
            }
            $bar_id = $pdo->lastInsertId();

            //酒局需要付钱将拼吧发起人写入数据表中
            if ($order['bar_type'] == 1) {
                $pay_no = self::create_order_number(4);
                //用户拼吧表中写入数据
                $member_bar = $pdo->prepare("INSERT INTO `api_bar_member`(`bar_id`,`pay_no`,`member_id`,`pay_price`,`pay_status`,`pay_type`,`created_time`,`updated_time`,`realname`,`sex`,`tel`,`avatar`,`age`,`is_evaluate`)VALUES (:bar_id,:pay_no,:member_id,:pay_price,:pay_status,:pay_type,:created_time,:updated_time,:realname,:sex,:tel,:avatar,:age,:is_evaluate)");
                $member_bar_add = $member_bar->execute([
                    ':bar_id' => $bar_id,
                    ':pay_no' => $pay_no,
                    ':member_id' => $order['member_id'],
                    ':pay_price' => $order['personal_price'],
                    ':pay_status' => 1,
                    ':pay_type' => 0,
                    ':created_time' => $created_time,
                    ':updated_time' => $updated_time,
                    ':realname' => $order['realname'],
                    ':sex' => $order['sex'],
                    ':tel' => $order['tel'],
                    ':avatar' => $order['avatar'],
                    ':age' => $order['age'],
                    ':is_evaluate' => 0,
                ]);

                if ($member_bar_add === false) {
                    $pdo->rollBack();
                    return self::response(self::DB_SAVE_ERROR, '1005-拼吧用户信息写入失败');
                }
                $pay_id = $pdo->lastInsertId();

                //将数据写入订单商品表中
                $order_pack_add = $pdo->prepare("INSERT INTO `api_bar_pack` (`bar_id`, `goods_pack_id`, `title`, `amount`, `price`, `image`, `merchant_id`, `member_id`, `pack_description`, `purchase_price`,`market_price`,`goods_type`) VALUES (:order_id, :goods_pack_id,
:title, :amount, :price, :image, :merchant_id, :member_id, :pack_description, :purchase_price,:market_price,:goods_type)");
                $order_pack_add = $order_pack_add->execute([
                    ':order_id' => $bar_id,
                    ':goods_pack_id' => $goods_stock['id'],
                    ':title' => $goods_stock['title'],
                    ':amount' => 1,
                    ':price' => $goods_stock['price'],
                    ':image' => $goods_stock['image'],
                    ':merchant_id' => $order['merchant_id'],
                    ':member_id' => $order['member_id'],
                    ':pack_description' => $goods_stock['description'],
                    ':purchase_price' => $goods_stock['purchase_price'],
                    ':market_price' => $goods_stock['market_price'],
                    ':goods_type' => $goods_stock['type'],
                ]);

                if ($order_pack_add === false) {
                    $pdo->rollBack();
                    return self::response(self::DB_SAVE_ERROR, '1005-订单商品写入失败');
                }
            }

            $pdo->commit();

            /**
             * 加入延时队列
             */
            //获取当前商户的营业时间
            /* $merchant_sql = $pdo->query("SELECT `begin_time` FROM `api_merchant` WHERE `id` = '{$order['merchant_id']}'");
             $merchant_time = $merchant_sql->fetch(PDO::FETCH_ASSOC);
             if (count($merchant_time) <= 0) {
                 $pdo->rollBack();
                 return self::response(self::INVALID_REQUEST, '延迟处理失败');
             }
             $begin_time = $merchant_time['begin_time'];*/

            //开启队列传输
            $Pheanstalk = new Pheanstalk\Pheanstalk($GLOBALS['CONFIG']['BEANS_OPTIONS']['HOST']);
            $tube_name = $GLOBALS['CONFIG']['BEANS_OPTIONS']['TUBE_NAME'][0];
            $delayed_data = [
                'version' => 'v1.1',
                'order_id' => $bar_id,
                'order_no' => $bar_no,
                'buy_type' => 3,    //1普通下单 2普通续酒 3拼吧下单 4拼吧续酒
                'exc_type' => 1,    //执行类型 1订单取消 2订单作废 3订单逾期
            ];
            $going_time = $order['obegin_time'];
            $diff_time = $going_time - time();
            $queue_end_time = $diff_time - $GLOBALS['CONFIG']['BEFORE_TIME'];
            $queue_time = $queue_end_time <= 0 ? 0 : $queue_end_time;

            //执行
            $Pheanstalk->putInTube($tube_name, json_encode($delayed_data), 0, $queue_time);

            //判断如果是酒局订单需要给队列里面加入任务
            if ($order['bar_type'] == 1) {
                $cancel_time = $GLOBALS['CONFIG']['ORDER_OVERTIME'];
                $delayed_data = [
                    'version' => 'v1.1',
                    'order_id' => $pay_id,
                    'order_no' => $pay_no,
                    'buy_type' => 3,    //1普通下单 2普通续酒 3拼吧下单 4拼吧续酒
                    'exc_type' => 4,    //执行类型 1订单取消 2订单作废 3订单逾期
                ];
                $Pheanstalk->putInTube($tube_name, json_encode($delayed_data), 0, $cancel_time);
            }

            /**
             * step.4 创建订单完成, 返回订单数据
             */
            //创建订单成功
            return self::response(self::SUCCESS, '下单成功', [
                'order_id' => isset($pay_id) ? $pay_id : 0,
                'order_no' => isset($pay_no) ? $pay_no : 0,
                'bar_id' => $bar_id,
                'buy_type' => 3,
                'pay_money' => $order['personal_price'],
            ]);

        } catch (PDOException $e) {
            file_put_contents('./log/' . date('Y-m-d') . 'log', date('Y-m-d H:i:s') . '||' . $e->getMessage() . "\n", FILE_APPEND);
            return self::response(self::INVALID_REQUEST, '1007-创建订单失败');
        }
    }

    /**
     * 拼吧续酒下单 version  2.0
     * @param $data
     * @return array|object|string
     */
    public static function buidRenewBarOrder($data)
    {
        try {
            //建立PDO数据库连接
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_DBNAME, DB_USERNAME, DB_PASSWORD);
            $pdo->query('set names utf8;');
        } catch (PDOException $e) {
            return self::response(self::DB_CONNECT_FAIL, '数据库连接失败' . $e->getMessage());
        }

        //获取订单号
        $bar_no = self::_createOrderNumber(3);
        $single_goods = $data['goods'];//续集商品信息
        $order = $data['order'];//拼吧续酒订单信息
        $members = $data['member'];//参与人信息
        $current_member_id = $data['current_member'];

        try {
            //检查是否存在未完成的续酒订单
            $today_stock = $pdo->query("SELECT COUNT(`id`) as total FROM `api_bar` WHERE `member_id`='{$order['member_id']}' AND `bar_status` IN (1,2,7) AND `is_xu`=1 limit 1");
            $stock = $today_stock->fetch(PDO::FETCH_ASSOC);
            if ($stock['total'] > 0) {
                return self::response(self::INVALID_REQUEST, '您有未完成的续酒订单,请完成后再购买新商品');
            }

            //订单创建时间与最后修改时间
            $created_time = $updated_time = time();
            //开启事务
            $pdo->beginTransaction();
            //到店日期格式化为yyyymmdd格式
            $date_int = (int)date('Ymd', $order['arrives_time']);

            //获取单品酒水商品id
            $single_goods_keys = array_keys($single_goods);
            $single_goods_ids_str = implode(',', $single_goods_keys);

            //查询该商品是否存在当日销售记录
            $pdo_sql = $pdo->query("SELECT `goods_id`, `xu_day_sales` FROM `api_goods_pack_stock` WHERE `goods_id` IN ($single_goods_ids_str) AND `date` = '$date_int'");
            $goods_everyday_total = [];     //每日销售记录表中的数据 ['商品ID' => '已售数量']
            while ($r = $pdo_sql->fetch(PDO::FETCH_ASSOC)) {
                $goods_everyday_total[$r['goods_id']] = $r['xu_day_sales'];
            }

            //将查询到的数据保存起来
            $single_goods_array = [];   //即将插入的单品酒水数据
            $add_single_goods_arr = []; //每日销售记录表中需要增加的商品销售数量数据
            $pdo_sql = $pdo->query("SELECT `id`,`merchant_id`,`title`,`type`,(select `price` from `api_goods_price` where `date` = '{$date_int}' AND `goods_id` = api_goods_pack.id) as price,`image`,`description`,`created_time`,`stock`,`xu_stock`,`status`,api_goods_pack.price as case_price,`market_price`,`purchase_price` FROM `api_goods_pack` WHERE `id` IN ({$single_goods_ids_str})");
            while ($r = $pdo_sql->fetch(PDO::FETCH_ASSOC)) {

                if ($r['type'] == 3 && is_null($r['price'])) {
                    $r['price'] = $r['case_price'];
                }

                if ($r['type'] != 3 && is_null($r['price'])) {
                    return self::response(self::DB_READ_ERROR, '商品暂不支持购买');
                }

                //该商品购买数量
                $amount = $single_goods[$r['id']];
                //判断商品库存是否充足
                if ($r['xu_stock'] < $amount) {
                    return self::response(self::NOT_STOCK, '商品已售馨');
                }

                //需要转换为字符串的写入数据
                $single_goods_array[] = [
                    $r['id'],       //商品ID
                    $r['title'],    //商品标题
                    $amount,        //购买数量
                    $r['price'],    //商品价格
                    $r['image'],    //商品图片
                    $order['merchant_id'],  //商户ID
                    $order['member_id'],    //用户ID
                    $r['description'],      //商品描述
                    $r['purchase_price'],   //结算价格
                    $r['market_price'],     //市场价格
                    $r['type']              //商品类型
                ];

                //验证库存记录是否存在
                if (isset($goods_everyday_total[$r['id']])) {
                    //存在,修改数据
                    $rs = $pdo->exec("UPDATE `api_goods_pack_stock` SET `xu_day_sales` = `xu_day_sales` + {$amount} WHERE `date` = '{$date_int}' AND `goods_id` = '{$r['id']}'");
                    if ($rs === false) {
                        $pdo->rollBack();
                        return self::response(self::DB_READ_ERROR, '记录销售数量失败');
                    }
                } else {
                    $add_single_goods_arr[] = "('{$order['merchant_id']}', '{$r['id']}', '{$date_int}', '{$r['type']}', '{$amount}')";
                }

                /**
                 * 扣除库存
                 */
                $goods_pack_rs = $pdo->exec("UPDATE `api_goods_pack` SET `xu_stock` = `xu_stock` - {$amount} WHERE `id`='{$r['id']}'");
                if ($goods_pack_rs === false) {
                    $pdo->rollBack();
                    return self::response(self::DB_READ_ERROR, '记录销售数量失败');
                }
            }

            /**
             * 新增数据,如果存在未新增数据才执行以下代码
             */
            if ($add_single_goods_arr) {
                $add_single_goods_string = implode(',', $add_single_goods_arr);
                $rs = $pdo->exec("INSERT INTO `api_goods_pack_stock` (`merchant_id`, `goods_id`, `date`, `type`, `xu_day_sales`) VALUES {$add_single_goods_string}");
                //判断是否执行成功
                if ($rs === false) {
                    $pdo->rollBack();
                    return self::response(self::DB_READ_ERROR, '记录销售数量失败');
                }
            }

            /**
             * 创建续酒主订单数据
             */
            $order_add = $pdo->prepare("INSERT INTO `api_bar` (`bar_no`, `bar_type`, `bar_theme`, `cost_type`, `man_number`, `woman_number`,`average_cost`, `merchant_id`, `member_id`, `contacts_realname`, `contacts_tel`, `contacts_sex`, `total_price`, `pay_price`, `purchase_price`, `bar_status`,`order_type`, `description`,`arrives_time`,`created_time`,`updated_time`,`top_bar_id`,`is_xu`) VALUES (:bar_no, :bar_type,:bar_theme,:cost_type,:man_number,:woman_number,:average_cost,:merchant_id, :member_id, :contacts_realname, :contacts_tel, :contacts_sex, :total_price, :pay_price, :purchase_price, :bar_status, :order_type, :description, :arrives_time, :created_time, :updated_time, :top_bar_id ,:is_xu)");
            //绑定参数
            $order_add = $order_add->execute([
                ':bar_no' => $bar_no,
                ':bar_type' => $order['bar_type'],
                ':bar_theme' => $order['bar_theme'],
                ':cost_type' => $order['cost_type'],
                ':man_number' => $order['man_number'],
                ':woman_number' => $order['woman_number'],
                ':average_cost' => $order['average_cost'],
                ':merchant_id' => $order['merchant_id'],
                ':member_id' => $order['member_id'],
                ':contacts_realname' => $order['realname'],
                ':contacts_tel' => $order['tel'],
                ':contacts_sex' => $order['sex'],
                ':total_price' => $order['total_price'],
                ':pay_price' => $order['pay_price'],
                ':purchase_price' => $order['purchase_price'],
                ':bar_status' => 1,
                ':order_type' => $order['order_type'],
                ':description' => $order['description'],
                ':arrives_time' => $order['arrives_time'],
                ':created_time' => $created_time,
                ':updated_time' => $updated_time,
                ':top_bar_id' => $order['top_bar_id'],
                ':is_xu' => 1,
            ]);
            if ($order_add === false) {
                $pdo->rollBack();
                return self::response(self::DB_SAVE_ERROR, '1005-订单创建失败');
            }
            $bar_id = $pdo->lastInsertId();


            //即将插入数据表中的商品数据
            $order_goods_table_data = [];

            //单品酒水存在且不为空
            if (isset($single_goods_array) && $single_goods_array == true) {
                $single_goods_array = array_map(function ($rows) use ($bar_id) {
                    $rows[] = $bar_id;
                    $new_rows = [];
                    foreach ($rows as $item) {
                        $new_rows[] = "'" . $item . "'";
                    }
                    $str = implode(',', $new_rows);
                    return "({$str})";
                }, $single_goods_array);

                //单品酒水数据字符串
                $order_goods_table_data = $single_goods_array;
            }

            //套餐
            if (isset($pack_goods_stock) && $pack_goods_stock == true) {
                $pack_goods_stock[] = $bar_id;
                $pack_goods_stock = array_map(function ($pack_row) use ($bar_id) {
                    return "'" . $pack_row . "'";
                }, $pack_goods_stock);
                $order_goods_table_data[] = "(" . implode(', ', $pack_goods_stock) . ")";
            }

            //最终插入数据
            $goods_array_string = implode(', ', $order_goods_table_data);

            //订单商品表数据添加
            $pack_add = $pdo->exec("INSERT INTO `api_bar_pack` ( `goods_pack_id`, `title`, `amount`, `price`, `image`, `merchant_id`, `member_id`, `pack_description`,`purchase_price`, `market_price`, `goods_type`,`bar_id`) VALUES {$goods_array_string}");
            if (!$pack_add) {
                $pdo->rollBack();
                return self::response(self::DB_SAVE_ERROR, '下单失败');
            }

            $member_data = [];
            $members1 = [];

            foreach ($members as $key => $member) {
                $pay_no = self::_createOrderNumber(4);
                $members1[$key][] = "'" . $bar_id . "'";
                $members1[$key][] = "'" . $pay_no . "'";
                $members1[$key][] = "'" . $member['member_id'] . "'";
                $members1[$key][] = "'" . $order['average_cost'] . "'";
                $members1[$key][] = "'" . 1 . "'";
                $members1[$key][] = "'" . 0 . "'";
                $members1[$key][] = "'" . $created_time . "'";
                $members1[$key][] = "'" . $updated_time . "'";
                $members1[$key][] = "'" . $order['arrives_time'] . "'";
                $members1[$key][] = "'" . $member['nickname'] . "'";
                $members1[$key][] = "'" . $member['sex'] . "'";
                $members1[$key][] = "'" . $member['tel'] . "'";
                $members1[$key][] = "'" . $member['avatar'] . "'";
                $members1[$key][] = "'" . $member['age'] . "'";
                $members1[$key][] = "'" . 0 . "'";
                $member_data[] = "(" . implode(",", $members1[$key]) . ")";
            }

            $member_array_string = implode(",", $member_data);

            //插入拼吧续酒用户信息
            $member_bar_add = $pdo->exec("INSERT INTO `api_bar_member` (`bar_id`, `pay_no`, `member_id`, `pay_price`,`pay_status`, `pay_type`, `created_time`, `updated_time`, `arrives_time`, `realname`,`sex`, `tel`, `avatar`,`age`,`is_evaluate`) VALUES {$member_array_string}");
            if (!$member_bar_add) {
                $pdo->rollBack();
                return self::response(self::DB_SAVE_ERROR, '参与续酒用户信息录入失败', $member_array_string);
            }

            //获取当前发起续酒用户的支付信息拼吧信息
            $current_pay_info = $pdo->query("SELECT `id`,`pay_price`,`pay_no` FROM `api_bar_member` WHERE `member_id`='{$current_member_id}' AND `bar_id`= '{$bar_id}' ");
            $current_pay_info = $current_pay_info->fetch(PDO::FETCH_ASSOC);

            /**
             * 加入延时队列
             */
            //获取当前商户的营业时间
            /* $merchant_sql = $pdo->query("SELECT `begin_time`,`end_time` FROM `api_merchant` WHERE `id` = '{$order['merchant_id']}'");
             $merchant_time = $merchant_sql->fetch(PDO::FETCH_ASSOC);
             if (count($merchant_time) <= 0) {
                 $pdo->rollBack();
                 return self::response(self::INVALID_REQUEST, '延迟处理失败');
             }*/

            //开启队列传输
            $Pheanstalk = new Pheanstalk\Pheanstalk($GLOBALS['CONFIG']['BEANS_OPTIONS']['HOST']);
            $tube_name = $GLOBALS['CONFIG']['BEANS_OPTIONS']['TUBE_NAME'][0];
            $delayed_data = [
                'version' => 'v1.1',
                'order_id' => $bar_id,
                'order_no' => $bar_no,
                'buy_type' => 4,    //1普通下单 2普通续酒 3拼吧下单 4拼吧续酒
                'exc_type' => 1,    //执行类型 1订单取消 2订单作废 3订单逾期
            ];

            //计算时间
            $cancel_time = $GLOBALS['CONFIG']['ORDER_OVERTIME'];

            //执行
            $Pheanstalk->putInTube($tube_name, json_encode($delayed_data), 0, $cancel_time);

            //所有参与用户
            $all_sql = $pdo->query("SELECT `id`,`pay_no` FROM `api_bar_member` WHERE `bar_id`= '{$bar_id}' ");
            while ($all = $all_sql->fetch(PDO::FETCH_ASSOC)) {
                //执行用户支付订单超时队列
                $delay_data = [
                    'version' => 'v1.1',
                    'order_id' => $all['id'],
                    'order_no' => $all['pay_no'],
                    'buy_type' => 4,    //1普通下单 2普通续酒 3拼吧下单 4拼吧续酒
                    'exc_type' => 4,    //执行类型 1订单取消 2订单作废 3订单逾期,4 拼吧用户支付订单超时
                ];
                $Pheanstalk->putInTube($tube_name, json_encode($delay_data), 0, $cancel_time);
            }

            //提交订单事务
            $pdo->commit();

            //创建订单成功
            return self::response(self::SUCCESS, '下单成功',
                [
                    'order_id' => $current_pay_info['id'],
                    'order_no' => $current_pay_info['pay_no'],
                    'buy_type' => 4,
                    'pay_money' => $current_pay_info['pay_price'],
                ]);

        } catch (PDOException $e) {
            file_put_contents('./log/' . date('Y-m-d') . 'log', date('Y-m-d H:i:s') . '||' . $e->getMessage() . "\n", FILE_APPEND);
            return self::response(self::INVALID_REQUEST, '请求失败');
        }

    }

    /**
     * 商户端创建线下订座订单 v1.0
     * @param $data array 订单数据
     * @return array|object|string
     */
    public static function buidOfflineSeatOrder($data)
    {
        try {
            //建立PDO数据库连接
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_DBNAME, DB_USERNAME, DB_PASSWORD);
            $pdo->query('set names utf8;');
        } catch (PDOException $e) {
            return self::response(self::DB_CONNECT_FAIL, '数据库连接失败');
        }

        $order = $data['order'];
        //判断卡座在当前日期是否已被预定
        $lock_seat = $pdo->query("select `id` from `api_seat_lock` WHERE `merchant_id` = '{$order['merchant_id']}' AND `arrives_time` = '{$order['arrives_time']}' AND `goods_seat_id` = '{$order['goods_seat_id']}' limit 1");
        $lock_seat = $lock_seat->fetch(PDO::FETCH_ASSOC);
        if ($lock_seat['id']) {
            return self::response(self::NOT_STOCK, '该卡座已被预定');
        }

        //锁定卡座当日库存
        $seat_lock = $pdo->prepare("INSERT INTO `api_seat_lock` (`seat_number`, `order_no`, `arrives_time`, `floor`, `goods_seat_id`, `merchant_id`) VALUES(:seat_number, :order_no, :arrives_time, :floor, :goods_seat_id, :merchant_id)");
        $seat_lock = $seat_lock->execute([
            ':seat_number' => $order['seat_number'],
            ':order_no' => isset($order['order_no']) ? $order['order_no'] : '',
            ':arrives_time' => $order['arrives_time'],
            ':floor' => $order['floor'],
            ':goods_seat_id' => $order['goods_seat_id'],
            ':merchant_id' => $order['merchant_id']
        ]);

        //执行语句结果
        if (!$seat_lock) {
            return self::response(self::DB_SAVE_ERROR, '锁定卡座失败');
        }

        //锁座成功
        return self::response(self::SUCCESS, '下单成功');
    }
}

//$data = '{"buy_type":2,"order":{"merchant_id":"1","member_id":"2","contacts_realname":"\u98ce\u884c","contacts_tel":"15883700780","contacts_sex":"1","total_price":"400.00","pay_price":"400.00","purchase_price":"300.00","discount_money":0,"status":1,"settlement_status":0,"order_type":3,"arrives_time":"1513958400","employee_id":"3","employee_realname":"\u8881\u53ef\u7acb","employee_avatar":"\/employee_20171220_vdo1513761350iwxr337 . jpg","employee_tel":"15184454658","description":"","top_order_id":"1"},"goods":{"goods_pack_id":"1","merchant_id":"1","member_id":"2","title":"\u7cbe\u9009\u6d0b\u9152\u5957\u9910","amount":1,"price":"400.00","image":"\/goods_2017_fwr1510542154wnrp505 . jpg","pack_description":"\u7cbe\u9009\u6d0b\u9152\u9650\u91cf\u5957\u9910,\u5148\u5230\u5148\u5f97"}}';
//$data = json_decode($data, true);
//echo createOrder::buidRenewPackOrder($data);

//$data = '{"buy_type":1,"order":{"merchant_id":"1","member_id":"15","contacts_realname":"\u8ba2\u5355","contacts_tel":"15184545854","contacts_sex":"1","total_price":"400.00","pay_price":"400.00","purchase_price":"300.00","discount_money":0,"status":1,"settlement_status":0,"order_type":"3","description":"","arrives_time":1514995200},"goods":{"goods_pack_id":"1","title":"\u7cbe\u9009\u6d0b\u9152\u5957\u9910","amount":1,"price":"400.00","image":"\/goods_2017_fwr1510542154wnrp505 . jpg","merchant_id":"1","member_id":"15","pack_description":"\u7cbe\u9009\u6d0b\u9152\u9650\u91cf\u5957\u9910,\u5148\u5230\u5148\u5f97"}}';
//$data = json_decode($data, true);
//echo createOrder::buidPackOrder($data);
//$data = '{"version":"v1.1","buy_type":1,"order":{"merchant_id":"1","member_id":"2","contacts_realname":"\u4e50\u4e50","contacts_tel":"13730686533","contacts_sex":"1","total_price":2530.1,"pay_price":1130.01,"purchase_price":1130.01,"discount_money":1400.09,"status":1,"settlement_status":0,"order_type":3,"description":"","arrives_time":1538323200,"card_id":0},"goods":{"pack_goods":[{"id":"17","amount":"1"}],"single_goods":{"23":"4","24":"2","25":"6"}}}';

/*$data = '{"version":"v1.1","buy_type":1,"order":{"merchant_id":"1","member_id":"19","contacts_realname":"\u848b\u4ee4","contacts_tel":"18780087200","contacts_sex":"1","total_price":2460,"pay_price":1700,"purchase_price":2120,"discount_money":760,"status":1,"settlement_status":0,"order_type":0,"description":"\u53ea\u6709\u6211\u5b88\u7740\u5b89\u9759\u7684\u6c99\u6f20\uff0c\u7b49\u5f85\u7740\u82b1\u5f00\uff0c\u54e6\u3002\u3002\u3002\u3002\u3002","arrives_time":1524672000,"card_id":0,"is_xu":0,"is_bar":0,"obegin_time":1524738720,"oend_time":1524757680},"goods":{"pack_goods":[],"single_goods":{"7":"1","8":"2","9":"1"}}}';
$data = json_decode($data, true);
echo queuev2::buildGoodsOrder($data);*/

//续酒数据测试
/*$data = '{"version":"v1.1","buy_type":2,"order":{"merchant_id":"1","member_id":"1","contacts_realname":"\u6606\u5361","contacts_tel":"15883700780","contacts_sex":"1","total_price":9500,"pay_price":5800.01,"purchase_price":4800,"discount_money":3699.99,"status":1,"settlement_status":0,"order_type":0,"arrives_time":"1517414400","employee_id":"23","employee_realname":"\u8881\u5eb7","employee_avatar":"\/employee_20180129_aps1517210288puqa098.jpg","employee_tel":"15184454658","description":"","desk_number":"","top_order_id":"16","card_id":"0","is_xu":1,"is_bar":0},"goods":{"1":"10","2":"7","3":"10","4":"3","24":"5"}}';
$data = json_decode($data, true);
echo createOrder::buildMultiRenewOrder($data);*/


//拼吧正常下单数据测试
//$data = '{"version":"v1.1","buy_type":1,"order":{"merchant_id":"1","member_id":"11","realname":"","tel":"15184454658","sex":"2","avatar":"\/member_20180206104756_cde1517885276yjio580.JPG","age":27,"bar_type":"1","total_price":500,"purchase_price":0.0003,"pay_price":0.01,"bar_theme":"1","arrives_time":"1520179200","cost_type":"1","man_number":"10","woman_number":"5","average_cost":"200.00","description":"\u6d4b\u8bd5\u9636\u6bb5\u7684\u6570\u636e\u53d1\u73b0\u662f\u5426\u4e00\u573a","is_bar":1,"order_type":0},"goods":{"pack_goods":[{"id":"1","amount":"1"}],"single_goods":[]}}';
//$data = json_decode($data, true);
//echo createOrder::buidBarOrder($data);

//拼吧续酒
//$data = '{"version":"v1.1","buy_type":4,"order":{"bar_type":1,"bar_theme":0,"cost_type":0,"man_number":0,"woman_number":0,"average_cost":"0.01","merchant_id":"1","member_id":"58","realname":"\u963f\u540c\u840c\u5fb7","tel":"17381909915","sex":"2","total_price":0.01,"pay_price":"0.01","purchase_price":0.01,"bar_status":1,"order_type":0,"arrives_time":"1522684800","description":"","top_bar_id":"13","is_xu":1,"is_bar":1},"goods":{"1":"1"},"member":[{"member_id":"14","nickname":"\u51cc\u98ce","sex":"1","tel":"18780087200","avatar":"https:\/\/wx.qlogo.cn\/mmopen\/vi_32\/oA8yhGYuVMC9jmPbaAriaibwvcPh09IX06rkPNKuQAynkhQApZQZmwa2fSosMpOcfreeO1sZJUtN5Mss1pU7sD5w\/0","age":17},{"member_id":"49","nickname":"vanillasky","sex":"1","tel":"15883700780","avatar":"https:\/\/wx.qlogo.cn\/mmopen\/vi_32\/Q0j4TwGTfTLfOZiaOCKuckCJFOwFDEDsgHPkYQr3NJAu9icdDWDe08umBJ17dAwK46Lbib6mCKnWjhR1V36u1APfA\/0","age":48}],"current_member":"49"}';
//$data = json_decode($data, true);
//echo queuev2::buidRenewBarOrder($data);

/*$data = '{"order":{"version":"v1.1","seat_number":"V01","arrives_time":1521734400,"floor":"1","order_no":"3521785614616957","goods_seat_id":"1","merchant_id":"1","order_type":10}}';
$data = json_decode($data, true);
echo queuev2::buidOfflineSeatOrder($data);*/

//正常续酒
/*$data = '{ "version": "v1.1", "buy_type": 2, "order": { "merchant_id": "1", "member_id": "18", "contacts_realname": "\u4e50\u4e50", "contacts_tel": "13730686533", "contacts_sex": "1", "total_price": 599, "pay_price": 399, "purchase_price": 499, "discount_money": 200, "status": 1, "settlement_status": 0, "order_type": "3", "arrives_time": "1524844800", "employee_id": 0, "employee_realname": "", "employee_avatar": "", "employee_tel": 0, "description": "", "desk_number": "", "top_order_id": "145", "card_id": "", "is_xu": 1, "is_bar": 0 }, "goods": { "2": "1", "1": "4", "3": "3", "4": "2" } }';
$data = json_decode($data, true);
echo queuev2::buildMultiRenewOrder($data);*/


