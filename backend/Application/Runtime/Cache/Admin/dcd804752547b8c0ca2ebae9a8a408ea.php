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
    
<div class="page-title">拼吧列表</div>
            <form class="layui-form" method="get" action="<?php echo U('index');?>">
                <div class="layui-inline">
                <select name="search_type">
                    <option value="1">联系人电话</option>
                    <option value="2">拼吧编号</option>
                </select>
                </div>
                <div class="layui-inline">
                <input class="layui-input" type="text" name="keywords" value="" />
                </div>
                <div class="layui-inline">
                <select name="bar_status">
                    <option value="">拼吧状态</option>
                    <?php if(is_array($bar_status)): foreach($bar_status as $k=>$vo): ?><option value="<?php echo ($k); ?>"><?php echo ($vo); ?></option><?php endforeach; endif; ?>
                </select>
                </div>
                <div class="layui-inline">
                    <select name="bar_type">
                        <option value="">拼吧类型</option>
                        <?php if(is_array($bar_type)): foreach($bar_type as $k=>$vo): ?><option value="<?php echo ($k); ?>"><?php echo ($vo); ?></option><?php endforeach; endif; ?>
                    </select>
                </div>
                <div class="layui-inline">
                <button class="layui-btn" type="submit">搜索</button>
                </div>
                <button class="layui-btn layui-btn-radius" onclick="location.reload();" style="float: right">
                    <i class="layui-icon layui-anim layui-anim-rotate layui-anim-loop">&#x1002;</i> 刷新
                </button>
            </form>
            <table class="layui-table">
                <thead>
                <tr>
                    <th>拼吧编号</th>
                    <th>商户信息</th>
                    <th>发起人昵称</th>
                    <th>发起人电话</th>
                    <th>拼吧类型</th>
                    <th>订单总价</th>
                    <th>实付金额</th>
                    <th>平均价格</th>
                    <th>订单状态</th>
                    <th>到店时间</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                        <td><?php echo ($vo['bar_no']); ?></td>
                        <td><?php echo ($vo['merchant_title']); ?></td>
                        <td><?php echo ($vo['contacts_realname']); ?></td>
                        <td><?php echo ($vo['contacts_tel']); ?></td>
                        <td><?php echo ($bar_type[$vo['bar_type']]); ?></td>
                        <td><?php echo ($vo['total_price']); ?></td>
                        <td><?php echo ($vo['pay_price']); ?></td>
                        <td><?php echo ($vo['average_cost']); ?></td>
                        <td><span class="order_<?php echo ($vo['bar_status']); ?>"><?php echo ($bar_status[$vo['bar_status']]); ?></span></td>
                        <td><?php echo (date("Y-m-d H:i",$vo['arrives_time'])); ?></td>
                        <td><?php echo (date("Y-m-d H:i",$vo['created_time'])); ?></td>
                        <td>
                            <a href="javascript:;" data-url="<?php echo U('barDetail', array('bar_id' => $vo['id']));?>" class="click-show"><span class="layui-btn layui-btn-normal layui-btn-radius">查看</span></a>
                            <?php if(!in_array($vo['bar_status'],[0,6,3,4,5])): ?><a href="javascript:;" data-url="<?php echo U('barRefund');?>" data="<?php echo ($vo['id']); ?>" class="click-cancel">
                                    <span class="layui-btn layui-btn-danger layui-btn-radius">取消拼吧</span>
                                </a><?php endif; ?>
                        </td>
                    </tr><?php endforeach; endif; else: echo "" ;endif; ?>
                <tr>
                    <td colspan="23" align="right">
                        总支付金额: <?php echo ($money['pay_prices']); ?>
                        总优惠金额: <?php echo ($money['discount_prices']); ?>
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
layui.use(['layer','form'], function() {
    var layer = layui.layer //弹层
        ,form = layui.form;
    $('.click-show').click(function () {
        var url = $(this).attr('data-url');
        //显示数据弹窗
        layer.open({
            type: 2,
            title: "订单详情",
            area: ['1000px', '800px'],
            skin: 'layui-layer-rim', //加上边框
            content: url
        });
    });

    $('.click-cancel').click(function () {
        var url = $(this).attr('data-url');
        //显示数据弹窗
        var bar_id = $(this).attr('data');
        var This = $(this);


        layer.confirm('是否确认取消订单', {btn: ['确认', '取消']}, function () {
            $.post(url, {bar_id: bar_id}, function (res) {

                if (res.code == 1) {
                    layer.msg(res.msg);
                    This.remove();
                    setTimeout(function () {
                        location.reload()
                    }, 600)
                } else {
                    layer.msg(res.info);
                }


            });
        })

    });
});
</script>

</body>
</html>