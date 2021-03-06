<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo C('APP_NAME');?>管理后台</title>
    <script type="text/javascript" src="/Public/js/jquery.min.js"></script>
    <link rel="stylesheet" href="/Public/layui/css/layui.css">
    <style>
        .layui-nav-child .layui-nav-item {
            padding-left: 15px;
        }

        .layui-layout-admin .layui-body {
            top: 0;
            bottom: 0
        }

        .layui-layout-admin .layui-header {
            width: 200px;
        }

        .admin-info {
            position: absolute;
            top: 0;
            left: 0;
            height: 60px;
            background: none;
        }

        .admin-info li a.nickname {
            left: 0 !important;
            width: auto !important;
        }

        .admin-info .layui-nav-child {
            z-index: 999 !important;
        }

        .admin-info .layui-nav-bar {
            display: none !important;
        }
    </style>
</head>

<body>
<!-- 布局容器 -->
<div class="layui-layout layui-layout-admin">
    <!-- 头部 -->
    <div class="layui-header" style="padding: 0;margin: 0;">
        <div class="layui-main" style="padding: 0;margin: 0;">
            <!-- 水平导航 -->
            <ul class="layui-nav admin-info">
                <li class="layui-nav-item">
                    <a href="javascript:;" class="nickname">您是：<?php echo ($userInfo["nickname"]); ?></a>
                    <dl class="layui-nav-child">
                        <dd class="api-add"><a href="javascript:;">个人信息</a></dd>
                        <dd><a href="<?php echo U('Login/logOut');?>">退出登录</a></dd>
                    </dl>
                </li>
            </ul>
        </div>
    </div>

    <!-- 侧边栏 -->
    <div class="layui-side layui-bg-black">

        <div class="layui-side-scroll">
            <ul class="layui-nav layui-nav-tree" lay-filter="left-nav" style="border-radius: 0;">
            </ul>
        </div>
    </div>

    <!-- 主体 -->
    <div class="layui-body">
        <!-- 顶部切换卡 -->
        <div class="layui-tab layui-tab-brief" lay-filter="top-tab" lay-allowClose="true" style="margin: 0;">
            <ul class="layui-tab-title"></ul>
            <div class="layui-tab-content"></div>
        </div>
    </div>

    <!-- 底部 -->
    <div class="layui-footer" style="text-align: center; line-height: 44px;display: none">
        <strong>Copyright &copy; 2014-<?php echo date('Y');?> <a href=""><?php echo C('COMPANY_NAME');?></a>.</strong> All rights reserved.
    </div>
</div>

<script type="text/javascript" src="/Public/layui/layui.js"></script>
<script type="text/javascript">
    layui.config({
        base: '/Public/js/'
    });

    layui.use(['cms'], function () {
        var cms = layui.cms('left-nav', 'top-tab');
        cms.addNav(JSON.parse('<?php echo json_encode($list);?>'), 0, 'id', 'fid', 'name', 'url');
        //cms.bind(60 + 41 + 20 + 44); //头部高度 + 顶部切换卡标题高度 + 顶部切换卡内容padding + 底部高度
        cms.bind(60 + 10 + 0 + 0); //头部高度 + 顶部切换卡标题高度 + 顶部切换卡内容padding + 底部高度
        cms.clickLI(0);
    });

    layui.use(['layer'], function () {
        $('.api-add').on('click', function () {
            layer.open({
                type: 2,
                area: ['600px', '450px'],
                maxmin: true,
                content: '<?php echo U("Login/changeUser");?>'
            });
        });
        var updateTime = '<?php echo ($userInfo["updateTime"]); ?>';
        if (updateTime == 0) {
            /*layer.open({
             title: '初次登陆请重置密码！',
             type: 2,
             area: ['600px', '450px'],
             maxmin: true,
             closeBtn:0,
             content: '<?php echo U("Login/changeUser");?>'
             });*/
        } else {
            var nickname = '<?php echo ($userInfo["nickname"]); ?>';
            if (!nickname) {
                layer.open({
                    title: '初次登陆请补充真实姓名！',
                    type: 2,
                    area: ['600px', '450px'],
                    maxmin: true,
                    closeBtn: 0,
                    content: '<?php echo U("Login/changeUser");?>'
                });
            }
        }
    });

    //新订单通知
    showMessage();
    setInterval('showMessage()',1000 * 60);
    function showMessage() {
        $.get("<?php echo U('Order/neworder');?>", function (data) {
            if (data.total > 0) {
                layer.open(
                    {
                        anim:2, //2
                        shade:0,
                        btn: [],
                        content: '<audio autoplay="autoplay" style="display: none"><source src="/Public/images/music.mp3" type="audio/mpeg"></audio>' +
                        '<i class="layui-layer-ico layui-layer-ico6" style="position: static;display:block;margin: 0 auto; margin-bottom: 15px"></i>' +
                        '<div style="font-size: 17px; padding: 0 0 20px;text-align: center">您有 <span style="color: red;font-weight: bold; font-family: Arial">' + data.total + '</span> 个新订单来啦!</div>',
                        offset: 'rb',
                        title:'新订单通知'
                    });
            }
        });
    }
</script>
</body>
</html>