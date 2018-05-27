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
        <legend><?php echo (isset($list['id'])?'修改卡券':'添加卡券');?></legend>
        <div class="layui-field-box" style="width: 700px">
            <form class="layui-form" action="" method="post">
                <?php if(isset($list['id'])): ?><input type="hidden" name="id" value="<?php echo ($list['id']); ?>"><?php endif; ?>
                <div class="layui-form-item">
                    <label class="layui-form-label"><span style="color:red">*</span> 卡券名称</label>
                    <div class="layui-input-block">
                        <input type="text" name="card_name" required value="<?php echo (isset($list['card_name']) ? $list['card_name']:'');?>" lay-verify="required" placeholder="卡券名称" class="layui-input">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">商户</label>
                    <div class="layui-input-block">
                        <select name="merchant_id" lay-filter="aihao" style="width: 75%">
                            <?php if(is_array($merchant)): foreach($merchant as $k=>$vo): ?><option value="<?php echo ($vo['id']); ?>" <?php if($k == $list['merchant_id']): ?>selected<?php endif; ?> ><?php echo ($vo['title']); ?></option><?php endforeach; endif; ?>
                        </select>
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label"> 卡券类型</label>
                    <div class="layui-input-block">
                        <select name="card_type" lay-filter="card_type" style="width: 75%" >
                            <?php if(is_array($card_type)): foreach($card_type as $k=>$vo): ?><option value="<?php echo ($k); ?>" <?php if($k == $list['card_type']): ?>selected<?php endif; ?> ><?php echo ($vo); ?></option><?php endforeach; endif; ?>
                        </select>
                    </div>
                </div>

                <div class="layui-form-item layui-form-text">
                    <label class="layui-form-label">卡券抵扣金额</label>
                    <div class="layui-input-block">
                        <input type="number" name="deductible" required value="<?php echo (isset($list['deductible']) ? $list['deductible']:'');?>" placeholder="卡券抵扣" class="layui-input">
                    </div>
                </div>

                <div class="layui-form-item layui-form-text high_amount">
                    <label class="layui-form-label">消费达到金额</label>
                    <div class="layui-input-block">
                        <input type="number" name="high_amount" id="high_amount"  placeholder="消费达到金额" value="<?php echo (isset($list['high_amount']) ? $list['high_amount']:'');?>" autocomplete="off" class="layui-input">
                    </div>
                </div>

                <div class="layui-form-item effective_time">
                    <div class="layui-inline">
                        <label class="layui-form-label"> 有效时间</label>
                        <div class="layui-inline">
                            <input type="number" name="effective_time" id="effective_time" placeholder="请填入有效天数" value="<?php echo (isset($list['effective_time']) ? $list['effective_time']:'');?>" autocomplete="off" class="layui-input">
                        </div>
                    </div>
                </div>
                <div class="layui-form-item time">
                    <div class="layui-inline">
                        <label class="layui-form-label"> 开始时间</label>
                        <div class="layui-inline">
                            <input type="text" name="start_time" id="start_time" placeholder="yyyy-MM-dd" value="<?php echo ($list['start_time'] >0 ? date('Y-m-d',$list['start_time']):''); ?>" autocomplete="off" class="layui-input">
                        </div>
                    </div>
                </div>
                <div class="layui-form-item time">
                    <div class="layui-inline">
                        <label class="layui-form-label"> 结束时间</label>
                        <div class="layui-inline">
                            <input type="text" name="end_time" id="end_time" placeholder="yyyy-MM-dd" value="<?php echo ($list['end_time'] >0 ? date('Y-m-d',$list['end_time']):''); ?>" autocomplete="off" class="layui-input">
                        </div>
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">卡券状态</label>
                    <div class="layui-input-block adstatus">
                        <input type="radio" name="status" value="1" title="激活" <?php if($list['status'] == 1): ?>checked="checked"<?php endif; ?>>
                        <input type="radio" name="status" value="0" <?php if($list['status'] == 0): ?>checked="checked"<?php endif; ?> title="封禁">
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">卡券领取</label>
                    <div class="layui-input-block adstatus">
                        <input type="radio" name="flag" value="1" title="领券中心" <?php if($list['flag'] == 1): ?>checked="checked"<?php endif; ?>>
                        <input type="radio" name="flag" value="2" <?php if($list['flag'] == 2): ?>checked="checked"<?php endif; ?> title="首页领券">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">男女赠送</label>
                    <div class="layui-input-block adstatus">
                        <input type="radio" name="is_sex" value="0"  title="所有" <?php if($list['is_sex'] == 0): ?>checked="checked"<?php endif; ?>>
                        <input type="radio" name="is_sex" value="1"  title="男"  <?php if($list['is_sex'] == 1): ?>checked="checked"<?php endif; ?>>
                        <input type="radio" name="is_sex" value="2"  title="女" <?php if($list['is_sex'] == 2): ?>checked="checked"<?php endif; ?>>
                    </div>
                </div>

                <?php if(empty($list['id'])): ?><div class="layui-form-item">
                        <div class="layui-inline">
                            <label class="layui-form-label">卡券的数量</label>
                            <div class="layui-inline">
                                <input type="number" name="cardnumber"  placeholder="批量生成的卡券数量" value="1"  class="layui-input">
                            </div>
                            <label style="color: #00FF00">默认不用填</label>
                        </div>
                    </div><?php endif; ?>

                <div class="layui-form-item ">
                    <label class="layui-form-label">卡券备注</label>
                    <div class="layui-input-block">
                        <textarea class="layui-textarea" name="marks" id="ss" placeholder="卡券描述"><?php echo ($list['marks']); ?></textarea>
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-input-block">
                        <button class="layui-btn" type="submit" lay-submit lay-filter="admin-form">立即提交</button>
                        <a  href="<?php echo U('Activity/index');?>" class="layui-btn layui-btn-danger">返回活动列表</a>
                    </div>
                </div>
            </form>
        </div>
    </fieldset>

