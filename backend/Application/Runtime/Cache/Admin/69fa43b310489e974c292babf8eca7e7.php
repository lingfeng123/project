<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>welcome</title>
    <link rel="stylesheet" href="/Public/ui/css/layui.css">
    <script src="/Public/js/jquery.min.js"></script>
    <script type="text/javascript" src="/Public/ui/layui.js"></script>
    <script type="text/javascript" src="/Public/js/echarts.min.js"></script>
    <script type="text/javascript" src="/Public/js/shine.js"></script>
</head>
<body>
<style type="text/css">
    .data-bg{background: #f5f5f5!important; padding: 10px;}
    .data-warper{margin-bottom: 30px;}
    .data-box-block{border-radius: 2px; background-color: #fff; box-shadow: 0 1px 2px 0 #eee;border: 1px solid #eee;margin: 7px;}
    .title-name{height: 42px; line-height: 42px; font-weight: bold; padding: 0 15px; border-bottom: 1px solid #eee; color: #333; border-radius: 2px 2px 0 0; font-size: 14px; }
    .data-box{color: #555; padding: 20px 0 10px;}
    .data-box li{float: left; width: 180px; background: #fff; padding: 15px 0; margin: 0 0 20px 20px; border: 1px solid #dedede; line-height: 250%; text-align: center; border-radius: 3px;box-shadow: 0 1px 2px 0 #eee;}
    .data-box li h3{ font-size: 26px;}
    .data-box li strong{display: block;font-weight: normal; text-align: center; font-size: 15px;}
</style>

<div class="data-warper layui-row">
    <div class="layui-col-md6">
        <div class="data-box-block">
            <div class="title-name"><i class="layui-icon layui-icon-group">&#xe657;</i> 订单数据</div>
            <ul class="data-box">
                <li>
                    <strong>总完成订单数</strong>
                    <h3><?php echo ($successCount); ?></h3>
                </li>

                <li>
                    <strong>今日创建订单</strong>
                    <h3><?php echo ($todayOrdersCount); ?></h3>
                </li>

                <li>
                    <strong>今日未支付订单</strong>
                    <h3><?php echo ($todayQuxiaoCount); ?></h3>
                </li>

                <li>
                    <strong>今日拒绝订单</strong>
                    <h3><?php echo ($todayJujueCount); ?></h3>
                </li>

                <li>
                    <strong>今日完成订单</strong>
                    <h3><?php echo ($todaySuccessCount); ?></h3>
                </li>

                <li>
                    <strong>今日已完成订单总额</strong>
                    <h3><?php echo ($totalPayPrice); ?></h3>
                </li>

                <div style="clear: both"></div>
            </ul>
        </div>
    </div>

    <div class="layui-col-md6">
        <div class="data-box-block">
            <div class="title-name"><i class="layui-icon layui-icon-group">&#xe613;</i> 用户数据</div>
            <ul class="data-box">
                <li>
                    <a href="<?php echo U('Member/index');?>">
                        <strong>平台总用户数</strong>
                        <h3><?php echo ($memberTotal); ?></h3>
                    </a>
                </li>

                <li>
                    <a href="<?php echo U('Member/index', ['bind_tel' => 1]);?>">
                        <strong>平台已绑手机总用户数</strong>
                        <h3><?php echo ($memberBindTelTotal); ?></h3>
                    </a>
                </li>

                <li>
                    <a href="<?php echo U('Member/index', ['start_time' => date('Y-m-d')]);?>">
                        <strong>今日注册用户数</strong>
                        <h3><?php echo ($todayRegTotal); ?></h3>
                    </a>
                </li>

                <li>
                    <a href="<?php echo U('Member/index', ['bind_tel' => 1,'start_time' => date('Y-m-d')]);?>">
                        <strong>今日绑定手机用户数</strong>
                        <h3><?php echo ($todayBindTelTotal); ?></h3>
                    </a>
                </li>

                <li>
                    <strong>今日渠道注册用户数</strong>
                    <h3><?php echo ($channelTotal); ?></h3>
                </li>

                <li>
                    <strong>今日邀请注册用户数</strong>
                    <h3><?php echo ($promoterCodeTotal); ?></h3>
                </li>

                <div style="clear: both"></div>
            </ul>
        </div>
    </div>
</div>

<!--今日订单数-->
<!--<div class="data-warper layui-row">
    <div class="layui-col-md6">
        <div class="data-box-block">
            <div class="data-bg" id="order" style="height: 300px;"></div>
        </div>
    </div>

    <div class="layui-col-md6">
        <div class="data-box-block">
            <div class="data-bg" id="register" style="height: 300px;"></div>
        </div>
    </div>
</div>-->
<script type="text/javascript">
    layui.use(['element', 'layer'], function(){
        var element = layui.element;
        var layer = layui.layer;

        //监听折叠
        element.on('collapse(test)', function(data){
            layer.msg('展开状态：'+ data.show);
        });
    });
</script>
<script type="text/javascript">
    /*    var regsters = <?php echo json_encode($register);?>;
        var un_regster = <?php echo json_encode($un_register);?>;
        var status = <?php echo json_encode($order_type["order_type"]);?>;
        var status_num = <?php echo json_encode($order_type["num"]);?>;

    window.onload = function () {

        //饼状图
        var order = echarts.init(document.getElementById('order'),'shine');
        var  option2 = {
            title : {
                text: '今日订单统计',
                x:'center'
            },
            tooltip : {
                trigger: 'item',
                formatter: "{a} <br/>{b} : {c} ({d}%)"
            },
            legend: {
                orient : 'vertical',
                x : 'left',
                data:status
            },
            toolbox: {
                show : true,
                feature : {
                    mark : {show: true},
                    magicType : {
                        show: true,
                        type: ['pie', 'funnel'],
                        option: {
                            funnel: {
                                x: '25%',
                                width: '50%',
                                funnelAlign: 'left',
                                max: 1548
                            }
                        }
                    },
                    restore : {show: true},
                    saveAsImage : {show: true}
                }
            },
            calculable : true,
            series : [
                {
                    name:'订单状态',
                    type:'pie',
                    radius : '55%',
                    center: ['50%', '60%'],
                    data:status_num
                }
            ]
        };

        //柱状图
        var register = echarts.init(document.getElementById('register'),'shine');
        var option3 = {
            title : {
                text: '用户注册'
            },
            tooltip : {
                trigger: 'axis'
            },
            legend: {
                data:['未绑定','绑定']
            },
            toolbox: {
                show : true,
                feature : {
                    mark : {show: true},
                    restore : {show: true},
                    saveAsImage : {show: true}
                }
            },
            calculable : true,
            xAxis : [
                {
                    type : 'category',
                    data : ['注册用户']
                }
            ],
            yAxis : [
                {
                    type : 'value'
                }
            ],
            series : [
                {
                    name:'未绑定',
                    type:'bar',
                    data:[regsters],
                },
                {
                    name:'绑定',
                    type:'bar',
                    data:[un_regster],
                },
            ]
        };

        order.setOption(option2);
        register.setOption(option3);
    }*/
</script>

</body>
</html>