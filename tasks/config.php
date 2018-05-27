<?php
/**
 * task任务配置文件
 */
return array(

    //beanstalkd服务器地址
    'BEANS_OPTIONS' => array(
        'HOST' => '139.199.10.155',
        'PORT' => '11300',
        'TUBE_NAME' => array(
            'kpz_order_buy_delayed',    //正常下单订单延迟操作
        ),
    ),

    //rabbitmq配置项
    'RABBITMQ_OPTIONS' => array(
        'HOST' => '127.0.0.1',
        'PORT' => 5672,
        'USER' => 'kpzamqpmanage',
        'PASS' => 'AA1WPf0gOfqsgeqW',
        'VHOST' => '/',
    ),

    //rabbitmq数据库配置
    'DATABASE' => array(
        'DB_HOST' => '139.199.10.155',
        'DB_DBNAME' => 'kpzformaldata',
        'DB_USERNAME' => 'kpzweb',
        'DB_PASSWORD' => 'HbMLn2zqbyKZV2fT',
    ),

    //拼吧下单取消订单计算时间
    'BEFORE_TIME' => 1 * 60 * 60,

    //订单超时时间
    'ORDER_OVERTIME' => 5 * 60,

    //订单点击完成提前时间限制
    'EARLY_COMPLETION_TIME' => 3 * 60 * 60,

    //订单已完成操作推迟时间
    'FINISH_DELAY_TIME' => 5 * 60,

    //云片短信模板配置
    'YUNPIAN' => array(
        'kazuoyuding' => 2169804,  //【空瓶子】尊敬的#name#，您预定的#product#将在#start#开始生效，请您在#date#到店消费，如需延时请联系商家操作，祝您消费愉快。
        'baojing' => 2169736,  //【空瓶子】用户#name#在进行#operate#时发生#wrong#异常；订单编号: #code#；请及时查看并处理异常！
        'kataoyuqi' => 2169744,  //【空瓶子】尊敬的#name#，您购买的#merchant#的#money#元卡座套餐由于未准时到店消费，系统将为您保留该套餐#day#日，您须再次预定卡座到店消费。
        'yuexiaofei' => 2169748,  //【空瓶子】尊敬的#name#，您已于#time#消费#paymoney#元，当前余额#totalmoney#元，感谢您的使用。
        'santaogoumai' => 2169752,  //【空瓶子】尊敬的#name#，你已购买#product#，请于#time#前到店消费，到店后向酒吧工作人员出示订单二维码，服务员验证后即可使用，商家地址#address#,联系电话#tel#。如有使用问题,请联系空瓶子客服#telphone#。
        'judan' => 2169760,  //【空瓶子】尊敬的#name#，您购买的#product#,因#reason#；商家已拒单，系统将自动退还您的金额，您可以尝试购买其他套餐。
        'jiedantixing' => 2169764,  //【空瓶子】顾客#name#购买了#product#，将在#time#到店消费，请做好接待准备。
        'taocandaoqi' => 2169776,  //【空瓶子】尊敬的#name#，您购买的#product#只剩下#day#天将过期，请于#date#之前重新预定卡座到店消费，过期订单自动作废并不退还费用，若有疑问请拨打商家电话#tel#询问。
        'bangdingshouji' => 2169698,  //【空瓶子】短信验证码为#code#，您正在申请绑定手机号码，请勿将验证码告诉他人，请于10分钟内完成操作。
        'xiugaimima' => 2169702,  //【空瓶子】短信验证码为#code#，您正在申请修改密码，请勿将验证码告诉他人，请于10分钟内完成操作。
        'yudingkazuochenggong' => 2169704,  //【空瓶子】尊敬的#name#，恭喜您已成功预定#goodsinfo#，请于#date#之前到店消费，过期将不退还定金，更多详情请登录空瓶子查看或拨打商家电话#tel#询问。
        'daijiedan' => 2169706,  //【空瓶子】顾客#name#购买了#product#，请及时处理订单。
        'smscode' => 2187994,   //【空瓶子】您的验证码是#code#。如非本人操作，请忽略本短信。
        'tuikuantongzhi' => 2213934, //您支付的订单#orderno#由于#reason#，现将已付款退还到您的来源账户中。退款可能延迟到账，请耐心等待。如有疑问请联系空瓶子客服!
        'zuofeitongzhi' => 2213980, //尊敬的#name#，很抱歉您的订单#orderno#未按时到店消费，订单自动作废并不退还费用，下次请在规定时间内完成消费，更多详情请登录空瓶子查看或拨打商家电话询问!
        'jijiangyuqizuofei' => 2224118, //尊敬的#name#，您购买的#product#还有#day#天将逾期，请于#date#之前到店消费，逾期订单自动作废并不退还费用，若有疑问请拨打商家电话#tel#询问。
        'ershifenzhongtixing' => 2243262, //【空瓶子】尊敬的#name#，您购买的#product#将在#begintime#开始生效，请留意时间准时到店消费，如有疑问请拨打空瓶子客服电话或与酒吧联系。
    ),

    //微信公众号配置
    'WECHAT_OPTION' => array(
        'token' => 'kpzwechat', //填写你设定的key
        'encodingaeskey' => '57ugJzNaVNDFVeNvj8inE0L5HZNIYOCcGRcxz8KDQLg', //填写加密用的EncodingAESKey
        'appid' => 'wx369fb3d6cb65b07b', //填写高级调用功能的app id, 请在微信开发模式后台查询
        'appsecret' => 'a8b4a1f7e1fdcf4597246513efafbdfe' //填写高级调用功能的密钥
    ),

    //微信小程序支付相关配置
    'WXPAY_OPTION' => array(
        'APPID' => 'wxa69e89cc7261ae4f',                    //微信小程序APPID
        'APPSECRET' => '94ab51ab47afd8f601751df122b8bf88',  //微信小程序的appsecret
        'MCH_ID' => '1496908062',                           //微信支付商户号
        'KEY' => 's7c5C4A5ffitKJqssWIVo0pEZ41pRghh',        //微信支付api秘钥
        'IP' => '139.199.10.155',                           //微信支付发起服务器Ip  139.199.10.155
        'NOTIFY_URL' => 'https://member.app.sc-csj.com/pay/wxXcxNotify', //回调地址
        'CERT_PATH' => '/mnt/data/kpzapp/cert/test/xcx/',
    ),

    //微信APP支付相关配置
    'APP_WXPAY_OPTION' => array(
        'APPID' => 'wx0937c51aea0bd6c5',                    //开放平台APP应用的appid
        'APPSECRET' => 'd31d9b75e3bf4e261e0e62408b7905ae',  //开放平台app应用secret
        'MCH_ID' => '1499547812',                           //APP微信支付商户号
        'KEY' => 'hr6pG4ZD7kndqMsObc9lA6X0ZaG9huHw',        //微信支付商户平台中的api秘钥
        'IP' => '139.199.10.155',                           //允许发起支付请求的IP
        'NOTIFY_URL' => 'https://member.app.sc-csj.com/pay/wxAppNotify',    //回调地址
        'CERT_PATH' => '/mnt/data/kpzapp/cert/test/app/',
    ),

    //alipay支付宝配置参数
    'alipay' => array(
        'app_id' => '2018022302256228',
        'seller_id' => '2088921405419825',
        'rsa_private_key' => 'MIIEowIBAAKCAQEAvNRpWGTkjzLSh/TwrIFPM6Xt3ZYKotYx6kMG3aWZnx0IBlrMFJjLIqI+3/VHkCfsnhx16ya4Uojg7VM9Zs6bbJ31D6d6Ik5JMJVRVZStSU5D5F1OpC1UxmgSF1HzhodGSevM2DZXrwY1DLhXyqOMck+uJMv+Q8XdSU76ny6P5IHlJ67EgPwesrWb1SmrqM6R6/TVsovPdNiTWVMPdx+R27ZW6g2+ZOY5PDVP6/Vzn2wPuuvu7oOdM8b+8FInt5X/6XjHTHx1lb/LuxWpsTMt6B7C4FYmzPy/OfE4iV9SEjeiorUOCGpZYR7iSB92vMSJ2efSfn2YFi7UCvjswt0KxQIDAQABAoIBAG8MB6vJIbSo8bCstkDshrRb92fhjf23M4GNy2LbuV0eSJLcILpYJNYITiuM5nn7UKanHB2fFrTK9GP9GNX12OdmeTCskCHOojIlDcDjf4jlsv2AdfgNBJbtqv313t4VfZuJRV09kBFI+DezzUVJKxYqj8HqCWy31uv+u1qTmmGJk3ZdHW4k4yxRzM/BkhM1IijCN+Kkb+98zmsMpKJDyty0fBJ0u7fbZvFeuctCg1vn/Vu3AbofPHFkYwdjEJWmNlIwgRmkSafmwLwOkPrk6OZ5SWJjqXQN4oewxCwlwj9RKqFL5SsKWLxNBh+CfsP2fBulKq6/kv9c7wPIVNjvP90CgYEA5QJM/0otdy/Hqq9HFdIJCD1CHA17QncXvVPAf/wgyYeJntPtTCgZ953hn1uZ5WwD3kO71xhPGeYk5qCyO42Rw5uAgsjXPacWCx2vR1Gw9s3Yed4zjEkYe8hR+ZMBGX4TMeLHb8MTmyyA8iQ3b0JSKOq5FVcxyQtp/qZR6vKqeosCgYEA0xXQsqCVWWxn2nzL+C+cVwkoJKBQMylcjTJhgmVRk+J9ZBoUh2Hw0RYmLrADP7nlw4Ljnh28bhjQnw7J72M1OPlf+mzhNaVlZHsoMUAZGkJi0kQ5u6fIxhmpvd0YOo0J/A4cZ0KLsKhahCBr9M96xHKypUuFeovLqnxnIXmpSe8CgYAx1TdWEhkkp2QGc0+/os3OWi4plpFs3CdxTmlEMGQeFn1O74TinpNP+64eFDu/3apV0l1sp+CGnTsIaI2AQgUnEI59ZyDXTKWSU6pRlagxfIePtVd4Pmuye9vCuEdz+ahJobSOUF/S03NXvaPGdSMVvkX6K9gsjGxLOnv26UlJkwKBgBDo1HkayBwLxR4JlUViewG16BNXDFWs4I8nFAygFTLll+nm9PILdIErZw+iZgA64h8RYy6Nb0TalAvJ4X9d0SupPnkPM9NaVo8AFq4rVld2LfhuIrnXrQvBjol5JrG6Dqy0bK4Q9KPIOMgQ2NUZsdn+3jTDogO5Iy2bHZ/e4SLdAoGBAIiXGXnybsPQS+bTFryf9e1ibxGOSDdrvhttsti4LPuo1NUAofJnptNqedaPI0A3d7tckbADvJlMjFVU2hzLGvAl8Y+ViyDec1c2jQu7Q4UTu0gG/MoYnLT58fTlTVpPHdU5FOQWcswEgGkWGgER+SggWEdsW/h6qdmVjiCufU/a',
        'alipay_rsa_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjyqz4KHTvh/qLD3shmCNuSCV+W417nRqv5bRxD5YFpMzIE45izxq4np+co5TslvYxHZXppI7vgHD0pUFn8kDj1wBqaSwN7fhXf6aHJXA6up7GQzMqo7sRgfuit2EoZVJHAYNyfFOiPdzUZir+rv+n2zqugwV9+FaRHOkIEWqyVDfcXvbuChXXoFCYPuN94Zs/Kue/+hUaI4W3YCUGk69s0/H2hb7Hk1WHTDqgSFctR5m/aLKm5ILKkHi1PcFa2YK585aM6dhMm/qzoZcffbjD+YXr1ivSBjTRUFYNosL0eKEfgLne4+x7Z3liMvkWjiPGY8LEtWETqy1pWwxrznMvQIDAQAB',
        'gate_way_url' => 'https://openapi.alipay.com/gateway.do',
        'sign_type' => 'RSA2',
        'notify_url' => "https://member.app.sc-csj.com/pay/alipayNotify",
        'timeout_express' => "10m",
    ),

);