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
    
        <div class="page-title"><?php echo (isset($detail['id'])?'修改商品':'添加商品');?></div>
        <div class="layui-field-box" style="width: 700px">
            <form class="layui-form" action="" method="post">
                <?php if(isset($detail['id'])): ?><input type="hidden" name="id" value="<?php echo ($detail['id']); ?>"><?php endif; ?>
                <input type="hidden" name="merchant_id" value="<?php echo ($detail['merchant_id']); ?>">

                <div class="layui-form-item" id="pack-type">
                    <label class="layui-form-label"><span style="color: #f00">*</span>商品类型</label>
                    <div class="layui-input-block pack-type">
                        <input type="radio" name="type" value="1" title="优惠套餐">
                        <input type="radio" name="type" value="2" title="卡座套餐">
                        <input type="radio" name="type" value="3" title="单品酒水">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">商品名称</label>
                    <div class="layui-input-block">
                        <input type="text" name="title" required value="<?php echo ($detail['title']); ?>" lay-verify="required" class="layui-input">
                    </div>
                </div>

                <div class="layui-upload">
                    <label class="layui-form-label">商品图片</label>
                    <div class="layui-input-block">
                        <input type="text" class="layui-input" name="image" value="<?php echo ($detail['image']); ?>"  id="ad_img" style="display: inline-block;width: 400px;" />
                        <button type="button" class="layui-btn" id="upimg" style="display: inline-block">选择图片</button>
                        <div id="reupload" style="display: inline-block"></div>
                        <div class="layui-upload-list">
                            <img class="upload_img" src="<?php if(!empty($detail["image"])): echo ($attachment_url); echo ($detail['image']); endif; ?>" style="max-height:100px;"/>
                        </div>
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">售价</label>
                    <div class="layui-input-inline">
                        <input type="text" name="price" required value="<?php echo ($detail['price']?$detail['price']:0); ?>" lay-verify="required|money" class="layui-input" >
                    </div>
                    <div class="layui-input-inline">
                        <div class="layui-form-mid layui-word-aux">本商品线上销售价格</div>
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">参考价格</label>
                    <div class="layui-input-inline">
                        <input type="text" name="market_price" required value="<?php echo ($detail['market_price']?$detail['market_price']:0); ?>" lay-verify="required|money" class="layui-input" >
                    </div>
                    <div class="layui-input-inline">
                        <div class="layui-form-mid layui-word-aux">商品市场销售价格</div>
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">销售库存</label>
                    <div class="layui-input-inline">
                        <input type="text" name="stock" required value="<?php echo ($detail['stock']?$detail['stock']:0); ?>" lay-verify="required|number" class="layui-input" >
                    </div>
                    <div class="layui-input-inline">
                        <div class="layui-form-mid layui-word-aux">商品销售数量</div>
                    </div>
                </div>

                <div class="layui-form-item layui-form-text" id="description">
                    <label class="layui-form-label">商品描述</label>
                    <div class="layui-input-block">
                        <textarea placeholder="请填写商品描述" name="description" required lay-verify="required" class="layui-textarea"><?php echo ($detail['description']); ?></textarea>
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">商品状态</label>
                    <div class="layui-input-block pack-status">
                        <input type="radio" name="status" value="0" title="下架">
                        <input type="radio" name="status" value="1" title="上架">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">限制购买数量</label>
                    <div class="layui-input-inline">
                        <input type="text" name="limit_buy" required value="<?php echo ($detail['limit_buy']?$detail['limit_buy']:0); ?>" lay-verify="number" class="layui-input" >
                    </div>
                    <div class="layui-input-inline">
                        <div class="layui-form-mid layui-word-aux">每单购买该商品的数量</div>
                    </div>
                </div>

                <div class="layui-form-item">
                    <div class="layui-input-block">
                        <button class="layui-btn" lay-submit lay-filter="admin-form">立即提交</button>
                        <a  href="<?php echo U('index',['merchant_id' => $detail['merchant_id']]);?>" class="layui-btn layui-btn-danger">返回列表</a>
                    </div>
                </div>
            </form>
        </div>

</div>

    <script type="text/javascript">
        $(function () {
            //设置生日特权值
            $('.pack-type input').val([<?php echo ((isset($detail['type']) && ($detail['type'] !== ""))?($detail['type']):1); ?>]);
            //设置免预定金值
            $('.pack-status input').val([<?php echo ((isset($detail['status']) && ($detail['status'] !== ""))?($detail['status']):0); ?>]);

            layui.use(['form','upload', 'laydate'], function(){
                var form = layui.form,
                    layer = layui.layer,
                    upload = layui.upload,
                    laydate = layui.laydate;

                //普通图片上传
                var uploadInst = upload.render({
                    elem: '#upimg'
                    ,url: "<?php echo U('Upload/index', ['mold' => 'goods']);?>"
                    ,done: function(res){
                        if (res.code != 0){
                            return layer.msg(res.msg);
                        }
                        //显示上传成功的图片
                        $('#ad_img').val(res.data.src);
                        $('.upload_img').prop('src','<?php echo ($attachment_url); ?>'+res.data.src);

                    }
                    ,error: function(){
                        //演示失败状态，并实现重传
                        var demoText = $('#reupload');
                        demoText.html('<span style="color: #FF5722;">上传失败</span> <a class="layui-btn layui-btn-mini demo-reload">重试</a>');
                        demoText.find('.demo-reload').on('click', function(){
                            uploadInst.upload();
                        });
                    }
                });

                form.verify({
                    money: [/^[0-9]+(\.[0-9]{1,2})?$/, '金额填写不正确']
                });

                //显示隐藏生日文本框
                //var value = $('#birthday .layui-form-radio').prev('input').val();
                var value = $('#birthday input:checked').val();
                if (value == 1){
                    $('#birthday_content_box').show();
                }else {
                    $('#birthday_content_box').hide();
                }


                $('#birthday .layui-form-radio').click(function () {
                    var value = $(this).prev('input').val();
                    if (value == 1){
                        $('#birthday_content_box').show();
                    }else {
                        $('#birthday_content_box').hide();
                    }
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