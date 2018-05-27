<?php
/**
 * FileName: MerchantController.class.php
 * User: Comos
 * Date: 2017/8/22 14:03
 */

namespace V1_1\Controller;

use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\Tools;
use Think\Cache\Driver\Redis;

class MerchantController extends BaseController
{

    /**
     * 根据商户ID获取商户详情
     * @param merchant_id int 商户ID
     * @param client string 客户端标识 xcx, ios, android
     */
    public function merchantDetail()
    {
        //接收传入参数
        $data = I('post.');
        $merchant_id = isset($data['merchant_id']) ? $data['merchant_id'] : '';

        //判断数据是否合法
        if (!is_numeric($merchant_id)) Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');

        //调用模型查询的数据
        $merchantModel = D('Merchant');
        $data = $merchantModel->getMerchantById($merchant_id);

        //判断数据返回结果
        if ($data) Response::success($data);

        //错误提示
        Response::error(ReturnCode::INVALID_REQUEST, '当前商户已封禁或未审核');
    }

    /**
     * 获取商户简略信息
     * @param merchant_id int 商户ID
     */
    public function simpleInfo()
    {
        $merchant_id = I('post.merchant_id', '');

        //判断数据是否合法
        if (!is_numeric($merchant_id)) Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');

        $merchantModel = D('Merchant');
        $result = $merchantModel->getmerchantSimpleInfoById($merchant_id);

        if ($result) {
            //格式化数据
            $result['logo'] = C('attachment_url') . $result['logo'];
            $result['begin_time'] = substr($result['begin_time'], 0, -3);
            $result['end_time'] = substr($result['end_time'], 0, -3);

            //获取评分
            $redis = new Redis();
            $star = $redis->hGet('merchant_star', $merchant_id);
            if ($star) {
                $result['average'] = $star;
            }

            Response::success($result);
        }

        //数据请求失败,返回错误状态
        Response::error(ReturnCode::NOT_FOUND, $merchantModel->getError());
    }


    /**
     * 根据定位按距离获取周围商家
     */
    public function nearbyMerchant()
    {
        $keyword = I('post.keyword', '');
        $lng = I('post.lng', 0);
        $lat = I('post.lat', 0);
        $page = I('post.page', 1);
        $pagesize = I('post.pageSize', C('PAGE.PAGESIZE'));
        $type = I('post.type', 1);
        $sort = I('post.sort', 1);
        $radius = I('post.radius', 40);

        //获取商户列表数据
        if ($lng == false || $lat == false) {

            $redis = Tools::redisInstance();
            $merchantList = $redis->get('kpz_merchant_list');
            if (!$merchantList) {

                $merchantList = D('merchant')->searchNearby($lng, $lat, $radius, $keyword, $type, $sort, $page, $pagesize);
                if (!$merchantList) {
                    Response::error(ReturnCode::DB_READ_ERROR, '获取数据失败');
                }
                $redis->set('kpz_merchant_list', json_encode($merchantList), 6 * 60 * 60);

            } else {
                $merchantList = json_decode($merchantList);
            }

        } else {
            $merchantList = D('merchant')->searchNearby($lng, $lat, $radius, $keyword, $type, $sort, $page, $pagesize);
            if (!$merchantList) {
                Response::error(ReturnCode::DB_READ_ERROR, '获取数据失败');
            }
        }

        //返回数据结果
        Response::success($merchantList);
    }

    /**
     * 获取商户的卡座预定周期
     */
    public function preordainCycle()
    {
        $merchant_id = I('post.merchant_id', '');
        if (!is_numeric($merchant_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //查询商户的预定卡座周期
        $preordain_cycle = D('Merchant')->field('preordain_cycle,begin_time')->where(['id' => $merchant_id, 'status' => 2])->find();
        if (!$preordain_cycle) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取预定周期失败');
        }

        $preordain_cycle['now_time'] = time();
        $preordain_cycle['begin_time'] = substr($preordain_cycle['begin_time'], 0, 5);

        //输出数据
        Response::success($preordain_cycle);
    }

    /**
     * 获取卡座预定时间选择控件所需的时间戳
     */
    public function serverTime()
    {
        $merchant_id = I('post.merchant_id', '');
        if (!empty($merchant_id)) {
            //验证商户ID是否合法
            if (!is_numeric($merchant_id)) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
            }

            //获取商户的营业时间点
            $begin_time = D('Merchant')->getMerchantTime($merchant_id);
            if (!$begin_time) {
                Response::error(ReturnCode::DB_READ_ERROR, '获取商户营业时间失败');
            }
            //营业时间
            $data['begin_time'] = strtotime(date('Y-m-d') . ' ' . $begin_time);
        }

        //当前时间戳
        $data['now_time'] = time();
        $data['order_overtime'] = C('ORDER_OVERTIME');

        //成功返回数据
        Response::success($data);
    }


    /**
     * 商户的下单截止时间
     */
    public function closeTime()
    {
        //商户ID
        $merchant_id = I('post.merchant_id', '');

        //商户ID合法性校验
        if (!is_numeric($merchant_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //获取酒吧时间
        $mch_data = D('merchant')->getMerchantCloseTime($merchant_id);
        if (!$mch_data) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取下单截止时间失败');
        }

        //获取节点时间
        $time = time();
        $now_date = date('Y-m-d');
        $end_time = strtotime($now_date . ' ' . $mch_data['end_time']);
        $allowable_time = strtotime($now_date . ' ' . $mch_data['allowable_time']);

        //修改最晚下单时间判定 2018年1月9日15:52:46
        if ($mch_data['begin_time'] < $mch_data['allowable_time']) {
            $allowable_times = $allowable_time;
        } else {
            $allowable_times = strtotime('+1 day', $allowable_time);
        }

        //判断是否跨天（2018年1月9日15:55:23）
        if ($mch_data['begin_time'] < $mch_data['end_time']) {
            $end_times = $end_time;
        } else {
            $end_times = strtotime('+1 day', $end_time);
        }

        //判断是否在区间时间内
        if ($time > $allowable_times && $time < $end_times) {
            Response::success(['tips_msg' => '您本次购买的套餐只能在今日' . substr($mch_data['begin_time'], 0, -3) . '酒吧营业时到店消费']);
        } else {
            //你本次购买的套餐可以在酒吧营业时间结束（6：00）前到店消费；
            //也可以在营业时间段（20：00-次日6：00）期间到店消费。
            Response::success(['tips_msg' => "1、您本次购买的套餐可以在酒吧营业时间结束" . substr($mch_data['end_time'], 0, -3) . "前到店消费\n 2、也可以在营业时间段" . substr($mch_data['begin_time'], 0, -3) . "-次日" . substr($mch_data['end_time'], 0, -3) . "期间到店消费"]);
        }
    }
}
