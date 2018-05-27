<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo C('APP_NAME');?>管理后台</title>
    <link rel="stylesheet" href="/Public/layui/css/layui.css">
    <script type="text/javascript" src="/Public/layui/layui.js"></script>
    <script src="/Public/js/jquery.min.js"></script>
    
</head>
<body>
<div style="margin: 15px;">
    
    <fieldset class="layui-elem-field">
        <legend>权限管理 - 权限组成员列表</legend>
        <div class="layui-field-box">
            <table class="layui-table" lay-even>
                <thead>
                <tr>
                    <th>#</th>
                    <th>用户账号</th>
                    <th>用户昵称</th>
                    <th>登录次数</th>
                    <th>最后登录时间</th>
                    <th>最后登录IP</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                        <td><?php echo ($i); ?></td>
                        <td><?php echo ($vo['username']); ?></td>
                        <td><?php echo ($vo['nickname']); ?></td>
                        <td><?php echo intval($vo['loginTimes']);?></td>
                        <td><?php echo (empty($vo['lastLoginTime'])?'该用户未曾登录过':date('Y-m-d H:i:s', $vo['lastLoginTime']));?></td>
                        <td><?php echo (empty($vo['lastLoginIp'])?'该用户未曾登录过':long2ip($vo['lastLoginIp']));?></td>
                        <td>
                            <?php if($vo['status']): ?><span style="border-radius: 2px;background-color: #5FB878;padding:5px 10px;color: #ffffff">启用</span>
                                <?php else: ?>
                                <span style="border-radius: 2px;background-color: #FF5722;padding:5px 10px;color: #ffffff">禁用</span><?php endif; ?>
                        </td>
                        <td>
                            <span class="layui-btn layui-btn-danger confirm" data-uid="<?php echo ($vo['uid']); ?>" data-info="你确定踢出当前用户么？" data-url="<?php echo U('delMember');?>">删除</span>
                        </td>
                    </tr><?php endforeach; endif; else: echo "" ;endif; ?>
                </tbody>
            </table>
        </div>
    </fieldset>

</div>

    <script>
        layui.use(['layer'], function() {
            $('.confirm').on('click', function () {
                var ownObj = $(this);
                layer.confirm(ownObj.attr('data-info'), {
                    btn: ['确定','取消'] //按钮
                }, function(){
                    $.ajax({
                        type: "POST",
                        url: ownObj.attr('data-url'),
                        data: {uid:ownObj.attr('data-uid'),groupId:<?php echo I("get.group_id");?>},
                        success: function(msg){
                            if( msg.code == 1 ){
                                location.reload();
                            }else{
                                layer.msg(msg.msg, {
                                    icon: 5,
                                    shade: [0.6, '#393D49'],
                                    time:1500
                                });
                            }
                        }
                    });
                });
            });
        });
    </script>

</body>
</html>