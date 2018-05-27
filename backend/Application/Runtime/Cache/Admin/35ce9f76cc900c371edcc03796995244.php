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
    

    <div class="layui-field-box">
        <form class="layui-form" method="get" action="<?php echo U('index');?>">
            <div class="layui-inline"><input class="layui-input" type="text" name="keywords" placeholder="请输入订单编号" value="<?php echo ($_GET['keywords']); ?>" /></div>
            <div class="layui-inline">
                <select name="pay_type">
                    <option value="">支付类型</option>
                    <?php if(is_array($pay_type)): foreach($pay_type as $k=>$vo): ?><option value="<?php echo ($k); ?>"><?php echo ($vo); ?></option><?php endforeach; endif; ?>
                </select>
            </div>
            <div class="layui-inline">
                <select name="buy_type">
                    <option value="">购买类型</option>
                    <?php if(is_array($buy_type)): foreach($buy_type as $k=>$vo): ?><option value="<?php echo ($k); ?>"><?php echo ($vo); ?></option><?php endforeach; endif; ?>
                </select>
            </div>
            <div class="layui-inline">
                <select name="trade_status">
                    <option value="">退款状态</option>
                    <?php if(is_array($trade_status)): foreach($trade_status as $k=>$vo): ?><option value="<?php echo ($k); ?>"><?php echo ($vo); ?></option><?php endforeach; endif; ?>
                </select>
            </div>
            <button class="layui-btn" type="submit">搜索</button>
        </form>
        <table class="layui-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>会员昵称</th>
                <th>所属商户</th>
                <th>应用ID</th>
                <th>交易订单号</th>
                <th>商户订单号</th>
                <th>实收金额</th>
                <th>退款金额</th>
                <th>退款时间</th>
                <th>支付类型</th>
                <th>购买类型</th>
                <th>退款状态</th>
                <th>退款原因</th>
                <th>创建时间</th>
            </tr>
            </thead>
            <tbody>
            <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                    <td><?php echo ($vo['id']); ?></td>
                    <td><?php echo ($vo['nickname']); ?></td>
                    <td><?php echo ($vo['merchant_title']); ?></td>
                    <td><?php echo ($vo['app_id']); ?></td>
                    <td><?php echo ($vo['trade_no']); ?></td>
                    <td><?php echo ($vo['order_no']); ?></td>
                    <td><?php echo ($vo['receipt_fee']); ?></td>
                    <td><?php echo ($vo['refund_fee']); ?></td>
                    <td><?php echo (date("Y-m-d H:i:s",$vo['refund_time'])); ?></td>
                    <td><?php echo ($pay_type[$vo['pay_type']]); ?></td>
                    <td><?php echo ($buy_type[$vo['buy_type']]); ?></td>
                    <td><?php echo ($trade_status[$vo['trade_status']]); ?></td>
                    <td><?php echo ($vo['refund_desc']); ?></td>
                    <td><?php echo (date("Y-m-d H:i:s",$vo['created_time'])); ?></td>
                    <!--<td>-->
                        <!--<a href="javascript:;" data-url="<?php echo U('detail', array('id' => $vo['id']));?>" class="click-show"><span class="layui-btn layui-btn-small edit layui-btn-primary">查看</span></a>-->
                    <!--</td>-->
                </tr><?php endforeach; endif; else: echo "" ;endif; ?>
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


</div>

    <script type="text/javascript">
        layui.use(['layer','form'], function() {
            var layer = layui.layer //弹层
                ,form = layui.form;
            $('.click-show').click(function () {
                var url = $(this).attr('data-url');
                console.log(url);
                //显示数据弹窗
                layer.open({
                    type: 2,
                    title: "查看套餐信息",
                    area: ['700px', '500px'],
                    skin: 'layui-layer-rim', //加上边框
                    content: url
                });
            });
        });
    </script>

</body>
</html>