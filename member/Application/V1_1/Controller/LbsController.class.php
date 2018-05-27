<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace V1_1\Controller;

class LbsController extends BaseController
{
    private $_lbsAk;

    public function _initialize()
    {
        parent::_initialize();
        $this->_lbsAk = C('LBS_AK.API');
    }

    /**
     * 百度lbs获取地理位置
     */
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

    /*public function getLal()
    {
        $address = '成都市成华区建设北路二段9号万科华茂广场';
        $result = $this->curl_request($address);
        var_dump($result);
        $std = json_decode($result, TRUE);
        var_dump($std['result']['location']);
        var_dump($std['result']['location']['lng']);
        var_dump($std['result']['location']['lat']);
    }*/

    /**
     * POST方式请求CURL
     * @param $url string 请求URL地址
     * @param $data
     * @return mixed
     */
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

    /**
     * 使用GET获取LBS数据
     * @param $url string 请求URL地址
     * @return mixed
     */
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

    /**
     * 创建lbs表
     */
    /* public function lbs_create_table()
     {
         $url = 'http://api.map.baidu.com/geodata/v3/geotable/create';
         $data = array(
             'name' => 'merchant_position',
             'geotype' => '1',
             'is_published' => '1',
             'ak' => $this->_lbsAk
         );
         $result = $this->curl_post_http($url, $data);
         var_dump($result);
     }*/

    /**
     *
     * 创建lbs列
     */
    /*public function create_lbs_column()
    {
        $url = 'http://api.map.baidu.com/geodata/v3/column/create';
        $data = array(
            'geotable_id' => 177482,
            'name' => '商户名称',
            'key' => 'merchant_name',
            'type' => 1,
            'is_sortfilter_field' => 1,
            'is_search_field' => 0,
            'max_length' => 11,
            'is_index_field' => 1,
            'ak' => $this->_lbsAk
        );
        $result = $this->curl_post_http($url, $data);
        var_dump(json_decode($result));
    }*/

    /**
     *
     * 查询lbs、列
     */
    /*public function check_lbs_column()
    {
        $url = 'http://api.map.baidu.com/geodata/v3/column/list?
geotable_id=177482&ak='.$this->_lbsAk;
        $result = $this->curl_get_http($url);
        var_dump($result);
        var_dump(json_decode($result, TRUE));
    }*/

    /**
     *
     * 修改lbs列
     */
    /*public function update_lbs_column()
    {
        $url = 'http://api.map.baidu.com/geodata/v3/column/delete';
        $data = array(
            'id' => 306787,
            'geotable_id' => 177482,
            'ak' => $this->_lbsAk
        );
        $result = $this->curl_post_http($url, $data);
        var_dump(json_decode($result));
    }*/

    /**
     *
     * 创建lbs数据
     */
    /*public function create_poi() {
        $url = 'http://api.map.baidu.com/geodata/v3/poi/create';
        $data = array(
            'latitude' => '30.650977',
            'longitude' => '104.091759',
            'coord_type' => 3,
            'geotable_id' => 177482,
            'ak' => $this->_lbsAk,
            'merchant_name' => '兰桂坊酒吧',
            'merchant_id' => '10',
            'title' => '兰桂坊酒吧',
            'address' => '四川省成都市锦江区水津街1号(艾荷音乐餐吧西南12米)'
        );
        $result = $this->curl_post_http($url, $data);
        var_dump(json_decode($result, TRUE));
    }*/

    /**
     * 检索周边数据
     * @param $lng float 经度
     * @param $lat float 纬度
     * @param $page int 页码
     * @param $pageSize int 每页显示数量
     * @param $keyword string 关键字
     * @return mixed
     */
    public function retrieveSurrounding($lng, $lat, $page, $pageSize, $keyword)
    {
        $keyword = !empty(trim($keyword)) ? "&q=$keyword" : '';
        $page_value = $page - 1;
        $ak = $this->_lbsAk;
        $geotable_id = C('LBS_AK.GEOTABLE_ID');
        $url = "http://api.map.baidu.com/geosearch/v3/nearby?ak=$ak&geotable_id={$geotable_id}{$keyword}&location=$lng,$lat&radius=1000000&page_index=$page_value&page_size=$pageSize&sortby=distance:1";
        $result = $this->curl_get_http($url);
        return json_decode($result, true);
    }

    /**
     * 根据经纬度获取地址
     */
    public function getAddress()
    {
        $lng = I('param.lng');
        $lat = I('param.lat');
        $ak = $this->_lbsAk;
        $url = "http://api.map.baidu.com/geocoder/v2/?location=$lat,$lng&output=json&pois=1&ak=$ak";
        $result = $this->curl_get_http($url);
        $result = json_decode($result, TRUE);

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
        $this->ajaxReturn($data);
    }

    /**
     * 将字符串转换为数组
     * @param $str
     * @return array
     */
    public function ch2arr($str)
    {
        $length = mb_strlen($str, 'utf-8');
        $array = [];
        for ($i = 0; $i < $length; $i++)
            $array[] = mb_substr($str, $i, 1, 'utf-8');
        return $array;
    }

    /**
     * 将腾讯地图的经纬度转换为百度地图经纬度
     * @param $lng float 经度
     * @param $lat float 纬度
     */
    public function getBaiduCoord($lng, $lat)
    {
        $ak = $this->_lbsAk;
        $url = "http://api.map.baidu.com/geoconv/v1/?coords=$lng,$lat&from=3&to=5&ak=$ak";
        $data = $this->curl_get_http($url);
        $data = json_decode($data, true);
        if ($data['status'] == 0) {
            return $data['result'][0];
        } else {
            return false;
        }
    }
}
