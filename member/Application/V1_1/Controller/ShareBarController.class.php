<?php
/**
 * FileName: ShareBarController.class.php
 * User: Comos
 * Date: 2018/3/29 9:44
 */

namespace V1_1\Controller;


use Org\Util\Response;
use Org\Util\ReturnCode;
use Think\Controller;

class ShareBarController extends Controller
{

    /**
     * 拼吧详情
     */
    public function barDetail()
    {
        $bar_id = I('post.bar_id', '');

        if (!is_numeric($bar_id)) {
            Response::error(ReturnCode::PARAM_INVALID, '请求参数不合法');
        }

        $orderModel = D('bar');
        //存在的订单,获取订单信息
        $order = $orderModel->getBarOrderInfo($bar_id);
        if ($order === false) {
            Response::error(ReturnCode::DATA_EXISTS, '该订单不存在');
        }
        Response::setSuccessMsg('数据获取成功');
        Response::success($order);
    }

    /**
     * 分享拼吧
     */
    public function index($bar_id)
    {
        if (!is_numeric($bar_id)) {
            $this->error('抱歉, 请求参数错误');
        }

        $this->assign('bar_id', $bar_id);
        $this->display();
    }
}