</div>


    <?php if($list[id]): ?><script type="text/javascript">
            $(function () {
                layui.use(['form','upload', 'laydate', 'layedit'], function(){
                    var form = layui.form,
                        layer = layui.layer,
                        layedit = layui.layedit;
                    laydate = layui.laydate;

                    form.on('submit(admin-form)', function(data){
                        $.ajax({
                            type: "POST",
                            url: '<?php echo U("edit");?>',
                            data: data.field,
                            success: function(msg){
                                console.log(msg);
                                if( msg.status == 1 ){
                                    window.location.href=msg.url;
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

                    layedit.build('remaks'); //建立编辑器
                    laydate.render({
                        elem: '#start_time'
                        ,type: 'datetime'
                        ,format: 'yyyy-MM-dd '
                    });
                    laydate.render({
                        elem: '#end_time'
                        ,type: 'datetime'
                        ,format: 'yyyy-MM-dd '
                    });
                });
            })
        </script>
        <?php else: ?>
        <script type="text/javascript">
            $(function () {
                layui.use(['form','upload', 'laydate', 'layedit'], function(){
                    var form = layui.form,
                        layer = layui.layer,
                        layedit = layui.layedit;
                    laydate = layui.laydate;

                    form.on('submit(admin-form)', function(data){
                        $.ajax({
                            type: "POST",
                            url: '<?php echo U("add");?>',
                            data: data.field,
                            success: function(msg){
                                console.log(msg);
                                if( msg.status == 1 ){
                                    window.location.href=msg.url;
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

                    form.on('select(card_type)', function(data){
                        if(data.value == 2){
                            $('div.high_amount').hide();
                        }
                    });

                  $('#effective_time').onblur(function(){
                      console.log($(this));
                  })

                    layedit.build('remaks'); //建立编辑器
                    laydate.render({
                        elem: '#start_time'
                        ,type: 'datetime'
                        ,format: 'yyyy-MM-dd '
                    });
                    laydate.render({
                        elem: '#end_time'
                        ,type: 'datetime'
                        ,format: 'yyyy-MM-dd '
                    });
                });
            })
        </script><?php endif; ?>

</body>
</html>