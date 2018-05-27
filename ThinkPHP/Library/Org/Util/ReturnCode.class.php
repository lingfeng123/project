<?php
/**
 * 错误码统一维护
 */

namespace Org\Util;

class ReturnCode
{

    const SUCCESS = 200;                  //服务器成功返回用户请求的数据
    const INVALID_REQUEST = 400;          //用户发出的请求有错误。
    const UNAUTHORIZED = 401;             //用户没有权限。
    const FORBIDDEN = 403;                //禁止访问。
    const NOT_FOUND = 404;                //请求数据不存在。
    const INTERNAL_SERVER_ERROR = 500;    //服务器发生错误。

    const INVALID = 1;                	//非法操作
    const DB_SAVE_ERROR = 2;           //数据存储失败
    const DB_READ_ERROR = 3;           //数据读取失败
    const CACHE_SAVE_ERROR = 4;        //缓存存储失败
    const CACHE_READ_ERROR = 5;        //缓存读取失败
    const FILE_SAVE_ERROR = 6;         //文件读取失败
    const LOGIN_ERROR = 7;             //登录失败
    const NOT_EXISTS = 8;              //不存在
    const JSON_PARSE_FAIL = 9;         //JSON数据格式错误
    const TYPE_ERROR = 10;             //类型错误
    const NUMBER_MATCH_ERROR = 11;     //数字匹配失败
    const EMPTY_PARAMS = 12;           //丢失必要数据
    const DATA_EXISTS = 13;            //数据已经存在
    const AUTH_ERROR = 14;             //权限认证失败
    const OTHER_LOGIN = 16;            //别的终端登录
    const VERSION_INVALID = 17;        //API版本不合法
    const CURL_ERROR = 18;             //CURL操作异常

    const PARAM_WRONGFUL = 20;          //传入参数不合法
    const SEND_SMS_FAIL = 21;          //短信发送失败
    const SEND_VALIDATE_FAIL = 22;     //短信验证失败
    const CACHE_DEL_FAIL = 23;          //缓存清除失败
    const DEL_FILE_FAIL = 24;           //删除文件失败
    const DEL_DATA_FAIL = 25;           //删除数据失败
    const TOKEN_EXPIRED = 26;           //TOKEN已过期

    const PARAM_INVALID = 995;         //数据类型非法
    const ACCESS_TOKEN_TIMEOUT = 996;  //身份令牌过期
    const SESSION_TIMEOUT = 997;       //SESSION过期
    const UNKNOWN = 998;               //未知错误
    const EXCEPTION = 999;             //系统异常


}