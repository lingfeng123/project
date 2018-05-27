<?php
/**
 * FileName: TipsController.class.php
 * User: Comos
 * Date: 2018/3/21 14:42
 */

namespace V1_1\Controller;


use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\Tools;

class TipsController extends BaseController
{
    protected $orderType = [0, 1, 2, 3];
    protected $tipsContent = array(
        //【酒吧详情页里】
        'MERCHANT' => array(
            'MERCHANT_SANTAO' => "1、套餐内包含指定酒水和一个散座位置；\n2、成功购买优惠套餐后因个人原因未到店消费，该套餐将逾期自动作废，不会退还您费用。",
            'MERCHANT_KATAO' => "1、卡座套餐包含指定酒水和一个卡座，成功购买卡座套餐后，酒吧会分配一个客户经理和一个卡座给您；\n2、成功购买卡座套餐后，因个人原因未到店消费，自付款日起，套餐内的酒水我们将根据您的会员等级为您保留一定的天数，在保留期里，您须预定一个卡座后到店消费，若保留期结束还未消费，则该卡座套餐将作废，不会退还您费用。",
            'MERCHANT_KAZUO' => "1、如果卡座预定页面显示了客户经理，则您需要先选择一位客户经理，如果没有显示客户经理，在您成功预定卡座后，酒吧会为您分配一位客户经理；\n2、在去酒吧消费前，若有任何疑问，您可以联系您的客户经理，若因个人原因未到店消费，卡座将作废，不会退还您预定金。\n3、周末、节假日卡座的最低消费会与平时的最低消费不同，根据酒吧实际情况，价格会有所上涨。",
            'MERCHANT_DANPIN' => "1、购买套餐时可以一起购买单品酒水。\n2、如果只购买了单品酒水，在有效期内可以直接去酒吧消费，有效期可以在酒吧公告里看到。\n3、有效期截止后还未到店消费，则购买的单品酒水将作废，不会退还您费用。",
        ),

        //【确认订单页面】
        'CONFIRM' => array(
            'CONFIRM_SANTAO' => "1、本套餐须在#keyword1#前到店消费，若因个人原因未到店消费，本套餐将逾期自动作废，不会退还您费用。\n2、本套餐内包含指定酒水和一个散座位置。",
            'CONFIRM_KATAO' => "1、成功购买卡座套餐后，若因个人原因未到店消费，自付款日起，套餐内的酒水我们将根据您的会员等级为您保留一定的天数，在保留期里，您须预定一个卡座后到店消费，若保留期结束还未消费，则该卡座套餐将作废，不会退还您费用。\n2、卡座套餐包含指定酒水和一个卡座，成功购买卡座套餐后，酒吧会分配一个客户经理和一个卡座给您，您有任何疑问都可以联系您的客户经理。",
            'CONFIRM_KAZUO' => "1、本卡座须在#keyword1#前到店消费，若因个人原因未到店消费，本卡座将作废，不会退还您预定金。\n2、预定金会抵扣您在酒吧的消费金额。\n3、如果您未选择客户经理，在您成功预定卡座后，酒吧会为您分配一位客户经理，您有任何疑问都可以联系您的客户经理。",
            'CONFIRM_DANPIN' => "1、您购买的单品酒水在有效期内任何一天都可以去酒吧消费，有效期截止后还未到店消费，该订单将作废，不会退还您费用。\n2、单品酒水的有效期可以在酒吧公告里查看。",
        ),

        //【点击“提交订单”按钮弹出框的说明】
        'SUBMIT' => array(
            'SUBMIT_SANTAO' => "本套餐须在#keyword1#前到店消费，逾期作废将不退还费用。",
            'SUBMIT_KATAO' => "当前为酒吧营业时间内#keyword1#购买卡座套餐，因卡座套餐会分配一个卡座，请与酒吧联系是否还有空余卡座可消费，核实清楚后再决定是否购买。",
            'SUBMIT_KAZUO' => "1、本卡座须在#keyword1#前到店消费，逾期作废将不退还预定金。\n2、您可在去消费前联系客户经理做进一步的沟通。",
            'SUBMIT_DANPIN' => "购买的单品酒水在有效期内任何一天可以去酒吧消费，有效期结束后还未消费，则订单将作废，不会退还您费用，请按时去消费。",
        ),
    );

