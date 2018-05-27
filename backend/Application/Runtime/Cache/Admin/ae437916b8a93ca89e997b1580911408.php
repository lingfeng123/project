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
        <legend>员工推广结算</legend>

        <div class="layui-field-box">
            <!--<a href="javascript:;" data-url="<?php echo U('Message/add');?>" class="click-show-member"><span class="layui-btn layui-btn-normal api-add"> <i-->
                    <!--class="layui-icon">&#xe608;</i> 新增</span></a>-->
            <table class="layui-table">
                <thead>
                <tr>
                    <th>序号</th>
                    <th>员工姓名</th>
                    <th>提现金额</th>
                    <th>提现状态</th>
                    <th>支付宝账号</th>
                    <th>申请时间</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                        <td><?php echo ($i); ?></td>
                        <td><?php echo ($vo['realname']); ?></td>
                        <td><?php echo ($vo['total_money']); ?></td>
                        <td>
                            <?php
 if($vo['status'] ==1 ){ echo '审核中'; }else if($vo['status'] ==2 ){ echo '提现中'; }else{ echo '提现完成'; } ?>
                        </td>
                        <td><?php echo ($vo["account"]); ?></td>
                        <td><?php echo date('Y-m-d H:i:s',$vo['create_time']);?></td>
                        <td align="center">
                            <?php if($vo['status'] == 1): ?><a href="<?php echo U('SpreadExpressive/validate', array('id' => $vo['id']));?>;"><span class="layui-btn layui-btn-small edit layui-btn-primary">提现</span></a><?php elseif($vo['status'] == 2): ?><a href="<?php echo U('SpreadExpressive/finishExpressive', array('id' => $vo['id']));?>" ><span class="layui-btn layui-btn-small edit layui-btn-primary">完成</span></a><?php else: ?>已完成<?php endif; ?>
                            <!--<a href="javascript:;" data-url="<?php echo U('del', array('id' => $vo['id']));?>" class="click-show-member"><span class="layui-btn layui-btn-small edit layui-btn-primary">删除</span></a>-->
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
        layui.use(['layer'], function() {
            var layer = layui.layer //弹层
            $('.click-show-member').click(function () {
                var url = $(this).attr('data-url');
                //显示数据弹窗
//                layer.open({
//                    type: 2,
//                    title: "查看用户信息",
//                    area: ['800px', '600px'],
//                    skin: 'layui-layer-rim', //加上边框
//                    content: url
//                });
            });

        });
    </script>

</body>
</html>