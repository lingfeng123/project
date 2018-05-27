<?php

return array(
    //URL模式
    'URL_MODEL' => 0,

    'MODULE_ALLOW_LIST' => array('V1_1', 'Home', 'Membera'),

    'LOG_RECORD' => true, // 开启日志记录
    'LOG_LEVEL' => 'WARN,NOTICE,EMERG,ALERT,CRIT,ERR', // 只记录EMERG ALERT CRIT ERR 错误

    // 加载扩展配置文件
    'LOAD_EXT_CONFIG' => 'route,database',

    //约定sign加密字符串
    'CONVENTION' => 'qYSBThsYaZRidJxBCe',

    //用户uid加密字符串
    'USER_ID_STR' => 'k34fjh329ic9j5aa',

    //阿里云短信ACCESSKEY参数
    "ALIDAYU" => array(
        "ACCESSKEYID" => "LTAIoXskpmqepo6C",        //ACCESSKEYID
        "ACCESSKEYSECRET" => "LcsmlwlnZdlniOvkDHldrOKRAJgomB",      //ACCESSKEYSECRET
        "SIGNNAME" => "空瓶子",      //短信签名,短信中前缀【空瓶子】此部分内容

        //短信模板
        "TEMPLATECODE" => array(
            "BIND_TEL_NUMBER" => "SMS_89960045",    //【空瓶子】短信验证码为555555，您正在申请绑定手机账号，请勿将验证码告诉他人，请于10分钟内完成操作。
            "VERIFY_PAY_PASSWORD" => "SMS_91885072", //【空瓶子】验证码为555555，您正在申请修改密码，请勿将验证码告诉他人，请于10分钟内完成操作。
            "SEAT_OVERDUE_NOTICE" => "SMS_94320020", //订单逾期短信提醒。
            "SEAT_RESERVATION" => "SMS_96620016",   //卡座购买成功
            "FIT_PACKAGE" => "SMS_96825015",    //散套购买成功
            "SEAT_PACKAGE" => "SMS_96730029",   //卡套购买成功
            "PACK_USE" => "SMS_121851540",   //套餐购买成功后指引短信
            "ACCOUNT_BALANCE" => "SMS_121145228",   //账户余额消费提醒
            "SUBEMLPOYEE" => "SMS_120375620",   //新支付订单预订部通知消息
            "SER_EMLPOYEE" => "SMS_120375621",   //新订单员工（客服经理或者服务员）通知消息
            "ADMIN_NOTICE" => "SMS_121165721",   //三方支付回调处理失败报警管理员通知消息
            "ADMIN_RECHARGE" => "SMS_121160729",   //三方充值支付回调处理失败报警管理员通知消息
        )
    ),

    //数据分页参数
    'PAGE' => array(
        'PAGESIZE' => 20,
        'THEME' => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%',
    ),

    //微信小程序配置
    "MINI_PROGRAM" => array(
        'APPID' => 'wxa69e89cc7261ae4f',
        'SECRET' => '94ab51ab47afd8f601751df122b8bf88'
    ),

    //账户类型配置
    'ACCOUNT_TYPE' => array(
        'MEMBER' => 'member',
        'EMPLOYEE' => 'employee'
    ),

    //用户推广码前缀
    "INVITE_CODE_PREFIX" => array(
        'MEMBER' => 1,
        'EMPLOYEE' => 2,
    ),

    //消息中心消息模板
    'SYS_MESSAGES_TMP' => array(
        '卡座审核被拒绝|您提交的卡座{seat_number},审核被拒绝',
        '新卡座审核|{job_name}{employee_name}提交了{seat_number}，快去审核吧。',
        '卡座审核通过|您提交的卡座{seat_number}，审核通过了。',
        '新支付订单|客人：{contacts_realname}，{buy_goods}。',
        '拼吧支付新订单|客人：{contacts_realname}，{buy_goods}。',
    ),

    //会员充值与赠送额度
    'RECHARGE_LIMIT' => array(
        '0.01' => 0.01,
        '0.05' => 0.02,
        '0.10' => 0.05,
        '0.20' => 0.1,
        '0.30' => 0.2,
        '100' => 0,
        '200' => 9,
        '500' => 29,
        '1000' => 59,
        '2000' => 119,
        '5000' => 299,
    ),

    //预订部判定权限
    'YUDINGBU_PERMISSION' => array(23, 24),

    //卡座结算手续费比例
    'SERVICE_CHARGE' => 0.03,   //3%

    //接口请求频次限制
    'VALIDATE_ACTION_NAME' => array(
        'Order/buyPackGoods',   //卡套下单 v1.0
        'Order/buySeatGoods',   //卡座下单
        'Order/payment',        //线上订单支付 v1.0
        'Wxpay/payment',        //充值订单提交
        'Comment/submitComment',    //提交评价
        'MemberContacts/createContacts',    //添加联系人

        //v2.0新增限制接口
        'Order/buyGoods',           //购物车普通下单接口
        'Spread/expressive',        //申请提现
        'Unified/payment',          //支付下单接口
        'Recharge/payment',         //钱包余额充值下单接口
        'Renew/createWineOrder',    //正常续酒下单接口
        'Feedback/apply',           //提交反馈
        'Bar/barAdd',               //发起拼吧
        'Bar/takePartBar',          //发起拼吧
        'Bar/addComment',           //评论拼吧
        'Bar/reNewBarAdd',          //拼吧续酒
        'MemberAuthRecord/apply',   //派对大使申请
    ),

    //百度LBS云访问应用（AK）
    'LBS_AK' => array(
        'WEB' => 'VfvrHlrSVHZN4FU4RYiPOCrq6NhxFsQ2',    //web浏览器调用ak
        'API' => '0utAGBqT8XwB3tCgC0ctTaed8ZcSn3A5',    //api应用调用ak
        'GEOTABLE_ID' => 183892
    ),

    //推广奖励金额度/单次消费
    'PROMOTION_QUOTA' => 10,

    //推广提现最低提现记录次数要求
    'EXPRESSIVE_NUMBER' => 1,

    //充值最大赠送额度
    'SYS_MAX_GIVE_MONEY' => 200000,

    //推广注册页文案
    'SHARE_CONTENT' => array(
        array('title' => '泡吧还能赚钱？', 'intro' => '我刚刚在空瓶子赚了一笔，你也赶紧来，推广好友，现金立返！'),
        array('title' => '娱乐就该简单点~', 'intro' => '酒水套餐在线购买，省心省力省钱！'),
        array('title' => '这里的精彩比你想的多！', 'intro' => '手机互动，线下交友，值得拥有的酒吧娱乐神器~'),
        array('title' => '酒吧订座点单，手机一键搞定！', 'intro' => '海量信息，超多优惠，随时随地下订单！'),
    ),

    //拼吧分享页文案
    'BAR_SHARE_CONTENT' => array(
        array('title' => '就差你了~', 'intro' => '我刚刚在空瓶子上拼到一个萌妹子，你也一起来吧！'),
        array('title' => '有你更好玩~', 'intro' => '没别的，刚刚拼到一群陌生人面基，就问你敢不敢来！')
    )
);