    /**
     * 商户详情页面提示文字
     */
    public function merchantDetail()
    {
        $merchant_id = I('post.merchant_id', '');
        $order_type = I('post.order_type', '');

        if (!is_numeric($merchant_id) || $order_type === '' || !in_array($order_type, $this->orderType)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        $tips = $this->tipsContent['MERCHANT'];
        switch ($order_type) {
            case 0:
                $order_tips = $tips['MERCHANT_DANPIN'];
                break;
            case 1:
                $order_tips = $tips['MERCHANT_KAZUO'];
                break;
            case 2:
                $order_tips = $tips['MERCHANT_KATAO'];
                break;
            case 3:
                $order_tips = $tips['MERCHANT_SANTAO'];
                break;
        }

        Response::setSuccessMsg('请求提示信息成功');
        Response::success(['merchant_id' => $merchant_id, 'order_tips' => $order_tips]);
    }


    /**
     * 确认订单页面提示文字
     */
    public function firmOrder()
    {
        $merchant_id = I('post.merchant_id', '');
        $order_type = I('post.order_type', '');

        if (!is_numeric($merchant_id) || $order_type === '' || !in_array($order_type, $this->orderType)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        $merchant = M('merchant')->field('begin_time, end_time')->find($merchant_id);
        if (!$merchant) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取商户数据失败');
        }

        $tips = $this->tipsContent['CONFIRM'];
        switch ($order_type) {
            case 0:
                $order_tips = $tips['CONFIRM_DANPIN'];
                break;
            case 1:
                $order_tips = str_replace('#keyword1#', Tools::formatTimeStr($merchant['begin_time']), $tips['CONFIRM_KAZUO']);
                break;
            case 2:
                $order_tips = $tips['CONFIRM_KATAO'];
                break;
            case 3:
                $order_tips = str_replace('#keyword1#', Tools::formatTimeStr($merchant['begin_time']), $tips['CONFIRM_SANTAO']);
                break;
        }

        Response::setSuccessMsg('请求提示信息成功');
        Response::success(['merchant_id' => $merchant_id, 'order_tips' => $order_tips]);
    }

    /**
     * 提交订单页面提示文字
     */
    public function submitOrder()
    {
        $merchant_id = I('post.merchant_id', '');
        $order_type = I('post.order_type', '');
        $date = I('post.date', '');

        if (!is_numeric($merchant_id) || $order_type === '' || !in_array($order_type, $this->orderType)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        $date = date('Y年m月d日', strtotime($date));

        $merchant = M('merchant')->field('begin_time, end_time')->find($merchant_id);
        if (!$merchant) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取商户数据失败');
        }

        $tips = $this->tipsContent['SUBMIT'];
        switch ($order_type) {
            case 0:
                //单品酒水
                $order_tips = $tips['SUBMIT_DANPIN'];
                break;
            case 1:
                //卡座
                $order_tips = str_replace('#keyword1#', $date . Tools::formatTimeStr($merchant['begin_time']), $tips['SUBMIT_KAZUO']);
                break;
            case 2:
                //卡套
                $order_tips = str_replace('#keyword1#', Tools::formatTimeStr($merchant['begin_time']) . '-' . Tools::formatTimeStr($merchant['end_time']), $tips['SUBMIT_KATAO']);
                break;
            case 3:
                //散套
                $order_tips = str_replace('#keyword1#', $date . Tools::formatTimeStr($merchant['begin_time']), $tips['SUBMIT_SANTAO']);
                break;
        }

        Response::setSuccessMsg('请求提示信息成功');
        Response::success(['merchant_id' => $merchant_id, 'order_tips' => $order_tips]);
    }
}