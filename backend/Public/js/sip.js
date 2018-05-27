/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function setImagePreview(id) {
    var docObj = document.getElementById("doc");
var img_id = id+"_a";
alert(img_id);
    var imgObjPreview = document.getElementById(img_id);
    if (docObj.files && docObj.files[0])
             {
                //火狐下，直接设img属性
                        imgObjPreview.style.display = 'block';
                imgObjPreview.style.width = '150px';
                imgObjPreview.style.height = '180px';

                //火狐7以上版本不能用上面的getAsDataURL()方式获取，需要一下方式
                        imgObjPreview.src = window.URL.createObjectURL(docObj.files[0]);
                }
     else
             {
                //IE下，使用滤镜
                        docObj.select();
                var imgSrc = document.selection.createRange().text;
                var localImagId = document.getElementById("localImag");
                //必须设置初始大小
                        localImagId.style.width = "150px";
                localImagId.style.height = "180px";
                //图片异常的捕捉，防止用户修改后缀来伪造图片
                        try {
                            localImagId.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale)";
                            localImagId.filters.item("DXImageTransform.Microsoft.AlphaImageLoader").src = imgSrc;
                            }
                 catch (e)
                         {
                            alert("您上传的图片格式不正确，请重新选择!");
                            return false;
                            }
                imgObjPreview.style.display = 'none';
                document.selection.empty();
                }
    return true;
}

//添加html代码
function add_file()
{
    var s = '<img id="preview" src="http://pic.cnblogs.com/face/1023040/20160923152523.png" width="150" height="180" style="display: block; width: 150px; height: 180px;">';
    document.getElementById("localImag").innerHTML += s;
}

function add_file01()
{
//var img = document.createElement('img');
//img.src = 'http://pic.cnblogs.com/face/1023040/20160923152523.png';
//img.width= '150';
//img.height='180';
//img.style='display: block; width: 150px; height: 180px;';
//var div = document.getElementById("localImag");
//div.appendChild(img);
var div_locl = document.createElement('div');
//div_locl.id = "localImag";
var img = document.createElement('img');
img.src = 'https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1503406297738&di=f0b3005678ab8ed52cc09bff177cf54b&imgtype=0&src=http%3A%2F%2Fdl.bizhi.sogou.com%2Fimages%2F2013%2F09%2F10%2F378381.jpg';
img.width= '150';
img.height='180';
img.style='display: inline-block; width: 150px; height: 180px;';
div_locl.appendChild(img);
var fl = document.createElement('input');
fl.type = "file";
fl.name = "images[]";
fl.onchange = "javascript:setImagePreview();";
fl.style = "width:500px;";
var div_p = document.createElement('div');
div_p.style = "width:33%;display: inline-block;";
div_p.appendChild(fl);
div_p.appendChild(div_locl);
var div_m = document.getElementById("m_images");
div_m.appendChild(div_p);
}


function testpost()
{
//    var ul = "{:U('Membera/MerchantAdmin/testpost')}";
//    alert(ul);
  $.post("testpost",{suggest:'1'},function(result){
    if(result)
    {
        alert(result['status']);
    }
  });

}


function del_html()
{
     document.cookie = "userId=828";
     var ck =  document.cookie.split(";")[0].split("=")[1];
     alert(ck);
//var str = 'asdasd';
//document.getElementById("img_src").value = str;
}