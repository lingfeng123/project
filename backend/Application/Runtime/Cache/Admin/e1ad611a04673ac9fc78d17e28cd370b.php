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
    
    <fieldset class="layui-elem-field">
        <legend>系统消息列表</legend>

        <div class="layui-field-box">
            <a href="javascript:;" data-url="<?php echo U('Message/add');?>" class="click-show-member"><span class="layui-btn layui-btn-normal api-add"> <i
                    class="layui-icon">&#xe608;</i> 新增</span></a>
            <table class="layui-table">
                <thead>
                <tr>
                    <th>序号</th>
                    <th>主题</th>
                    <th>内容</th>
                    <th>发送终端</th>
                    <th>发表时间</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                        <td><?php echo ($vo['id']); ?></td>
                        <td><?php echo ($vo['title']); ?></td>
                        <td>
                            <?php
 echo mb_substr($vo['content'],0,15,'utf-8'); ?>
                            ...
                        </td>
                        <td>
                            <?php
 if($vo['toclient'] ==1 ){ echo '所有'; }else if($vo['toclient'] ==2 ){ echo '用户端'; }else{ echo '商户端'; } ?>
                        </td>
                        <td><?php echo date('Y-m-d H:i:s',$vo['created_time']);?></td>
                        <td align="center">
                            <a href="javascript:;" data-url="<?php echo U('Message/detail', array('id' => $vo['id']));?>" class="click-show-member"><span class="layui-btn layui-btn-small edit layui-btn-primary">详情</span></a>
                            <a href="javascript:;" data-url="<?php echo U('Message/edit', array('id' => $vo['id']));?>" class="click-show-member"><span class="layui-btn layui-btn-small edit layui-btn-primary">编辑</span></a>
                            <a href="javascript:;" data-url="<?php echo U('del', array('id' => $vo['id']));?>" class="click-show-member"><span class="layui-btn layui-btn-small edit layui-btn-primary">删除</span></a>
                        </td>
                    </tr><?php endforeach; endif; else: echo "" ;endif; ?>
                <tr>
                    <td align="right" nowrap="true" colspan="11">
                        <div class="pagination">
                            <?php echo ($pageHtml); ?>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </fieldset>

</div>

    <script type="text/javascript">
        layui.use(['layer'], function() {
            var layer = layui.layer //弹层
            $('.click-show-member').click(function () {
                var url = $(this).attr('data-url');
                //显示数据弹窗
                layer.open({
                    type: 2,
                    title: "查看用户信息",
                    area: ['800px', '600px'],
                    skin: 'layui-layer-rim', //加上边框
                    content: url
                });
            });

        });
    </script>

</body>
</html>