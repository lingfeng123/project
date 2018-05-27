<?php
/**
 * FileName: FeedbackController.class.php
 * User: Comos
 * Date: 2018/2/26 17:24
 */

namespace Admin\Controller;


//class FeedbackController //extends BaseController
use Think\Controller;

class FeedbackController extends Controller
{
    private $client_type = [
        1 => '小程序', 2 => '用户端安卓APP',3 => '用户端iOSAPP',4 => '商户端安卓APP',5 => '商户端iOSAPP'
    ];

    /**
     * 反馈记录列表
     * @param $p int 当前页码
     * @param null $question_type 问题类型
     * @param null $client_type 终端类型
     * @param null $status 问题处理状态
     */
    public function index($p = 1, $question_type = null, $client_type = null, $status = null)
    {
        $where = [];
        //反馈问题类型
        if($question_type){
            $where['question_type'] = $question_type;
        }

        //终端类型
        if ($client_type){
            $where['client_type'] = $question_type;
        }

        //记录处理状态
        if ($status !== null){
            $where['status'] = $status;
        }

        $data = D('feedback')->getFeedList($where, $p);
        if (!$data){
            $this->error('反馈问题记录');
        }

        $this->assign('list', $data['list']);
        $this->assign('pageHtml', $data['pageHtml']);
        $this->assign('status', [1 => '已处理', 0 => '未处理']);
        $this->assign('question_type', C('FEEDBACK_TYPE'));
        $this->assign('client_type', $this->client_type);
        $this->display();
    }

    /**
     * 更改反馈状态
     * @param $id int 反馈ID
     */
    public function edit($id)
    {
        $rs = D('feedback')->updateFeedStatus($id);
        if (!$rs){
            $this->error('修改反馈状态失败');
        }

        //更改状态成功
        $this->success('修改反馈状态成功');
    }


    /**
     * 删除反馈记录
     * @param $id int 反馈ID
     */
    public function del($id)
    {
        if (D('feedback')->delete($id) === false){
            $this->error('删除反馈记录失败');
        }

        //删除成功提示
        $this->success('删除反馈记录成功');
    }
}