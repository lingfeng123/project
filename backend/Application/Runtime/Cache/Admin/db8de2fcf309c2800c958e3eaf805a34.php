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
        <legend>授权管理</legend>
        <div class="layui-field-box">
            <form class="layui-form" action="">
                <input type="hidden" name="uid" value="<?php echo I('get.uid');?>">
                <div class="layui-form-item">
                    <label class="layui-form-label"><span style="color:red">*</span> 请选择组</label>
                    <div class="layui-input-block">
                        <?php if(is_array($allGroup)): $i = 0; $__LIST__ = $allGroup;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i; if(in_array($vo['id'], $groupAccess)): ?><input type="checkbox" name="groupAccess[<?php echo ($vo['id']); ?>]" value="<?php echo ($vo['id']); ?>" title="<?php echo ($vo['name']); ?>" checked>
                                <?php else: ?>
                                <input type="checkbox" name="groupAccess[<?php echo ($vo['id']); ?>]" value="<?php echo ($vo['id']); ?>" title="<?php echo ($vo['name']); ?>"><?php endif; endforeach; endif; else: echo "" ;endif; ?>
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-input-block">
                        <button class="layui-btn" lay-submit lay-filter="admin-form">立即提交</button>
                        <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                    </div>
                </div>
            </form>
        </div>
    </fieldset>

</div>

    <script>
        layui.use('form', function(){
            var form = layui.form();
            form.on('submit(admin-form)', function(data){
                $.ajax({
                    type: "POST",
                    url: '<?php echo U("group");?>',
                    data: data.field,
                    success: function(msg){
                        if( msg.code == 1 ){
                            parent.location.reload();
                        }else{
                            parent.layer.msg(msg.msg, {
                                icon: 5,
                                shade: [0.6, '#393D49'],
                                time:1500
                            });
                        }
                    }
                });
                return false;
            });

        });
    </script>

</body>
</html>