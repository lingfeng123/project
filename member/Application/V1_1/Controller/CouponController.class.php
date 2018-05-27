<?php
/**
 * FileName: CouponController.class.php
 * User: Comos
 * Date: 2018/1/18 15:49
 */

namespace V1_1\Controller;


use Org\Util\JPushNotify;
use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\Tools;

class CouponController extends BaseController
{
    private $couponModel;
    private $couponMemberModel;

    /**
     * 初始化默认数据
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->couponModel = D('coupon');
        $this->couponMemberModel = M('coupon_member');
    }

    /**
     * 会员已拥有优惠券
     */
    public function memberCards()
    {
        $member_id = I('post.member_id', '');
        $is_effective = I('post.is_effective', 1);
        $pagesize = I('post.page_size', C('PAGE.PAGESIZE'));
        $page = I('post.page', 1);

        //验证会员ID合法性
        if (!is_numeric($member_id) || !is_numeric($page) || !is_numeric($pagesize)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //列表类型
        if (!in_array($is_effective, [0, 1, 2])) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求列表参数不合法');
        }

        //根据会员ID获取会员所有优惠券
        $cards = $this->couponModel->getCardsListByMemberId($member_id, $is_effective, $pagesize, $page);
        if (!$cards) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '获取优惠券失败');
        }

        //返回成功数据
        Response::setSuccessMsg('获取卡券列表成功');
        Response::success($cards);
    }


    /**
     * 优惠券领券中心
     */
    public function cardsCenter()
    {
        $member_id = I('post.member_id', '');
        $pagesize = I('post.page_size', C('PAGE.PAGESIZE'));
        $page = I('post.page', 1);

        //验证会员ID合法性
        if (!is_numeric($member_id) || !is_numeric($page) || !is_numeric($pagesize)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //根据会员ID获取会员所有优惠券
        $cards = $this->couponModel->getAllCardsList($member_id, $pagesize, $page);
        if (!$cards) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '获取优惠券失败');
        }

        //输出成功数据
        Response::setSuccessMsg('获取可领优惠券成功');
        Response::success($cards);
    }


    /**
     * 领券中心领取优惠券
     */
    public function getCard()
    {
        $member_id = I('post.member_id', '');
        $card_id = I('post.card_id', '');

        //验证数据
        if (!is_numeric($member_id) || !is_numeric($card_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //查询卡券是否存在
        $card = $this->couponModel->where(['status' => 1])->find($card_id);
        if (!$card) {
            Response::error(ReturnCode::DB_READ_ERROR, '优惠券不存在或查询失败');
        }

        //判断是否已领取过
        $total = $this->couponMemberModel->where(['member_id' => $member_id, 'card_id' => $card_id])->count();
        if ($total) {
            Response::error(ReturnCode::DATA_EXISTS, '您已领取过此优惠券!');
        }

        //获取当前用户的性别
        $member_sex = D('member')->where(['id' => $member_id])->getField('sex');

        //判断是否是性别区分优惠券
        if ($card['is_sex'] == 0) {
            //无性别限制 普通领取
            //添加用户新卡券
            if (!$this->couponModel->addMemberNewCard($member_id, $card_id)) {
                Response::error(ReturnCode::INVALID, '领取优惠券失败!');
            }

        } else {

            //提示信息
            $message = [
                1 => "该优惠券只有男士才可以领取!",
                2 => "该优惠券只有女士才可以领取!",
            ];
            //有性别限制
            if ($card['is_sex'] != $member_sex) {
                Response::error(ReturnCode::INVALID, $message[$card['is_sex']]);
            }

            //添加用户新卡券
            if (!$this->couponModel->addMemberNewCard($member_id, $card_id)) {
                Response::error(ReturnCode::INVALID, $message[$card['is_sex']]);
            }
        }

        $this->sendNotify($member_id);

        //成功提示
        Response::setSuccessMsg('优惠券领取成功!');
        Response::success();
    }


    /**
     * 首页弹窗领取券
     */
    public function indexCards()
    {
        $member_id = I('post.member_id', '');
        if (!is_numeric($member_id)) {
            Response::error(ReturnCode::INVALID, '请求参数不合法');
        }

        //获取首页卡券并写入
        $cards = $this->couponModel->indexCardsAdd($member_id);
        if (!$cards) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取可领优惠券失败');
        }

        //成功提示
        Response::setSuccessMsg('首页券领取成功!');
        Response::success($cards);
    }

    /**
     * 推送通知
     * @param $member_id
     */
    private function sendNotify($member_id)
    {
        $msg_title = '优惠券到账通知';
        $alert = '您已收到很劲爆的优惠券，快去使用吧!';
        $message = [
            'alert' => $alert,
            'title' => $msg_title,
            'extras' => [
                'msg_type' => 'system',  //system order bar
                'title' => $msg_title,
                'content' => $alert,
                'icon' => C('MEMBER_API_URL') . '/Public/images/message/message_coupons.png',
                'order_id' => 0
            ]
        ];
        JPushNotify::toAliasNotify($member_id, $message);
    }

    /**
     * 获取可用优惠券
     * @param member_id int 用户id
     * @param merchant_id int 商户id
     * @param page int 页码
     * @param page_size int 页码大小
     * @param order_price double 订单金额
     */
    public function useFulCard()
    {
        $client = I('post.client', '');
        $member_id = I('post.member_id', '');
        $merchant_id = I('post.merchant_id', '');
        $goods_ids = I('post.goods_ids', '');
        $page = I('post.page', 1);
        $pagesize = I('post.page_size', 20);
        $money = I('post.order_price');

        if (is_numeric($member_id) && is_numeric($merchant_id) && !preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $money)) {
            Response::error(ReturnCode::INVALID, '请求参数不合法');
        }

        if ($client == 'xcx' && !is_array($goods_ids)) {
            $goods_ids = explode(',', $goods_ids);
        }

        $goods_new_arr = [];
        foreach ($goods_ids as $goods_id) {
            $goods_arr = explode('=', $goods_id);
            $goods_id = $goods_arr[0];
            $number = $goods_arr[1];
            $goods_new_arr[$goods_id] = $number;

            //验证商品ID是否合法
            if (!is_numeric($goods_id) || $goods_id < 1 || !is_numeric($number) || $number < 1) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '商品数据不合法');
            }
        }

        //重新赋值
        $goods_ids = array_keys($goods_new_arr);
        $data = $this->couponModel->useFulCard($member_id, $page, $pagesize, $merchant_id, $money, $goods_ids);
        if ($data === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取优惠券失败');
        }

        //成功提示
        Response::setSuccessMsg('获取可用优惠券成功');
        Response::success($data);
    }


    /**
     * 活动优惠券领券
     * TODO::自由变动的优惠券领取
     */
    public function getPubcard()
    {
        $member_id = I('post.member_id', '');
        $merchant_id = I('post.merchant_id', 0);
        if (!is_numeric($member_id)) {
            Response::error(ReturnCode::INVALID, '请求参数不合法');
        }

        $couponModel = D('coupon');
        //通用老用户回馈券
        $couponModel->oldUserGetCard($member_id);

        //通用首单消费返利
        $couponModel->consumerGetCard($member_id);

        //店铺 - 首单返利券
        $couponModel->dianpuFirstOrderCard($member_id);

        //店铺 - 老用户回馈券
        $couponModel->dianpuOldUserCard($member_id);

        $number = $couponModel->couponNumber;

        //查询是否绑定手机号码
        $tel = M('member')->where(['id' => $member_id])->getField('tel');

        if ($number > 0) {
            $stats = 1;

            if ($tel) {
                $bind_tel = 1;
                $message = '百元优惠礼包已到账, 点击查看我的优惠券';
            } else {
                $bind_tel = 0;
                $message = '百元优惠礼包已到账, 请绑定手机号即可使用';
            }

        } else {
            $stats = 0;
            $bind_tel = $tel ? 1 : 0;
            $message = '未领取到优惠券';
        }

        Response::setSuccessMsg($message);
        Response::success(['stats' => $stats, 'bind_tel' => $bind_tel]);
    }
}