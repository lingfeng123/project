<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/5
 * Time: 17:20
 */

namespace V1_1\Controller;


use Org\Util\Response;
use Org\Util\ReturnCode;

class OrderBarController extends BaseController
{
    private $orderModel;
    private $orderBarModel;
    private $orderMemberModel;
    private $bar_type=['1'=> '酒局','2'=>'派对'];

    public function _initialize()
    {
//        parent::_initialize();
        $this->orderModel=D('order');
        $this->orderBarModel=D('order_bar');
        $this->orderMemberModel=D('order_member');
    }

    /**
     * 拼吧列表（派对列表）
     * 根据经纬度计算距离(距离近的排在最前面)
     */
    public function barIndex()
    {
       $lat=I('post.lat','30.6823000');
       $lng=I('post.lng','103.9682410');
       $page=I('post.page',1);
       $page_size=I('post.page_size',C('PAGE.PAGESIZE'));


       //费用类型和拼吧主题筛选
       $keyword=I('post.keywords','');
       $pay_type=I('post.pay_type','');
       $bar_theme=I('post.bar_theme','');


       if(!is_numeric($page) && !is_numeric($page_size)){
           Response::error(ReturnCode::PARAM_WRONGFUL,'请求参数不正确');
       }

       //查询数据库
       $barlists=$this->orderModel->getBarList($lat,$lng,$page,$page_size,$keyword,$pay_type,$bar_theme);
       Response::setSuccessMsg('读取列表成功');
       Response::success($barlists);
    }

    /**
     * 获取筛选里面的东西
     */
    public function searchList()
    {
        $payment=C('PAY_TYPE');
        $bar_theme=C('BAR_THEME');
        $data['payment']=$payment;
        $data['bar_theme']=$bar_theme;
        Response::setSuccessMsg('数据读取成功');
        Response::success($data);
    }

    /**
     * 拼吧主题
     */
    public function barTheme()
    {
        $bartheme=C('BAR_THEME');
        Response::success($bartheme);
    }

