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
    
    <div class="layui-row">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">商户名称</div>
        </div>
        <div class="layui-col-xs9">
            <div class="grid-demo"><?php echo ($detail["title"]); ?></div>
        </div>
    </div>

    <div class="layui-row layui-form-item">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">商户标志</div>
        </div>
        <div class="layui-col-xs9">
            <?php if(isset($detail['logo'])): ?><img src="<?php echo C('ATTACHMENT_URL');?>/<?php echo ($detail["logo"]); ?>" width="150"><?php endif; ?>
        </div>
    </div>

    <div class="layui-row layui-form-item">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">商户简介</div>
        </div>
        <div class="layui-col-xs9"><?php echo ($detail["description"]); ?></div>
    </div>

    <div class="layui-row">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">电话号码</div>
        </div>
        <div class="layui-col-xs9">
            <?php echo ($detail["tel"]); ?>
        </div>
    </div>

    <div class="layui-row layui-form-item">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">商户相册</div>
        </div>
        <div class="layui-col-xs9">
            <?php if(!empty($detail['image'])): if(is_array($$detail["image"])): $i = 0; $__LIST__ = $$detail["image"];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$row): $mod = ($i % 2 );++$i;?><img src="//attachment.sc-csj.cn/<?php echo ($row); ?>" width="150" height="100" /><?php endforeach; endif; else: echo "" ;endif; endif; ?>
        </div>
    </div>

    <div class="layui-row">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">套餐销售价格</div>
        </div>
        <div class="layui-col-xs9">
            <?php if($detail['status'] == 1): ?>待审核<?php endif; ?>
            <?php if($detail['status'] == 2): ?>正常<?php endif; ?>
            <?php if($detail['status'] == 0): ?>封禁<?php endif; ?>
        </div>
    </div>

    <div class="layui-row">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">商户地址</div>
        </div>
        <div class="layui-col-xs9">
            <?php echo ($detail["address"]); ?>
        </div>
    </div>

    <div class="layui-row">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">营业时间</div>
        </div>
        <div class="layui-col-xs9">
            <?php echo ($detail["begin_time"]); ?> - <?php echo ($detail["end_time"]); ?>
        </div>
    </div>

    <div class="layui-row">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">商户标签</div>
        </div>
        <div class="layui-col-xs9">
                <?php if(is_array($$detail["tags"])): $i = 0; $__LIST__ = $$detail["tags"];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$row): $mod = ($i % 2 );++$i; echo ($row); endforeach; endif; else: echo "" ;endif; ?>
        </div>
    </div>

    <div class="layui-row">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">最低消费</div>
        </div>
        <div class="layui-col-xs9">
            <?php echo ($detail["avg_consume"]); ?> 元
        </div>
    </div>

    <div class="layui-row">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">店铺公告</div>
        </div>
        <div class="layui-col-xs9">
            <div class="grid-demo"><?php echo ($detail["notice"]); ?></div>
        </div>
    </div>

    <div class="layui-row">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">卡座预定周期</div>
        </div>
        <div class="layui-col-xs9">
            <div class="grid-demo"><?php echo ($detail["preordain_cycle"]); ?> 天</div>
        </div>
    </div>



</div>


</body>
</html>