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
    
    <script type="text/javascript" src="/Public/ui/layui.js"></script>

        <h3>商户结算</h3>
        <div class="layui-field-box">
            <form method="get" action="<?php echo U('Finance/orderlist');?>">
                    <div class="layui-inline" >
                        <label class="layui-form-label" style="width: 66px"><span style="color:red">*</span> 开始时间</label>
                        <div class="layui-input-inline">
                            <input type="text" name="start" id="start" lay-verify="date" required placeholder="" value="<?php echo ($_GET['start']); ?>" autocomplete="off" class="layui-input">
                        </div>
                    </div>
                <div class="layui-inline" >
                    <label class="layui-form-label" style="width: 66px"><span style="color:red">*</span> 结束时间</label>
                    <div class="layui-input-inline">
                        <input type="text" name="end" id="end" lay-verify="date" required  placeholder="" value="<?php echo ($_GET['end']); ?>" autocomplete="off" class="layui-input">
                        <input type="hidden" name="merchant_id" id="merchant_id"  value="<?php echo $_GET['id'];?>" class="layui-input">
                    </div>
                </div>
                    <input class="layui-btn" type="submit" value="添加结算">
                    <!--<a href="#"><span class="layui-btn layui-btn-normal api-add"> <i class="layui-icon">&#xe608;</i> 添加结算</span></a>-->
            </form>
            <table class="layui-table">
                <thead>
                <tr>
                    <th>序号</th>
                    <th>结算编号</th>
                    <th>结算金额</th>
                    <th>结算订单数</th>
                    <th>结算状态</th>
                    <th>结算起止时间</th>
                    <th>结算时间</th>
                    <th>操作人员</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                        <td><?php echo ($vo['id']); ?></td>
                        <td><?php echo ($vo['settlement_number']); ?></td>
                        <td><?php echo ($vo['money']); ?></td>
                        <td><?php echo ($vo['order_total']); ?></td>
                        <td><?php if($vo['status'] == 1): ?>已结算<?php else: ?>未结算<?php endif; ?></td>
                        <td><?php echo date('Y-m-d H:i:s',$vo['create_time']);?></td>
                        <td><?php echo date('Y-m-d',$vo['begin_time']);?> - <?php echo date('Y-m-d',$vo['end_time']);?></td>
                        <td><?php echo ($vo['operate_account']); ?></td>
                        <td align="center">
                            <a href="<?php echo U('Finance/detail', array('id' => $vo['id']));?>"><span class="layui-btn layui-btn-small edit layui-btn-primary">详情</span></a>
                            <!--<a href="<?php echo U('Finance/del', array('id' => $vo['id']));?>;" class="click-show-member" onclick="return confirm('是否删除已结算记录,删除后结算记录不能恢复')"><span class="layui-btn layui-btn-small edit layui-btn-primary">删除</span></a>-->
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


</div>

    <script type="text/javascript">
        $(function () {
            $('.adstatus input').val([1]);

            layui.use(['form','laydate'], function(){
                var form = layui.form,
                    layer = layui.layer,
                    laydate = layui.laydate;

                //时间选择器
                laydate.render({
                    elem: '#start',
                    max: <?php echo C('Financelimit');?>,
                });
                laydate.render({
                    elem: '#end',
                    max: <?php echo C('Financelimit');?>,
                });
            });
        })

//        layui.use(['form','laydate'], function() {
//            var laydate = layui.laydate, form = layui.form;
//
//                //时间选择器
//            laydate.render({
//                elem: '#start',
//            });
//            laydate.render({
//                elem: '#end',
//                max: <?php echo C('Financelimit');?>,
//            });
//
//        });

    </script>

</body>
</html>