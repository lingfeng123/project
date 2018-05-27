<?php

/**
 * 获取HTTP全部头信息
 */
if (!function_exists('apache_request_headers')) {
    function apache_request_headers()
    {
        $arh = array();
        $rx_http = '/\AHTTP_/';
        foreach ($_SERVER as $key => $val) {
            if (preg_match($rx_http, $key)) {
                $arh_key = preg_replace($rx_http, '', $key);
                $rx_matches = explode('_', $arh_key);
                if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                    foreach ($rx_matches as $ak_key => $ak_val)
                        $rx_matches[$ak_key] = ucfirst($ak_val);
                    $arh_key = implode('-', $rx_matches);
                }
                $arh[$arh_key] = $val;
            }
        }

        return $arh;
    }
}

/**
 * 系统非常规MD5加密方法
 * @param  string $str 要加密的字符串
 * @param  string $auth_key 要加密的字符串
 * @return string
 * @author jry <598821125@qq.com>
 */
function user_md5($str, $auth_key = '')
{
    if (!$auth_key) {
        $auth_key = C('AUTH_KEY');
    }
    return '' === $str ? '' : md5(sha1($str) . $auth_key);
}

/**
 * @param     $url
 * @param int $timeOut
 * @return bool|mixed
 */
if (!function_exists('curl_get')) {
    function curl_get($url, $timeOut = 10)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== false) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, $timeOut);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }
}

/**
 * 返回格式化api接口数据
 * @param array $data 需要输出的数据
 * @param null $code 状态码
 */
function success($data, $code = null)
{
    $code = is_null($code) ? \Org\Util\ReturnCode::SUCCESS : $code;
    $msg = '操作成功';
    $returnData = array(
        'code' => $code,
        'msg' => $msg,
        'data' => $data
    );
    header('Content-Type:application/json; charset=utf-8');
    $returnStr = json_encode($returnData);
    exit($returnStr);
}

/**
 * 获取附件上传目录根地址
 * @return string   目录地址
 */
function attachment_path()
{
    //获取附件上传绝对路径
    $ds = DIRECTORY_SEPARATOR;
    $imgPath = $_SERVER['DOCUMENT_ROOT'];
    $arr = explode($ds, $imgPath);
    array_pop($arr);
    $str = implode($arr, $ds);
    $imgPath = $str . $ds . 'attachment';
    return $imgPath;
}

/**
 * 获取图片上传文件名
 * @return string
 */
function get_filename(){
    return \Org\Util\Tools::file_name();
}

/**
 * 生成员账号密码
 * @param $tel int 员工手机号码
 */
function create_employee_password($tel){
    return md5(md5($tel) . md5($tel));
}

/**
 * 将模型错误信息变成一个有序列表字符串.
 * @param \Think\Model $model 模型.
 * @return string
 */
function get_error(\Think\Model $model)
{
    $errors = $model->getError();
    if (!is_array($errors)) {
        $errors = [$errors];
    }
    $html = '';
    foreach ($errors as $error) {
        $html .= '<p>' . $error . '</p>';
    }
    return $html;
}