    /**
     * 发起拼吧
     */
    public function barAdd()
    {
        $merchant_id=I('post.merchant_id','');
        $member_id=I('post.member_id','');
        $bar_type=I('post.bar_type','');
        $goods_id=I('post.goods_id','');
        $pay_price=I('post.pay_price','');
        $bar_theme=I('post.bar_theme','');
        $arrives_time=I('post.arrives_time','');
        $cost_type=I('post.cost_type','');
        $man_number=I('post.man_number',0);
        $woman_number=I('post.woman_number',0);
        $average_cost=I('post.average_cost','');
        $description=I('post.description','');

        if(is_numeric($merchant_id) && in_array($bar_type,[1,2]) && is_numeric($member_id)){
            Response::error(ReturnCode::PARAM_WRONGFUL,'请求参数不合法');
        }
        //判断是否传入支付金额
        if(empty($pay_price)) Response::error(ReturnCode::PARAM_WRONGFUL,'请传入正确的支付金额');
        //判断人均费用是否者却
        if(empty($average_cost)) Response::error(ReturnCode::PARAM_WRONGFUL,'人均费用不能为空');
        //判断主题选择
        if(empty($bar_theme)) Response::error(ReturnCode::PARAM_WRONGFUL,'请选择主题');
        //判断支付类型
        if(empty($cost_type)) Response::error(ReturnCode::PARAM_WRONGFUL,'请选择支付类型');
        //判断到店时间
        if(empty($arrives_time)) Response::error(ReturnCode::PARAM_WRONGFUL,'请选择到店时间');
        //判断到店人数男女不能同时为0
        if($man_number ==0 && $woman_number ==0) Response::error(ReturnCode::PARAM_WRONGFUL,'到店人数男,女不能同时为0');
        //验证订单描述不能太长
        $description = Tools::filterEmoji($description);
        $description = str_replace('|', '', $description);
        if (!empty($description) && strlen($description) > 100) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '订单描述不能超过100个字符');
        }

        //获取联系人信息
        $member=M('member')->field('realname,tel,sex,avatar')->where(['id'=>$member_id])->find();

        $total_price=$pay_price;
        $purchase_price=$pay_price*C('SERVICE_CHARGE');
        if($goods_id){
            //获取商品信息
            $goodpack=M('goods_pack')->where(['id'=>$goods_id,'status'=>1])->find();
            $pay_price=$goodpack['price'];
            $total_price=$goodpack['market_price'];
            $purchase_price=$goodpack['purchase_price'];
        }

        //查看费用类型 1 男A女免 2 男免女A 3 男女AA
        if($cost_type ===1 ){
           $average_cost=$pay_price/$man_number;
        }else if($cost_type ===2){
            $average_cost=$pay_price/$woman_number;
        }elseif($cost_type ===3){
            $average_cost=$pay_price/($man_number+$woman_number);
        }
        //计算平均费用
        $average_cost=sprintf("%.2f", ceil($average_cost*100)/100);
        //订单数据
        $order=[
          'merchant_id'  =>$merchant_id,
          'member_id'  =>$member_id,
          'realname'  =>$member['realname'],
          'tel'  =>$member['tel'],
          'sex'  =>$member['sex'],
          'avatar'  =>$member['avatar'],
          'bar_type'  =>$bar_type,
          'total_price'  =>$total_price,
          'purchase_price'  =>$purchase_price,
          'pay_price'  =>$pay_price,
          'bar_theme'  =>$bar_theme,
          'arrives_time'  =>$arrives_time,
          'cost_type'  =>$cost_type,
          'man_number'  =>$man_number,
          'woman_number'  =>$woman_number,
          'average_cost'  =>$average_cost,
          'description'  =>$description,
            'is_bar'     => 1,
        ];

        $data['buy_type'] = 1;
        $data['order']=$order;
        $data['goods']=$goodpack;
        //调用消息队列生成订单
        $data = json_encode($data);
        $option = C('RABBITMQ_OPTION');
        $fibonacci_rpc = new FibonacciRpcClient($option);
        $response = $fibonacci_rpc->call($data);

        $message = json_decode($response, true);
        if ($message['code'] == 200) {
            Response::success($message['data']);
        } else {
            Response::error(ReturnCode::INVALID_REQUEST, $message['msg']);
        }
    }

    /**
     * 拼吧详情
     * @param  $order_id  int  拼吧订单ID
     */
    public function barDetail()
    {
        $order_id=I('post.order_id','');
        if(!is_numeric($order_id)){
            Response::error(ReturnCode::PARAM_INVALID,'请求参数不合法');
        }

        $orderModel=D('order');
        //存在的订单,获取订单信息
        $order=$orderModel->getBarOrderInfo($order_id);
        if($order===false){
            Response::error(ReturnCode::DATA_EXISTS,'该订单不存在');
        }
        Response::setSuccessMsg('数据获取成功');
        Response::success($order);
    }

    /**
     * 参与拼吧
     * @param  $order_id  int  拼吧订单ID
     *
     */
    public function takePartBar()
    {
        $order_id=I('post.order_id');
        $member_id=I('post.member_id');
        if(!is_numeric($order_id) && !is_numeric($member_id)){
            Response::error(ReturnCode::PARAM_INVALID,'请求参数不合法');
        }
        $barModel=D('order_member');
        //判断用户是否存在未支付的,或者是当天已经存在拼吧订单了
        $member_bar=$barModel->where(['member_id'=>$member_id,'pay_status'=>1])->find();
        if($member_bar){
            Response::error(ReturnCode::INVALID_REQUEST,'你存在未支付的拼吧信息',$member_bar);
        };

        $orderModel=D('order');
        $bar_rs=$orderModel->takeBar($order_id,$member_id);
        if($bar_rs === false){
            Response::error(ReturnCode::DATA_EXISTS,$orderModel->getError());
        }
        Response::setSuccessMsg('您已成功参与拼吧');
        Response::success();
    }


    /**
     * 查看我的酒局,或者我的派对
     */
    public function myBarList()
    {
        $bar_type=I('post.bar_type','');
        $type=I('post.type','');
        $member_id=I('post.member_id','');
        $page=I('post.page',1);
        $pageSize=I('post.page_size',C('PAGE.PAGESIZE'));

        if(!is_numeric($member_id) && !is_numeric($type) && !is_numeric($bar_type)){
            Response::error(ReturnCode::PARAM_INVALID,'请求参数错误');
        }

        $orderModel=D('order');

        $barData=$orderModel->myWinePartList($type,$member_id,$bar_type,$page,$pageSize);
        if($barData===false){
            Response::error(ReturnCode::INVALID_REQUEST,$orderModel->getError());
        }

        Response::setSuccessMsg('拼吧列表请求成功');
        Response::success($barData);
    }

    /**
     * 拼吧订单详情
     */
    public function barInfo()
    {
        $order_id =I('post.order_id','');

        $orderModel=D('order');
        $barlist=$orderModel->getOrderInfo($order_id);
        if($barlist ===false ){
            Response::error(ReturnCode::INVALID_REQUEST,$orderModel->getError());
        }
        $data['list']=$barlist;
        Response::setSuccessMsg('拼吧订单详情获取成功');
        Response::success($data);

    }

    /**
     * 查看拼吧订单评论
     * $order_id  订单ID
     */
    public function lookComment()
    {
        $order_id=I('post.order_id','');
        $page=I('post.page',1);
        $pageSize=I('post.page_size',C('PAGE.PAGESIZE'));
        if(is_numeric($order_id)){
            Response::error(ReturnCode::PARAM_WRONGFUL,'请求参数错误');
        }

        $barModel=D('orderBar');

        $commentlist=$barModel->getOrderComment($order_id,$page,$pageSize);
        if($commentlist===false){
            Response::error(ReturnCode::DB_READ_ERROR,'查看评论失败');
        }
        Response::setSuccessMsg('订单评论列表获取成功');
        Response::success($commentlist);
    }



    /**
     * 点击评论拼吧
     * @param  $order_id  int  拼吧订单ID
     */
    public function commentBar()
    {
        $member_id=I('post.member_id');
        $order_id=I('post.order_id');
        if(!is_numeric($member_id) && !is_numeric($order_id)){
           Response::error(ReturnCode::PARAM_INVALID,'请求参数错误');
        }

        //获取用户相关的信息
        $member=M('member')->field('tel,id,nickname,sex,age,avatar')->where(['id'=>$member_id])->find();
        if(empty($member)){
            Response::error(ReturnCode::DB_READ_ERROR,'用户不存在');
        }

        Response::setSuccessMsg('用户信息请求成功');
        Response::success($member);

    }

    /**
     * 评论星级选择标签
     */
    public function star_tags()
    {
        $starTags=C('STAR_TAG');
        Response::setSuccessMsg('数据获取成功');
        Response::success($starTags);
    }


    /**
     * 提交评论
     */
    public function addComment()
    {
       $order_id=I('post.order_id','');
       $member_id= I('post.member_id','');
       $bar_member_id=I('post.bar_member_id','');
       $star=I('post.star','');
       $tags=I('post.tags','');
       $is_show=I('post.is_show',0);
       if(!is_numeric($member_id) && !is_numeric($order_id) && !is_numeric($bar_member_id)){
            Response::error(ReturnCode::PARAM_INVALID,'请求参数错误');
       }
       if(empty($star)){
           Response::error(ReturnCode::PARAM_INVALID,'评分不能为空');
       }
       $data=[
           'order_id'=>$order_id,
           'member_id'=>$member_id,
           'bar_member_id'=>$bar_member_id,
           'star'=>$star,
           'tag'=>$tags,
           'is_show'=>$is_show
           ];

       $barRes=$this->orderBarModel->addComment($data);
       if($barRes===false){
           Response::error(ReturnCode::DB_SAVE_ERROR,$this->orderBarModel->getError());
       }

       Response::setSuccessMsg('评价成功');
       Response::success();

    }


    /**
     * 查看他的评价
     */
    public function memberComment()
    {
        $member_id=I('post.member_id','');
        $page=I('post.page',1);
        $pageSize=I('post.page_size',C('PAGE.PAGESIZE'));

        if(!is_numeric($member_id)){
            Response::error(ReturnCode::PARAM_INVALID,'请求参数错误');
        }

        $commentList=$this->orderBarModel->getMemberComment($member_id,$page,$pageSize);
        if($commentList ===false){
            Response::error(ReturnCode::DB_SAVE_ERROR,'获取他的评价信息');
        }

        Response::setSuccessMsg('获取他的评价信息成功');
        Response::success($commentList);

    }

    /**
     * 他的派对(用户中心他的派对)
     */
    public function getMemberPart()
    {
        $member_id=I('post.member_id','');
        $page=I('post.page',1);
        $pageSize=I('post.page_size',C('PAGE.PAGESIZE'));
        if(!is_numeric($member_id)){
            Response::error(ReturnCode::PARAM_INVALID,'请求参数错误');
        }

        $memberParts=$this->orderModel->getMemberPart($member_id,$page,$pageSize);
        if($memberParts ===false){
            Response::error(ReturnCode::DB_READ_ERROR,'获取用户派对失败');
        }

        Response::setSuccessMsg('获取用户派对成功');
        Response::success($memberParts);
    }





}