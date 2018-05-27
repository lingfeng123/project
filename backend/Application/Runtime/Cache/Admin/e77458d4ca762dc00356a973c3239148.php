<?php if (!defined('THINK_PATH')) exit(); if(C('LAYOUT_ON')) { echo ''; } ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0,viewport-fit=cover">
    <title>跳转提示</title>
    <link rel="stylesheet" href="https://cdn.bootcss.com/weui/1.1.2/style/weui.min.css"/>
</head>
<body>
<div class="page msg_success js_show">
    <div class="weui-msg">
        <div class="weui-msg__icon-area">
            <?php if(isset($message)) {?>
            <i class="weui-icon-success weui-icon_msg"></i>
            <?php }else{?>
            <i class="weui-icon-warn weui-icon_msg"></i>
            <?php }?>
        </div>
        <div class="weui-msg__text-area">
            <?php if(isset($message)) {?>
            <h2 class="weui-msg__title"><?php echo($message); ?></h2>
            <p class="weui-msg__desc">页面将在 <span id="wait"><?php echo($waitSecond); ?></span> 后自动 <a id="href" href="<?php echo($jumpUrl); ?>">跳转</a></p>
            <span id="hidden_s" style="display:none"><?php echo($waitSecond); ?></span>
            <?php }else{?>
            <h2 class="weui-msg__title"><?php echo($error); ?></h2>
            <p class="weui-msg__desc">页面将在 <span id="wait"><?php echo($waitSecond); ?></span> 后自动 <a id="href" href="<?php echo($jumpUrl); ?>">跳转</a></p>
            <span id="hidden_s" style="display:none"><?php echo($waitSecond); ?></span>
            <?php }?>
        </div>
        <div class="weui-msg__extra-area">
            <div class="weui-footer">
                <p class="weui-footer__links">
                    <a class="weui-footer__link">四川创时捷科技有限公司</a>
                </p>
                <p class="weui-footer__text">Copyright © 2017 KONGPINGZI</p>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    (function () {
        var wait = document.getElementById('wait'),
            href = document.getElementById('href').href;
        var interval = setInterval(function () {
            var time = --wait.innerHTML;
            if (time <= 0) {
                location.href = href;
                clearInterval(interval);
            };
        }, 1000);

        var hidden_s = document.getElementById('hidden_s');
        var interval_b = setInterval(function () {
            var time_b = --hidden_s.innerHTML;
            if (time_b <= 0) {
                WeixinJSBridge.call('closeWindow');
                clearInterval(interval_b);
            };
        }, 1000);
    })();
</script>
</body>
</html>