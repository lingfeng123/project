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
    
    <link rel="stylesheet" href="/Public/webupload/css/webuploader.css">
    <link rel="stylesheet" href="/Public/webupload/examples/image-upload/style.css">
    <script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=<?php echo C('LBS_AK.WEB');?>"></script>
    <script type="text/javascript" src="/Public/ui/layui.js"></script>
    <script type="text/javascript" src="/Public/js/webuploader.js"></script>
    <script type="text/javascript">var serverPath = "<?php echo U('Merchant/upload', array('dir' => 'merchant'));?>";</script>
    <script type="text/javascript" src="/Public/js/upload.js"></script>

    <div class="page-title"><?php echo (isset($detail['id'])?'编辑店铺':'新增店铺');?></div>
    <div class="layui-field-box" style="width: 800px">
        <form class="layui-form" action="" method="post">
            <?php if(isset($detail['id'])): ?><input type="hidden" name="id" value="<?php echo ($detail['id']); ?>"><?php endif; ?>
            <div class="layui-form-item">
                <label class="layui-form-label"><span style="color:red">*</span> 店铺名称</label>
                <div class="layui-input-block">
                    <input type="text" name="title" required value="<?php echo ($detail['title']); ?>" lay-verify="required" placeholder="请输入酒吧名称" class="layui-input">
                </div>
            </div>

            <div class="layui-upload">
                <label class="layui-form-label">店铺LOGO</label>
                <div class="layui-input-block">
                    <input type="hidden" name="logo" id="ad_img" value="<?php echo ($detail['logo']); ?>">
                    <button type="button" class="layui-btn" id="upimg" style="display: inline-block">选择图片</button>
                    <div id="reupload" style="display: inline-block"></div>
                    <div class="layui-upload-list">
                        <img class="upload_img" src="<?php if(!empty($detail["logo"])): echo ($attachment_url); echo ($detail['logo']); endif; ?>" style="max-height:100px;"/>
                    </div>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label"><span style="color:red">*</span> 店铺简介</label>
                <div class="layui-input-block">
                    <textarea placeholder="请输入酒吧简介" name="description" lay-verify="description" class="layui-textarea"><?php echo ($detail['description']); ?></textarea>
                </div>
            </div>

            <div class="layui-form-item layui-form-text">
                <label class="layui-form-label">联系号码</label>
                <div class="layui-input-inline">
                    <input type="text" name="tel" value="<?php echo ($detail['tel']); ?>" placeholder="例: 028-12345678" class="layui-input">
                </div>
                <div class="layui-input-inline">
                    <div class="layui-form-mid layui-word-aux">填写店铺座机 如: 028-12345678</div>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">店铺地址</label>
                <div class="layui-input-inline">
                    <input type="text" name="province" placeholder="省 例:四川省" value="<?php echo ($detail['province']); ?>" class="layui-input">
                </div>
                <div class="layui-input-inline">
                    <input type="text" name="city" placeholder="市 例:成都市" value="<?php echo ($detail['city']); ?>" class="layui-input">
                </div>
                <div class="layui-input-inline">
                    <input type="text" name="area" placeholder="区 例:锦江区" value="<?php echo ($detail['area']); ?>" class="layui-input">
                </div>
                <div class="layui-input-block" style="padding-top: 10px;clear: both">
                    <input type="text" name="address" placeholder="具体地址" value="<?php echo ($detail['address']); ?>" class="layui-input">
                </div>
            </div>


            <div class="layui-form-item">
                <label class="layui-form-label">搜索定位：</label>
                <div class="layui-input-block" style="z-index: 9999999!important;">
                    <div id="r-result"><input type="text" id="suggestId" style="width:500px;" style="z-index: 9999999!important;" class="layui-input" placeholder="输入要定位的地址" /></div>
                    <div id="searchResultPanel" style="border:1px solid #C0C0C0;width:150px;height:auto;z-index: 9999999!important; display:none;">
                    </div>
                </div>
            </div>
            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">百度经度：</label>
                    <div class="layui-input-inline">
                        <input type="number" id="bd_lng" name="lng" autocomplete="off" value="<?php echo ($detail['lng']); ?>" readonly="readonly" class="layui-input">
                    </div>
                </div>
                <div class="layui-inline">
                    <label class="layui-form-label">百度纬度：</label>
                    <div class="layui-input-inline">
                        <input type="number" id="bd_lat" name="lat" autocomplete="off" value="<?php echo ($detail['lat']); ?>" readonly="readonly" class="layui-input">
                    </div>
                </div>
            </div>

            <div style="margin: 0;padding: 0;position: relative">
                    <div id="l-map" style="position:absolute;right:-550px;top:-170px;width:500px; height:300px;border: 3px solid #ddd;background: #fff;"></div>
            </div>


            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label"><span style="color:red">*</span> 营业时间</label>
                    <div class="layui-input-inline">
                        <input type="text" name="begin_time" id="begin_time" lay-verify="required" placeholder="HH:mm:ss" value="<?php echo ($detail['begin_time']); ?>" autocomplete="off" class="layui-input">
                    </div>

                    <div class="layui-input-inline">
                        <input type="text" name="end_time" id="end_time" lay-verify="required" placeholder="HH:mm:ss" value="<?php echo ($detail['end_time']); ?>" autocomplete="off" class="layui-input">
                    </div>

                </div>
            </div>

            <div class="layui-form-item layui-form-text">
                <label class="layui-form-label"><span style="color:red">*</span> 平均消费</label>
                <div class="layui-input-inline">
                    <input type="text" name="avg_consume" value="<?php echo $detail['avg_consume'];?>" lay-verify="required" class="layui-input">
                </div>
                <div class="layui-input-inline">
                    <div class="layui-form-mid layui-word-aux">输入店铺平均消费 单位:元</div>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label"><span style="color:red">*</span> 店铺公告</label>
                <div class="layui-input-block">
                    <textarea placeholder="请输入酒吧公告内容" name="notice" class="layui-textarea"><?php echo $detail['notice'];?></textarea>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label"><span style="color:red">*</span> 店铺状态</label>
                <div class="layui-input-block adstatus">
                    <input type="radio" name="status" value="0" title="封禁">
                    <input type="radio" name="status" value="1" title="待审核">
                    <input type="radio" name="status" value="2" title="正常" checked>
                </div>
            </div>

            <?php if(!empty($detail["image_view"])): ?><div class="layui-form-item">
                <label class="layui-form-label"><span style="color:red">*</span> 历史相册</label>
                <div class="layui-input-block">
                    <?php if(is_array($detail['image_view'])): $i = 0; $__LIST__ = $detail['image_view'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$row): $mod = ($i % 2 );++$i;?><img src="<?php echo ($row); ?>" style="width: 100px; height: 80px; border: 3px solid #aaa;margin: 5px" /><?php endforeach; endif; else: echo "" ;endif; ?>
                </div>
            </div><?php endif; ?>

            <div class="layui-form-item">
                <label class="layui-form-label"><span style="color:red">*</span> 店铺相册</label>
                <div class="layui-input-block">
                    <input type="hidden" name="image" id="merchant_img_src"  value="<?php echo ($detail['image']); ?>">
                    <div id="wrapper">
                        <div id="container">
                            <!--头部，相册选择和格式选择-->
                            <div id="uploader">
                                <div class="queueList">
                                    <div id="dndArea" class="placeholder">
                                        <div id="filePicker"></div>
                                    </div>
                                </div>
                                <div class="statusBar" style="display:none;">
                                    <div class="progress">
                                        <span class="text">0%</span>
                                        <span class="percentage"></span>
                                    </div><div class="info"></div>
                                    <div class="btns">
                                        <div id="filePicker2"></div><div class="uploadBtn">开始上传</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                    <div class="layui-form-item">
                        <div class="layui-input-block">
                            <button class="layui-btn" lay-submit lay-filter="admin-form">立即提交</button>
                            <a  href="<?php echo U('index');?>" class="layui-btn layui-btn-danger">返回商户列表</a>
                        </div>
                    </div>
                    </form>
                </div>

