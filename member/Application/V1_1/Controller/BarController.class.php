<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/5
 * Time: 17:20
 */

namespace V1_1\Controller;


use Org\Util\FibonacciRpcClient;
use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\Tools;
use Think\Log;

class BarController extends BaseController
{
    private $orderModel;
    private $barModel;
    private $barMemberModel;
    private $bar_type = ['1' => '酒局', '2' => '派对'];

    public function _initialize()
    {
        parent::_initialize();
        $this->orderModel = D('order');
        $this->barModel = D('bar');
        $this->barMemberModel = D('bar_member');
    }

    /**
     * 拼吧列表（派对列表）
     */
    public function barIndex()
    {
        $lat = I('post.lat', 0);
        $lng = I('post.lng', 0);
        $member_id = I('post.member_id', '');
        $page = I('post.page', 1);
        $page_size = I('post.page_size', C('PAGE.PAGESIZE'));

        //费用类型和拼吧主题筛选
        $keyword = I('post.keywords', '');
        $pay_type = I('post.pay_type', '');
        $bar_theme = I('post.bar_theme', '');

        if (!is_numeric($page) && !is_numeric($page_size) && !is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不正确');
        }

        //查询数据库
        $barlists = $this->barModel->getBarList($lat, $lng, $page, $page_size, $keyword, $pay_type, $bar_theme, $member_id);
        Response::setSuccessMsg('读取列表成功');
        Response::success($barlists);
    }

    /**
     * 获取筛选里面的东西
     */
    public function searchList()
    {
        $payment = C('PAY_TYPE');
        $bar_theme = C('BAR_THEME');
        $data['payment'] = $payment;
        $data['bar_theme'] = $bar_theme;
        Response::setSuccessMsg('数据读取成功');
        Response::success($data);
    }

    /**
     * 拼吧主题
     */
    public function barTheme()
    {
        $bartheme = C('BAR_THEME');
        $data['list'] = $bartheme;
        Response::success($data);
    }

