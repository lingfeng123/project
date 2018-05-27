<?php
/**
 * FileName: CommentController.class.php
 * User: Comos
 * Date: 2017/8/22 16:02
 */

namespace V1_1\Controller;


use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\Tools;

class CommentController extends BaseController
{

    /**
     * 根据商户ID获取商户的评价星星
     * @param $merchant_id int 商户ID
     * @param $client string 客户端标识 xcx, ios, android
     */
    public function merchantStar()
    {
        //接收传入参数
        $data = I('post.');
        $merchant_id = isset($data['merchant_id']) ? $data['merchant_id'] : '';

        //判断数据是否合法
        if (empty($merchant_id) || !is_numeric($merchant_id)) Response::error(ReturnCode::INVALID_REQUEST, '请求参数不合法');
        //查询评论数据
        $commentMchstar = M('CommentMchstar');
        $data = $commentMchstar->where(['merchant_id' => $merchant_id])->find();
        $data['environment_star'] = sprintf("%.1f", $data['environment_star'] / $data['amount']);
        $data['atmosphere_star'] = sprintf("%.1f", $data['atmosphere_star'] / $data['amount']);
        $data['service_star'] = sprintf("%.1f", $data['service_star'] / $data['amount']);
        unset($data['amount']);

        //判断数据返回结果
        if ($data) Response::success($data);
        //错误提示
        Response::error(ReturnCode::INVALID_REQUEST, '请求数据不存在');
    }

    /**
     * 根据商户ID获取商户的评价内容
     * @param $merchant_id int 商户ID
     * @param $client string 客户端标识 xcx, ios, android
     * @param $page string 当前页码
     * @param $page_size int 每页显示数据
     */
    public function commentList()
    {
        //接收传入参数
        $data = I('post.');
        $merchant_id = isset($data['merchant_id']) ? $data['merchant_id'] : '';
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : C('PAGE.PAGESIZE');

        if (!is_numeric($merchant_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数非法');
        }

        //查询评论数据
        $commentMerchant = D('CommentMerchant');
        $commentList = $commentMerchant->getMerchantListByMerchantId($merchant_id, $page, $page_size);
        if ($commentList === false) {
            Response::error(ReturnCode::INVALID_REQUEST, '请求数据失败');
        }
        //返回结果数据
        Response::success($commentList);
    }

    /**
     * 根据订单编号用户ID获取基础数据
     * @param $order_no int 订单编号
     * @param $member_id int 用户ID
     */
    public function commentBaseInfo()
    {
        $order_id = I('post.order_id');
        $member_id = I('post.member_id');

        //验证订单合法性
        if (!is_numeric($member_id) || !is_numeric($order_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '参数请求不正确');
        }

        //查询数据
        $infos = D('Order')->field('api_order.id as order_id,order_no, merchant_id, logo,order_type, title as merchant_title, employee_id, employee_realname, employee_avatar')
            ->join('api_merchant ON api_order.merchant_id = api_merchant.id')
            ->where(['api_order.id' => $order_id, 'api_order.member_id' => $member_id])
            ->find();

        //判断数据是否存在
        if (!$infos) {
            Response::error(ReturnCode::NOT_FOUND, '数据未找到');
        }

        $attachment_url = C('ATTACHMENT_URL');
        $infos['logo'] = $attachment_url . $infos['logo'];

        //判断员工ID是否存在
        if ($infos['order_type'] == 1) {
            $infos['employee_avatar'] = $attachment_url . $infos['employee_avatar'];
        } else {
            unset($infos['employee_id']);
            unset($infos['employee_realname']);
            unset($infos['employee_avatar']);
        }

        //返回数据
        Response::success($infos);
    }

    /**
     * 用户提交评价
     * @param $member_id    int    用户id
     * @param $order_no    string    订单号
     * @param $environment_star    int    环境评分
     * @param $atmosphere_star    int    演义评分
     * @param $service_star    int    服务评分
     * @param $employee_star int 对服务人员的评分
     * @param $content    string    评价内容
     */
    public function submitComment()
    {
        $member_id = I('member_id', '');
        $order_id = I('order_id', '');
        $environment_star = I('environment_star', 5);
        $atmosphere_star = I('atmosphere_star', 5);
        $service_star = I('service_star', 5);
        $employee_star = I('employee_star', 5);
        $content = I('content', '');

        //验证订单合法性
        if (!is_numeric($member_id) || !is_numeric($order_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '参数请求不正确');
        }

        //验证评分合法性
        $vali_data = [$environment_star, $service_star, $employee_star, $atmosphere_star];
        foreach ($vali_data as $value) {
            if (!is_numeric($value) || $value > 5 || $value < 1) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '参数请求不正确');
            }
        }