</div>

<script type="text/javascript">
$(function () {
    $('#bar_tags input').val(<?php echo ($detail['tags']); ?>);
    $('.signed input').val([<?php echo ((isset($detail['signed']) && ($detail['signed'] !== ""))?($detail['signed']):0); ?>]);
    $('.adstatus input').val([<?php echo ((isset($detail['status']) && ($detail['status'] !== ""))?($detail['status']):2); ?>]);
    $('.open_buy input').val([<?php echo ((isset($detail['open_buy']) && ($detail['open_buy'] !== ""))?($detail['open_buy']):0); ?>]);

    layui.use(['form','upload', 'laydate'], function(){
        var form = layui.form,
            layer = layui.layer,
            upload = layui.upload,
            laydate = layui.laydate;


        //自定义验证规则
        form.verify({
            merchant_des: function(value){
                if(value.length <= 0){
                    return '商户简介不能为空';
                }
                if(value.length > 80){
                    return '商户简介不能不能超过80字';
                }
            },merchant_notice:function(value){
                if(value.length > 40){
                    return '商户公告不能不能超过40字';
                }
            }
        });

        //普通图片上传
        var uploadInst = upload.render({
            elem: '#upimg'
            ,url: "<?php echo U('Upload/index', ['mold' => 'merchant']);?>"
            ,done: function(res){
                if (res.code != 0){
                    return layer.msg(res.msg);
                }
                //显示上传成功的图片
                $('#ad_img').val(res.data.src);
                $('.upload_img').prop('src','<?php echo ($attachment_url); ?>'+res.data.src);

            }
            ,error: function(){
                //演示失败状态，并实现重传
                var demoText = $('#reupload');
                demoText.html('<span style="color: #FF5722;">上传失败</span> <a class="layui-btn layui-btn-mini demo-reload">重试</a>');
                demoText.find('.demo-reload').on('click', function(){
                    uploadInst.upload();
                });
            }
        });

        //时间选择器
        laydate.render({
            elem: '#begin_time'
            ,type: 'time'
        });
        laydate.render({
            elem: '#end_time'
            ,type: 'time'
        });
        laydate.render({
            elem: '#delay_time'
            ,type: 'time'
        });
    });
})
</script>
<script type="text/javascript">
// 百度地图API功能
function G(id) {
    return document.getElementById(id);
}

