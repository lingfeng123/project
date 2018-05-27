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
        <h3>拼吧基本信息</h3>
        <dl>
            <dt>拼吧ID：</dt>
            <dd><?php echo ($order["id"]); ?></dd>
            <dt>拼吧编号：</dt>
            <dd><?php echo ($order["bar_no"]); ?></dd>
            <dt>商户名称：</dt>
            <dd><?php echo ($order["title"]); ?></dd>
        </dl>
        <dl>
            <dt>拼吧类型：</dt>
            <dd><?php echo ($bar_type[$order['bar_type']]); ?></dd>
            <dt>拼吧状态：</dt>
            <dd><?php echo ($bar_status[$order['bar_status']]); ?></dd>
            <dt>拼吧主题：</dt>
            <dd><?php echo ($bar_theme[$order['bar_theme']]); ?></dd>
        </dl>
        <dl>
            <dt>男士人数：</dt>
            <dd><?php echo ($order['man_number']); ?></dd>
            <dt>女士人数：</dt>
            <dd><?php echo ($order['woman_number']); ?></dd>
            <dt>到店日期：</dt>
            <dd><?php echo (date("Y-m-d",$order['arrives_time'])); ?></dd>
        </dl>
        <dl>
            <dt>订单备注：</dt>
            <dd><?php echo ($order['description']); ?></dd>
            <dt>拼吧时间：</dt>
            <dd><?php echo (date("Y-m-d H:i:s",$order['created_time'])); ?></dd>
            <?php if($order['bar_status'] == 6): ?><dt>拒绝理由：</dt>
                <dd><?php echo ($order['cancel_reason']); ?></dd><?php endif; ?>
        </dl>
    </div>

    <?php if(!empty($order['employee'])): ?><div class="addr-note">
            <h4>员工信息</h4>
            <?php if(!empty($order['employee']['employee_avatar'])): ?><dl>
                    <dt>员工头像：</dt>
                    <dd><img src="<?php echo ($attachment_url); echo ($order['employee']['employee_avatar']); ?>" width="50" height="50" /> </dd>
                </dl><?php endif; ?>
            <dl>
                <dt>员工ID：</dt>
                <dd><?php echo ($order['employee']['employee_id']); ?></dd>
                <dt>员工姓名：</dt>
                <dd><?php echo ($order['employee']['employee_realname']); ?></dd>
                <dt>员工电话：</dt>
                <dd><?php echo ($order['employee']['employee_tel']); ?></dd>
            </dl>
        </div><?php endif; ?>

    <div class="addr-note">
        <h4>参与人信息</h4>
        <table>
            <tr>
                <th>参与人昵称</th>
                <th>参与人头像</th>
                <th>参与人性别</th>
                <th>参与人电话</th>
            </tr>
            <?php if(is_array($take_member)): $i = 0; $__LIST__ = $take_member;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                    <td><?php echo ($vo['nickname']); ?></td>
                    <td><img src="<?php echo ($vo['avatar']); ?>" width="50px" height="50px"/></td>
                    <td><?php echo ($vo['sex']); ?></td>
                    <td><?php echo ($vo['tel']); ?></td>
                </tr><?php endforeach; endif; else: echo "" ;endif; ?>

        </table>

    </div>

    <?php if($order['bar_type'] == 1): ?><div class="goods-info">
            <h4>商品信息</h4>
            <table>
                <tr>
                    <th>商品图片</th>
                    <th>商品名称</th>
                    <th>购买数量</th>
                    <th>销售价格</th>
                    <th>结算价格</th>
                </tr>
                <?php if(is_array($goods_pack)): $i = 0; $__LIST__ = $goods_pack;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$goods): $mod = ($i % 2 );++$i;?><tr>
                        <td><img width="50" height="50" src="<?php echo ($goods['image']); ?>"></td>
                        <td><?php echo ($goods['title']); ?></td>
                        <td><?php echo ($goods['amount']); ?></td>
                        <td>￥<?php echo ($goods['price']); ?></td>
                        <td>￥<?php echo ($goods['purchase_price']); ?></td>
                    </tr><?php endforeach; endif; else: echo "" ;endif; ?>
            </table>
        </div><?php endif; ?>
    <div class="total-amount contact-info"></div>
    <div class="contact-info">
        <h3>费用信息 </h3>
        <dl>
            <dt>费用类型：</dt>
            <dd>￥<?php echo ($cost_type[$order['cost_type']]); ?></dd>
            <dt>应付金额：</dt>
            <dd>￥<?php echo ($order['total_price']); ?></dd>
            <dt>实付金额：</dt>
            <dd>￥<?php echo ($order['pay_price']); ?></dd>
            <dt>平均价格：</dt>
            <dd>￥<?php echo ($order['average_cost']); ?></dd>
        </dl>
    </div>
    <div class="total-amount contact-info"></div>
    <?php if(!empty($order['employee_op'])): ?><div class="goods-info">
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
        </div><?php endif; ?>
</div>

</div>

</body>
</html>