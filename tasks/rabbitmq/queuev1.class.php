<?php

/**
 * FileName: createOrder.class.php
 * User: Comos
 * Date: 2017/10/25 16:56
 */
require_once 'base.class.php';

class queuev1 extends base
{
    /**
     * 创建卡座订单 v1.0
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
            return self::response(self::DB_CONNECT_FAIL, '数据库连接失败');
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
                $order_add = $pdo->prepare("INSERT INTO `api_order` (`order_no`, `merchant_id`, `member_id`, `contacts_realname`, `contacts_tel`, `contacts_sex`, `total_price`, `pay_price`, `purchase_price`, `discount_money`, `status`, `settlement_status`, `order_type`, `description`, `arrives_time`, `employee_id`, `employee_realname`, `employee_tel`, `employee_avatar`, `created_time`, `updated_time`, `relation_order_no`, `relation_order_id`) VALUES (:order_no, :merchant_id, :member_id, :contacts_realname, :contacts_tel, :contacts_sex, :total_price, :pay_price, :purchase_price, :discount_money, :status, :settlement_status, :order_type, :description, :arrives_time, :employee_id, :employee_realname, :employee_tel, :employee_avatar, :created_time, :updated_time, :relation_order_no, :relation_order_id)");
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
                    ':relation_order_id' => $order['relation_order_id']
                ];
                $order_add = $order_add->execute($order_main_data);

            } else {
                //写入订单主表SQL 无客户经理
                $order_add = $pdo->prepare("INSERT INTO `api_order` (`order_no`, `merchant_id`, `member_id`, `contacts_realname`, `contacts_tel`, `contacts_sex`, `total_price`, `pay_price`, `purchase_price`, `discount_money`, `status`, `settlement_status`, `order_type`, `description`, `arrives_time`, `created_time`, `updated_time`, `relation_order_no`, `relation_order_id`) VALUES (:order_no, :merchant_id, :member_id, :contacts_realname, :contacts_tel, :contacts_sex, :total_price, :pay_price, :purchase_price, :discount_money, :status, :settlement_status, :order_type, :description, :arrives_time, :created_time, :updated_time, :relation_order_no, :relation_order_id)");

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
                    ':relation_order_id' => $order['relation_order_id']
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

            //判断逾期卡是否可扣
            if ($data['used_card'] > 0) {
                //修改用户已使用逾期卡数量
                $seat_lock = $pdo->exec("UPDATE `api_member` SET `used_card` = {$data['used_card']} WHERE `id` = {$order['member_id']}");
                //执行语句结果
                if ($seat_lock === false) {
                    $pdo->rollBack();
                    return self::response(self::DB_SAVE_ERROR, '逾期卡变更失败');
                }
            }

            //提交事务,完成订单创建
            $pdo->commit();
            return self::response(self::SUCCESS, '下单成功',
                [
                    'order_id' => $last_order_id,
                    'order_no' => $order_no,
                    'created_time' => $created_time,
                    'now_time' => $created_time
                ]);

        } catch (PDOException $e) {
            file_put_contents('./log/' . date('Y-m-d') . 'log', date('Y-m-d H:i:s') . '||' . $e->getMessage() . "\n", FILE_APPEND);
            return self::response(self::INVALID_REQUEST, '请求失败');
        }
    }

    /**
     * 创建套餐订单 卡套|散套 v1.0
     * @param $data array 订单数据
     * @return array|object|string
     */
    public static function buidPackOrder($data)
    {
        try {
            //建立PDO数据库连接
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_DBNAME, DB_USERNAME, DB_PASSWORD);
            $pdo->query('set names utf8;');
        } catch (PDOException $e) {
            return self::response(self::DB_CONNECT_FAIL, '数据库连接失败');
        }

        $order_no = self::_createOrderNumber();     //获取订单号
        $goods = $data['goods'];
        $order = $data['order'];

