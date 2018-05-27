<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php echo C('APP_NAME');?>管理后台</title>
    <script src="/Public/js/jquery.min.js"></script>
    <link rel="stylesheet" href="/Public/ui/css/layui.css">
    <script type="text/javascript" src="/Public/ui/layui.js"></script>
    
</head>
<body>
<div style="margin: 15px;">
    
<style type="text/css">
.tabs-panels{}
.misc-info,.addr-note{padding-bottom:10px;margin-bottom:10px;border-bottom:solid 1px #E6E6E6}
.tabs-panels h3{font-weight:700; line-height: 24px; }
.ncap-order-details .tabs-panels dl{font-size:0;padding-bottom:5px}
.tabs-panels dd,.tabs-panels dt{font-size:13px;line-height:24px;vertical-align:top;display:inline-block}
.tabs-panels dt{color:#999;width:10%;text-align:right}
.tabs-panels dd{color:#333;width:22%}
.tabs-panels table{border:solid 1px #D7D7D7;width:100%;border-collapse:collapse}
.tabs-panels table td,.tabs-panels table th{text-align:center;min-height:20px;padding:9px}
.tabs-panels table th{font-weight:normal;background-color:#edfbf8;border-bottom:solid 1px #D7D7D7}
.tabs-panels table td{border-bottom:solid 1px #D7D7D7}
.tabs-panels h4{ font-size: 14px; line-height: 24px; font-weight: 600; color: #333; margin-bottom: 8px; }
.tabs-panels .total-amount { text-align: right; padding: 10px 0; }
.tabs-panels .total-amount h3 { font-size: 14px; font-weight: normal; color: #777; line-height: 24px; }
</style>
<div class="tabs-panels">
    <div class="misc-info">
        <h3>基本信息</h3>
        <dl>
            <dt>订单ID：</dt>
            <dd><?php echo ($order["id"]); ?></dd>
            <dt>订单号：</dt>
            <dd><?php echo ($order["order_no"]); ?></dd>
            <dt>下单时间：</dt>
            <dd><?php echo (date("Y-m-d H:i:s",$order['created_time'])); ?></dd>
        </dl>
        <dl>
            <dt>所属商户：</dt>
            <dd><?php echo ($merchant["title"]); ?></dd>
            <dt>支付方式：</dt>
            <dd><?php echo ($payment[$order['payment']]); ?></dd>
            <dt>订单状态：</dt>
            <dd><?php echo ($order_status[$order['status']]); ?></dd>
        </dl>
        <dl>
            <dt>所属用户：</dt>
            <dd><?php echo ($member["nickname"]); ?></dd>
            <dt>所属用户ID：</dt>
            <dd><?php echo ($member["id"]); ?></dd>
            <dt>结算状态：</dt>
            <dd><?php echo ($settlement_status[$order['settlement_status']]); ?></dd>
        </dl>
        <dl>
            <dt>订单类型：</dt>
            <dd><?php echo ($order_type[$order['order_type']]); ?></dd>
            <dt>到店日期：</dt>
            <dd><?php echo (date("Y-m-d",$order['arrives_time'])); ?></dd>
            <dt>订单延时：</dt>
            <dd><?php echo ($order['incr_time']); ?> 分钟</dd>
        </dl>
        <dl>
            <dt>接单时间：</dt>
            <dd><?php echo $order['take_time'] ? date('Y-m-d H:i:s', $order['take_time']) : '';?></dd>
            <dt>是否已评价：</dt>
            <dd><?php echo $order['is_evaluate'] ? '已评价' : '未评价';?></dd>
            <?php if($order['status'] == 6): ?><dt>拒绝理由：</dt>
                <dd><?php echo ($order['cancel_reason']); ?></dd><?php endif; ?>
        </dl>
    </div>

    <?php if($order['employee_id'] != 0): ?><div class="addr-note">
            <h4>员工信息</h4>
            <?php if(!empty($order['employee_avatar'])): ?><dl>
                    <dt>员工头像：</dt>
                    <dd><img src="<?php echo ($attachment_url); echo ($order['employee_avatar']); ?>" width="50" height="50" /> </dd>
                </dl><?php endif; ?>
            <dl>
                <dt>员工ID：</dt>
                <dd><?php echo ($order['employee_id']); ?></dd>
                <dt>员工姓名：</dt>
                <dd><?php echo ($order['employee_realname']); ?></dd>
                <dt>员工电话：</dt>
                <dd><?php echo ($order['employee_tel']); ?></dd>
            </dl>
        </div><?php endif; ?>

    <div class="addr-note">
        <h4>客户信息</h4>
        <dl>
            <dt>客户姓名：</dt>
            <dd><?php echo ($order['contacts_realname']); ?></dd>
            <dt>客户电话：</dt>
            <dd><?php echo ($order['contacts_tel']); ?></dd>
            <dt>客户性别：</dt>
            <dd><?php echo ($sex[$order['contacts_sex']]); ?></dd>
        </dl>
        <dl>
            <dt>订单备注：</dt>
            <dd><?php echo ($order['description']); ?></dd>
        </dl>
    </div>

    <div class="goods-info">
        <h4>商品信息</h4>
        <table>
            <?php if($order['order_type'] == 1): ?><tr>
                    <th>卡座ID</th>
                    <th>卡座名称</th>
                    <th>预约到店人数</th>
                    <th>容纳人数</th>
                    <th>预定金额</th>
                    <th>最低消费金额</th>
                </tr>
                <tr>
                    <td><?php echo ($seat['goods_seat_id']); ?></td>
                    <td><?php echo ($seat['seat_number']); ?></td>
                    <td><?php echo ($seat['total_people']); ?></td>
                    <td>1-<?php echo ($seat['max_people']); ?></td>
                    <td>￥<?php echo ($seat['set_price']); ?></td>
                    <td>￥<?php echo ($seat['floor_price']); ?></td>
                </tr>
            <?php else: ?>
                <tr>
                    <th>商品ID</th>
                    <th>商品图片</th>
                    <th>商品名称</th>
                    <th>购买数量</th>
                    <th>销售价格</th>
                    <th>结算价格</th>
                </tr>
                <?php if(is_array($pack)): $i = 0; $__LIST__ = $pack;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$goods): $mod = ($i % 2 );++$i;?><tr>
                    <td><?php echo ($goods['goods_pack_id']); ?></td>
                    <td><img width="50" height="50" src="<?php echo ($attachment_url); echo ($goods['image']); ?>"></td>
                    <td><?php echo ($goods['title']); ?></td>
                    <td><?php echo ($goods['amount']); ?></td>
                    <td>￥<?php echo ($goods['price']); ?></td>
                    <td>￥<?php echo ($goods['purchase_price']); ?></td>
                </tr><?php endforeach; endif; else: echo "" ;endif; endif; ?>
        </table>

        <?php if(isset($relation_order)): ?><br />
            <table>
                <tr>
                    <th>逾期订单ID</th>
                    <th>逾期订单编号</th>
                    <th>逾期订单结算价格</th>
                    <th>逾期订单实付金额</th>
                </tr>
                <tr>
                    <td><?php echo ($relation_order['id']); ?></td>
                    <td><?php echo ($relation_order['order_no']); ?></td>
                    <td>￥<?php echo ($relation_order['purchase_price']); ?></td>
                    <td>￥<?php echo ($relation_order['pay_price']); ?></td>
                </tr>
            </table><?php endif; ?>
    </div>
    <div class="total-amount contact-info"></div>
    <div class="contact-info">
        <h3>费用信息 </h3>
        <dl>
            <dt>应付金额：</dt>
            <dd>￥<?php echo ($order['total_price']); ?></dd>
            <dt>实付金额：</dt>
            <dd>￥<?php echo ($order['pay_price']); ?></dd>
            <dt>总优惠金额：</dt>
            <dd>￥<?php echo ($order['discount_money']); ?></dd>
        </dl>
        <?php if(isset($card)): ?><dl>
            <dt>使用优惠券：</dt>
            <dd>是 </dd>
            <dt>优惠券抵扣：</dt>
            <dd>￥<?php echo ($card['deductible']); ?></dd>
        </dl>
        <?php else: ?>
            <dl>
                <dt>使用优惠券：</dt>
                <dd>否</dd>
            </dl><?php endif; ?>
    </div>
    <div class="total-amount contact-info"></div>
    <div class="goods-info">
        <h4>员工操作记录</h4>
        <table>
            <tr>
                <th>操作时间</th>
                <th>员工ID</th>
                <th>员工姓名</th>
                <th>员工电话</th>
                <th>操作类型</th>
            </tr>
            <?php if(is_array($operation)): $i = 0; $__LIST__ = $operation;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                    <td class="text-center"><?php echo (date("Y-m-d H:i:s",$vo['updated_time'])); ?></td>
                    <td class="text-center"><?php echo ($vo['employee_id']); ?></td>
                    <td class="text-center"><?php echo ($vo['employee_realname']); ?></td>
                    <td class="text-center"><?php echo ($vo['employee_tel']); ?></td>
                    <td class="text-center"><?php echo ($employee_operation[$vo['type']]); ?></td>
                </tr><?php endforeach; endif; else: echo "" ;endif; ?>
        </table>
    </div>
</div>

</div>

</body>
</html>