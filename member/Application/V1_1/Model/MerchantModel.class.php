<?php
/**
 * FileName: MerchantModel.class.php
 * User: Comos
 * Date: 2017/8/22 14:31
 */

namespace V1_1\Model;


use Org\Util\Tools;
use Think\Cache\Driver\Redis;
use Think\Model;

class MerchantModel extends Model
{

    /**
     * mechant detail
     * @param $merchant_id
     * @return bool|mixed
     */
    public function getMerchantById($merchant_id)
    {
        //根据商户ID获取商户资料
        $fields = "id,description,tel,image,province,city,area,address,tags,lat,lng,tencent_map,avg_consume,notice";
        $merchantInfo = $this->field($fields)->where(['status' => 2])->find($merchant_id);

        //判断数据是否存在
        if (!$merchantInfo) return false;

        //处理格式化商户信息数据
        $merchantInfo['image'] = explode('|', $merchantInfo['image']);
        foreach ($merchantInfo['image'] as $key => $value) {
            $merchantInfo['image'][$key] = C('attachment_url') . $value;
        }

        $merchantInfo['tags'] = explode('|', $merchantInfo['tags']);
        $merchantInfo['address'] = $merchantInfo['province'] . $merchantInfo['city'] . $merchantInfo['area'] . $merchantInfo['address'];

        unset($merchantInfo['province']);
        unset($merchantInfo['city']);
        unset($merchantInfo['area']);

        $merchantInfo['id'] = (int)$merchantInfo['id'];
        $merchantInfo['lat'] = (float)$merchantInfo['lat'];
        $merchantInfo['lng'] = (float)$merchantInfo['lng'];


        //返回商户资料
        return $merchantInfo;
    }


    /**
     * simple Info
     * @param $merchant_id
     * @return bool|mixed
     */
    public function getmerchantSimpleInfoById($merchant_id)
    {
        $merchant_data = $this->field('id as merchant_id,title,logo,avg_consume,begin_time,end_time,average,tel,notice,ka_tips,san_tips,seat_tips,dan_tips,activity,merchant_type')
            ->join('__COMMENT_MCHSTAR__ ON __COMMENT_MCHSTAR__.merchant_id = __MERCHANT__.id')
            ->where(['id' => $merchant_id, 'status' => 2])
            ->find();
        if ($merchant_data === false) {
            $this->error = '获取商户数据失败';
            return false;
        }

        return $merchant_data;
    }

    /**
     * 根据关键词或条件搜索商家列表
     * @param $lng  float 经度
     * @param $lat  float 纬度
     * @param $radius  int  检索半径 单位km
     * @param $keyword string   搜索关键字
     * @param $type int 排序类型
     * @param $sort int 排序方式
     * @param $page int 当前页码
     * @param $pagesize int 每页显示数量
     * @return array|bool
     */
    public function searchNearby($lng, $lat, $radius, $keyword, $type, $sort, $page, $pagesize)
    {
        $where['api_merchant.status'] = 2;
        if (!empty(trim($keyword))) {
            $where['title'] = ['like', '%' . $keyword . '%'];
        }

        //排序的类型。1距离，3评分，4人均消费
        $order = 'signed desc, sort asc, ';

        //判断是否传入了坐标
        $field = "round(6378.138*2*asin(sqrt(pow(sin( ($lat*pi()/180-lat*pi()/180)/2),2)+cos($lat*pi()/180)*cos(lat*pi()/180)* pow(sin( ($lng*pi()/180-lng*pi()/180)/2),2))),1) as distance,";
        if ($lng == false || $lat == false) {
            $field = "('') AS distance,";
            $order_ext = 'signed';
        } else {
            $radius = $radius * 1000;   //计算成米
            $range = Tools::getRange($lat, $lng, $radius);  //计算四个角坐标
            $where['lat'] = ['BETWEEN', [$range['minLat'], $range['maxLat']]];
            $where['lng'] = ['BETWEEN', [$range['minLng'], $range['maxLng']]];
            $order_ext = 'distance';
        }

        //排序方式
        switch ($type) {
            case 1:
                $order .= $order_ext;
                break;
            case 3:
                $order .= 'average';
                break;
            case 4:
                $order .= 'avg_consume';
                break;
            default:
                $order .= $order_ext;
                break;
        }

        //排序序列。1升序，2降序
        switch ($sort) {
            case 1:
                $order .= ' ASC';
                break;
            case 2:
                $order .= ' DESC';
                break;
            default:
                $order .= ' ASC';
                break;
        }

        //查询数据
        $count = $this->join('api_comment_mchstar ON api_comment_mchstar.merchant_id = api_merchant.id', 'LEFT')->where($where)->count();
        $result = $this->field("
        api_merchant.id as merchant_id,
        api_merchant.logo,
        api_merchant.title as merchant_name,
        api_merchant.avg_consume,
        api_comment_mchstar.average,
        $field
        api_merchant.city,
        api_merchant.area,
        api_merchant.signed,
        api_merchant.address")
            ->join('api_comment_mchstar ON api_comment_mchstar.merchant_id = api_merchant.id', 'LEFT')
            ->page($page, $pagesize)
            ->where($where)
            ->order($order)
            ->select();

        if ($result === false || $count === false) {
            return false;
        }

        $attachment_url = C('ATTACHMENT_URL');
        if (count($result) > 0) {
            foreach ($result as $key => $value) {
                unset($result[$key]['city']);
                unset($result[$key]['area']);
                unset($result[$key]['address']);
                $result[$key]['logo'] = $attachment_url . $value['logo'];
                $result[$key]['address'] = $value['city'] . $value['area'] . $value['address'];

                if ($value['average'] < 3) {
                    $redis = Tools::redisInstance();
                    $star = $redis->hGet('merchant_star', $value['merchant_id']);
                    if ($star) {
                        $result[$key]['average'] = $star;
                    } else {
                        $star = '3.0';
                        if ($value['signed']) {
                            $star = '5.0';
                        }
                        $redis->hSet('merchant_star', $value['merchant_id'], $star);
                        $result[$key]['average'] = $star;
                    }
                }
            }
        }

        return ['list' => $result, 'total' => $count];
    }

    /**
     * 获取营业时间
     */
    public function getMerchantTime($merchant_id)
    {
        $begin_time = $this->where(['id' => $merchant_id])->getField('begin_time');
        if ($begin_time === false) {
            $this->error = '获取营业时间失败';
            return false;
        }
        return $begin_time;
    }

    /**
     * 获取商户的最迟下单时间
     */
    public function getMerchantCloseTime($merchant_id)
    {
        $mch_data = $this->field('id as merchant_id,allowable_time,end_time,begin_time')->where(['id' => $merchant_id])->find();
        if (!$mch_data) {
            return false;
        }

        return $mch_data;
    }
}