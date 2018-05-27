<?php

namespace Admin\Controller;


use Admin\ORG\Auth;
use function GuzzleHttp\Psr7\str;

class IndexController extends BaseController
{
    private $status = [0 => '取消', 1 => '未支付', 2 => '已支付', 3 => '逾期','完成','作废','拒绝','接单'];

    public function index() {
        $isAdmin = isAdministrator();
        $list = array();
        $menuAll = $this->allMenu;
        foreach ($menuAll as $menu) {
            if ($menu['hide'] == 0) {
                if ($isAdmin) {
                    $menu['url'] = U($menu['url']);
                    $list[] = $menu;
                } else {
                    $authObj = new Auth();
                    $authList = $authObj->getAuthList($this->uid);
                    if (in_array(strtolower($menu['url']), $authList) || $menu['url'] == '') {
                        $menu['url'] = U($menu['url']);
                        $list[] = $menu;
                    }
                }
            }
        }
        $list = listToTree($list);
        foreach ($list as $key => $item) {
            if(empty($item['_child']) && $item['url'] != U('Index/welcome')){
                unset($list[$key]);
            }
        }
        $list = formatTree($list);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * welcome统计页
     */
    public function welcome()
    {
        //根据订单类型获取订单数据(获取最近6个月的订单)
        $time = date('Y-m-d');
        $start_time = strtotime('-6 months',strtotime($time));
        $end_time  = strtotime($time);
        $end_time  = 1525536000;

        $statuss = M('order')->field('status,count(id) as num')->where(['arrives_time'=>$end_time])->group('status')->select();
        $order_data = [];
        $order_type_data = [];
        $order_num_data = [];
        foreach ($statuss as $key => $status) {
            $order_type_data [] = $this->status[$status['status']];
            $order_num_data[] = ['value'=>$status['num'],'name'=>$this->status[$status['status']]];

        }

        $order_data['order_type'] = $order_type_data;
        $order_data['num'] = $order_num_data;

        //根据订单状态获取订单数据


        //根据是否是拼吧订单

        //拼吧的订单

        //注册用户
        $register = M('member')->where(['tel'=>['neq',0]])->count();
        $un_register = M('member')->where(['tel'=>['eq',0]])->count();

        $this->assign('register',$register);
        $this->assign('order_type',$order_data);
        $this->assign('un_register',$un_register);

        //查询已结算订单
        //已完成订单
        //M('order')->where(['status' => 5])->select();
        $this->display();
    }
}