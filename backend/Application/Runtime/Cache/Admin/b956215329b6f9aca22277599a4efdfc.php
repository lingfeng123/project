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
        <legend>菜单管理 - 菜单列表</legend>
        <div class="layui-field-box">
            <span class="layui-btn layui-btn-normal api-add"><i class="layui-icon">&#xe608;</i> 新增</span>
            <table class="layui-table" lay-even>
                <thead>
                <tr>
                    <th>#</th>
                    <th>菜单名称</th>
                    <th>排序</th>
                    <th>菜单URL</th>
                    <th>隐藏</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                        <td><?php echo ($i); ?></td>
                        <td><?php echo ($vo['showName']); ?></td>
                        <td><?php echo ($vo['sort']); ?></td>
                        <td><?php echo ($vo['url']); ?></td>
                        <td>
                            <?php if($vo['hide']): ?><span style="border-radius: 2px;background-color: #FF5722;padding:5px 10px;color: #ffffff">隐藏</span>
                                <?php else: ?>
                                <span style="border-radius: 2px;background-color: #5FB878;padding:5px 10px;color: #ffffff">显示</span><?php endif; ?>
                        </td>
                        <td>
                            <?php if($vo['hide']): ?><span class="layui-btn layui-btn-small confirm" data-info="你确定显示当前菜单么？" data-id="<?php echo ($vo['id']); ?>"
                                      data-url="<?php echo U('open');?>">显示</span>
                                <?php else: ?>
                                <span class="layui-btn layui-btn-small layui-btn-danger confirm" data-info="你确定隐藏当前菜单么？"
                                      data-id="<?php echo ($vo['id']); ?>" data-url="<?php echo U('close');?>">隐藏</span><?php endif; ?>
                            <span data-url="<?php echo U('edit', array('id' => $vo['id']));?>"
                                  class="layui-btn layui-btn-small edit layui-btn-normal">编辑</span>
                            <span class="layui-btn layui-btn-small layui-btn-danger confirm" data-id="<?php echo ($vo['id']); ?>"
                                  data-info="你确定删除当前菜单么？" data-url="<?php echo U('del');?>">删除</span>
                        </td>
                    </tr><?php endforeach; endif; else: echo "" ;endif; ?>
                </tbody>
            </table>
        </div>
    </fieldset>

</div>

    <script>
        layui.use(['layer'], function () {
            $('.api-add').on('click', function () {
                layer.open({
                    type: 2,
                    title: '新增菜单',
                    area: ['800px', '550px'],
                    maxmin: true,
                    content: '<?php echo U("add");?>'
                });
            });
            $('.edit').on('click', function () {
                var ownObj = $(this);
                layer.open({
                    type: 2,
                    title: '编辑菜单',
                    area: ['800px', '550px'],
                    maxmin: true,
                    content: ownObj.attr('data-url')
                });
            });
            $('.confirm').on('click', function () {
                var ownObj = $(this);
                layer.confirm(ownObj.attr('data-info'), {
                    btn: ['确定', '取消'] //按钮
                }, function () {
                    $.ajax({
                        type: "POST",
                        url: ownObj.attr('data-url'),
                        data: {id: ownObj.attr('data-id')},
                        success: function (msg) {
                            if (msg.code == 1) {
                                location.reload();
                            } else {
                                layer.msg(msg.msg, {
                                    icon: 5,
                                    shade: [0.6, '#393D49'],
                                    time: 1500
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