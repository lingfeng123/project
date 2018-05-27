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
            <div class="grid-demo-bg1">所属商户名称</div>
        </div>
        <div class="layui-col-xs9">
            <div class="grid-demo"><?php echo ($detail["merchant_title"]); ?></div>
        </div>
    </div>

    <div class="layui-row">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">商品名称</div>
        </div>
        <div class="layui-col-xs9">
            <div class="grid-demo"><?php echo ($detail["title"]); ?></div>
        </div>
    </div>

    <div class="layui-row">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">商品类别</div>
        </div>
        <div class="layui-col-xs9">
            <div class="grid-demo">
                <?php if($detail["type"] == 1): ?>优惠套餐
                    <?php elseif($detail["type"] == 2): ?>
                    卡座套餐
                    <?php elseif($detail["type"] == 3): ?>
                    单品酒水<?php endif; ?>
            </div>
        </div>
    </div>

    <div class="layui-row layui-form-item">
        <div class="layui-col-xs3">
            <div class="layui-form-label">查看图片</div>
        </div>
        <div class="layui-input-inline">
            <?php if(isset($detail['image'])): ?><img src="<?php echo ($attachment_url); echo ($detail["image"]); ?>" width="150" height="100"><?php endif; ?>
        </div>
    </div>

    <div class="layui-row">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">参考售价</div>
        </div>
        <div class="layui-col-xs9">
            <div class="grid-demo"><?php echo ($detail["price"]); ?></div>
        </div>
    </div>

    <div class="layui-row">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">商品状态</div>
        </div>
        <div class="layui-col-xs9">
            <div class="grid-demo">
                <?php if($detail["status"] == 1): ?>销售中
                    <?php else: ?>
                    已下架<?php endif; ?>
            </div>
        </div>
    </div>

    <div class="layui-row">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">市场价格</div>
        </div>
        <div class="layui-col-xs9">
            <div class="grid-demo"><?php echo ($detail["market_price"]); ?></div>
        </div>
    </div>

    <div class="layui-row">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">进货价格</div>
        </div>
        <div class="layui-col-xs9">
            <div class="grid-demo"><?php echo ($detail["purchase_price"]); ?></div>
        </div>
    </div>

    <div class="layui-row">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">添加时间</div>
        </div>
        <div class="layui-col-xs9">
            <div class="grid-demo"><?php echo (date("Y-m-d h:i:s",$detail["created_time"])); ?></div>
        </div>
    </div>

    <div class="layui-row">
        <div class="layui-col-xs3">
            <div class="grid-demo-bg1">商品描述</div>
        </div>
        <div class="layui-col-xs9">
            <div class="grid-demo"><?php echo ($detail["description"]); ?></div>
        </div>
    </div>



</div>


</body>
</html>