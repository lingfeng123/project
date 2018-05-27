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
    
        <div class="page-title">注册用户列表</div>

        <button class="layui-btn layui-btn-danger layui-btn-sm layui-btn-radius" onclick="location.reload();" style="float: right">
            <i class="layui-icon layui-anim layui-anim-rotate layui-anim-loop">&#x1002;</i> 刷新
        </button>
        <form class="layui-form" method="get" action="<?php echo U('Member/index');?>">
            <div class="layui-inline">
                <input class="layui-input" type="text" placeholder="请输入电话或昵称" name="keywords" value="" />
            </div>
            <div class="layui-inline" style="width: 100px;">
                <select name="bind_tel">
                    <option value="">手机绑定</option>
                    <option value="1">已绑定</option>
                    <option value="2">未绑定</option>
                </select>
            </div>
            <div class="layui-inline" style="width: 70px;">
                <select name="sex">
                    <option value="">性别</option>
                    <option value="0">未知</option>
                    <option value="1">男</option>
                    <option value="2">女</option>
                </select>
            </div>
            <div class="layui-inline" style="width: 70px;">
                <select name="status">
                    <option value="">状态</option>
                    <option value="0">封禁</option>
                    <option value="1">正常</option>
                </select>
            </div>
            <div class="layui-inline" style="width: 100px;">
                <select name="is_auth">
                    <option value="">派对大使</option>
                    <option value="0">未认证</option>
                    <option value="1">已认证</option>
                </select>
            </div>
            <div class="layui-inline">
                <div class="layui-input-inline" style="width: 150px;">
                    <input type="text" id="start_time" name="start_time" placeholder="选择开始时间" autocomplete="off" class="layui-input">
                </div>
                <div class="layui-input-inline" style="width: 8px">-</div>
                <div class="layui-input-inline" style="width: 150px;">
                    <input type="text" id="stop_time" name="stop_time" placeholder="选择结束时间" autocomplete="off" class="layui-input">
                </div>
            </div>
            <div class="layui-inline">
                <button class="layui-btn" type="submit">搜索</button>
            </div>
        </form>


    <table class="layui-table">
                <thead>
                <tr>
                    <th>会员ID</th>
                    <th>手机号码</th>
                    <th>昵称</th>
                    <th>性别</th>
                    <th>派对大使</th>
                    <th>积分</th>
                    <th>会员等级</th>
                    <th>消费额度</th>
                    <th>赠送金额</th>
                    <th>充值金额</th>
                    <th>状态</th>
                    <th>注册时间</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if(is_array($member)): $i = 0; $__LIST__ = $member;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                        <td><?php echo ($vo['id']); ?></td>
                        <td><?php echo ($vo['tel']); ?></td>
                        <td><?php echo ($vo['nickname']); ?></td>
                        <td><?php if($vo['sex'] == 1): ?><i class="layui-icon" style="color: #00a2d4">&#xe662;</i><?php elseif($vo['sex'] == 2): ?><i class="layui-icon" style="color: #ff3ec9">&#xe661;</i><?php else: ?><i class="layui-icon">&#xe607;</i><?php endif; ?></td>
                        <td>
                            <?php if($vo['is_auth']): ?><i class="layui-icon" style="color: #5FB878">&#xe616;</i>
                                <?php else: ?>
                                <i class="layui-icon" style="color: #FF5722">&#x1006;</i><?php endif; ?>
                        </td>
                        <td><?php echo ($vo['coin']); ?></td>
                        <td><?php echo ($vo['level_name']); ?></td>
                        <td><?php echo ($vo['consume_money']); ?></td>
                        <td><?php echo ($vo['give_money']); ?></td>
                        <td><?php echo ($vo['recharge_money']); ?></td>
                        <td align="center">
                            <?php if($vo['status']): ?><i class="layui-icon" style="color: #5FB878">&#xe616;</i>
                            <?php else: ?>
                                <i class="layui-icon" style="color: #FF5722">&#x1006;</i><?php endif; ?>
                        </td>
                        <td><?php echo ($vo['created_time']); ?></td>
                        <td align="center">
                            <a href="javascript:;" data-url="<?php echo U('show', array('id' => $vo['id']));?>" class="click-show-member"><span class="layui-btn layui-btn-small edit layui-btn-primary">查看</span></a>
                            <a href="<?php echo U('Member/constractList', array('id' => $vo['id']));?>"><span class="layui-btn layui-btn-small edit layui-btn-primary">联系人</span></a>
                            <a href="<?php echo U('Member/orderList', array('id' => $vo['id']));?>"><span class="layui-btn layui-btn-small edit layui-btn-primary">充值订单</span></a>
                            <a href="<?php echo U('Member/consumeList', array('id' => $vo['id']));?>"><span class="layui-btn layui-btn-small edit layui-btn-primary">消费记录</span></a>
                            <a href="<?php echo U('Member/rechargeList', array('id' => $vo['id']));?>"><span class="layui-btn layui-btn-small edit layui-btn-primary">充值记录</span></a>

                            <?php if($vo['status']): ?><a href="<?php echo U('isclosure', array('id'=> $vo['id']));?>" onclick="return confirm('是否封禁此用户账号?'); return false"><span class="layui-btn layui-btn-small layui-btn-danger confirm">封禁</span></a>
                                <?php else: ?>
                                <a href="<?php echo U('isclosure', array('id'=> $vo['id']));?>" onclick="return confirm('是否恢复此用户账号?'); return false"><span class="layui-btn layui-btn-small layui-btn-success confirm">恢复</span></a><?php endif; ?>
                        </td>
                    </tr><?php endforeach; endif; else: echo "" ;endif; ?>
                <tr>
                    <td align="right" nowrap="true" colspan="13">
                        <div class="pagination">
                            <?php echo ($pageHtml); ?>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>

</div>

<script type="text/javascript">
layui.use(['layer', 'form', 'laydate'], function() {
    var layer = layui.layer, //弹层
        laydate = layui.laydate,
        form = layui.form;

    laydate.render({elem: '#start_time', type: 'datetime', format: 'yyyy-MM-dd'});
    laydate.render({elem: '#stop_time', type: 'datetime', format: 'yyyy-MM-dd'});

    $('.click-show-member').click(function () {
        var url = $(this).attr('data-url');
        console.log(url);
        //显示数据弹窗
        layer.open({
            type: 2,
            title: "查看用户信息",
            area: ['600px', '500px'],
            skin: 'layui-layer-rim', //加上边框
            content: url
        });
    });

});
</script>

</body>
</html>