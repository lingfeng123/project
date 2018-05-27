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
    
    <h1>优惠券列表</h1>
    <div class="layui-field-box">
        <a href="<?php echo U('Coupon/add');?>"><span class="layui-btn layui-btn-normal api-add"> <i class="layui-icon">&#xe608;</i> 添加优惠券</span></a>
        <table class="layui-table">
            <thead>
            <tr>
                <th width="60">序号</th>
                <th width="200">优惠券编号</th>
                <th width="200">优惠券名称</th>
                <th width="180">商户名称</th>
                <th width="100">有效天数</th>
                <th width="100">优惠券类型</th>
                <th width="250">有效期</th>
                <th width="120">创建时间</th>
                <th width="180">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                    <td><?php echo ($i); ?></td>
                    <td><?php echo ($vo['card_no']); ?></td>
                    <td><?php echo ($vo['card_name']); ?></td>
                    <td><?php echo ($vo['title']); ?></td>
                    <td><?php echo ($vo['effective_time']); ?></td>
                    <td><?php echo ($type[$vo['card_type']]); ?></td>
                    <td><?php echo (date("Y年m月d日 -",$vo['start_time']>0 ? $vo['start_time'] :'')); echo (date("Y年m月d日",$vo['end_time']>0 ? $vo['end_time'] :'')); ?></td>
                    <td><?php echo (date("Y-m-d",$vo['created_time'])); ?></td>
                    <td>
                        <?php if($vo['status'] == 0): ?><a href="<?php echo U('Coupon/cardActivation', array('id'=> $vo['id'],'status'=>1));?>" ><span class="layui-btn layui-btn-normal confirm">激活</span></a><?php endif; ?>
                        <?php if($vo['status'] == 1): ?><a href="<?php echo U('Coupon/cardActivation', array('id'=> $vo['id'],'status'=>0));?>" ><span class="layui-btn layui-btn-danger confirm">封禁</span></a><?php endif; ?>
                        <a href="<?php echo U('Coupon/edit', array('id'=> $vo['id']));?>" ><span class="layui-btn confirm">编辑</span></a>

                    </td>
                </tr><?php endforeach; endif; else: echo "" ;endif; ?>
            <tr>
                <td align="right" nowrap="true" colspan="10">
                    <div class="pagination">
                        <?php echo ($pageHtml); ?>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
    </div>


</div>


</body>
</html>