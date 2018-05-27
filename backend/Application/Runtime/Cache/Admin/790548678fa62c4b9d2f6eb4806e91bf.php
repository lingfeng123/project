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
    
        <div class="page-title"><?php echo ($merchant_name); ?>商品列表</div>
        <form class="layui-form" method="get" action="<?php echo U('index');?>">
            <input type="hidden" name="merchant_id" value="<?php echo I('get.merchant_id');?>" />
            <div class="layui-inline">
                <input class="layui-input" name="keywords" value="" placeholder="请输入商品关键词">
            </div>
            <!--<div class="layui-inline" style="width: 100px">
                <select name="type">
                    <option value="">全部分类</option>
                    <option value="1">炒菜</option>
                    <option value="2">汤</option>
                </select>
            </div>-->
            <div class="layui-inline" style="width: 100px">
                <select name="status">
                    <option value="">全部状态</option>
                    <option value="0">下架</option>
                    <option value="1">上架</option>
                </select>
            </div>
            <button type="submit" class="layui-btn">搜索</button>
            <a href="<?php echo U('add', ['merchant_id' => $merchant_id]);?>" style="float: right"><span class="layui-btn layui-btn-normal api-add"> <i class="layui-icon">&#xe608;</i> 添加商品</span></a>
        </form>
        <table class="layui-table">
            <thead>
            <tr>
                <th>商品ID</th>
                <th>商品名称</th>
                <th>商品分类</th>
                <th>参考售价</th>
                <th>市场价格</th>
                <th>商品状态</th>
                <th>商品库存</th>
                <th>添加时间</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if(is_array($lists)): $i = 0; $__LIST__ = $lists;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                    <td><?php echo ($vo['id']); ?></td>
                    <td><?php echo ($vo['title']); ?></td>
                    <td align="center">
                        <?php if($vo['type'] == 1): ?>优惠套餐
                            <?php elseif($vo['type'] == 2): ?>
                            卡座套餐
                            <?php elseif($vo['type'] == 3): ?>
                            单品酒水<?php endif; ?>
                    </td>
                    <td><?php echo ($vo['price']); ?></td>
                    <td><?php echo ($vo['market_price']); ?></td>
                    <td>
                        <?php if($vo['status']): ?>销售中
                            <?php else: ?>
                            已下架<?php endif; ?>
                    </td>
                    <td><?php echo ($vo['stock']); ?></td>
                    <td><?php echo (date("Y-m-d h:i:s",$vo['created_time'])); ?></td>
                    <td>
                        <a href="javascript:;" data-url="<?php echo U('detail', array('id' => $vo['id']));?>" class="click-show"><span class="layui-btn layui-btn-small edit layui-btn-primary">查看</span></a>
                        <a href="<?php echo U('edit', array('id' => $vo['id'],'merchant_id' => $vo['merchant_id']));?>"><span class="layui-btn layui-btn-small edit layui-btn-normal">编辑</span></a>
                        <a href="<?php echo U('delete', array('id'=> $vo['id'],'merchant_id' => $vo['merchant_id']));?>" onclick="return confirm('你确定删除吗?'); return false"><span class="layui-btn layui-btn-small layui-btn-danger confirm">删除</span></a>
                    </td>
                </tr><?php endforeach; endif; else: echo "" ;endif; ?>
            <tr>
                <td align="right" nowrap="true" colspan="13">
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
        console.log(url);
        //显示数据弹窗
        layer.open({
            type: 2,
            title: "商品信息",
            area: ['700px', '500px'],
            skin: 'layui-layer-rim', //加上边框
            content: url
        });
    });
    $('.click-set').click(function () {
        var url = $(this).attr('data-url');
        console.log(url);
        //显示数据弹窗
        layer.open({
            type: 2,
            title: "商品价格设置",
            area: ['362px', '520px'],
            skin: 'layui-layer-rim', //加上边框
            content: url
        });
    });
});
</script>

</body>
</html>