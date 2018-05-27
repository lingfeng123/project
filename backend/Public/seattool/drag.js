
$.fn.extend({
		//---元素拖动插件
    dragging:function(data){   
		var $this = $(this);
		var xPage;
		var yPage;
		var X;//
		var Y;//
		var father = $this.parent();
		var defaults = {
			hander:1
		};
		var opt = $.extend({},defaults,data);
		var hander = opt.hander;
		var arr = opt.arr;

		if(hander == 1){
			hander = $this; 
		}else{
			hander = $this.find(opt.hander);
		}

		//---初始化
		father.css({"position":"relative","overflow":"hidden"});
		$this.css({"position":"absolute"});
		hander.css({"cursor":"move"});

		var faWidth = father.width();
		var faHeight = father.height();
		var thisWidth = $this.width()+parseInt($this.css('padding-left'))+parseInt($this.css('padding-right'));
		var thisHeight = $this.height()+parseInt($this.css('padding-top'))+parseInt($this.css('padding-bottom'));
		
		var mDown = false;//
		var positionX;
		var positionY;
		var moveX ;
		var moveY ;

        //                矩形解析
        function getmatrix(a,b,c,d,e,f){
            var aa=Math.round(180*Math.asin(a)/ Math.PI);
            var bb=Math.round(180*Math.acos(b)/ Math.PI);
            var cc=Math.round(180*Math.asin(c)/ Math.PI);
            var dd=Math.round(180*Math.acos(d)/ Math.PI);
            var deg=0;
            if(aa==bb||-aa==bb){
                deg=dd;
            }else if(-aa+bb==180){
                deg=180+cc;
            }else if(aa+bb==180){
                deg=360-cc||360-dd;
            }
            return deg>=360?0:deg;
            //return (aa+','+bb+','+cc+','+dd);
        }

		hander.mousedown(function(e){

			father.children().css({"zIndex":"0"});
			$this.css({"zIndex":"1"});
			mDown = true;
			X = e.pageX;
			Y = e.pageY;

			positionX = $this.position().left;
			positionY = $this.position().top;

            if('get'+$this.css('transform') == 'getnone'){
                var deg = 0
            }else {
                var deg=eval('get'+$this.css('transform'));
            }
            if(deg != 0 && deg != 180){
                positionX -= 28
                positionY += 28
            }
			return false;
		});
			
		$(document).mouseup(function(e){
			mDown = false;
            if(arr[$this.index()] != undefined){
                arr[$this.index()].axis_x = $this.position().left
                arr[$this.index()].axis_y = $this.position().top
			}

		});
			
		$(document).mousemove(function(e){

			xPage = e.pageX;//--
			moveX = positionX+xPage-X;


			yPage = e.pageY;//--
			moveY = positionY+yPage-Y;
            if(mDown){
                // console.log('移动')
                // console.log("移动的物体X是："+moveX)
                // console.log("移动的鼠标X是："+xPage)
                // console.log("移动的物体Y是："+moveY)
                // console.log("移动的鼠标Y是："+yPage)
            }


			function thisAllMove(){ //全部移动
				if(mDown == true){
					$this.css({"left":moveX,"top":moveY});
				}else{
					return;
				}
				if(moveX < 0){

					$this.css({"left":"0"});
				}
				if(moveX > (faWidth-thisWidth)){
					$this.css({"left":faWidth-thisWidth});
				}

				if(moveY < 0){
					$this.css({"top":"0"});
				}
				if(moveY > (faHeight-thisHeight)){
					$this.css({"top":faHeight-thisHeight});
				}
			}
				thisAllMove();

		});
    }

}); 