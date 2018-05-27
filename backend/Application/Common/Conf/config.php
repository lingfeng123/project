<?php

return array(
    'URL_MODEL' => 2,   //URL模式
    'AUTH_KEY' => 'I&TC{pft>L,C`wFQ>&#ROW>k{Kxlt1>ryW(>r<#R',   //密码加密串

    'DEFAULT_MODULE'  =>  'Admin',  // 绑定默认模块
    'DEFAULT_CONTROLLER'  =>  'Login',  // 绑定默认模块

    //模板相关配置
    //'TMPL_ACTION_ERROR'     =>  APP_PATH.'Admin/Tpl/jump.tpl', // 默认错误跳转对应的模板文件
    //'TMPL_ACTION_SUCCESS'   =>  APP_PATH.'Admin/Tpl/jump.tpl', // 默认成功跳转对应的模板文件
    //'TMPL_EXCEPTION_FILE'   =>  APP_PATH.'Admin/Tpl/exception.tpl',// 异常页面的模板文件


    //分页参数
    'PAGE' => array(
        'PAGESIZE' => 50,
        'THEME' => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%',
    ),

    'USER_ADMINISTRATOR' => array(1),     //后台管理员配置

    'APP_VERSION' => 'v1.0',
    'APP_NAME'    => '空瓶子',
    'COMPANY_NAME' => 'CSJ',
    'PROMOTION_QUOTA' => 50,

    //原生Redis配置
    'REDIS_CONFIG' => array(
        'HOSTNAME' => '139.199.10.155',
        'PORT' => '6379',
        'PASSWORD' => 'OdJyBZivF%eVdtlF'
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

    /* 数据库设置 */
    'DB_TYPE'               =>  'mysql',     // 数据库类型
    'DB_HOST'               =>  '127.0.0.1', // 服务器地址
    'DB_NAME'               =>  'wxdc',          // 数据库名
    'DB_USER'               =>  'root',      // 用户名
    'DB_PWD'                =>  '123456',          // 密码
    /*'DB_USER'               =>  'remoteuser',      // 用户名
    'DB_PWD'                =>  'KKp3RDbLbchzt8Gi',          // 密码*/

    'DB_PORT'               =>  '3306',        // 端口
    'DB_PREFIX'             =>  'api_',    // 数据库表前缀
    'DB_PARAMS'          	=>  array(),    // 数据库连接参数
    'DB_DEBUG'  			=>  TRUE,       // 数据库调试模式 开启后可以记录SQL日志
    'DB_FIELDS_CACHE'       =>  false,        // 启用字段缓存
    'DB_CHARSET'            =>  'utf8',      // 数据库编码默认采用utf8
    'DB_DEPLOY_TYPE'        =>  0,          // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    'DB_RW_SEPARATE'        =>  false,       // 数据库读写是否分离 主从式有效
    'DB_MASTER_NUM'         =>  1, // 读写分离后 主服务器数量
    'DB_SLAVE_NO'           =>  '', // 指定从服务器序号

    'ATTACHMENT_URL' => "http://o9fr3rius.bkt.clouddn.com",

     //结算时间限制
    'Financelimit' => -3,


    //百度LBS云访问应用（AK）
    'LBS_AK' => array(
        'WEB' => 'VfvrHlrSVHZN4FU4RYiPOCrq6NhxFsQ2',    //web浏览器调用ak
        'API' => '0utAGBqT8XwB3tCgC0ctTaed8ZcSn3A5',    //api应用调用ak
        'GEOTABLE_ID' => 183892
    ),
);

