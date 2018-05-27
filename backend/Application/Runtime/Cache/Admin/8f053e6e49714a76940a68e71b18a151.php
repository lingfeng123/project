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
        <legend>个人信息维护</legend>
        <div class="layui-field-box">
            <form class="layui-form" action="">
                <div class="layui-form-item">
                    <label class="layui-form-label">账号名</label>
                    <div class="layui-input-block">
                        <input type="text" name="username" value="<?php echo ($uname); ?>" readonly class="layui-input">
                    </div>
                </div>
                <div class="layui-form-item layui-form-text">
                    <label class="layui-form-label">真实姓名</label>
                    <div class="layui-input-block">
                        <input type="text" name="nickname" value="" placeholder="请输入新的姓名，留空表示不修改" class="layui-input">
                    </div>
                </div>
                <div class="layui-form-item layui-form-text">
                    <label class="layui-form-label">账号密码</label>
                    <div class="layui-input-block">
                        <input type="password" name="password" value="" placeholder="请输入新的密码，留空表示不修改" class="layui-input">
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
                    url: '<?php echo U("changeUser");?>',
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