        try {
            //订单创建时间与最后修改时间
            $created_time = $updated_time = time();

            //开启事务
            $pdo->beginTransaction();

            $date_int = (int)date('Ymd', $order['arrives_time']);

            //order_type 订单类型 2卡套订单 3散套订单
            //pack_type 商品类型 1散客套餐 2卡座套餐
            if ($order['order_type'] == 2) $pack_type = 2;
            if ($order['order_type'] == 3) $pack_type = 1;

            //修改时间 2017年12月26日10:56:19
            //验证是否满足下单条件
            $arrives_time = strtotime(date('Y-m-d', $order['arrives_time']));
            if ($order['order_type'] == 2) {

                //卡套订单查询验证
                $today_stock = $pdo->query("SELECT COUNT(`id`) as total FROM `api_order` WHERE `arrives_time` = '{$arrives_time}' AND `member_id` = {$order['member_id']} AND `merchant_id` = '{$order['merchant_id']}' AND `order_type` = 2 AND `status` IN (1, 2, 3, 7) limit 1");
                $stock = $today_stock->fetch(PDO::FETCH_ASSOC);
                if ($stock['total'] > 0) return self::response(self::INVALID_REQUEST, '1000-您有未完成卡套订单,请先完成后再购买新套餐');

            } elseif ($order['order_type'] == 3) {

                //散套订单查询验证
                $today_stock = $pdo->query("SELECT COUNT(`id`) as total FROM `api_order` WHERE `arrives_time` = '{$arrives_time}' AND `member_id` = {$order['member_id']} AND `merchant_id` = '{$order['merchant_id']}' AND `order_type` = 3 AND `status` IN (1, 2, 7) limit 1");
                $stock = $today_stock->fetch(PDO::FETCH_ASSOC);
                if ($stock['total'] > 0) return self::response(self::INVALID_REQUEST, '1000-您有未完成订单,请先完成后再购买新套餐');

            }

            //扣减套餐库存
            //查询当日库存记录是否存在
            $stock_count = $pdo->query("SELECT count(`id`) AS stock_count FROM `api_goods_pack_stock` WHERE `merchant_id` = '{$order['merchant_id']}' AND `type` = '$pack_type' AND `date` = '$date_int' limit 1");
            $stock_result = $stock_count->fetch(PDO::FETCH_ASSOC);
            if ($stock_result === false) {
                return self::response(self::DB_READ_ERROR, '查询库存数据失败');
            }

            //判断库存数量是否存在,不存在就新增一条,存在就修改一条
            if ($stock_result['stock_count']) {
                //查询当日库存
                $today_stock = $pdo->query("SELECT `pack_stock` FROM `api_goods_pack_stock` WHERE `merchant_id` = '{$order['merchant_id']}' AND `type` = '$pack_type' AND `date` = '$date_int' limit 1");
                $stock = $today_stock->fetch(PDO::FETCH_ASSOC);
                //判断当日库存是否大于0
                if ($stock['pack_stock'] > 0) {
                    //修改库存记录
                    $pack_add = $pdo->exec("UPDATE `api_goods_pack_stock` SET `pack_stock` = `pack_stock` - 1 WHERE `merchant_id` = '{$order['merchant_id']}' AND `type` = '$pack_type' AND `date` = '$date_int'");
                    if (!$pack_add) {
                        $pdo->rollBack();
                        return self::response(self::DB_SAVE_ERROR, '扣减库存失败');
                    }
                } else {
                    $pdo->rollBack();
                    return self::response(self::INVALID_REQUEST, '该套餐已售罄');
                }
            } else {
                //查询商户默认库存数量
                //散座库存
                if ($pack_type == 1) {
                    $merchant_stock = $pdo->query("SELECT `sanpack_stock`,`san_wine_stock` FROM `api_merchant` WHERE `id` = '{$order['merchant_id']}' limit 1");
                    $merchant_stock = $merchant_stock->fetch(PDO::FETCH_ASSOC);
                    if (!$merchant_stock) {
                        return self::response(self::DB_SAVE_ERROR, '获取散座套餐库存失败');
                    }
                    $pack_stock = $merchant_stock['sanpack_stock'];
                    $wine_stock = $merchant_stock['san_wine_stock'];
                }

                //2卡座套餐
                if ($pack_type == 2) {
                    $merchant_stock = $pdo->query("SELECT `kapack_stock` FROM `api_merchant` WHERE `id` = '{$order['merchant_id']}' limit 1");
                    $merchant_stock = $merchant_stock->fetch(PDO::FETCH_ASSOC);
                    if (!$merchant_stock) {
                        return self::response(self::DB_SAVE_ERROR, '获取卡座套餐库存失败');
                    }
                    $pack_stock = $merchant_stock['kapack_stock'];
                    //TODO::此处库存未实现对应功能,默认设置为0
                    $wine_stock = 0;
                }

                //如果库存为0,返回无库存
                if ($pack_stock <= 0) {
                    return self::response(self::NOT_STOCK, '商品已售馨');
                }

                //写入当日库存记录
                $pack_add = $pdo->prepare("INSERT INTO `api_goods_pack_stock` (`merchant_id`, `pack_stock`, `date`, `type`,`wine_stock`) VALUES (:merchant_id, :pack_stock, :date, :type, :wine_stock)");
                $pack_set_res = $pack_add->execute([
                    ':merchant_id' => $order['merchant_id'],
                    ':pack_stock' => $pack_stock - 1,
                    ':date' => $date_int,
                    ':type' => $pack_type,
                    ':wine_stock' => $wine_stock
                ]);
                //执行语句
                if (!$pack_set_res) {
                    $pdo->rollBack();
                    return self::response(self::DB_SAVE_ERROR, '扣除库存失败');
                }
            }

            //预处理SQL
            $order_add = $pdo->prepare("INSERT INTO `api_order` (`order_no`, `merchant_id`, `member_id`, `contacts_realname`, `contacts_tel`, `contacts_sex`, `total_price`, `pay_price`, `purchase_price`, `discount_money`, `status`, `settlement_status`, `order_type`, `description`, `arrives_time`, `created_time`, `updated_time`) VALUES (:order_no, :merchant_id, :member_id, :contacts_realname, :contacts_tel, :contacts_sex, :total_price, :pay_price, :purchase_price, :discount_money, :status, :settlement_status, :order_type, :description, :arrives_time, :created_time, :updated_time)");
            //绑定参数
            $order_add = $order_add->execute([
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
                ':updated_time' => $updated_time
            ]);

            //执行语句结果
            if (!$order_add) {
                $pdo->rollBack();
                return self::response(self::DB_SAVE_ERROR, '订单创建失败');
            }

            //获取订单ID
            $last_order_id = $pdo->lastInsertId();

            //订单商品表数据添加
            $pack_add = $pdo->prepare("INSERT INTO `api_order_pack` (`order_no`, `goods_pack_id`, `title`, `amount`, `price`, `image`, `merchant_id`, `member_id`, `pack_description`,`order_id`) VALUES (:order_no, :goods_pack_id, :title, :amount, :price, :image, :merchant_id, :member_id, :pack_description, :order_id)");
            $pack_add = $pack_add->execute([
                ':order_no' => $order_no,
                ':goods_pack_id' => $goods['goods_pack_id'],
                ':title' => $goods['title'],
                ':amount' => $goods['amount'],
                ':price' => $goods['price'],
                ':image' => $goods['image'],
                ':merchant_id' => $goods['merchant_id'],
                ':member_id' => $goods['member_id'],
                ':pack_description' => $goods['pack_description'],
                ':order_id' => $last_order_id,
            ]);

            //执行语句结果
            if (!$pack_add) {
                $pdo->rollBack();
                return self::response(self::DB_SAVE_ERROR, '订单商品数据存储失败');
            }

            //提交订单事务
            $pdo->commit();
            //创建订单成功
            return self::response(self::SUCCESS, '下单成功',
                [
                    'order_id' => $last_order_id,
                    'order_no' => $order_no,
                    'created_time' => $created_time,
                    'now_time' => $created_time
                ]);

        } catch (PDOException $e) {
            file_put_contents('./log/' . date('Y-m-d') . 'log', date('Y-m-d H:i:s') . '||' . $e->getMessage() . "\n", FILE_APPEND);
            return self::response(self::INVALID_REQUEST, '请求失败');
        }
    }

    /**
     * 创建线下订座订单 v1.0
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
        $seat_lock = $pdo->prepare("INSERT INTO `api_seat_lock` (`seat_number`, `order_no`, `arrives_time`, `floor`, `goods_seat_id`, `merchant_id`) VALUES (:seat_number, :order_no, :arrives_time, :floor, :goods_seat_id, :merchant_id)");
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

    /**
     * 续酒下单操作 v1.0
     * @param $data array 续酒订单数据
     * @return array|object|string
     */
    public static function buidRenewPackOrder($data)
    {
        try {
            //建立PDO数据库连接
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_DBNAME, DB_USERNAME, DB_PASSWORD);
            $pdo->query('set names utf8;');
        } catch (PDOException $e) {
            return self::response(self::DB_CONNECT_FAIL, '数据库连接失败');
        }

        $goods = $data['goods'];
        $order = $data['order'];

        try {
            /**
             * setp.0 检查是否存在未支付订单
             */
            $today_stock = $pdo->query("SELECT COUNT(`id`) as total FROM `api_order` WHERE `merchant_id` = '{$order['merchant_id']}' AND `member_id` = {$order['member_id']} AND `status` IN (1,7) AND `top_order_id` <> 0 limit 1");
            $stock = $today_stock->fetch(PDO::FETCH_ASSOC);
            if ($stock['total'] > 0) {
                return self::response(self::INVALID_REQUEST, '1000-您有未支付的订单,请先完成购买之后再购买新套餐');
            }

            //订单创建时间与最后修改时间
            $created_time = $updated_time = time();
            $order_no = self::create_order_number();

            //扣减套餐库存
            $date_int = (int)date('Ymd', $created_time);

            //order_type 订单类型 2卡套订单 3散套订单
            //pack_type 商品类型 1散客套餐 2卡座套餐
            if ($order['order_type'] == 2) $pack_type = 2;
            if ($order['order_type'] == 3) $pack_type = 1;

            /**
             * setp.1 检查商品库存是否充足
             */
            //查询当日库存是否存在
            $count_stock = $pdo->query("SELECT COUNT(`id`) AS stock_total FROM `api_goods_pack_stock` WHERE `merchant_id` = '{$order['merchant_id']}' AND `type` = '$pack_type' AND `date` = '$date_int' limit 1");
            $stock_total = $count_stock->fetch(PDO::FETCH_ASSOC);
            if ($stock_total === false) {
                $pdo->rollBack();
                return self::response(self::DB_READ_ERROR, '10012-获取库存出错');
            }

            //开启事务
            $pdo->beginTransaction();

            //判断库存数量是否存在,不存在就新增一条,存在就修改一条
            if ($stock_total['stock_total']) {
                //查询当日库存
                $today_stock = $pdo->query("SELECT `wine_stock` FROM `api_goods_pack_stock` WHERE `merchant_id` = '{$order['merchant_id']}' AND `type` = '$pack_type' AND `date` = '$date_int' limit 1");
                $stock = $today_stock->fetch(PDO::FETCH_ASSOC);
                if ($stock['wine_stock'] > 0) {
                    //修改库存记录
                    $pack_add = $pdo->exec("UPDATE `api_goods_pack_stock` SET `wine_stock` = `wine_stock` - 1 WHERE `merchant_id` = '{$order['merchant_id']}' AND `type` = '$pack_type' AND `date` = '$date_int'");
                    if (!$pack_add) {
                        $pdo->rollBack();
                        return self::response(self::DB_SAVE_ERROR, '1001-扣减库存失败');
                    }
                } else {
                    $pdo->rollBack();
                    return self::response(self::DB_SAVE_ERROR, '10011-商品已售馨');
                }

            } else {
                //查询商户默认库存数量
                //散座库存
                if ($pack_type == 1) {
                    $merchant_stock = $pdo->query("SELECT `san_wine_stock`,`sanpack_stock` FROM `api_merchant` WHERE `id` = '{$order['merchant_id']}' limit 1");
                    $merchant_stock = $merchant_stock->fetch(PDO::FETCH_ASSOC);
                    if (!$merchant_stock) {
                        return self::response(self::DB_SAVE_ERROR, '1002-获取散座套餐库存失败');
                    }
                }

                //2卡座套餐
                /*if ($pack_type == 2) {
                    $merchant_stock = $pdo->query("SELECT `kapack_stock` FROM `api_merchant` WHERE `id` = '{$order['merchant_id']}' limit 1");
                    $merchant_stock = $merchant_stock->fetch(PDO::FETCH_ASSOC);
                    if (!$merchant_stock) {
                        return self::response(self::DB_SAVE_ERROR, '获取卡座套餐库存失败');
                    }
                    $pack_stock = $merchant_stock['kapack_stock'];
                }*/

                //如果库存为0,返回无库存
                if ($merchant_stock['san_wine_stock'] <= 0) {
                    return self::response(self::NOT_STOCK, '1003-商品已售馨');
                }

                //写入当日库存记录
                $pack_add = $pdo->prepare("INSERT INTO `api_goods_pack_stock` (`merchant_id`, `wine_stock`, `date`, `type`, `pack_stock`) VALUES (:merchant_id, :wine_stock, :date, :type, :pack_stock)");
                $pack_add->execute([
                    ':merchant_id' => $order['merchant_id'],
                    ':wine_stock' => $merchant_stock['san_wine_stock'] - 1,
                    ':pack_stock' => $merchant_stock['sanpack_stock'],
                    ':date' => $date_int,
                    ':type' => $pack_type
                ]);

                //执行语句
                if (!$pack_add) {
                    $pdo->rollBack();
                    return self::response(self::DB_SAVE_ERROR, '1004-扣除库存失败');
                }
            }

            /**
             * step.2 写入续酒主订单信息
             */
            //预处理SQL
            $order_add = $pdo->prepare("INSERT INTO `api_order` (`order_no`, `merchant_id`, `member_id`, `contacts_realname`, `contacts_tel`, `contacts_sex`, `total_price`, `pay_price`, `purchase_price`, `discount_money`, `status`, `settlement_status`, `order_type`, `description`,`arrives_time`,`created_time`, `employee_id`,`employee_realname`,`employee_avatar`,`employee_tel`,`updated_time`,`desk_number`,`top_order_id`) VALUES (:order_no, :merchant_id, :member_id, :contacts_realname, :contacts_tel, :contacts_sex, :total_price, :pay_price, :purchase_price, :discount_money, :status, :settlement_status, :order_type, :description, :arrives_time, :created_time, :employee_id, :employee_realname, :employee_avatar, :employee_tel, :updated_time,:desk_number, :top_order_id)");
            //绑定参数
            $order_add = $order_add->execute([
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
                ':employee_id' => $order['employee_id'],
                ':employee_realname' => $order['employee_realname'],
                ':employee_avatar' => $order['employee_avatar'],
                ':employee_tel' => $order['employee_tel'],
                ':updated_time' => $updated_time,
                ':desk_number' => $order['desk_number'],
                ':top_order_id' => $order['top_order_id'],
            ]);

            //执行语句结果
            if (!$order_add) {
                $pdo->rollBack();
                return self::response(self::DB_SAVE_ERROR, '1005-订单创建失败');
            }

            //获取订单ID
            $wine_order_id = $pdo->lastInsertId();

            /**
             * step.3 写入续酒订单附表数据
             */
            //订单商品表数据添加
            $pack_add = $pdo->prepare("INSERT INTO `api_order_pack` (`order_no`, `goods_pack_id`, `title`, `amount`, `price`, `image`, `merchant_id`, `member_id`, `pack_description`, `order_id`) VALUES (:order_no, :goods_pack_id, :title, :amount, :price, :image, :merchant_id, :member_id, :pack_description, :order_id)");
            $pack_add = $pack_add->execute([
                ':order_no' => $order_no,
                ':goods_pack_id' => $goods['goods_pack_id'],
                ':title' => $goods['title'],
                ':amount' => $goods['amount'],
                ':price' => $goods['price'],
                ':image' => $goods['image'],
                ':merchant_id' => $goods['merchant_id'],
                ':member_id' => $goods['member_id'],
                ':pack_description' => $goods['pack_description'],
                ':order_id' => $wine_order_id,
            ]);

            //执行语句结果
            if (!$pack_add) {
                $pdo->rollBack();
                return self::response(self::DB_SAVE_ERROR, '1006-续酒商品数据添加失败');
            }

            $pdo->commit();

            /**
             * step.4 创建订单完成, 返回订单数据
             */
            //创建订单成功
            return self::response(self::SUCCESS, '下单成功', [
                'order_id' => $wine_order_id,
                'order_no' => $order_no,
                'created_time' => $created_time,
                'now_time' => $created_time
            ]);

        } catch (PDOException $e) {
            file_put_contents('./log/' . date('Y-m-d') . 'log', date('Y-m-d H:i:s') . '||' . $e->getMessage() . "\n", FILE_APPEND);
            return self::response(self::INVALID_REQUEST, '1007-创建订单失败');
        }
    }
}