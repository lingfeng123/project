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
    
    <!--主菜单-->
    <div class="layui-field-box">
        <a href="<?php echo U('Wechat/qrcodeAdd');?>" class="layui-btn layui-btn-normal api-add" id="create_data"> <i
                class="layui-icon"></i> 添加渠道</a>
        <table class="layui-table">
            <thead>
            <tr>
                <th>渠道标识ID</th>
                <th>渠道名称</th>
                <th>渠道描述</th>
                <th>注册量</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
            </thead>
            <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                    <td><?php echo ($vo['id']); ?></td>
                    <td><?php echo ($vo['title']); ?></td>
                    <td><?php echo ($vo['description']); ?></td>
                    <td><?php echo ($vo['total']); ?></td>
                    <td><?php echo (date("Y-m-d H:i:s", $vo['created_time'])); ?></td>
                    <td>
                        <a href="javascript:;" data-url="<?php echo ($vo['qrcode']); ?>" class="click-show">
                            <span class="layui-btn layui-btn-small edit layui-btn-primary">查看二维码</span>
                        </a>
                    </td>
                </tr><?php endforeach; endif; else: echo "" ;endif; ?>
        </table>

    </div>


</div>

    <script type="text/javascript">
        layui.use(['form', 'layedit', 'laydate'], function () {
            var form = layui.form
                , layer = layui.layer;

            $('.click-show').click(function () {
                var url = $(this).attr('data-url');
                //显示数据弹窗
                layer.open({
                    type: 2,
                    title: false,
                    area: ['430px', '430px'],
                    skin: 'layui-layer-rim', //加上边框
                    content: url
                });
            });

        });

    </script>

</body>
</html>