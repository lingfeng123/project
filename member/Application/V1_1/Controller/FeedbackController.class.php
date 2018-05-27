<?php
/**
 * FileName: FeedbackController.class.php
 * User: Comos
 * Date: 2018/2/26 15:48
 */

namespace V1_1\Controller;


use Org\Util\Response;
use Org\Util\ReturnCode;

class FeedbackController extends BaseController
{
    /**
     * 反馈类型列表
     */
    public function qustionList()
    {
        $list = C('FEEDBACK_TYPE');

        $formatList = [];
        foreach ($list as $key => $value) {
            $formatList[] = [
                'question_id' => $key,
                'question_name' => $value,
            ];
        }

        Response::setSuccessMsg('获取问题成功');
        Response::success(['list' => $formatList]);
    }

    /**
     * 根据用户ID获取反馈列表
     * @param $member_id int 用户ID
     * @param $page int 当前页码
     * @param $pagesize int 每页显示数量
     */
    public function feedList()
    {
        //获取反馈列表
        $member_id = I('post.member_id', '');
        $page = I('post.page', 1);
        $pagesize = I('post.page_size', C('PAGE.PAGESIZE'));

        if (!is_numeric($member_id) || !is_numeric($page) || !is_numeric($pagesize)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        $feedbackModel = M('feedback');
        $where = ['member_id' => $member_id];
        $count = $feedbackModel->where($where)->count();
        $list = $feedbackModel->field('id, question_type, content, created_time')->where($where)->page($page, $pagesize)->select();
        if ($count === false || $list === false) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '获取数据失败');
        }

        //遍历处理数据
        $question_title = C('FEEDBACK_TYPE');
        foreach ($list as $key => $item) {
            $list[$key]['question_title'] = $question_title[$list[$key]['question_type']];
            unset($list[$key]['question_type']);
        }

        Response::setSuccessMsg('获取反馈数据成功');
        Response::success(['total' => $count, 'list' => $list]);
    }


    /**
     * 提交反馈意见
     * @param $member_id int 用户ID
     * @param $question_type int 用户ID 反馈问题类型 具体见配置文件
     * @param $client_type int 反馈终端类型 1小程序 2用户端安卓APP 3用户端iOSAPP 4商户端安卓APP  5商户端iOSAPP
     * @param $tel int 用户电话号码
     * @param $content string 反馈内容
     */
    public function apply()
    {
        $member_id = I('post.member_id', '');
        $question_type = I('post.question_type', 1);
        $client_type = I('post.client_type', 1);
        $tel = I('post.tel', '');
        $content = I('post.content', '');

        //验证反馈的终端  1小程序 2用户端安卓APP 3用户端iOSAPP 4商户端安卓APP  5商户端iOSAPP
        if (!is_numeric($member_id) || !in_array($client_type, [1, 2, 3, 4, 5])) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //验证反馈类型
        $question_type_key = array_keys(C('FEEDBACK_TYPE'));
        if (!in_array($question_type, $question_type_key)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '反馈类型填写不正确');
        }

        //验证手机号码合法性
        if (!preg_match("/^1[345789]\d{9}$/i", $tel)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '手机号码不合法');
        }

        //验证反馈内容是否合法
        if (mb_strlen($content) < 10 || mb_strlen($content) > 240) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '建议内容为10-240个字以内');
        }

        //准备写入数据
        $time = time();
        $data = [
            'member_id' => $member_id,
            'question_type' => $question_type,
            'client_type' => $client_type,
            'tel' => $tel,
            'content' => $content,
            'created_time' => $time,
            'updated_time' => $time,
        ];
        if (!M('feedback')->add($data)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '反馈提交失败');
        }

        Response::setSuccessMsg('提交反馈成功');
        Response::success();
    }
}