var map = new BMap.Map("l-map");
map.centerAndZoom("成都",12);                   // 初始化地图,设置城市和地图级别。

var ac = new BMap.Autocomplete(    //建立一个自动完成的对象
    {"input" : "suggestId"
    ,"location" : map
});

ac.addEventListener("onhighlight", function(e) {  //鼠标放在下拉列表上的事件
var str = "";
    var _value = e.fromitem.value;
    var value = "";
    if (e.fromitem.index > -1) {
        value = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
    }
    str = "FromItem<br />index = " + e.fromitem.index + "<br />value = " + value;

    value = "";
    if (e.toitem.index > -1) {
        _value = e.toitem.value;
        value = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
    }
    str += "<br />ToItem<br />index = " + e.toitem.index + "<br />value = " + value;
    G("searchResultPanel").innerHTML = str;
});

var myValue;
ac.addEventListener("onconfirm", function(e) {    //鼠标点击下拉列表后的事件
var _value = e.item.value;
    myValue = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
    G("searchResultPanel").innerHTML ="onconfirm<br />index = " + e.item.index + "<br />myValue = " + myValue;

    setPlace();
});

//显示地址到文本框
show_address();
<?php if(!empty($detail['lat'])): ?>init_place();<?php endif; ?>

//搜索选中地理位置
function setPlace(){
    map.clearOverlays();    //清除地图上所有覆盖物
    function myFun(){
        var pp = local.getResults().getPoi(0).point;    //获取第一个智能搜索的结果
        console.log(pp);

        document.getElementById('bd_lat').value = pp['lat'];
        document.getElementById('bd_lng').value = pp['lng'];

        map.centerAndZoom(pp, 18);
        map.addOverlay(new BMap.Marker(pp));    //添加标注
    }
    var local = new BMap.LocalSearch(map, { //智能搜索
      onSearchComplete: myFun
    });
    local.search(myValue);
}

function show_address() {
    setTimeout(function () {
        $('#suggestId').val('<?php echo ($detail['gps_address']); ?>');
    },1000)
}

/**
 * 初始化选中地理位置
 */
function init_place(){
//    map.clearOverlays();    //清除地图上所有覆盖物
    function myFun(){
        var pp = {lat:<?php echo ((isset($detail['lat']) && ($detail['lat'] !== ""))?($detail['lat']):30.66452); ?>, lng:<?php echo ((isset($detail['lng']) && ($detail['lng'] !== ""))?($detail['lng']):104.073485); ?>};
        document.getElementById('bd_lat').value = pp['lat'];
        document.getElementById('bd_lng').value = pp['lng'];

        map.centerAndZoom(pp, 18);
        map.addOverlay(new BMap.Marker(pp));    //添加标注
    }
    var local = new BMap.LocalSearch(map, { //智能搜索
        onSearchComplete: myFun
    });
    local.search(myValue);
}
</script>

</body>
</html>