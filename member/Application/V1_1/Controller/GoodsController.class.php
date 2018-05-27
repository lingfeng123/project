<?php
/**
 * Created by PhpStorm.
 * User: MeBook
 * Date: 2017/8/9
 * Time: 22:38
 */

namespace V1_1\Controller;


use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\Tools;

class GoodsController extends BaseController
{

    /**
     * 卡座列表(无排序) 2017年10月24日18:37:48 v1.0
     * @param merchant_id int 商户ID
     * @param date string 年月日 2010-10-11
     */
    public function seatList()
    {
        $merchant_id = I('post.merchant_id', '');
        $date = I('post.date', '');

        //验证数据
        if (!is_numeric($merchant_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //验证选择日期格式
        if (!preg_match('/^\d{4}(\-|\/|.)\d{1,2}\1\d{1,2}$/', $date)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '日期格式不正确');
        }

        //获取未预定卡座
        $goodsSeatModel = D('goods_seat');
        $result = $goodsSeatModel->seatList($merchant_id, $date);
        if ($result === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取卡座失败');
        }

        //返回全部卡座数据
        Response::setSuccessMsg('获取小程序卡座列表成功');
        Response::success($result);
    }

    /**
     * APP 卡座列表 v2.0
     * @param merchant_id int 商户ID
     * @param date string 年月日 2010-10-11
     */
    public function seats()
    {
        $merchant_id = I('post.merchant_id', '');
        $date = I('post.date', '');

        //验证数据
        if (!is_numeric($merchant_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //验证选择日期格式
        if (!preg_match('/^\d{4}(\-|\/|.)\d{1,2}\1\d{1,2}$/', $date)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '日期格式不正确');
        }

        //获取未预定卡座
        $goodsSeatModel = D('goods_seat');
        $result = $goodsSeatModel->seatList($merchant_id, $date);
        if ($result === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取卡座失败');
        }

        //获取可以预定的卡座，并且按照价格分类；修改时间：2017-10-26 14:05:48
        $free_seat = array();
        foreach ($result as $k => $val) {
            if ($result[$k]['floor_price'] < C('floor_sale.low')) {
                $free_seat[C('floor_sale.low')][] = $val;
            } else if ($result[$k]['floor_price'] >= C('floor_sale.low') && $result[$k]['floor_price'] < C('floor_sale.mid')) {
                $free_seat[C('floor_sale.mid')][] = $val;
            } else if ($result[$k]['floor_price'] >= C('floor_sale.mid')) {
                $free_seat[C('floor_sale.high')][] = $val;
            }
        }
        //数组排序
        ksort($free_seat);

        $free_seat = $this->array_map($free_seat);
        //返回全部卡座数据
        Response::setSuccessMsg('获取APP端卡座列表成功');
        Response::success($free_seat);
    }

    protected function array_map($arr)
    {
        $arr1 = array();
        $arr2 = [];
        foreach ($arr as $k => $v) {
            $arr1['key'] = $k;
            $arr1['value'] = $v;
            $arr2[] = $arr1;
        }
        return $arr2;
    }

    /**
     * 验证卡座是否可预定
     * @param merchant_id int 商户ID
     * @param seat_id int 卡座列表
     * @param date string 年月日 2010-10-11
     */
    public function checkSeat()
    {
        $merchant_id = I('post.merchant_id', '');
        $seat_id = I('post.seat_id', '');
        $member_id = I('post.member_id', '');
        $date = I('post.date', '');

        Tools::orderAllowedValid();     //下单时间限制判断

        //验证数据
        if (!is_numeric($merchant_id) || empty($date) || !is_numeric($seat_id) || !is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        $goodsSeatModel = D('goods_seat');
        $result = $goodsSeatModel->validateSeatStock($merchant_id, $seat_id, $member_id, $date);
        if (!$result) {
            Response::error(ReturnCode::INVALID_REQUEST, $goodsSeatModel->getError());
        }

        //有库存响应结果
        Response::success();
    }

    /**
     * 获取卡座平面图
     * @param merchant_id int 商户ID
     */
    public function seatMap()
    {
        $merchant_id = I('param.merchant_id');

        if (!is_numeric($merchant_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //查询卡座平面图
        $maps = M('goods_seatmap')->where("merchant_id = '$merchant_id'")->field('image_client,floor')->select();
        if ($maps === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取数据失败');
        }

        //组装图片地址前缀
        $attachment_url = C('attachment_url');
        //处理数据,添加图片地址前缀
        foreach ($maps as $key => $item) {
            $maps[$key]['image_client'] = $attachment_url . $maps[$key]['image_client'];
        }

        //返回成功数据
        Response::success($maps, ReturnCode::SUCCESS);
    }


    /**
     * 获取套餐列表 v2.0
     * @param date string 当天日期  2017-10-01
     * @param type int 套餐类型
     * @param merchant_id int 商户ID
     */
    public function packList()
    {
        $date = I('post.date', '');
        $type = I('post.type', '');
        $member_id = I('post.member_id', 0);
        $merchant_id = I('post.merchant_id', '');

        //验证数据合法性
        if (!$date || !is_numeric($merchant_id) || !in_array($type, [1, 2])) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //获取库存套餐
        $goodsPackModel = D('goods_pack');
        $list = $goodsPackModel->getPackGoodsList($merchant_id, $date, $type, $member_id);
        if (!$list) {
            Response::error(ReturnCode::DB_READ_ERROR, $goodsPackModel->getError());
        }

        //返回数据结果
        Response::success($list);
    }

    /**
     * 验证商品库存是否可售  v1.0
     * @param date string 当天日期  2017-10-01
     * @param merchant_id int 商户ID
     */
    public function checkStock()
    {
        $date = I('post.date', '');
        $goods_id = I('post.goods_id', '');
        $merchant_id = I('post.merchant_id', '');
        $member_id = I('post.member_id', '');

        Tools::orderAllowedValid();     //下单时间限制判断

        //验证数据合法性
        if (!is_numeric($goods_id) || !is_numeric($merchant_id) || !is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //验证预定商品的日期
        if (!preg_match('/^\d{4}(\-|\/|.)\d{1,2}\1\d{1,2}$/', $date)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '日期选择不正确');
        }

        //验证库存是否充足
        $goodsPackModel = D('goods_pack');
        $rs = $goodsPackModel->getGoodsStockBoolean($date, $goods_id, $merchant_id, $member_id);
        if (!$rs) {
            Response::error(ReturnCode::DB_READ_ERROR, $goodsPackModel->getError());
        }

        //返回数据结果
        Response::success([]);
    }


    /**
     * 正常购买单品酒水  v2.0
     * 此接口不用修改每天一个价格操作
     * @param $merchant_id int 商户ID
     */
    public function singleWineList()
    {
        $merchant_id = I('post.merchant_id', '');

        //验证数据合法性
        if (!is_numeric($merchant_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //获取库存套餐
        $goodsPackModel = D('goods_pack');
        $list = $goodsPackModel->getSingleWineData($merchant_id);
        if (!$list) {
            Response::error(ReturnCode::DB_READ_ERROR, $goodsPackModel->getError());
        }

        //返回数据结果
        Response::success($list);
    }

    /**
     * 优惠金额
     * @param $member_id int 用户ID
     * @param $merchant_id int 商户ID
     * @param $seat_id int 卡座ID
     */
    public function discount()
    {
        $member_id = I('post.member_id', '');
        $merchant_id = I('post.merchant_id', '');
        $seat_id = I('post.seat_id', '');

        if (!is_numeric($member_id) || !is_numeric($merchant_id) || !is_numeric($seat_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //获取卡座商品数据
        $goods_seat_info = D('goods_seat')->where(['id' => $seat_id, 'merchant_id' => $merchant_id])->find();
        if (!$goods_seat_info) {
            Response::error(ReturnCode::DB_READ_ERROR, '请求卡座数据失败');
        }

        //查询历史逾期卡套订单
        $relation_order_no = D('order')->where(['merchant_id' => $merchant_id, 'member_id' => $member_id, 'status' => 3, 'order_type' => 2])->order('id desc')->getField('order_no');
        if ($relation_order_no === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '请求数据失败');
        }

        //获得当前会员的逾期卡与免预定金特权数据
        $member_card = D('member')->field('used_card,overdue,free_seat')->join('api_member_privilege ON api_member.level = api_member_privilege.level')->where(['api_member.id' => $member_id])->find();
        if ($member_card === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '请求数据失败');
        }

        //优惠金额
        $discount_money_value = $goods_seat_info['set_price'];
        $pay_price = $goods_seat_info['set_price'];
        //判断是否有可用逾期卡
        $have_card = $member_card['overdue'] - $member_card['used_card'];
        $have_card = $have_card ? $have_card : 0;

        $discount_money = 0;
        if ($relation_order_no) {
            //获取未使用逾期卡数量
            if (!$member_card['overdue']) {
                $pay_price = $goods_seat_info['set_price'];
                $discount_money = '0.00';
            } else {
                //根据逾期卡数据来设定应支付金额
                if ($have_card) {
                    $discount_money = $discount_money_value;
                    $pay_price = '0.00';
                } else {
                    $pay_price = $goods_seat_info['set_price'];
                    $discount_money = '0.00';
                }
            }
        }

        //判断是否有免预定金特权
        if ($member_card['free_seat']) {
            $discount_money = $discount_money_value;
            $pay_price = '0.00';
        }

        //返回成功数据
        Response::success(['discount_money' => $discount_money, 'pay_price' => $pay_price]);
    }

    /**
     * 钱包余额充值金额
     */
    public function recharge()
    {
        $member_id = I('post.member_id', '');
        if (!is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }
        $recharge_limit = C('RECHARGE_LIMIT');

        //总充值金额大于阀值关闭赠送
        $give_money = M('member_capital')->sum('give_money');
        if ($give_money >= C('SYS_MAX_GIVE_MONEY')) {
            $recharge_limit = array_map(function () {
                return 0;
            }, $recharge_limit);
        }

        $data = [];
        foreach ($recharge_limit as $key => $value) {
            $count = M('member_order')->where(['member_id' => $member_id, 'recharge_money' => $key])->count();
            if ($count > 0) {
                $value = 0;
            }

            $data[] = [
                'recharge_money' => $key,
                'give_money' => $value,
            ];
        }
        //返回成功数据
        Response::success($data);
    }
}