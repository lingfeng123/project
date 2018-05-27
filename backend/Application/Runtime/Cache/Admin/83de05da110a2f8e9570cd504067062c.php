<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php echo C('APP_NAME');?>管理后台</title>
    <script src="/Public/js/jquery.min.js"></script>
    <link rel="stylesheet" href="/Public/ui/css/layui.css">
    <script type="text/javascript" src="/Public/ui/layui.js"></script>
    
</head>
<body>
<div style="margin: 15px;">
    
    <fieldset class="layui-elem-field">
        <legend>订单修改</legend>
        <div class="layui-field-box" style="width: 700px">
            <form class="layui-form" action="<?php echo U('modify');?>" method="post">
                <div class="layui-form-item">
                    <label class="layui-form-label">订单编号</label>
                    <div class="layui-input-inline">
                        <input type="text" name="order_no" required value="<?php echo ($list['order_no']); ?>" lay-verify="required|number" class="layui-input" readonly>
                        <input type="hidden" name="order_id" required value="<?php echo ($list['id']); ?>" lay-verify="hidden|number" class="layui-input">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">商户名称</label>
                    <div class="layui-input-inline">
                        <input type="text" name="title" required value="<?php echo ($list['title']); ?>" lay-verify="required" class="layui-input"  readonly>
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">客户姓名</label>
                    <div class="layui-input-inline">
                        <input type="text" name="contacts_realname" required value="<?php echo ($list['contacts_realname']); ?>" lay-verify="required" class="layui-input" readonly >
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">客户联系方式</label>
                    <div class="layui-input-inline">
                        <input type="text" name="contacts_tel" required value="<?php echo ($list['contacts_tel']); ?>" lay-verify="required" class="layui-input" readonly>
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">订单状态</label>
                    <div class="layui-input-inline">

                        <select name="order_status" lay-filter="aihao" style="width: 75%">
                            <?php if(is_array($order_status)): foreach($order_status as $k=>$vo): ?><option value="<?php echo ($k); ?>" <?php if($k == $list['status']): ?>selected<?php endif; ?> ><?php echo ($vo); ?></option><?php endforeach; endif; ?>
                        </select>
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">订单类型</label>
                    <div class="layui-input-inline">
                        <input type="text" name="order_type" required value="<?php echo ($order_type[$list['order_type']]); ?>" lay-verify="required" class="layui-input" readonly >
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">结算状态</label>
                    <div class="layui-input-inline">
                        <select name="settlement_status" lay-filter="aihao" style="width: 75%">
                            <?php if(is_array($settlement_status)): foreach($settlement_status as $k=>$vo): ?><option name="settlement_status" value="<?php echo ($k); ?>" <?php if($k == $list['settlement_status']): ?>selected<?php endif; ?> ><?php echo ($vo); ?></option><?php endforeach; endif; ?>
                        </select>
                    </div>
                </div>


                <div class="layui-form-item">
                    <div class="layui-input-block">
                        <a  href="<?php echo U('modify');?>" class="layui-btn layui-btn-normal" lay-submit lay-filter="layui-form" onclick="return confirm('请确认是否要修改订单')">确认修改</a>
                        <a  href="<?php echo U('index');?>" class="layui-btn layui-btn-danger">返回列表</a>
                    </div>
                </div>
            </form>
        </div>
    </fieldset>

</div>

    <script type="text/javascript">
        $(function () {
            layui.use(['form','upload', 'laydate'], function(){
                var form = layui.form,
                    layer = layui.layer,
                    upload = layui.upload,
                    laydate = layui.laydate;

                form.on('submit(layui-form)', function(data){
                    $.ajax({
                        type: "POST",
                        url: '<?php echo U("modify");?>',
                        data: data.field,
                        success: function(msg){
                            if( msg.status == 1 ){
                                window.location.href='<?php echo U(index);?>';
                            }else{
                                parent.layer.msg(msg.info, {
                                    icon: 5,
                                    shade: [0.6, '#393D49'],
                                    time:1500
                                });
                            }
                        }
                    });
                    return false;
                });

                //时间选择器
                laydate.render({
                    elem: '#start_time'
                    ,type: 'datetime'
                    ,format: 'yyyy-MM-dd HH:mm:ss'
                });

                laydate.render({
                    elem: '#end_time'
                    ,type: 'datetime'
                    ,format: 'yyyy-MM-dd HH:mm:ss'
                });
            });
        })
    </script>
    <script>
        $(function () {

        })
    </script>

</body>
</html>