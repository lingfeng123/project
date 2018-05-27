<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Admin\Controller;


use Think\Controller;

//class LbsController extends BaseController {
class LbsController extends Controller
{
    private $_lbsAk;

    public function _initialize()
    {
        $this->_lbsAk = C('LBS_AK.API');
    }

    //百度lbs获取地理位置
    private function curl_request($address)
    {
        $url = 'http://api.map.baidu.com/geocoder/v2/';

        $data = array(
            'address' => $address,
            'output' => 'json',
            'ak' => $this->_lbsAk
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 4);
        curl_setopt($ch, CURLOPT_ENCODING, ""); //必须解压缩防止乱码
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 5.1; zh-CN) AppleWebKit/535.12 (KHTML, like 
Gecko) Chrome/22.0.1229.79 Safari/535.12");
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public function getLal()
    {
        $address = '成都市成华区建设北路二段9号万科华茂广场';
        $result = $this->curl_request($address);
        var_dump($result);
        $std = json_decode($result, TRUE);
        var_dump($std['result']['location']);
        var_dump($std['result']['location']['lng']);
        var_dump($std['result']['location']['lat']);
    }

    //curl_post_http
    private function curl_post_http($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 4);
        curl_setopt($ch, CURLOPT_ENCODING, ""); //必须解压缩防止乱码
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 5.1; zh-CN) AppleWebKit/535.12 (KHTML, like Gecko) Chrome/22.0.1229.79 Safari/535.12");
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    //使用get获取lbs数据
    public function curl_get_http($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    //创建lbs表
    public function lbs_create_table()
    {
        $url = 'http://api.map.baidu.com/geodata/v3/geotable/create';
        $data = array(
            'name' => 'merchant_position',
            'geotype' => '1',
            'is_published' => '1',
            'ak' => $this->_lbsAk
        );
        echo $this->curl_post_http($url, $data);
    }

    //创建lbs列
    public function create_lbs_column()
    {
        $url = 'http://api.map.baidu.com/geodata/v3/column/create';
        $data = array(
            'geotable_id' => C('LBS_AK.GEOTABLE_ID'),
            'name' => '商户id',
            'key' => 'merchant_id',
            'type' => 1,
            'is_sortfilter_field' => 0,
            'is_search_field' => 0,
            'max_length' => 11,
            'is_index_field' => 1,
            'is_unique_field' => 1,
            'ak' => $this->_lbsAk
        );
        echo $this->curl_post_http($url, $data);
    }

    //修改lbs列
    public function update_lbs_column()
    {
        $url = 'http://api.map.baidu.com/geodata/v3/column/update';
        $data = array(
            'id' => 312455,
            'geotable_id' => C('LBS_AK.GEOTABLE_ID'),
            'name' => '商户id',
            'key' => 'merchant_id',
            'type' => 1,
            'is_sortfilter_field' => 0,
            'is_search_field' => 0,
            'max_length' => 11,
            'is_index_field' => 1,
            'is_unique_field' => 1,
            'ak' => $this->_lbsAk
        );
        echo $this->curl_post_http($url, $data);
    }

    //查询lbs列
    public function get_lbs_column()
    {
        $url = 'http://api.map.baidu.com/geodata/v3/column/list?';
        $params = [
            'geotable_id' => C('LBS_AK.GEOTABLE_ID'),
            'ak' => $this->_lbsAk
        ];
        $url = $url . http_build_query($params);
        echo $this->curl_get_http($url);
    }

    //创建lbs数据
    public function create_poi($data)
    {
        $url = 'http://api.map.baidu.com/geodata/v3/poi/create';
        $result = $this->curl_post_http($url, $data);
        return json_decode($result, TRUE);
    }

    //修改lbs数据
    public function update_poi($data)
    {
        $url = 'http://api.map.baidu.com/geodata/v3/poi/update';
        $result = $this->curl_post_http($url, $data);
        return json_decode($result, true);
    }

    //检索周边数据
    public function retrieveSurrounding()
    {
        $ak = $this->_lbsAk;
        $geotable_id = C('LBS_AK.GEOTABLE_ID');
        $url = 'http://api.map.baidu.com/geosearch/v3/nearby?ak=' . $ak . '&geotable_id=' . $geotable_id . '&location=104.11230963686,30.680447313213&radius=10000&sortby=distance:1';
        echo $this->curl_get_http($url);
    }

    /**
     * 同时经纬度获取地址
     * @param type $lng
     * @param type $lat
     * @return string
     */
    public function getAddress($lng, $lat)
    {
        $ak = $this->_lbsAk;
        $url = "http://api.map.baidu.com/geocoder/v2/?location=$lat,$lng&output=json&pois=1&ak=$ak";
        $result_a = $this->curl_get_http($url);
        $result = json_decode($result_a, TRUE);

        $data['province'] = $result['result']['addressComponent']['province'];
        $data['city'] = $result['result']['addressComponent']['city'];
        $data['district'] = $result['result']['addressComponent']['district'];

        $str = $result['result']['pois']['0']['addr'];
        $add_list = $this->ch2arr($str);
        $c = mb_substr($result['result']['addressComponent']['city'], -1);
        $d = mb_substr($result['result']['addressComponent']['district'], -1);
        $d_index = array_search($d, $add_list);
        $c_index = array_search($c, $add_list);
        if ($d_index) {
            $start = (int)($d_index + 1);
        } else {
            $start = (int)($c_index + 1);
        }
        $addr = mb_substr($str, $start, -1);
        $address = $addr . $result['result']['pois']['0']['name'];
        $data['address'] = $address;
        return $data;
    }

    public function ch2arr($str)
    {
        $length = mb_strlen($str, 'utf-8');
        $array = [];
        for ($i = 0; $i < $length; $i++)
            $array[] = mb_substr($str, $i, 1, 'utf-8');
        return $array;
    }

}
