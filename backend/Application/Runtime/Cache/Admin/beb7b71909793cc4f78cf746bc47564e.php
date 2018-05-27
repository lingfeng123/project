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
        <legend>权限管理 - 权限组细节配置</legend>
        <div class="layui-field-box">
            <form class="layui-form" action="">
                <input type="hidden" name="groupId" value="<?php echo I('get.group_id');?>">
                <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><div class="layui-form-item">
                        <input lay-skin="primary" type="checkbox" data-id="<?php echo ($vo['id']); ?>" lay-filter="admin-check" name="rule[<?php echo ($vo['id']); ?>]" value="<?php echo ($vo['url']); ?>" title="<?php echo ($vo['name']); ?>" <?php echo (in_array($vo['url'], $hasRule)?'checked':'');?>>
                    </div>
                    <?php if(count($vo['_child'])): ?><div class="layui-form-item">
                            <div style="margin-left: 50px;">
                                <?php if(is_array($vo['_child'])): $i = 0; $__LIST__ = $vo['_child'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$child): $mod = ($i % 2 );++$i;?><input lay-skin="primary" type="checkbox" lay-filter="admin-check" data-id="<?php echo ($child['id']); ?>" fid="<?php echo ($vo['id']); ?>" name="rule[<?php echo ($child['id']); ?>]" value="<?php echo ($child['url']); ?>" title="<?php echo ($child['name']); ?>" <?php echo (in_array($child['url'], $hasRule)?'checked':'');?>>
                                    <?php if(count($child['_child'])): ?><div style="margin-left: 50px;">
                                            <?php if(is_array($child['_child'])): $i = 0; $__LIST__ = $child['_child'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$_child): $mod = ($i % 2 );++$i;?><input lay-skin="primary" type="checkbox" pid="<?php echo ($vo['id']); ?>" data-id="<?php echo ($_child['id']); ?>" fid="<?php echo ($child['id']); ?>" name="rule[<?php echo ($_child['id']); ?>]" value="<?php echo ($_child['url']); ?>" title="<?php echo ($_child['name']); ?>" <?php echo (in_array($_child['url'], $hasRule)?'checked':'');?>><?php endforeach; endif; else: echo "" ;endif; ?>
                                        </div><?php endif; endforeach; endif; else: echo "" ;endif; ?>
                            </div>
                        </div><?php endif; endforeach; endif; else: echo "" ;endif; ?>
                <div class="layui-form-item">
                    <button class="layui-btn" lay-submit lay-filter="admin-form">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </form>
        </div>
    </fieldset>

</div>

    <script>
        layui.use('form', function(){
            var form = layui.form();
            form.on('checkbox(admin-check)', function(data){
                var dataId = $(this).attr('data-id');
                var $el = data.elem;
                if( $el.checked ){
                    $('input[fid="'+dataId+'"]').prop('checked','checked');
                    $('input[pid="'+dataId+'"]').prop('checked','checked');
                }else{
                    $('input[fid="'+dataId+'"]').prop('checked', false);
                    $('input[pid="'+dataId+'"]').prop('checked', false);
                }
                form.render();
            });
            form.on('submit(admin-form)', function(data){
                $.ajax({
                    type: "POST",
                    url: '<?php echo U("rule");?>',
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