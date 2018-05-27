<?php

return array(
    // 开启路由
    'URL_ROUTER_ON' => true,

    //路由规则
    'URL_ROUTE_RULES' => array(

        /**
         * 公共请求接口路由
         */
        'wechat/:action'            => 'Home/Wechat/:1',                //微信公众号开发
        'wxpay/:action'             => 'Home/Wxpay/:1',                 //微信支付
        'sms/:action'               => 'Home/Sms/:1',                   //短信发送接口
        'version/:action'           => 'Home/version/:1',               //版本管理

        /**
         * version v1.0 路由规则
         */
        'v1/index/:action'          => 'Membera/Index/:1',              //index控制器
        'v1/member/:action'         => 'Membera/Member/:1',             //会员接口
        'v1/ad/:action'             => 'Membera/Ad/:1',                 //广告接口
        'v1/merchant/:action'       => 'Membera/Merchant/:1',           //商户接口
        'v1/goods/:action'          => 'Membera/Goods/:1',              //商品列表
        'v1/comment/:action'        => 'Membera/Comment/:1',            //商户评论接口
        'v1/contacts/:action'       => 'Membera/MemberContacts/:1',     //联系人接口
        'v1/employee/:action'       => 'Membera/Employee/:1',           //员工接口
        'v1/order/:action'          => 'Membera/Order/:1',              //订单接口
        'v1/renew/:action'          => 'Membera/Renew/:1',              //订单接口

        /**
         * version v1.1 路由规则
         */
        'pay/:action'                 => 'Home/Pay/:1',                //支付回调

        'v1.1/login/:action'          => 'V1_1/Login/:1',              //登录
        'v1.1/coupon/:action'         => 'V1_1/Coupon/:1',             //优惠券
        'v1.1/index/:action'          => 'V1_1/Index/:1',              //index
        'v1.1/member/:action'         => 'V1_1/Member/:1',             //会员
        'v1.1/ad/:action'             => 'V1_1/Ad/:1',                 //广告
        'v1.1/merchant/:action'       => 'V1_1/Merchant/:1',           //商户
        'v1.1/goods/:action'          => 'V1_1/Goods/:1',              //商品
        'v1.1/comment/:action'        => 'V1_1/Comment/:1',            //评论
        'v1.1/contacts/:action'       => 'V1_1/MemberContacts/:1',     //联系人
        'v1.1/employee/:action'       => 'V1_1/Employee/:1',           //员工
        'v1.1/order/:action'          => 'V1_1/Order/:1',              //订单
        'v1.1/renew/:action'          => 'V1_1/Renew/:1',              //续酒
        'v1.1/bar/:action'            => 'V1_1/Bar/:1',                //拼吧
        'v1.1/unified/:action'        => 'V1_1/UnifiedOrder/:1',       //下单
        'v1.1/memberauth/:action'     => 'V1_1/MemberAuthRecord/:1',   //认证
        'v1.1/feedback/:action'       => 'V1_1/Feedback/:1',           //反馈
        'v1.1/Spread/:action'         => 'V1_1/Spread/:1',             //推广
        'v1.1/kcoin/:action'          => 'V1_1/Kcoin/:1',              //K币
        'v1.1/recharge/:action'       => 'V1_1/Recharge/:1',           //充值
        'v1.1/tips/:action'           => 'V1_1/Tips/:1',               //提示
        'v1.1/sharebar/:action'       => 'V1_1/ShareBar/:1',           //拼吧分享
    ),
);

