<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

/**
 * ThinkPHP惯例配置文件
 * 该文件请不要修改，如果要覆盖惯例配置的值，可在应用配置文件中设定和惯例不符的配置项
 * 配置名称大小写任意，系统会统一转换成小写
 * 所有配置参数都可以在生效前动态改变
 */
defined('THINK_PATH') or exit();
return array(
    /* 应用设定 */
    'APP_USE_NAMESPACE' => true,    // 应用类库是否使用命名空间
    'APP_SUB_DOMAIN_DEPLOY' => false,   // 是否开启子域名部署
    'APP_SUB_DOMAIN_RULES' => array(), // 子域名部署规则
    'APP_DOMAIN_SUFFIX' => '', // 域名后缀 如果是com.cn net.cn 之类的后缀必须设置
    'ACTION_SUFFIX' => '', // 操作方法后缀
    'MULTI_MODULE' => true, // 是否允许多模块 如果为false 则必须设置 DEFAULT_MODULE
    'MODULE_DENY_LIST' => array('Common', 'Runtime'),
    'CONTROLLER_LEVEL' => 1,
    'APP_AUTOLOAD_LAYER' => 'Controller,Model', // 自动加载的应用类库层 关闭APP_USE_NAMESPACE后有效
    'APP_AUTOLOAD_PATH' => '', // 自动加载的路径 关闭APP_USE_NAMESPACE后有效

    /* Cookie设置 */
    'COOKIE_EXPIRE' => 0,       // Cookie有效期
    'COOKIE_DOMAIN' => '',      // Cookie有效域名
    'COOKIE_PATH' => '/',     // Cookie路径
    'COOKIE_PREFIX' => '',      // Cookie前缀 避免冲突
    'COOKIE_SECURE' => false,   // Cookie安全传输
    'COOKIE_HTTPONLY' => '',      // Cookie httponly设置

    /* 默认设定 */
    'DEFAULT_M_LAYER' => 'Model', // 默认的模型层名称
    'DEFAULT_C_LAYER' => 'Controller', // 默认的控制器层名称
    'DEFAULT_V_LAYER' => 'View', // 默认的视图层名称
    'DEFAULT_LANG' => 'zh-cn', // 默认语言
    'DEFAULT_THEME' => '',    // 默认模板主题名称
    'DEFAULT_MODULE' => 'Home',  // 默认模块
    'DEFAULT_CONTROLLER' => 'Index', // 默认控制器名称
    'DEFAULT_ACTION' => 'index', // 默认操作名称
    'DEFAULT_CHARSET' => 'utf-8', // 默认输出编码
    'DEFAULT_TIMEZONE' => 'PRC',    // 默认时区
    'DEFAULT_AJAX_RETURN' => 'JSON',  // 默认AJAX 数据返回格式,可选JSON XML ...
    'DEFAULT_JSONP_HANDLER' => 'jsonpReturn', // 默认JSONP格式返回的处理方法
    'DEFAULT_FILTER' => 'htmlspecialchars', // 默认参数过滤方法 用于I函数...

    /* 数据库设置 */
    'DB_TYPE' => '',     // 数据库类型
    'DB_HOST' => '', // 服务器地址
    'DB_NAME' => '',          // 数据库名
    'DB_USER' => '',      // 用户名
    'DB_PWD' => '',          // 密码
    'DB_PORT' => '',        // 端口
    'DB_PREFIX' => '',    // 数据库表前缀
    'DB_PARAMS' => array(), // 数据库连接参数
    'DB_DEBUG' => true, // 数据库调试模式 开启后可以记录SQL日志
    'DB_FIELDS_CACHE' => false,        // 启用字段缓存
    'DB_CHARSET' => 'utf8',      // 数据库编码默认采用utf8
    'DB_DEPLOY_TYPE' => 0, // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    'DB_RW_SEPARATE' => false,       // 数据库读写是否分离 主从式有效
    'DB_MASTER_NUM' => 1, // 读写分离后 主服务器数量
    'DB_SLAVE_NO' => '', // 指定从服务器序号

    /* 数据缓存设置 */
    'DATA_CACHE_TIME' => 0,      // 数据缓存有效期 0表示永久缓存
    'DATA_CACHE_COMPRESS' => false,   // 数据缓存是否压缩缓存
    'DATA_CACHE_CHECK' => false,   // 数据缓存是否校验缓存
    'DATA_CACHE_PREFIX' => '',     // 缓存前缀
    'DATA_CACHE_TYPE' => 'File',  // 数据缓存类型,支持:File|Db|Apc|Memcache|Shmop|Sqlite|Xcache|Apachenote|Eaccelerator
    'DATA_CACHE_PATH' => TEMP_PATH,// 缓存路径设置 (仅对File方式缓存有效)
    'DATA_CACHE_KEY' => '',    // 缓存文件KEY (仅对File方式缓存有效)
    'DATA_CACHE_SUBDIR' => false,    // 使用子目录缓存 (自动根据缓存标识的哈希创建子目录)
    'DATA_PATH_LEVEL' => 1,        // 子目录缓存级别

    /* 错误设置 */
    'ERROR_MESSAGE' => '您访问的页面出错或不存在',//错误显示信息,非调试模式有效
    'ERROR_PAGE' => '',    // 错误定向页面
    'SHOW_ERROR_MSG' => false,    // 显示错误信息
    'TRACE_MAX_RECORD' => 100,    // 每个级别的错误信息 最大记录数

    /* 日志设置 */
    'LOG_RECORD' => false,   // 默认不记录日志
    'LOG_TYPE' => 'File', // 日志记录类型 默认为文件方式
    'LOG_LEVEL' => 'EMERG,ALERT,CRIT,ERR',// 允许记录的日志级别
    'LOG_FILE_SIZE' => 2097152,    // 日志文件大小限制
    'LOG_EXCEPTION_RECORD' => false,    // 是否记录异常信息日志

    /* SESSION设置 */
    'SESSION_AUTO_START' => true,    // 是否自动开启Session
    'SESSION_OPTIONS' => array(), // session 配置数组 支持type name id path expire domain 等参数
    'SESSION_TYPE' => '', // session hander类型 默认无需设置 除非扩展了session hander驱动
    'SESSION_PREFIX' => '', // session 前缀
    //'VAR_SESSION_ID'      =>  'session_id',     //sessionID的提交变量

    /* 模板引擎设置 */
    'TMPL_CONTENT_TYPE' => 'text/html', // 默认模板输出类型
    'TMPL_ACTION_ERROR' => THINK_PATH . 'Tpl/dispatch_jump.tpl', // 默认错误跳转对应的模板文件
    'TMPL_ACTION_SUCCESS' => THINK_PATH . 'Tpl/dispatch_jump.tpl', // 默认成功跳转对应的模板文件
    'TMPL_EXCEPTION_FILE' => THINK_PATH . 'Tpl/think_exception.tpl',// 异常页面的模板文件
    'TMPL_DETECT_THEME' => false,       // 自动侦测模板主题
    'TMPL_TEMPLATE_SUFFIX' => '.html',     // 默认模板文件后缀
    'TMPL_FILE_DEPR' => '/', //模板文件CONTROLLER_NAME与ACTION_NAME之间的分割符
    // 布局设置
    'TMPL_ENGINE_TYPE' => 'Think',     // 默认模板引擎 以下设置仅对使用Think模板引擎有效
    'TMPL_CACHFILE_SUFFIX' => '.php',      // 默认模板缓存后缀
    'TMPL_DENY_FUNC_LIST' => 'echo,exit',    // 模板引擎禁用函数
    'TMPL_DENY_PHP' => false, // 默认模板引擎是否禁用PHP原生代码
    'TMPL_L_DELIM' => '{',            // 模板引擎普通标签开始标记
    'TMPL_R_DELIM' => '}',            // 模板引擎普通标签结束标记
    'TMPL_VAR_IDENTIFY' => 'array',     // 模板变量识别。留空自动判断,参数为'obj'则表示对象
    'TMPL_STRIP_SPACE' => true,       // 是否去除模板文件里面的html空格与换行
    'TMPL_CACHE_ON' => true,        // 是否开启模板编译缓存,设为false则每次都会重新编译
    'TMPL_CACHE_PREFIX' => '',         // 模板缓存前缀标识，可以动态改变
    'TMPL_CACHE_TIME' => 0,         // 模板缓存有效期 0 为永久，(以数字为值，单位:秒)
    'TMPL_LAYOUT_ITEM' => '{__CONTENT__}', // 布局模板的内容替换标识
    'LAYOUT_ON' => false, // 是否启用布局
    'LAYOUT_NAME' => 'layout', // 当前布局名称 默认为layout

    // Think模板引擎标签库相关设定
    'TAGLIB_BEGIN' => '<',  // 标签库标签开始标记
    'TAGLIB_END' => '>',  // 标签库标签结束标记
    'TAGLIB_LOAD' => true, // 是否使用内置标签库之外的其它标签库，默认自动检测
    'TAGLIB_BUILD_IN' => 'cx', // 内置标签库名称(标签使用不必指定标签库名称),以逗号分隔 注意解析顺序
    'TAGLIB_PRE_LOAD' => '',   // 需要额外加载的标签库(须指定标签库名称)，多个以逗号分隔

    /* URL设置 */
    'URL_CASE_INSENSITIVE' => true,   // 默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_MODEL' => 1,       // URL访问模式,可选参数0、1、2、3,代表以下四种模式：
    // 0 (普通模式); 1 (PATHINFO 模式); 2 (REWRITE  模式); 3 (兼容模式)  默认为PATHINFO 模式
    'URL_PATHINFO_DEPR' => '/',    // PATHINFO模式下，各参数之间的分割符号
    'URL_PATHINFO_FETCH' => 'ORIG_PATH_INFO,REDIRECT_PATH_INFO,REDIRECT_URL', // 用于兼容判断PATH_INFO 参数的SERVER替代变量列表
    'URL_REQUEST_URI' => 'REQUEST_URI', // 获取当前页面地址的系统变量 默认为REQUEST_URI
    'URL_HTML_SUFFIX' => 'html',  // URL伪静态后缀设置
    'URL_DENY_SUFFIX' => 'ico|png|gif|jpg', // URL禁止访问的后缀设置
    'URL_PARAMS_BIND' => true, // URL变量绑定到Action方法参数
    'URL_PARAMS_BIND_TYPE' => 0, // URL变量绑定的类型 0 按变量名绑定 1 按变量顺序绑定
    'URL_PARAMS_FILTER' => false, // URL变量绑定过滤
    'URL_PARAMS_FILTER_TYPE' => '', // URL变量绑定过滤方法 如果为空 调用DEFAULT_FILTER
    'URL_ROUTER_ON' => false,   // 是否开启URL路由
    'URL_ROUTE_RULES' => array(), // 默认路由规则 针对模块
    'URL_MAP_RULES' => array(), // URL映射定义规则

    /* 系统变量名称设置 */
    'VAR_MODULE' => 'm',     // 默认模块获取变量
    'VAR_ADDON' => 'addon',     // 默认的插件控制器命名空间变量
    'VAR_CONTROLLER' => 'c',    // 默认控制器获取变量
    'VAR_ACTION' => 'a',    // 默认操作获取变量
    'VAR_AJAX_SUBMIT' => 'ajax',  // 默认的AJAX提交变量
    'VAR_JSONP_HANDLER' => 'callback',
    'VAR_PATHINFO' => 's',    // 兼容模式PATHINFO获取变量例如 ?s=/module/action/id/1 后面的参数取决于URL_PATHINFO_DEPR
    'VAR_TEMPLATE' => 't',    // 默认模板切换变量
    'VAR_AUTO_STRING' => false,    // 输入变量是否自动强制转换为字符串 如果开启则数组变量需要手动传入变量修饰符获取变量

    'HTTP_CACHE_CONTROL' => 'private',  // 网页缓存控制
    'CHECK_APP_DIR' => true,       // 是否检查应用目录是否创建
    'FILE_UPLOAD_TYPE' => 'Local',    // 文件上传方式
    'DATA_CRYPT_TYPE' => 'Think',    // 数据加密方式

    /**************************
     * 以下为本项目自定义公共配置项
     *******************************/
    //微信模板消息
    'WEIXINTPL' => array(
        "CANCEL_WEIXIN" => "cTtYMJeDs2X1R6hpGtaFhzwJ_RNNE26x6dZZoTaTFIs",//订单拒绝通知,
        "OVERDUE_ORDER" => "Gd2ubtN7ofnFvslbFFLRAIN1JRkBYyGOmNYpw6MV670",//订单逾期提醒,
        "WINNING" => "QG5pzvJ9S_z3JsHwpeVQ2C5qRE16voLscw9dKPBBPcA",//中奖结果通知,
        "WXPAY_SUCESS" => "usxo-QTvgKZ0Tfo7PY-YGeUQ6OCZRWAWW7jirRKTJyQ",//订单支付成功,
        "TAKING_ORDER" => "yos5AJCSsdqX5tRxnbef5mLMfr8lY64FzzuCeVq65dE",//接单提醒,
        "WEIXIN_CHANGE" => "zwZMXj3YW50INcEryavOaGnUiaVxa7zwhB9PuFIluVc",//充值通知,

    ),


    //云片短信模板配置
    'YUNPIAN' => array(
        //到店20分钟前提醒消息
        'kazuoyuding' => 2169804,  //【空瓶子】尊敬的#name#，您预定的#product#将在#start#开始生效，请您在#date#到店消费，如需延时请联系商家操作，祝您消费愉快。
        //支付报警管理员通知/余额充值报警管理员通知
        'baojing' => 2169736,  //【空瓶子】用户#name#在进行#operate#时发生#wrong#异常；订单编号: #code#；请及时查看并处理异常！
        //卡套过期提醒
        'kataoyuqi' => 2169744,  //【空瓶子】尊敬的#name#，您购买的#merchant#的#money#元卡座套餐由于未准时到店消费，系统将为您保留该套餐#day#日，您须再次预定卡座到店消费。
        //余额账户消费成功提醒
        'yuexiaofei' => 2169748,  //【空瓶子】尊敬的#name#，您已于#time#消费#paymoney#元，当前余额#totalmoney#元，感谢您的使用。
        //套餐购买成功客户通知
        'santaogoumai' => 2169752,  //【空瓶子】尊敬的用户，恭喜您已成功购买#product#，请于#time#前到店出示二维码进行验证消费，如有疑问请联系空瓶子客服#telphone#。
        //优惠套餐已被拒绝
        'judan' => 2169760,  //【空瓶子】尊敬的#name#，您购买的#product#,因#reason#；商家已拒单，系统将自动退还您的金额，您可以尝试购买其他套餐。
        //预订部接单后员工消息通知
        'jiedantixing' => 2169764,  //【空瓶子】顾客#name#购买了#product#，将在#time#到店消费，请做好接待准备。
        //卡套即将过期的短信提醒
        'taocandaoqi' => 2169776,  //【空瓶子】尊敬的#name#，您购买的#product#只剩下#day#天将过期，请于#date#之前重新预定卡座到店消费，过期订单自动作废并不退还费用，若有疑问请拨打商家电话#tel#询问。
        //绑定手机号码验证
        'bangdingshouji' => 2169698,  //【空瓶子】短信验证码为#code#，您正在申请绑定手机号码，请勿将验证码告诉他人，请于10分钟内完成操作。
        //密码修改验证
        'xiugaimima' => 2169702,  //【空瓶子】短信验证码为#code#，您正在申请修改密码，请勿将验证码告诉他人，请于10分钟内完成操作。
        //预定卡座成功提醒
        'yudingkazuochenggong' => 2169704,  //【空瓶子】尊敬的#name#，恭喜您已成功预定#goodsinfo#，请于#date#之前到店消费，过期将不退还定金，更多详情请登录空瓶子查看或拨打商家电话#tel#询问。
        //顾客购买后员工通知消息
        'daijiedan' => 2169706,  //【空瓶子】顾客#name#购买了#product#，请及时处理订单。
        //发送短信验证码
        'smscode' => 2187994,   //【空瓶子】您的验证码是#code#。如非本人操作，请忽略本短信。
        //拼吧卡套接单成功短信提醒
        'pinbaok' => 2255398, //【空瓶子】您好，您参与#product#拼吧，商户已接单，请于#time#前到店消费，到店后向酒吧工作人员出示订单二维码。如有疑问请联系空瓶子客服#telphone#
        //拼吧散套拼满成功短信提醒
        'pinsanok' => 2267480, //【空瓶子】尊敬的用户您好，您参与的#product#拼吧，商户已接单，请于#time#前到店消费 , 如有疑问，请联系空瓶子客服#telphone#。
        //拼吧卡套拼满成功
        'pinman' => 2267456, //【空瓶子】尊敬的用户您好，您参与的#name#拼吧已经拼满，正等待商户接单，稍后会通过短信提醒接单状态。
        //充值成功短信提示
        //'chongzhiok' => 2269970, //【空瓶子】尊敬的用户，您已于#time#成功充值#recharge_money#元，首充赠送#recharge_money#元，当前余额#total_money#元。如有疑问请联系空瓶子客服400-1608-198。
        //散套自动接单提醒
        'santaojiedan' => 2272178,  //【空瓶子】尊敬的用户您好，您已购买#product#，稍后将会有工作人员与您联系，请于#time#前到店消费。商家地址#address#，如有疑问请联系空瓶子客服400-1608-198。
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

    //骑牛云存储配置
    'QINIU_CONFIG' => array(
        'secretKey' => 'se8mgHckwsTF5w69AxC-SWzpbsRahiDQ-Prua2zk', //secretKey
        'accessKey' => 'S40NZssSMOQApQ0vKLaC6HnI4Wptt-4H9tvzQbOz', //accessKey
        'domain' => 'o9fr3rius.bkt.clouddn.com', //域名
        'bucket' => 'yanhui', //空间名称
        'timeout' => 300, //超时时间
    ),

    //beanstalkd服务器地址
    'BEANS_OPTIONS' => array(
        'HOST' => '139.199.10.155',
        'PORT' => '11300',
        'TUBE_NAME' => array(
            'kpz_order_buy_delayed',    //正常下单订单延迟操作
        ),
    ),

    //原生Redis配置
    'REDIS_CONFIG' => array(
        'HOSTNAME' => '139.199.10.155',
        'PORT' => '6379',
        'PASSWORD' => 'OdJyBZivF%eVdtlF'
    ),

    //TP自带redis类redis配置
    'REDIS_HOST' => '139.199.10.155',
    'REDIS_PORT' => '6379',
    "REDIS_AUTH_PASSWORD" => "OdJyBZivF%eVdtlF",

    //MQ配置项
    'RABBITMQ_OPTION' => array(
        'host' => '127.0.0.1',
        'port' => 5672,
        'account' => 'kpzamqpmanage',
        'password' => 'AA1WPf0gOfqsgeqW',
        'queue_name' => 'kpz_order',    //正向队列名
        'callback_queue_name' => 'kpz_order_callback',  //回调队列名
        'callback_consume_name' => 'kpz_order_callback_consume',    //回调消费者名
    ),

    //拼吧相关的配置
    'BAR_TYPE' => array(
        ['key' => 1, 'value' => '酒局'],
        ['key' => 2, 'value' => '派对'],
    ),

    //拼吧主题
    'BAR_THEME' => array(
        ['key' => 1, 'value' => '同城交友'],
        ['key' => 2, 'value' => '老乡趴'],
        ['key' => 3, 'value' => '单身趴'],
        ['key' => 4, 'value' => '趣味游戏'],
        ['key' => 5, 'value' => '校友趴'],
        ['key' => 6, 'value' => '看球聚会'],
    ),

    //费用类型
    'PAY_TYPE' => array(
        ['key' => 1, 'value' => '女免单男AA'],
        ['key' => 2, 'value' => '男女AA'],
        ['key' => 3, 'value' => '男免单女AA'],
    ),

    //卡座列表最低消费排序设置
    'floor_sale' => array(
        'low' => 2000,
        'mid' => 4000,
        'high' => 6000,
    ),

    //意见反馈类别
    'FEEDBACK_TYPE' => array(
        1 => '预定流程',
        2 => '酒吧服务问题',
        3 => '存取酒流程',
        4 => '拼吧流程问题',
        5 => '互动社交操作',
        6 => '活动问题',
        7 => '其他问题',
    ),

    //优惠券领取类型
    'COUPON_FLAG' => array(
        1 => '领券中心',
        2 => '首页领取',
        3 => '通用 - 老用户回馈券',
        4 => '通用 - 新人注册券',
        5 => '通用 - 首单返利券',
        6 => '店铺 - 老用户回馈券',
        7 => '店铺 - 新人注册券',
        8 => '店铺 - 首单返利券',
    ),


    "ADMIN_PHONE" => '13730686533',     //管理员手机号码
    "KPZKF_PHONE" => '400-1608-198',     //空瓶子平台客服电话号码

    //订单拒绝理由
    'CANCELLATION_REASONS' => array(
        '该卡座已被预定',
        '本酒吧卡座已被全部预定',
        '当前优惠套餐已售罄',
        '当前卡座套餐已售罄',
        '本酒吧正在装修升级中',
        '本酒吧目前暂停营业',
    ),

    //附件前缀
    'ATTACHMENT_URL' => "http://p2zu8i73w.bkt.clouddn.com",

    //用户端接口域名
    'MEMBER_API_URL' => "https://member.app.sc-csj.com",

    //商户端接口域名
    'MERCHANT_API_URL' => "https://merchant.app.sc-csj.com",

    //订单超时时间
    'ORDER_OVERTIME' => 5 * 60,

    //拼吧订单提前多少分钟没拼满作废
    'BEFORE_TIME' => 1 * 60 * 60,

    //订单点击完成提前时间限制
    'EARLY_COMPLETION_TIME' => 3 * 60 * 60,

    //订单已完成操作推迟时间
    'FINISH_DELAY_TIME' => 5 * 60,

    //下单允许时间
    'ORDER_ALLOWED_TIME' => '09:00',

    //积分赠送比例
    'COIN_RULE' => 0.1,

    //酒吧分类
    'COUPON_MERCHANT_TYPE' => array(
        0 => '不限制',
        1 => 'A类酒吧',
        2 => 'B类酒吧',
        3 => 'C类酒吧',
    ),

    //套餐分类
    'COUPON_GOODS_TYPE' => array(
        0 => '不限制',
        1 => '优惠套餐(散套)',
        2 => '卡座套餐(卡套)',
        3 => '单点酒水(单品酒水)'
    ),
);
