<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo C('APP_NAME');?>管理后台</title>
    <link rel="stylesheet" href="/Public/layui/css/layui.css">
    <script type="text/javascript" src="/Public/layui/layui.js"></script>
    <script src="/Public/js/jquery.min.js"></script>
    
</head>
<body>
<div style="margin: 15px;">
    
    <fieldset class="layui-elem-field">
        <legend>权限管理 - 权限组列表</legend>
        <div class="layui-field-box">
            <span class="layui-btn layui-btn-normal api-add"><i class="layui-icon">&#xe608;</i> 新增</span>
            <table class="layui-table" lay-even>
                <thead>
                <tr>
                    <th>#</th>
                    <th>权限组</th>
                    <th>描述</th>
                    <th>访问授权</th>
                    <th>成员授权</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                        <td><?php echo ($i); ?></td>
                        <td><?php echo ($vo['name']); ?></td>
                        <td><?php echo ($vo['description']); ?></td>
                        <td>
                            <!--<span data-url="<?php echo U('rule', array('group_id' => $vo['id']));?>" class="layui-btn edit">访问授权</span>-->
                            <a href="<?php echo U('rule', array('group_id' => $vo['id']));?>" class="layui-btn layui-btn-small">访问授权</a>
                        </td>
                        <td>
                            <!--<a href="<?php echo U('member', array('group_id' => $vo['id']));?>" class="layui-btn edit">成员授权</a>-->
                            <span data-url="<?php echo U('member', array('group_id' => $vo['id']));?>" class="layui-btn layui-btn-small edit">成员授权</span>
                        </td>
                        <td>
                            <?php if($vo['status']): ?><span style="border-radius: 2px;background-color: #5FB878;padding:5px 10px;color: #ffffff">启用</span>
                                <?php else: ?>
                                <span style="border-radius: 2px;background-color: #FF5722;padding:5px 10px;color: #ffffff">禁用</span><?php endif; ?>
                        </td>
                        <td>
                            <?php if($vo['status']): ?><span class="layui-btn layui-btn-small layui-btn-danger confirm" data-info="你确定禁用当前权限组么？" data-id="<?php echo ($vo['id']); ?>" data-url="<?php echo U('close');?>">禁用</span>
                                <?php else: ?>
                                <span class="layui-btn layui-btn-small confirm" data-info="你确定启用当前权限组么？" data-id="<?php echo ($vo['id']); ?>" data-url="<?php echo U('open');?>">启用</span><?php endif; ?>
                            <span data-url="<?php echo U('edit', array('id' => $vo['id']));?>" class="layui-btn layui-btn-small edit layui-btn-normal">编辑</span>
                            <span class="layui-btn layui-btn-small layui-btn-danger confirm" data-id="<?php echo ($vo['id']); ?>" data-info="你确定删除当前权限组么？" data-url="<?php echo U('del');?>">删除</span>
                        </td>
                    </tr><?php endforeach; endif; else: echo "" ;endif; ?>
                </tbody>
            </table>
        </div>
    </fieldset>

</div>

    <script>
        layui.use(['layer'], function() {
           $('.api-add').on('click', function () {
                layer.open({
                    title: '新增权限组',
                    type: 2,
                    area: ['500px', '380px'],
                    maxmin: true,
                    content: '<?php echo U("add");?>'
                });
            });
            $('.edit').on('click', function () {
                var ownObj = $(this);
                layer.open({
                    type: 2,
                    area: ['80%', '66%'],
                    maxmin: true,
                    content: ownObj.attr('data-url')
                });
            });
            $('.confirm').on('click', function () {
                var ownObj = $(this);
                layer.confirm(ownObj.attr('data-info'), {
                    btn: ['确定','取消'] //按钮
                }, function(){
                    $.ajax({
                        type: "POST",
                        url: ownObj.attr('data-url'),
                        data: {id:ownObj.attr('data-id')},
                        success: function(msg){
                            if( msg.code == 1 ){
                                location.reload();
                            }else{
                                layer.msg(msg.msg, {
                                    icon: 5,
                                    shade: [0.6, '#393D49'],
                                    time:1500
                                });
                            }
                        }
                    });
                });
            });
        });
    </script>

</body>
</html>