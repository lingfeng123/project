<?php

use Org\Util\ApiLog;

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
    $code = is_null($code) ? ReturnCode::SUCCESS : $code;
    $msg = '操作成功';
    $returnData = array(
        'code' => $code,
        'msg' => $msg,
        'data' => $data
    );
    header('Content-Type:application/json; charset=utf-8');
    $returnStr = json_encode($returnData);
//    ApiLog::setResponse($returnStr);
//    ApiLog::save();
    exit($returnStr);
}

/**
 * 根据经纬度计算距离
 * @param $lat_1 float 第一个纬度
 * @param $lng_1 float 第一个经度
 * @param $lat_2 float 第二个纬度
 * @param $lng_2 float 第二个经度
 * @return float
 */
/**
 * @desc根据两点间的经纬度计算距离
 * @paramfloat $lat纬度值
 * @paramfloat $lng经度值
 */
/*function getDistance($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6367000; //地球半径

    $lat1 = ($lat1 * pi()) / 180;
    $lng1 = ($lng1 * pi()) / 180;

    $lat2 = ($lat2 * pi()) / 180;
    $lng2 = ($lng2 * pi()) / 180;

    $calcLongitude = $lng2 - $lng1;
    $calcLatitude = $lat2 - $lat1;
    $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
    $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
    $calculatedDistance = $earthRadius * $stepTwo;
    return round($calculatedDistance);
}*/

function getDistance($lat_1, $lng_1, $lat_2, $lng_2)
{
    $earthRadius = 6367000; //approximate radius of earth in meters
    $lat1 = bcdiv(bcmul($lat_1, pi(), 12), 180, 12); //($lat_1 * pi() ) / 180
    $lng1 = bcdiv(bcmul($lng_1, pi(), 12), 180, 12); //($lng_1 * pi() ) / 180
    $lat2 = bcdiv(bcmul($lat_2, pi(), 12), 180, 12); //($lat_2 * pi() ) / 180
    $lng2 = bcdiv(bcmul($lng_2, pi(), 12), 180, 12); //($lng_2 * pi() ) / 180
    $calcLongitude = bcsub($lng2, $lng1, 12); //$lng2 - $lng1;
    $calcLatitude = bcsub($lat2, $lat1, 12); //$lat2 - $lat1;
    $stepOne = pow(sin(bcdiv($calcLatitude, 2, 12)), 2) + cos($lat1) * cos($lat2) * pow(sin(bcdiv($calcLongitude, 2, 12)), 2);
//$stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
    $stepTwo = bcmul(2, asin(min(1, sqrt($stepOne))), 12); //2 * asin(min(1, sqrt($stepOne)));
    $calculatedDistance = bcmul($earthRadius, $stepTwo, 12); //$earthRadius * $stepTwo;
    return round($calculatedDistance);
}

/**
 * 计算商家热度区间(火)
 * @param $count int 商家总数
 * @param $number int 商家排名
 * @return int 火
 */
function countFire($count, $number)
{
    $number = (string)$number;
    $count = (string)$count;

    $five = bcmul($count, '0.1');
    $four = bcmul($count, '0.2');
    $three = bcmul($count, '0.4');
    $two = bcmul($count, '0.7');

    switch ($number) {
        case $number <= $five:
            $fire = 5;
            break;
        case $number <= $four:
            $fire = 4;
            break;
        case $number <= $three:
            $fire = 3;
            break;
        case $number <= $two:
            $fire = 2;
            break;
        default:
            $fire = 1;
            break;
    }

    return $fire;
}


/**
 * 时间计算
 * @param type $first_time 年月日格式
 * @param type $second_time 年月日格式
 * @param type $type 计算的结果天还是月。1结果为月，2结果为天
 */
function timeCalculation($first_time, $second_time, $type)
{

    $first = date("Y-m-d", $first_time);
    $second = date("Y-m-d", $second_time);

    $first_arr = explode('-', $first);
    $second_arr = explode('-', $second);
    $year_diff = $first_arr[0] - $second_arr[0];
    $month_diff = $first_arr[1] - $second_arr[1];

    if ($year_diff > 0) {
        $year_convert_month = $year_diff * 12;
        $month_result = $year_convert_month + $month_diff;
    } else {
        $month_result = $month_diff;
    }

    if ($type == 1) {
        return $month_result;
    } else {
        $day_diff = abs(bcsub($first_time, $second_time));
        $day_number = bcdiv($day_diff, 86400);
        return $day_number;
    }
}

/**
 * 二维数组排序
 * @param $multi_array
 * @param $sort_key
 * @param int $sort
 * @return bool
 */
function multi_array_sort($multi_array, $sort_key, $sort = SORT_ASC)
{
    if (is_array($multi_array)) {
        foreach ($multi_array as $row_array) {
            if (is_array($row_array)) {
                $key_array[] = $row_array[$sort_key];
            } else {
                return false;
            }
        }
    } else {
        return false;
    }
    array_multisort($key_array, $sort, $multi_array);
    return $multi_array;
}

/**
 * 获取mq client文件目录
 * @return string
 */
function mq_path()
{
    $path_arr = explode(DIRECTORY_SEPARATOR, $_SERVER['DOCUMENT_ROOT']);
    array_pop($path_arr);
    $pathdir = implode(DIRECTORY_SEPARATOR, $path_arr);
    return $pathdir;
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
function get_filename()
{
    return \Org\Util\Tools::file_name();
}

