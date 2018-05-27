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
    
<div class="page-title">应用版本管理</div>
<form class="layui-form" method="get" action="<?php echo U('index');?>">
    <div class="layui-inline">
        <select name="platform">
            <option value="">平台类型</option>
            <option value="1">用户端</option>
            <option value="2">商户端</option>
        </select>
    </div>
    <div class="layui-inline">
        <select name="client">
            <option value="">终端</option>
            <option value="ios">IOS</option>
            <option value="android">Android</option>
        </select>
    </div>
    <div class="layui-inline">
        <select name="is_force">
            <option value="">更新类型</option>
            <option value="1">强制更新</option>
            <option value="0">非强制更新</option>
        </select>
    </div>
    <div class="layui-inline">
        <button class="layui-btn" type="submit">搜索</button>
    </div>
    <a href="<?php echo U('Version/add');?>"><span class="layui-btn layui-btn-normal api-add" style="float: right"> <i class="layui-icon">&#xe608;</i> 新增版本号</span></a>
</form>
<table class="layui-table">
    <thead>
    <tr>
        <th width="80">版本号</th>
        <th width="80">version code</th>
        <th width="50">终端</th>
        <th width="60">平台类型</th>
        <th width="60">强制更新</th>
        <th width="400">应用下载地址</th>
        <th>更新内容</th>
        <th width="160">发布时间</th>
        <th width="120">操作</th>
    </tr>
    </thead>
    <tbody>
    <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
            <td>v<?php echo ($vo['version']); ?></td>
            <td><?php echo ($vo['version_code']); ?></td>
            <td><img src="/Public/images/<?php echo ($vo['client']); ?>.png" /> </td>
            <td><?php if($vo['platform'] == 1): ?>用户端<?php else: ?>商户端<?php endif; ?></td>
            <td><?php if($vo['is_force'] == 1): ?>是<?php else: ?>否<?php endif; ?></td>
            <td><?php echo ($vo['url']); ?></td>
            <td><?php echo htmlspecialchars_decode($vo['content']);?></td>
            <td><?php echo (date("Y-m-d h:i:s",$vo['updated_time'])); ?></td>
            <td>
                <a href="<?php echo U('edit', array('id' => $vo['id']));?>"><span class="layui-btn layui-btn-small edit layui-btn-normal">编辑</span></a>
                <a href="<?php echo U('del', array('id'=> $vo['id']));?>" onclick="return confirm('你确定删除吗?'); return false"><span class="layui-btn layui-btn-small layui-btn-danger confirm">删除</span></a>
            </td>
        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
    <tr>
        <td align="right" nowrap="true" colspan="9">
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
    var layer = layui.layer, form = layui.form;
    $('.click-show').click(function () {
        var url = $(this).attr('data-url');
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