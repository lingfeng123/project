<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo C('APP_NAME');?>管理后台</title>
    <script src="/Public/js/jquery.min.js"></script>
    <link rel="stylesheet" href="/Public/ui/css/layui.css">
    <script type="text/javascript" src="/Public/ui/layui.js"></script>
    
</head>
<body>
<div style="margin: 15px;">
    
<div class="page-title">订单列表</div>
            <button class="layui-btn layui-btn-danger layui-btn-sm layui-btn-radius" onclick="location.reload();" style="float: right">
                <i class="layui-icon layui-anim layui-anim-rotate layui-anim-loop">&#x1002;</i> 刷新
            </button>
            <form class="layui-form" method="get" action="<?php echo U('index');?>">
                <div class="layui-inline" style="width: 115px;">
                <select name="search_type">
                    <option value="1">客人电话</option>
                    <option value="2">客人姓名</option>
                    <option value="3">员工姓名</option>
                    <option value="4">员工电话</option>
                    <option value="5">订单编号</option>
                </select>
                </div>
                <div class="layui-inline" style="margin-left: -10px">
                <input class="layui-input" type="text" placeholder="请输入精准匹配词" name="keywords" value="" />
                </div>
                <div class="layui-inline" style="width: 100px;">
                <select name="status">
                    <option value="">订单状态</option>
                    <?php if(is_array($order_status)): foreach($order_status as $k=>$vo): ?><option value="<?php echo ($k); ?>"><?php echo ($vo); ?></option><?php endforeach; endif; ?>
                </select>
                </div>
                <div class="layui-inline" style="width: 100px;">
                <select name="settlement_status">
                    <option value="">结算状态</option>
                    <?php if(is_array($settlement_status)): foreach($settlement_status as $k=>$vo): ?><option value="<?php echo ($k); ?>"><?php echo ($vo); ?></option><?php endforeach; endif; ?>
                </select>
                </div>
                <div class="layui-inline" style="width: 100px;">
                <select name="order_type">
                    <option value="">订单类型</option>
                    <?php if(is_array($order_type)): foreach($order_type as $k=>$vo): ?><option value="<?php echo ($k); ?>"><?php echo ($vo); ?></option><?php endforeach; endif; ?>
                </select>
                </div>
                <div class="layui-inline" style="width: 100px;">
                <select name="payment">
                    <option value="">支付方式</option>
                    <?php if(is_array($payment)): foreach($payment as $k=>$vo): ?><option value="<?php echo ($k); ?>"><?php echo ($vo); ?></option><?php endforeach; endif; ?>
                </select>
                </div>
                <div class="layui-inline">
                    <div class="layui-input-inline" style="width: 150px;">
                        <input type="text" id="start_time" name="start_time" placeholder="选择开始时间" autocomplete="off" class="layui-input">
                    </div>
                    <div class="layui-input-inline" style="width: 8px">-</div>
                    <div class="layui-input-inline" style="width: 150px;">
                        <input type="text" id="stop_time" name="stop_time" placeholder="选择结束时间" autocomplete="off" class="layui-input">
                    </div>
                </div>
                <div class="layui-inline"><button class="layui-btn" type="submit">搜索</button></div>
            </form>

    <style type="text/css">
        .order_type{border: 1px solid #ccc; font-size: 12px; line-height: 100%; padding:2px 3px;}
        .order_type_0{color: #1E9FFF;border-color: #1E9FFF}
        .order_type_1{color: #fd6eeb;border-color: #fd6eeb}
        .order_type_2{color: #fd6e72;border-color: #fd6e72}
        .order_type_3{color: #73bc42;border-color: #73bc42}
    </style>
            <table class="layui-table">
                <thead>
                <tr>
                    <th width="130">订单编号</th>
                    <th width="120">商户</th>
                    <th width="80">客人姓名</th>
                    <th width="40">性别</th>
                    <th width="100">客人电话</th>
                    <th width="80">订单类型</th>
                    <th width="60">是否拼吧</th>
                    <th width="60">是否续酒</th>
                    <th width="100">实付金额</th>
                    <th width="80">到店日期</th>
                    <th width="60">订单状态</th>
                    <th width="60">结算状态</th>
                    <th width="60">支付方式</th>
                    <th width="135">创建时间</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                        <td><?php echo ($vo['order_no']); ?></td>
                        <td><?php echo ($vo['merchant_title']); ?></td>
                        <td><?php echo ($vo['contacts_realname']); ?></td>
                        <td><img src="/Public/images/sex_<?php echo ($vo['contacts_sex']); ?>.png" /></td>
                        <td><?php echo ($vo['contacts_tel']); ?></td>
                        <td><span class="order_type order_type_<?php echo ($vo['order_type']); ?>"><?php echo ($order_type[$vo['order_type']]); ?></span></td>
                        <td align="center"><?php if($vo['is_bar'] == 1): ?><i class="layui-icon" style="color:#00AA00;">&#x1005;</i><?php else: ?>--<?php endif; ?>
                        <td align="center"><?php if($vo['is_xu'] == 1): ?><i class="layui-icon" style="color:#00AA00;">&#x1005;</i><?php else: ?>--<?php endif; ?>
                        </td>
                        <td align="right"><?php echo ($vo['pay_price']); ?></td>
                        <td><?php echo ($vo['arrives_time']); ?></td>
                        <td class="order_<?php echo ($vo['status']); ?>"><?php echo ($order_status[$vo['status']]); ?></td>
                        <!--<td><?php echo ($settlement_status[$vo['settlement_status']]); ?></td>-->
                        <td align="center">
                            <?php if($vo['settlement_status'] == 1): ?><i class="layui-icon" style="color:#0a0;" title="已结算">&#x1005;</i>
                                <?php else: ?>
                                <i class="layui-icon" style="color:#f00;" title="未结算">&#xe60e;</i><?php endif; ?>
                        <td align="center"><img src="/Public/images/pay_<?php echo ($vo['payment']); ?>.png"  /></td>
                        <td><?php echo ($vo['created_time']); ?></td>
                        <td>
                            <a href="javascript:;" data-url="<?php echo U('detail', array('id' => $vo['id']));?>" class="click-show"><span class="layui-btn layui-btn-small edit layui-btn-primary">查看</span></a>
                            <!--<a href="<?php echo U('Order/edit',array('id'=>$vo['id']));?>" ><span class="layui-btn layui-btn-small edit layui-btn-primary">修改</span></a>-->
                            <?php if(in_array($vo['status'], $successStatus)): ?><a href="<?php echo U('Order/complete', array('id' => $vo['id']));?>" onclick="return confirm('是否执行完成操作');"><span class="layui-btn order-success layui-btn-small edit layui-btn-normal">完成</span></a><?php endif; ?>
                            <?php if(!in_array($vo['status'],$disallowStatus)): ?><a href="javascript:;" data-ajax-url="<?php echo U('Refund/cancelOrder', array('order_id' => $vo['id']));?>" class="click-refund">
                                    <span class="layui-btn layui-btn-small edit layui-btn-danger">拒绝</span>
                                </a><?php endif; ?>
                        </td>
                    </tr><?php endforeach; endif; else: echo "" ;endif; ?>
                <tr>
                    <td colspan="23" align="right">
                        <!--总支付金额: <?php echo ($money['pay_prices']); ?>
                        总优惠金额: <?php echo ($money['discount_prices']); ?>-->
                    </td>
                </tr>
                <tr>
                    <td align="right" nowrap="true" colspan="23">
                        <div class="pagination">
                            <?php echo ($pageHtml); ?>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>


</div>

<script type="text/javascript">
layui.use(['layer','form', 'laydate'], function() {
    var layer = layui.layer //弹层
        ,laydate = layui.laydate
        ,form = layui.form;

    laydate.render({elem: '#start_time', type: 'datetime', format: 'yyyy-MM-dd'});
    laydate.render({elem: '#stop_time', type: 'datetime', format: 'yyyy-MM-dd'});

    $('.click-show').click(function () {
        var url = $(this).attr('data-url');
        //显示数据弹窗
        layer.open({
            type: 2,
            title: "订单详情",
            area: ['960px', '600px'],
            skin: 'layui-layer-rim', //加上边框
            content: url
        });
    });

    $('.click-refund').click(function () {
        var url = $(this).attr('data-ajax-url');
        var reasons = $('.cancellation_reasons').html();
        var This = $(this);

        layer.open({
            type: 1
            ,title: '取消订单原因' //不显示标题栏
            ,closeBtn: false
            ,area: '400px;'
            ,shade: 0.8
            ,id: 'LAY_refund' //设定一个id，防止重复弹出
            ,resize: false
            ,btn: ['确定取消并退款', '取消']
            ,btnAlign: 'c'
            ,moveType: 1 //拖拽模式，0或者1
            ,content: '<div  style="padding: 40px 0; margin: 0 auto; width: 260px;">' +
                        '<select name="reasons" id="reasons" style="padding: 10px; width: 260px; border: 1px solid #ccc">' +
                        <?php if(is_array($cancellation_reasons)): $i = 0; $__LIST__ = $cancellation_reasons;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?>'<option value="<?php echo ($v); ?>"><?php echo ($v); ?></option>' +<?php endforeach; endif; else: echo "" ;endif; ?>
                        '</select>' +
                        '</div>'
            ,success: function(layero){
                layero.find('.layui-layer-btn0').click(function () {
                    var values = $('#reasons option:selected').val();
                    $.get(url, {cancel_reason:values}, function (data) {
                        if (data.code == 1){
                            layer.msg(data.msg);
                            This.remove();
                            setTimeout(function () {
                                location.reload()
                            }, 600)
                        } else {
                            layer.msg(data.msg);
                        }
                    })
                });
            }
        });
    });
});
</script>

</body>
</html>