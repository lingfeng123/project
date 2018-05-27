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
    
    <fieldset class="layui-elem-field">
        <legend>商户列表</legend>
        <div class="layui-field-box">
            <form  class="layui-form" action="<?php echo U('index');?>" target="_self">
                <div class="layui-inline">
                    <input class="layui-input" name="keywords" value="" placeholder="">
                </div>
                <div class="layui-inline">
                    <select name="status">
                        <option value="">商户状态</option>
                        <option value="2">正常</option>
                        <option value="1">未审核</option>
                        <option value="0">封禁</option>
                    </select>
                </div>
                <button type="submit" class="layui-btn">搜索</button>
                <a href="<?php echo U('add');?>" style="float: right"><span class="layui-btn layui-btn-normal api-add"> <i class="layui-icon">&#xe608;</i> 新增商户</span></a>
            </form>
            <table class="layui-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>商户名称</th>
                    <th>联系电话</th>
                    <th>商户状态</th>
                    <th>营业时间</th>
                    <th>平均消费(元)</th>
                    <th>入驻时间</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if(is_array($lists)): $i = 0; $__LIST__ = $lists;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                        <td width="40"><?php echo ($vo['id']); ?></td>
                        <td width="200"><a href="javascript:;" data-url="<?php echo U('detail', array('id' => $vo['id']));?>" class="click-show"><?php echo ($vo['title']); ?> <i class="layui-icon">&#xe615;</i></a> <img src="<?php echo ($vo['logo']); ?>" /> </td>
                        <td width="110"><?php echo ($vo['tel']); ?></td>
                        <td width="60" align="center">
                            <?php if($vo['status'] == 1): ?>待审核<?php endif; ?>
                            <?php if($vo['status'] == 2): ?>正常<?php endif; ?>
                            <?php if($vo['status'] == 0): ?>封禁<?php endif; ?>
                        </td>
                        <td width="100"><?php echo substr($vo['begin_time'], 0, -3);?> - <?php echo substr($vo['end_time'], 0, -3);?></td>
                        <td width="100"><?php echo ($vo['avg_consume']); ?></td>
                        <td width="150"><?php echo ($vo['created_time']); ?></td>
                        <td width="490">
                            <a href="<?php echo U('Merchant/setStock', array('merchant_id' => $vo['id']));?>"><span class="layui-btn layui-btn-small layui-btn-default">库存</span></a>
                            <a href="<?php echo U('GoodsPack/index', array('merchant_id' => $vo['id']));?>"><span class="layui-btn layui-btn-small layui-btn-default">商品</span></a>
                            <a href="<?php echo U('edit', array('id' => $vo['id']));?>"><span class="layui-btn layui-btn-small edit layui-btn-normal">修改</span></a>
                            <a href="<?php echo U('del', array('id'=> $vo['id']));?>" onclick="return confirm('确定更新状态?'); return false"><span class="layui-btn layui-btn-small layui-btn-danger confirm"><?php if($vo['status'] == 2): ?>封禁<?php else: ?>解封<?php endif; ?></span></a>
                        </td>
                    </tr><?php endforeach; endif; else: echo "" ;endif; ?>
                <tr>
                    <td align="right" nowrap="true" colspan="11">
                        <div class="pagination">
                            <?php echo ($pageHtml); ?>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </fieldset>

</div>

<script type="text/javascript">
    layui.use(['layer','form'], function() {
        var layer = layui.layer //弹层
            ,form = layui.form;
        $('.click-show').click(function () {
            var url = $(this).attr('data-url');
            console.log(url);
            //显示数据弹窗
            layer.open({
                type: 2,
                title: "商户详情查看",
                area: ['800px', '600px'],
                skin: 'layui-layer-rim', //加上边框
                content: url
            });
        });
    });

    //修改排序
    $(".sort_merchant").blur(function(){
        console.log(000);
        var ajaxUrl = "<?php echo U('Merchant/sort');?>";
        var merchant_id = $(this).attr('data-mchid');
        var sort_number =  $(this).val();
        $.get(ajaxUrl, {merchant_id:merchant_id, sort_number: sort_number}, function(data){
            if (data.code == 0){
                alert('修改排序失败');
            }
        },'json');
    });

</script>

</body>
</html>