    /**
     * 发起拼吧
     */
    public function barAdd()
    {
        $merchant_id = I('post.merchant_id', '');
        $member_id = I('post.member_id', '');
        $bar_type = I('post.bar_type', '');
        $goods_ids = I('post.goods_id', '');  //购买的的商品array[1=2,2=3,3=4]
        $pay_price = I('post.pay_price', '');
        $bar_theme = I('post.bar_theme', '');
        $arrive_time = I('post.arrives_time', '');
        $arrives_time = strtotime($arrive_time);
        $cost_type = I('post.cost_type', '');
        $man_number = I('post.man_number', 0);
        $woman_number = I('post.woman_number', 0);
        $tel = I('post.tel', '');
        $average_cost = I('post.average_cost', '');
        $description = I('post.description', '');
        $is_join = I('post.is_join', 0);

        Tools::orderAllowedValid();     //下单时间限制判断

        if (I('post.client') == 'xcx') {
            $goods_ids = explode(',', $goods_ids);
        }

        //判断如果男生人数和女生人数<=0 的时候直接定义为0
        if ($man_number <= 0) {
            $man_number = 0;
        }
        if ($woman_number <= 0) {
            $woman_number = 0;
        }


        if (!empty($tel)) {
            if (!preg_match("/^1[345789]\d{9}$/", $tel)) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '电话号码不合法');
            }
        }

        if (!is_numeric($merchant_id) && !in_array($bar_type, [1, 2]) && !is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }
        //判断是否传入支付金额
        if (empty($pay_price)) Response::error(ReturnCode::PARAM_WRONGFUL, '请传入正确的支付金额');

        //判断人均费用是否者却
        if (empty($average_cost)) Response::error(ReturnCode::PARAM_WRONGFUL, '人均费用不能为空');

        //判断主题选择
        if (empty($bar_theme)) Response::error(ReturnCode::PARAM_WRONGFUL, '请选择主题');

        //判断支付类型
        if (empty($cost_type)) Response::error(ReturnCode::PARAM_WRONGFUL, '请选择支付类型');

        //判断到店时间 $arrives_time int
        if (empty($arrives_time)) Response::error(ReturnCode::PARAM_WRONGFUL, '请选择到店时间');

        //判断到店人数男女不能同时为0
        if ($man_number == 0 && $woman_number == 0) Response::error(ReturnCode::PARAM_WRONGFUL, '到店人数男,女不能同时为0');

        if (($man_number + $woman_number) < 2) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '拼吧人数必须为2人之上');
        }
        //验证订单描述不能太长
        $description = Tools::filterEmoji($description);
        $description = str_replace('|', '', $description);
        if (!empty($description) && mb_strlen($description) > 140) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '订单描述不能超过140个字符');
        }

        //获取商户信息
        $merchant = M('merchant')
            ->field('begin_time,end_time,preordain_cycle,sanpack_stock,kapack_stock')
            ->where(['id' => $merchant_id])->find();

        $limit_time = C('BEFORE_TIME');
        $begin_time = strtotime($arrive_time . ' ' . $merchant['begin_time']);
        $max_limit = $begin_time - $limit_time;
        if (time() > $max_limit) {
            Response::error(ReturnCode::INVALID_REQUEST, '现在您不能发起今天的拼吧,请选择明天或以后');
        }

        //判断是否在预定周期之内
        $max_arrives_time = strtotime(date('Y-m-d', time())) + $merchant['preordain_cycle'] * 24 * 60 * 60;
        if ($arrives_time > $max_arrives_time) {
            Response::error(ReturnCode::INVALID_REQUEST, '已超出预定周期,无法拼吧');
        }
        $obegin_time = $begin_time;
        if ($merchant['begin_time'] >= $merchant['end_time']) {
            $oend_time = strtotime(date('Y-m-d', $arrives_time) . ' ' . $merchant['end_time']) + 86400;
        } else {
            $oend_time = strtotime(date('Y-m-d', $arrives_time) . ' ' . $merchant['end_time']);
        }

        //判断beastalkd服务是否正常的开启
        $beanConfig = C('BEANS_OPTIONS');
        $status = Tools::beanstalkStats($beanConfig['TUBE_NAME'][0]);
        if (!$status) {
            Log::write('beanstalkd server Crashed', Log::ERR);
            Response::error(ReturnCode::INVALID_REQUEST, '当前服务不可用');
        }

        //获取联系人信息
        $member = M('member')->field('nickname,tel,sex,avatar,age')->where(['id' => $member_id])->find();
        $age = Tools::calculateAge($member['age']);
        // 1 女免 男AA 2 男女AA 3 女A 男免
        if ($member['sex'] == 1) {
            if ($cost_type == 1) {
                if ($man_number <= 0) {
                    Response::error(ReturnCode::PARAM_WRONGFUL, '男生人数不能为0');
                }
            } else if ($cost_type == 3) {
                if ($woman_number <= 0 || $man_number <= 0) {
                    Response::error(ReturnCode::PARAM_WRONGFUL, '男女人数不能为0');
                }
            } else if ($cost_type == 2) {
                if ($man_number <= 0) {
                    Response::error(ReturnCode::PARAM_WRONGFUL, '男生人数不能为0');
                }
            }
        } else if ($member['sex'] == 2) {
            if ($cost_type == 1) {
                if ($woman_number <= 0 || $man_number <= 0) {
                    Response::error(ReturnCode::PARAM_WRONGFUL, '男女人数不能为0');
                }
            } else if ($cost_type == 3) {
                if ($woman_number <= 0) {
                    Response::error(ReturnCode::PARAM_WRONGFUL, '女生人数不能为0');
                }
            } else if ($cost_type == 2) {
                if ($woman_number <= 0) {
                    Response::error(ReturnCode::PARAM_WRONGFUL, '女生人数不能为0');
                }
            }
        }

        //如果bar_type == 1 表示是需要商品的
        if ($bar_type == 1) {
            if (empty($goods_ids)) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '请选择商品');
            }
            //获取所有商品的ID ()
            $goods_pack_ids = [];
            foreach ($goods_ids as $goods_id) {
                $goods_pack = explode('=', $goods_id);
                $goods_pack_ids[$goods_pack[0]] = $goods_pack[1];
            }

            $good_ids = array_keys($goods_pack_ids);

            //获取所有商品信息
            $date = date('Ymd', $arrives_time);
            $son_sql = "(select `price` from `api_goods_price` where `date` = '{$date}' AND `goods_id` = api_goods_pack.id) as price";
            $goods = M('goods_pack')->field("id,merchant_id,title,type,{$son_sql},image,description,created_time,stock,xu_stock,status,api_goods_pack.price as case_price,market_price,purchase_price")
                ->where(['id' => ['in', $good_ids], 'status' => 1])
                ->select();
            if (!$goods) {
                Response::error(ReturnCode::DATA_EXISTS, '未找到符合要求的商品');
            }

            //价格赋值
            foreach ($goods as $skey => $sgood) {
                if ($sgood['type'] == 3 && is_null($sgood['price'])) {
                    $goods[$skey]['price'] = $sgood['case_price'];
                }

                if ($sgood['type'] != 3 && is_null($sgood['price'])) {
                    Response::error(ReturnCode::DB_READ_ERROR, '此商品暂不支持购买');
                }
            }

            //查询每日库存表中已售套餐数据
            $goods_sales_stock = M('goods_pack_stock')->where(['date' => $date, 'merchant_id' => $merchant_id])->getField('goods_id,day_sales');

            $pack_total = 0;    //套餐总数
            $pay_all_price = 0.00;  //总应支付金额
            $market_price = 0.00;   //总市场价格
            $purchase_price = 0.00; //总结算价格
            $every_day_stock = 0;   //每日库存量
            $order_type = 0;    //订单类型 0为纯单品酒水 1卡座 2卡套 3散套
            $pack_goods = [];   //套餐商品存储数组
            $single_goods = []; //单品酒水存储数组
            foreach ($goods as $key => $good) {

                //判断是否非酒水商品
                if ($good['type'] != 3) {
                    $pack_total += 1;

                    //判断订单类型
                    switch ($good['type']) {
                        case 1:
                            $order_type = 3;
                            $every_day_stock = $merchant['sanpack_stock'];
                            break;
                        case 2:
                            $order_type = 2;
                            $every_day_stock = $merchant['kapack_stock'];
                            break;
                    }

                    //判断套餐商品是否购买数量大于了1件
                    if ($goods_pack_ids[$good['id']] != 1) {
                        Response::error(ReturnCode::DB_READ_ERROR, '每个套餐只能购买一件');
                    }

                    //计算剩余库存
                    $sold_number = isset($goods_sales_stock[$good['id']]) ? $goods_sales_stock[$good['id']] : 0;
                    $surplus_stock = D('goods_pack')->calculateNowStock($every_day_stock, $good['stock'], $sold_number);
                    if ($surplus_stock < 1) {
                        Response::error(ReturnCode::DB_READ_ERROR, '该商品已售馨');
                    }

                    //套餐商品的购买数量
                    $pack_goods[] = [
                        'id' => $good['id'],
                        'amount' => $goods_pack_ids[$good['id']]
                    ];

                    //判断单品酒水商品
                } elseif ($good['type'] == 3) {
                    $single_goods[$good['id']] = $goods_pack_ids[$good['id']];
                }

                //判断是否选择了多个套餐
                if ($pack_total > 1) Response::error(ReturnCode::DB_READ_ERROR, '同时只能购买一个类型套餐');

                //计算各项总价
                $pay_all_price += $good['price'] * $goods_pack_ids[$good['id']];
                $market_price += $good['market_price'] * $goods_pack_ids[$good['id']];
                $purchase_price += $good['purchase_price'] * $goods_pack_ids[$good['id']];
            }
        }


        //支付总金额,如果没有商品就是传递的支付金额
        $pay_price = $pay_all_price ? $pay_all_price : $pay_price;
        $purchase_price = $pay_price * C('SERVICE_CHARGE');


        //查看费用类型 1 男A女免 2 男免女A 3 男女AA
        if ($cost_type == 1) {
            $average_cost = sprintf("%.2f", ceil(($pay_price / $man_number) * 100) / 100);
            if ($bar_type == 1 && $member['sex'] == 1) {
                $personal_price = $average_cost;
            } else if ($bar_type == 1 && $member['sex'] == 2) {
                $personal_price = 0;
            }
        } else if ($cost_type == 3) {
            $average_cost = sprintf("%.2f", ceil(($pay_price / $woman_number) * 100) / 100);
            if ($bar_type == 1 && $member['sex'] == 1) {
                $personal_price = 0;
            } else if ($bar_type == 1 && $member['sex'] == 2) {
                $personal_price = $average_cost;
            }
        } elseif ($cost_type == 2) {
            $average_cost = sprintf("%.2f", ceil(($pay_price / ($man_number + $woman_number)) * 100) / 100);
            if ($bar_type == 1) {
                $personal_price = $average_cost;
            }
        } else {
            $average_cost = sprintf("%.2f", ceil(($pay_price / ($man_number + $woman_number)) * 100) / 100);
        }

        //订单数据
        $bar = [
            'merchant_id' => $merchant_id,
            'member_id' => $member_id,
            'realname' => $member['nickname'],
            'tel' => $tel ? $tel : $member['tel'],
            'sex' => $member['sex'],
            'avatar' => $member['avatar'],
            'age' => $age,
            'bar_type' => $bar_type,
            'total_price' => $pay_price,
            'purchase_price' => $purchase_price,
            'pay_price' => $pay_price,
            'bar_theme' => $bar_theme,
            'arrives_time' => strtotime(date('Y-m-d', $arrives_time)),
            'cost_type' => $cost_type,
            'man_number' => $man_number,
            'woman_number' => $woman_number,
            'average_cost' => $average_cost,
            'description' => $description,
            'is_bar' => 1,
            'order_type' => isset($order_type) ? $order_type : 0,
            'personal_price' => $personal_price,
            'obegin_time' => $obegin_time,
            'oend_time' => $oend_time,
            'is_join' => $is_join,
        ];

        $data['version'] = 'v1.1';  //api接口版本号(项目开发版本)
        $data['buy_type'] = 3;
        $data['order'] = $bar;
        //订单数据
        $data['goods'] = ['pack_goods' => $pack_goods, 'single_goods' => $single_goods];
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
     * 验证派对大使
     */
    public function party_auth()
    {
        $member_id = I('post.member_id', '');
        if (!is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不正确');
        }

        $is_auth = M('member')->where(['id' => $member_id])->getField('is_auth');
        if ($is_auth === false) {
            Response::error(ReturnCode::DATA_EXISTS, '请先成为派对大使');
        }

        Response::success(['is_auth' => $is_auth]);
    }

    /**
     * 拼吧详情
     */
    public function barDetail()
    {
        $bar_id = I('post.bar_id', '');
        $member_id = I('post.member_id', '');
        if (!is_numeric($bar_id) || !is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_INVALID, '请求参数不合法');
        }

        $orderModel = D('bar');
        //存在的订单,获取订单信息
        $bar = $orderModel->getBarOrderInfo($bar_id, $member_id);
        if ($bar === false) {
            Response::error(ReturnCode::DATA_EXISTS, '该订单不存在');
        }

        Response::setSuccessMsg('数据获取成功');
        Response::success($bar);
    }

    /**
     * 分享拼吧地址
     */
    public function shareUrl()
    {
        $bar_id = I('post.bar_id', '');
        $client = I('post.client', '');

        if ($client != 'xcx') {
            if (!is_numeric($bar_id)) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
            }
        }

        $member_api_url = C('MEMBER_API_URL');
        $contents = C('BAR_SHARE_CONTENT');

        $count = count($contents);
        $contents = $contents[mt_rand(0, $count - 1)];
        $data['title'] = $contents['title'];
        $data['intro'] = $contents['intro'];
        $data['app_logo'] = $member_api_url . "/Public/images/logo.png";
        $data['share_url'] = $member_api_url . '/v1.1/sharebar/index?ftoken=39qcf4-0q239um&rsv_spt=1&rsv_iqid=0xfc1a095600014176&issp=1&f=8&rsv_bp=0&rsv_idx=2&ie=utf-8&tn=home_pg&rsv_enter=0&rsv_sug3=114&bar_id=' . $bar_id . '&rsv_sug1=2&rsv_sug7=100&inputT=8430&rsv_sug4=10705';

        Response::success($data);
    }

    /**
     * 参与拼吧
     */
    public function takePartBar()
    {
        $bar_id = I('post.bar_id');
        $member_id = I('post.member_id');
        if (!is_numeric($bar_id) && !is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_INVALID, '请求参数不合法');
        }

        //获取用户是否绑定手机号
        $tel = M('member')->where(['id' => $member_id])->getField('tel');
        if (empty($tel)) {
            Response::error(ReturnCode::INVALID_REQUEST, '请先绑定手机号');
        }

        $barModel = D('bar_member');
        //判断用户是否存在未支付的,或者是当天已经存在拼吧订单了
        $member_bar = $barModel->where(['member_id' => $member_id, 'pay_status' => 1])->find();
        if ($member_bar) {
            Response::error(ReturnCode::INVALID_REQUEST, '你有未支付的拼吧订单');
        };

        //判断用户是否参与了该订单
        $member_bar_rs = $barModel->where(['member_id' => $member_id, 'bar_id' => $bar_id, 'pay_status' => ['in', [1, 2]]])->find();
        if (!empty($member_bar_rs)) {
            Response::error(ReturnCode::INVALID_REQUEST, '你已参与了该拼吧');
        }

        $beanConfig = C('BEANS_OPTIONS');
        $status = Tools::beanstalkStats($beanConfig['TUBE_NAME'][0]);
        if (!$status) {
            Log::write('beanstalkd server Crashed', Log::ERR);
            Response::error(ReturnCode::INVALID_REQUEST, '当前服务不可用');
        }

        $orderModel = D('bar');
        $bar_rs = $orderModel->takeBar($bar_id, $member_id);
        if ($bar_rs === false) {
            Response::error(ReturnCode::DATA_EXISTS, $orderModel->getError());
        }

        Response::setSuccessMsg('您已成功参与拼吧');
        Response::success($bar_rs);
    }


    /**
     * 查看我的酒局,或者我的派对
     */
    public function myBarList()
    {
        $type = I('post.type', '');
        $member_id = I('post.member_id', '');
        $page = I('post.page', 1);
        $pageSize = I('post.page_size', C('PAGE.PAGESIZE'));

        if (!is_numeric($member_id) && !is_numeric($type)) {
            Response::error(ReturnCode::PARAM_INVALID, '请求参数错误');
        }

        $barModel = D('bar');

        $barData = $barModel->myWinePartList($type, $member_id, $page, $pageSize);
        if ($barData === false) {
            Response::error(ReturnCode::INVALID_REQUEST, $barModel->getError());
        }

        Response::setSuccessMsg('拼吧列表请求成功');
        Response::success($barData);
    }

    /**
     * 拼吧订单详情
     */
    public function barInfo()
    {
        $bar_id = I('post.bar_id', '');
        $member_id = I('post.member_id', '');
        if (!is_numeric($bar_id) && !is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_INVALID, '的请求参数不合法');
        }

        $orderModel = D('bar');
        $bar_list = $orderModel->getOrderInfo($bar_id, $member_id);
        if ($bar_list === false) {
            Response::error(ReturnCode::INVALID_REQUEST, $orderModel->getError());
        }

        if ($bar_list['bar_status'] == 7 && $bar_list['bar_type'] == 1 && $bar_list['order_type'] == 2) {
            //获取订单相关的信息
            $data = M('bar_order')->field('api_order.id,api_order.order_no,api_order.is_bar,api_order.merchant_id,api_order.order_type')
                ->join('api_order ON api_order.id = api_bar_order.order_id')
                ->where(['bar_id' => $bar_id])->find();

            $bar_list['order_qrcode'] = C('MEMBER_API_URL') . U('Home/Source/orderQrcode',
                    [
                        'order_id' => $data['id'],
                        'order_no' => $data['order_no'],
                        'order_type' => $data['order_type'],
                        'merchant_id' => $data['merchant_id'],
                    ]);
        } else {
            $bar_list['order_qrcode'] = '';
        }

        Response::setSuccessMsg('拼吧订单详情获取成功');
        Response::success($bar_list);
    }

    /**
     * 查看拼吧订单评论
     * $order_id  订单ID
     */
    public function lookComment()
    {
        $order_id = I('post.bar_id', '');
        $page = I('post.page', 1);
        $pageSize = I('post.page_size', C('PAGE.PAGESIZE'));
        if (!is_numeric($order_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数错误');
        }

        $barModel = D('bar');

        $commentlist = $barModel->getOrderComment($order_id, $page, $pageSize);
        if ($commentlist === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '查看评论失败');
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
        $member_id = I('post.member_id');
        $bar_id = I('post.bar_id');
        if (!is_numeric($member_id) && !is_numeric($bar_id)) {
            Response::error(ReturnCode::PARAM_INVALID, '请求参数错误');
        }

        //检测该用户是否有权限评价
        $mrs = M('bar_member')->where(['member_id' => $member_id, 'bar_id' => $bar_id])->count();
        if ($mrs == 0) {
            $data['member_id'] = $member_id;
            $data['bar_id'] = $bar_id;
            Response::error(ReturnCode::DATA_EXISTS, '您不能对该订单进行评价', $data);
        }

        //获取用户相关的信息
        $member = M('member')->field('tel,id as member_id,nickname,sex,age,avatar')->where(['id' => $member_id])->find();
        if (empty($member)) {
            Response::error(ReturnCode::DB_READ_ERROR, '用户不存在');
        }
        $member['age'] = Tools::calculateAge($member['age']);

        if (!preg_match('/^(http|https)/ius', $member['avatar'])) {
            $member['avatar'] = C('attachment_url') . $member['avatar'];
        }

        //获取评论标签 根据不同的星级分组
        $starTags = M('comment_tags')->select();
        $tags = [];
        foreach ($starTags as $starTag) {
            $tags[$starTag['star']][] = $starTag;
        }

        $data['member'] = $member;
        $data['tags'] = $tags;

        Response::setSuccessMsg('用户信息请求成功');
        Response::success($data);

    }


    /**
     * 提交评论
     */
    public function addComment()
    {
        $bar_id = I('post.bar_id', '');
        $member_id = I('post.member_id', '');
        $star = I('post.star', '');
        $tags = I('post.tags', '');
        $is_show = I('post.is_show', 0);
        $client = I('post.client');
        $clients = ['ios', 'android'];

        if (in_array($client, $clients)) {
            $tags = implode(',', $tags);
        }

        if (!is_numeric($member_id) && !is_numeric($bar_id)) {
            Response::error(ReturnCode::PARAM_INVALID, '请求参数错误');
        }

        if (empty($star)) {
            Response::error(ReturnCode::PARAM_INVALID, '评分不能为空');
        }

        //查看评价人是否参与了该订单
        $barcount = M('bar_member')->where(['member_id' => $member_id])->count();
        if ($barcount == 0) {
            Response::error(ReturnCode::DATA_EXISTS, '您不能评价该订单');
        }


        $data = [
            'bar_id' => $bar_id,
            'member_id' => $member_id,
            'star' => $star,
            'tag' => $tags,
            'is_show' => $is_show
        ];

        $barRes = $this->barModel->addComment($data);
        if ($barRes === false) {
            Response::error(ReturnCode::DB_SAVE_ERROR, $this->barModel->getError());
        }

        Response::setSuccessMsg('评价成功');
        Response::success();

    }


    /**
     * 查看他的评价
     */
    public function memberComment()
    {
        $member_id = I('post.member_id', '');
        $page = I('post.page', 1);
        $pageSize = I('post.page_size', C('PAGE.PAGESIZE'));

        if (!is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_INVALID, '请求参数错误');
        }

        $commentList = $this->barModel->getMemberComment($member_id, $page, $pageSize);
        if ($commentList === false) {
            Response::error(ReturnCode::DB_SAVE_ERROR, '获取他的评价信息');
        }

        Response::setSuccessMsg('获取他的评价信息成功');
        Response::success($commentList);

    }

    /**
     * 他的派对(用户中心他的派对)
     */
    public function getMemberPart()
    {
        $member_id = I('post.member_id', '');
        $page = I('post.page', 1);
        $pageSize = I('post.page_size', C('PAGE.PAGESIZE'));
        if (!is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_INVALID, '请求参数错误');
        }

        $memberParts = $this->barModel->getMemberPart($member_id, $page, $pageSize);
        if ($memberParts === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取用户派对失败');
        }

        Response::setSuccessMsg('获取用户派对成功');
        Response::success($memberParts);
    }


    /**
     * 获取可以参与续酒的人的列表
     *
     */
    public function wineMemberList()
    {
        $bar_id = I('post.bar_id', '');
        if (!is_numeric($bar_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不正确');
        }

        $bar_members = M('bar_member')->field('realname,sex,avatar,member_id')->where(['bar_id' => $bar_id, 'pay_status' => 2])->select();
        foreach ($bar_members as $key => $bar_member) {
            if (!preg_match('/^(http|https)/ius', $bar_member['avatar'])) {
                $bar_members[$key]['avatar'] = C('attachment_url') . $bar_member['avatar'];
            }
        }
        Response::setSuccessMsg('获取续酒人信息成功');
        Response::success($bar_members);
    }


    /**
     * 拼吧续酒
     */
    public function reNewBarAdd()
    {
        $bar_id = I('post.bar_id', '');             //拼吧ID
        $member_id = I('post.member_id');          //发起续酒的娃
        $goods_pack_ids = I('post.goods_id', '');    //商品ID array( 3= 3,4=4)
        $description = I('post.description', '');   //拼吧描述
        $member_ids = I('post.member_ids', '');    //参与拼吧人

        if (I('post.client') == 'xcx') {
            $goods_pack_ids = explode(',', $goods_pack_ids);
            $member_ids = explode(',', $member_ids);
        }

        //判断是否存在拼吧ID
        if (empty($bar_id) && !is_numeric($bar_id) && !is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_INVALID, '请求参数错误');
        }
        //获取订单是否存在
        $bar = M('bar')->where(['id' => $bar_id])->find();
        if (!$bar) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '订单不存在');
        }
        /*if ($bar['order_type'] == 2) {
            Response::error(ReturnCode::DATA_EXISTS, '卡套订单暂未开放续酒功能');
        }*/
        //判断是否选择用户
        if (empty($member_ids)) {
            Response::error(ReturnCode::PARAM_INVALID, '请选择参与拼吧的用户');
        }
        //拼吧描述
        $description = Tools::filterEmoji($description);
        $description = str_replace('|', '', $description);
        if (mb_strlen($description) > 100) {
            Response::error(ReturnCode::PARAM_INVALID, '备注不能超过100个字');
        }

        //判断传入商品ID
        if (empty($goods_pack_ids) && !is_array($goods_pack_ids)) {
            Response::error(ReturnCode::PARAM_INVALID, '您未选择购买的商品');
        }

        //判断发起续酒的用户是否符合条件
        $fa_wine_bar = M('bar_member')
            ->field('api_member.id,api_member.nickname,api_member.sex,api_member.tel,api_member.avatar,api_member.age')
            ->join('left join api_member ON api_member.id = api_bar_member.member_id')
            ->where(['member_id' => $member_id, 'bar_id' => $bar_id, 'pay_status' => 2])->find();
        if (empty($fa_wine_bar)) {
            Response::error(ReturnCode::DATA_EXISTS, '你不能发起该订单的续酒');
        }

        //拼吧续酒用户,判断参与续酒的用户
        $bar_member = M('bar_member')->where(['bar_id' => $bar_id])->getField('member_id', true);
        foreach ($member_ids as $members_id) {
            if (!in_array($members_id, $bar_member)) {
                Response::error(ReturnCode::PARAM_INVALID, '存在不符合续酒条件的用户');
            }
        }

        /**
         * 判断续酒单点酒水的库存 和套餐的库存
         */
        //判断商品库存(存在单品酒水)

        $goods_ids = [];
        foreach ($goods_pack_ids as $goods_id) {
            $goods_pack = explode('=', $goods_id);
            $goods_ids[$goods_pack[0]] = $goods_pack[1];
        }


        $goods_id_array = array_keys($goods_ids);
        $goods_pack_model = D('goods_pack');
        $date = date('Ymd', $bar['arrives_time']);
        $son_sql = "(select `price` from `api_goods_price` where `date` = '{$date}' AND `goods_id` = api_goods_pack.id) as price";
        $goods = $goods_pack_model->field("id,merchant_id,type,{$son_sql},stock,xu_stock,market_price,purchase_price")
            ->where(['id' => ['in', $goods_id_array], 'merchant_id' => $bar['merchant_id']])
            ->select();
        if (!$goods) {
            Response::error(ReturnCode::DB_READ_ERROR, '未找到符合条件的商品');
        }

        //价格赋值
        foreach ($goods as $skey => $sgood) {
            if ($sgood['type'] == 3 && is_null($sgood['price'])) {
                $goods[$skey]['price'] = $sgood['case_price'];
            }

            if ($sgood['type'] != 3 && is_null($sgood['price'])) {
                Response::error(ReturnCode::DB_READ_ERROR, '此商品暂不支持购买');
            }
        }

        $pay_price = 0.00;      //总应支付金额
        $market_price = 0.00;   //总市场价格
        $purchase_price = 0.00; //总结算价格
        $order_type = $bar['order_type'];        //订单类型 0为纯单品酒水(由于是续酒,不存在指定套餐类型了,所以订单以单品酒水订单形式存在) 1卡座 2卡套 3散套
        $goods_send = [];       //商品ID => 商品购买数量
        foreach ($goods as $key => $good) {
            //判断库存是否充足
            if ($good['xu_stock'] < $goods_ids[$good['id']]) {
                Response::error(ReturnCode::DB_READ_ERROR, '该商品已售馨');
            }

            //计算各项总价
            $pay_price += $good['price'] * $goods_ids[$good['id']];
            $market_price += $good['market_price'] * $goods_ids[$good['id']];
            $purchase_price += $good['purchase_price'] * $goods_ids[$good['id']];

            //套餐商品的购买数量
            $goods_send[$good['id']] = $goods_ids[$good['id']];
        }

        //获取用户的相关信息
        $members = M('member')->field('id as member_id,nickname,sex,tel,avatar,age')->where(['id' => ['in', $member_ids]])->select();
        foreach ($members as $key => $member) {
            $members[$key]['age'] = Tools::calculateAge($member['age']);
        }


        $average_cost = $pay_price / count($member_ids);

        $average_cost = sprintf("%.2f", ceil($average_cost * 100) / 100);

        //判断是否在营业范围之内
        $merchant = M('merchant')->field('begin_time,end_time')->where(['id' => $bar['merchant_id']])->find();
        $begin_time = strtotime(date('Y-m-d', $bar['arrives_time']) . ' ' . $merchant['begin_time']);
        if ($merchant['begin_time'] < $merchant['end_time']) {
            $end_time = strtotime(date('Y-m-d', $bar['arrives_time']) . ' ' . $merchant['end_time']);
        } else {
            $end_time = strtotime(date('Y-m-d', $bar['arrives_time']) . ' ' . $merchant['end_time']) + 86400;
        }

        if (time() < $begin_time || time() > $end_time) {
            Response::error(ReturnCode::INVALID_REQUEST, '未在营业时间范围内,不能发起续酒');
        }

        //订单基本信息
        $data['version'] = 'v1.1';  //api接口版本号(项目开发版本)
        $data['buy_type'] = 4;  //购买类型 1正常 2续酒
        $data['order']['bar_type'] = 1;  //拼吧续酒类型
        $data['order']['bar_theme'] = 0;  //购买类型 1正常 2续酒
        $data['order']['cost_type'] = 0;  //购买类型 1正常 2续酒
        $data['order']['man_number'] = 0;  //购买类型 1正常 2续酒
        $data['order']['woman_number'] = 0;  //购买类型 1正常 2续酒
        $data['order']['average_cost'] = $average_cost;  //购买类型 1正常 2续酒
        $data['order']['merchant_id'] = $bar['merchant_id'];
        $data['order']['member_id'] = $member_id;
        $data['order']['realname'] = $fa_wine_bar['nickname'];
        $data['order']['tel'] = $fa_wine_bar['tel'];
        $data['order']['sex'] = $fa_wine_bar['sex'];
        $data['order']['total_price'] = $market_price;    //市场总价
        $data['order']['pay_price'] = $pay_price > 0 ? $pay_price : 0;     //实付金额
        $data['order']['purchase_price'] = $purchase_price;  //结算价格
        $data['order']['bar_status'] = 1;    //订单状态
        $data['order']['order_type'] = $order_type;  //订单类型
        $data['order']['arrives_time'] = $bar['arrives_time'];
        $data['order']['description'] = $description; //订单备注
        $data['order']['top_bar_id'] = $bar_id;    //父级订单ID
        $data['order']['is_xu'] = 1;        //是否为续酒订单
        $data['order']['is_bar'] = 1;        //是否为拼吧订单

        //订单商品数据
        $data['goods'] = $goods_send;
        //参与用户
        $data['member'] = $members;
        $data['current_member'] = $member_id;

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
     * 检测该拼吧订单是否符合拼吧条件
     */
    public function checkWineBar()
    {
        $bar_id = I('post.bar_id', '');
        $member_id = I('post.member_id', '');
        if (!is_numeric($bar_id) && !is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数错误');
        }

        //第一:判断是否是酒局,判断该订单是否是已完成的状态
        $bar_res = $this->barModel->where(['id' => $bar_id, 'bar_status' => 4])->find();
        if (empty($bar_res) || $bar_res === false) {
            Response::error(ReturnCode::DATA_EXISTS, '未找到该拼吧订单');
        }

        //第二:判断该用户是否存在没有完成,或者没有支付的拼吧续酒订单
        $bar = $this->barModel->where(['member_id' => $member_id, 'is_xu' => 1, 'bar_status' => 7])->find();
        if ($bar) {
            Response::error(ReturnCode::INVALID_REQUEST, '你存在未完成的拼吧续酒订单');
        }

        $member_bar = M('bar_member')
            ->join('left join api_bar ON api_bar.id = api_bar_member.bar_id AND api_bar.bar_status = 1 AND api_bar.bar_type = 1')
            ->where(['api_bar_member.member_id' => $member_id, 'api_bar_member.pay_status' => 1])->find();
        if ($member_bar) Response::error(ReturnCode::INVALID_REQUEST, '你存在未支付拼吧续酒订单');

        $merchant = M('merchant')->field('begin_time,end_time')->where(['id' => $bar_res['merchant_id']])->find();
        //第三 判断该订单是否在营业返回内
        $a_time = str_replace(':', '', $merchant['begin_time']);
        $b_time = str_replace(':', '', $merchant['end_time']);

        //判断该订单是否已经超过了营业时间(订单只能在当天的营业范围内才能续酒)
        if ($a_time >= $b_time) {
            //截止格式化时间
            $laytime = date('Y-m-d', strtotime('+ 1 day', $bar_res['arrives_time'])) . ' ' . $merchant['end_time'];
        } else {
            $laytime = date('Y-m-d', $bar_res['arrives_time']) . ' ' . $merchant['end_time'];
        }

        //起点时间戳
        $begin_time = strtotime(date('Y-m-d', $bar_res['arrives_time']) . ' ' . $merchant['begin_time']);
        //计算提前时间点
        $start_time = $begin_time - C('EARLY_COMPLETION_TIME');

        //转换时间戳
        $laytime = strtotime($laytime);
        $now_time = time();
        if ($now_time >= $laytime || $now_time <= $start_time) {
            Response::error(ReturnCode::INVALID, '订单不在营业时间范围内,无法参与续酒');
        }

        Response::success();
    }

    /**
     * 验证拼吧续酒的库存是否充足
     */
    public function checkBarGoodsPack()
    {
        $goods_id = I('post.goods_id','');
        $bar_id = I('post.bar_id','');
        $clientversion =I('post.clientversion','');
        $client =I('post.client','');

        if($clientversion == 2){
            if($client == 'xcx'){
                $goods_id = explode(',',$goods_id);
            }

            if(!is_array($goods_id)){
                Response::error(ReturnCode::PARAM_INVALID, '请求参数不合法');
            }

            $bar = M('bar')->field('arrives_time,merchant_id')->where(['id'=>$bar_id])->find();
            if($bar == false){
                Response::error(ReturnCode::DATA_EXISTS, '未找到符合要求的拼吧订单');
            }

            $good_pack_num = [];
            foreach ($goods_id as $item){
                $good_pack_ids = explode('=',$item);
                $good_pack_num[$good_pack_ids[0]] =$good_pack_ids[1];
            }

            $good_ids = array_keys($good_pack_num);

            $goods = M('goods_pack')->where(['id' => ['in', $good_ids], 'status' => 1])->select();
            if (!$goods) {
                Response::error(ReturnCode::DATA_EXISTS, '未找到符合要求的商品');
            }

            foreach ($goods as $key => $good) {

                if($good['xu_stock'] ==0){
                    Response::error(ReturnCode::INVALID, $good['title'].'商品已售罄');
                }
                // 验证拼吧续酒商品库存是否充足
                $good_diff = $good['xu_stock'] -$good_pack_num[$good['id']];

                if($good_diff <= 0){
                    Response::error(ReturnCode::INVALID, $good['title'].'商品库存不足');
                }
            }

        }else{
            if(!is_numeric($goods_id)){
                Response::error(ReturnCode::PARAM_INVALID, '请求参数不合法');
            }

            //验证商品的续酒库存是否充足
            $bar_xu_stock = M('goods_pack')->where(['id' => $goods_id])->getField('xu_stock');

            if ($bar_xu_stock <= 0) {
                Response::error(ReturnCode::DATA_EXISTS, '该商品已售罄');
            }
        }

        Response::success();
    }

    /**
     * 拼吧续酒列表
     */
    public function wineBarList()
    {
        $bar_id = I('post.bar_id', ''); //拼吧主订单ID
        $member_id = I('post.member_id', ''); //查看续酒人ID
        $page = I('post.page', 1); //查看续酒人ID
        $pageSize = I('post.page_size', C('PAGE.PAGESIZE')); //查看续酒人ID
        if (!is_numeric($bar_id) && !is_numeric($member_id) && !is_numeric($page) && !is_numeric($pageSize)) {
            Response::error(ReturnCode::PARAM_INVALID, '请求参数不合法');
        }

        $wineList = $this->barModel->getBarWineList($bar_id, $member_id, $page, $pageSize);
        if ($wineList === false) {
            Response::error(ReturnCode::INVALID_REQUEST, '续酒列表获取失败');
        }

        Response::setSuccessMsg('拼吧续酒列表获取成功');
        Response::success($wineList);
    }

    /**
     * xujiuxiangqing
     */
    public function wineBarDetail()
    {
        $bar_id = I('post.bar_id', '');
        $member_id = I('post.member_id', '');
        if (!is_numeric($bar_id) && !is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_INVALID, '请求参数不合法');
        }

        $mywine = M('bar_member')->where(['bar_id' => $bar_id, 'member_id' => $member_id])->find();
        if (!$mywine) {
            $bar_list = M('bar')->field('
        api_bar.id as bar_id,
        api_bar.total_price,
        api_bar.pay_price,
        api_bar.average_cost,
        api_bar.created_time,
        api_bar.description,
        api_bar.bar_status')
                ->where(['api_bar.id' => $bar_id])->find();
        } else {
            $bar_list = M('bar')->field('
        api_bar.id as bar_id,
        api_bar.total_price,
        api_bar.pay_price,
        api_bar.average_cost,
        api_bar.created_time,
        api_bar.description,
        api_bar.bar_status,
        api_bar_member.pay_type,
        api_bar_member.pay_status,
        api_bar_member.id as order_id')
                ->join('left join api_bar_member ON api_bar_member.bar_id = api_bar.id')
                ->where(['api_bar.id' => $bar_id, 'api_bar_member.member_id' => $member_id])->find();
        }

        //获取商品信息
        $goods_info = M('bar_pack')->field('goods_pack_id,title,amount,price,image,pack_description,market_price,goods_type')->where(['bar_id' => $bar_id])->select();
        foreach ($goods_info as $k => $good) {
            $goods_info[$k]['image'] = C('attachment_url') . $good['image'];
        }

        //用户信息
        $member_info = M('bar_member')->field('member_id,pay_status,avatar')->where(['bar_id' => $bar_id])->select();
        foreach ($member_info as $ke => $item) {
            if (!preg_match('/^(http|https)/ius', $item['avatar'])) {
                $member_info[$ke]['avatar'] = C('attachment_url') . $item['avatar'];
            }
        }

        $have_time = $bar_list['created_time'] + C('ORDER_OVERTIME') - time();

        $bar_list['have_time'] = $have_time > 0 ? $have_time : 0;
        $bar_list['buy_type'] = '4';
        $bar_list['goods'] = $goods_info;
        $bar_list['member'] = $member_info;

        Response::setSuccessMsg('获取续酒详情成功');
        Response::success($bar_list);

    }


    /**
     * 个人中心里面的评价
     */
    public function evaluateCount()
    {
        $member_id = I('post.member_id', '');  //用户ID

        //获取综合频分
        $memberComment = M('comment_barstar')->where(['member_id' => $member_id])->getField('average_star');
        $ctags = [];
        //获取用户标签分组次数
        $member_tags = M('comment_bar')->field('tag')->where(['bar_member_id' => $member_id])->select();
        foreach ($member_tags as $member_tag) {
            $tags_id = explode(',', $member_tag['tag']);
            $tags = M('comment_tags')->field('tags')->where(['id' => ['in', $tags_id]])->select();
            foreach ($tags as $tag) {
                $ctags[] = $tag['tags'];
            }
        }
        $comment_tags = array_count_values($ctags);

        $com_tags = [];
        foreach ($comment_tags as $key => $comment_tag) {
            $com_tag['tags'] = $key;
            $com_tag['total'] = $comment_tag;
            $com_tags[] = $com_tag;
        }

        $data['average_star'] = $memberComment;
        $data['tag'] = $com_tags;

        Response::setSuccessMsg('获取评价成功');
        Response::success($data);
    }


    /**
     * 用户中心里面的他的派对
     */
    public function memberParty()
    {
        $member_id = I('post.member_id', '');

        $party = $this->barModel->getMemberPart($member_id, 1, 10);
        if ($party === false) {
            Response::error(ReturnCode::INVALID_REQUEST, '续酒列表获取失败');
        }

        Response::setSuccessMsg('获取评价成功');
        Response::success($party['list'][0]);

    }

    /**
     * 完成派对
     */
    public function finishBar()
    {
        $bar_id = I('post.bar_id', '');
        $member_id = I('post.member_id', '');
        if (!is_numeric($bar_id) && !is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }
        //验证用户是否可以处理该订单
        $bar_rs = $this->barModel->where(['id' => $bar_id, 'member_id' => $member_id, 'bar_status' => 7])->count();
        if ($bar_rs == false) {
            Response::error(ReturnCode::DB_READ_ERROR, '您不能对该订单执行完成操作');
        }
        //验证用户是否存在续酒订单
        $wine_list = $this->barModel->where(['top_bar_id' => $bar_id, 'is_xu' => 1, 'bar_status' => ['in', [1, 7]]])->count();
        if ($wine_list) {
            Response::error(ReturnCode::DB_READ_ERROR, '存在未完成的续酒订单');
        }

        //执行修改操作
        $finish_rs = $this->barModel->finishOneBar($bar_id);
        if ($finish_rs === false) {
            Response::error(ReturnCode::DB_READ_ERROR, $this->barModel->getError());
        }

        Response::setSuccessMsg('拼吧完成操作成功');
        Response::success();

    }
}