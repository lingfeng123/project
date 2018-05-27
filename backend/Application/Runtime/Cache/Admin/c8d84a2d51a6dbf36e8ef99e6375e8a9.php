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
    
<h1>活动列表</h1>
<div class="layui-field-box">
    <a href="<?php echo U('Activity/add');?>"><span class="layui-btn layui-btn-normal api-add"> <i class="layui-icon">&#xe608;</i> 添加活动</span></a>
    <table class="layui-table">
        <thead>
        <tr>
            <th width="60">活动ID</th>
            <th width="170">活动名称</th>
            <th width="150">开始时间</th>
            <th width="150">结束时间</th>
            <th width="80">活动状态</th>
            <th width="320">活动地址</th>
            <th width="150">创建时间</th>
            <th width="230">操作</th>
        </tr>
        </thead>
        <tbody>
        <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                <td><?php echo ($vo['id']); ?></td>
                <td><?php echo ($vo['title']); ?></td>
                <td><?php echo (date("Y-m-d H:i:s",$vo['start_time'])); ?></td>
                <td><?php echo (date("Y-m-d H:i:s",$vo['end_time'])); ?></td>
                <td>
                    <?php if($vo['status'] = 2): ?>进行中<?php else: ?>已停止<?php endif; ?>
                </td>
                <td><?php echo 'https://member.app.sc-csj.com'.U('Home/'.$vo['url']) ?></td>
                <td><?php echo (date("Y-m-d H:i:s",$vo['created_time'])); ?></td>
                <td>
                    <a href="<?php echo U('Activity/record', array('id'=> $vo['id']));?>" ><span class="layui-btn layui-btn-small layui-btn-primary confirm">中奖记录</span></a>
                    <a href="<?php echo U('Activity/detail', array('id'=> $vo['id']));?>" ><span class="layui-btn layui-btn-small layui-btn-primary confirm">查看</span></a>
                    <a href="<?php echo U('Activity/edit', array('id' => $vo['id']));?>"><span class="layui-btn layui-btn-small edit layui-btn-normal">编辑</span></a>
                    <a href="<?php echo U('Activity/del', array('id'=> $vo['id']));?>" onclick="return confirm('你确定删除此活动吗?'); return false"><span class="layui-btn layui-btn-small layui-btn-danger confirm">删除</span></a>
                </td>
            </tr><?php endforeach; endif; else: echo "" ;endif; ?>
        <tr>
            <td align="right" nowrap="true" colspan="10">
                <div class="pagination">
                    <?php echo ($pageHtml); ?>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</div>


</div>


</body>
</html>