        //过滤表情
        $content = Tools::filterEmoji($content);

        /*//查询用户是否已评价当前订单
        $is_evaluate = M('comment_member')->field('is_evaluate')->where(['order_no' => $order_no, 'member_id' => $member_id])->find();
        //判断订单是否已评价
        if ($is_evaluate['is_evaluate']) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '您已评价过该订单');
        }

        //查询该订单是是否已存在评价记录
        $res = D('comment_merchant')->where(['order_no' => $order_no])->find();
        if ($res) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '您已评价过该订单');
        }*/

        //查询订单信息
        $orderInfo = D('Order')->field('id,status, order_no,member_id, employee_id, order_type, merchant_id,is_evaluate')->where(['id' => $order_id, 'member_id' => $member_id])->find();
        if (!$orderInfo) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '该订单不存在');
        }

        //判断订单是否为已完成
        if (!$orderInfo || $orderInfo['status'] != 4) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '订单不存在或状态不允许评价');
        }

        //判断订单是否已评价
        if ($orderInfo['is_evaluate']) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '您已评价过该订单');
        }

        //如果评价内容不为空
        if ($content) {
            //验证用户输入评价内容合法性
            $this->_validateContent($content);
        }

        //组装数据
        $data = [
            'member_id' => $member_id,
            'employee_id' => $orderInfo['employee_id'],
            'merchant_id' => $orderInfo['merchant_id'],
            'order_no' => $orderInfo['order_no'],
            'order_id' => $orderInfo['id'],
            'environment' => $environment_star,
            'atmosphere' => $atmosphere_star,
            'service' => $service_star,
            'star' => $employee_star,
            'content' => $content
        ];

        //判断插入数据状态
        $CommentMerchantModel = D('comment_merchant');
        $res = $CommentMerchantModel->insertCommentData($data);
        if (!$res) {
            Response::error(ReturnCode::DB_SAVE_ERROR, $CommentMerchantModel->getError());
        }

        //评价成功,返回成功结果
        Response::success();
    }

    /**
     * 获取订单评价内容
     * @param $member_id int 用户ID
     * @param $order_no string  订单编号
     */
    public function commentByOrder()
    {
        $member_id = I('post.member_id', '');
        $order_id = I('post.order_id', '');

        //验证数据合法性
        if (!is_numeric($member_id) || !is_numeric($order_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //实例化模型类
        $commentMerchantModel = D('CommentMerchant');
        //查询评价数据
        $result = $commentMerchantModel->findOrderCommentInfo($member_id, $order_id);
        //判断执行结果
        if ($result === false) {
            //返回错误信息
            Response::error(ReturnCode::DB_READ_ERROR, $commentMerchantModel->getError());
        }

        //返回成功数据
        Response::success($result);
    }


    /**
     * 验证用户输入评价内容合法性
     * @param $content  string 评价内容
     */
    private function _validateContent($content)
    {
        //验证评论长度
        if (mb_strlen($content) > 80) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '评论内容不能超过80个字');
        }
        //验证用户输入的文字合法性,过滤敏感词
        $filterwordsFile = CONF_PATH . "filterwords.txt";
        //判断给定文件名是否为一个正常的文件
        if (is_file($filterwordsFile)) {
            //把整个文件读入一个数组中
            $filter_word = file_get_contents($filterwordsFile);
            $filter_word = explode('|', $filter_word);
            //匹配字符
            foreach ($filter_word as $word) {
                //应用正则表达式，判断传递的留言信息中是否含有敏感词
                if (preg_match("/" . trim($word) . "/i", $content)) {
                    Response::error(ReturnCode::PARAM_WRONGFUL, '评价内容包含敏感词');
                }
            }
        }
    }

}