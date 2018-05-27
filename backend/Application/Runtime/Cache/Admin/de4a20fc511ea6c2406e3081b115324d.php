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
        <legend>权限管理 - <?php echo (isset($detail['id'])?'编辑':'新增');?>权限组</legend>
        <div class="layui-field-box">
            <form class="layui-form" action="">
                <?php if(isset($detail['id'])): ?><input type="hidden" name="id" value="<?php echo ($detail['id']); ?>"><?php endif; ?>
                <div class="layui-form-item">
                    <label class="layui-form-label"><span style="color:red">*</span> 权限组名称</label>
                    <div class="layui-input-block">
                        <input type="text" name="name" required value="<?php echo (isset($detail['name'])?$detail['name']:'');?>" lay-verify="required" placeholder="请输入权限组名称" class="layui-input">
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">权限组描述</label>
                    <div class="layui-input-block">
                        <textarea name="description" placeholder="请输入权限组描述" class="layui-textarea"><?php echo (isset($detail['description'])?$detail['description']:'');?></textarea>
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

    <?php if(isset($detail['id'])): ?><script>
            layui.use('form', function(){
                var form = layui.form();
                form.on('submit(admin-form)', function(data){
                    $.ajax({
                        type: "POST",
                        url: '<?php echo U("edit");?>',
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
        <?php else: ?>
        <script>
            layui.use('form', function(){
                var form = layui.form();
                form.on('submit(admin-form)', function(data){
                    $.ajax({
                        type: "POST",
                        url: '<?php echo U("add");?>',
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
        </script><?php endif; ?>

</body>
</html>