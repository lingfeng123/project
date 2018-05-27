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
        <legend>菜单管理 - <?php echo (isset($detail['id'])?'编辑':'新增');?>菜单</legend>
        <div class="layui-field-box">
            <form class="layui-form" action="">
                <?php if(isset($detail['id'])): ?><input type="hidden" name="id" value="<?php echo ($detail['id']); ?>"><?php endif; ?>
                <div class="layui-form-item">
                    <label class="layui-form-label"><span style="color:red">*</span> 菜单名称</label>
                    <div class="layui-input-block">
                        <input type="text" name="name" required value="<?php echo (isset($detail['name'])?$detail['name']:'');?>" lay-verify="required" placeholder="请输入菜单名称" class="layui-input">
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label"><span style="color:red">*</span> 父级菜单</label>
                    <div class="layui-input-block">
                        <select name="fid" lay-verify="">
                            <option value="0">顶级菜单</option>
                            <?php if(is_array($options)): $i = 0; $__LIST__ = $options;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value="<?php echo ($key); ?>" <?php echo ($detail['fid'] == $key?'selected':'');?>><?php echo ($vo); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>
                        </select>
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label"><span style="color:red">*</span> 是否隐藏</label>
                    <div class="layui-input-block">
                        <input type="checkbox" name="hide" lay-skin="switch" lay-text="隐藏|显示" <?php echo ((isset($detail['hide']) && $detail['hide']==1)?'checked':'');?>>
                    </div>
                </div>
                <div class="layui-form-item layui-form-text">
                    <label class="layui-form-label">菜单URL</label>
                    <div class="layui-input-block">
                        <input type="text" name="url" value="<?php echo (isset($detail['url'])?$detail['url']:'');?>" placeholder="请输入菜单URL" class="layui-input">
                    </div>
                </div>
                <div class="layui-form-item layui-form-text">
                    <label class="layui-form-label">菜单排序</label>
                    <div class="layui-input-block">
                        <input type="text" name="sort" value="<?php echo (isset($detail['sort'])?$detail['sort']:'');?>" placeholder="请输入正整数，越大排名越靠后" class="layui-input">
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