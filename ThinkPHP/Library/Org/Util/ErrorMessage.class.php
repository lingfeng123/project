<?php
/**
 * 错误码消息
 */

namespace Org\Util;


class ErrorMessage
{

    const INVALID_REQUEST = '用户发出的请求有错误';
    const UNAUTHORIZED = '用户没有权限';
    const FORBIDDEN = '禁止访问';
    const NOT_FOUND = '请求数据不存在';
    const INTERNAL_SERVER_ERROR = '服务器发生错误';

    const INVALID = '非法操作';
    const DB_SAVE_ERROR = '数据存储失败';
    const DB_READ_ERROR = '数据读取失败';
    const CACHE_SAVE_ERROR = '缓存存储失败';
    const CACHE_READ_ERROR = '缓存读取失败';
    const FILE_SAVE_ERROR = '文件读取失败';
    const LOGIN_ERROR = '登录失败';
    const NOT_EXISTS = '不存在';
    const JSON_PARSE_FAIL = 'JSON数据格式错误';
    const TYPE_ERROR = '类型错误';
    const NUMBER_MATCH_ERROR = '数字匹配失败';
    const EMPTY_PARAMS = '丢失必要数据';
    const DATA_EXISTS = '数据已经存在';
    const AUTH_ERROR = '权限认证失败';
    const OTHER_LOGIN = '别的终端登录';
    const VERSION_INVALID = 'API版本不合法';
    const CURL_ERROR = 'CURL操作异常';
    const PARAM_INVALID = '数据类型非法';
    const ACCESS_TOKEN_TIMEOUT = '身份令牌过期';
    const SESSION_TIMEOUT = 'SESSION过期';
    const UNKNOWN = '未知错误';
    const EXCEPTION = '系统异常';

}