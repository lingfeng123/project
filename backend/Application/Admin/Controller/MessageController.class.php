<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/13
 * Time: 18:13
 */

namespace Admin\Controller;

use Org\Util\AuthSign;
use Org\Util\SocketPush;

class MessageController extends BaseController
{
    private $_model;

    //实例化模型
    public function _initialize()
    {
        parent::_initialize();
        $this->_model = D('message');
    }

    /**
     * @author jiangling
     * 系统消息列表
     * @param  $params  传入参数
     */
    public function index()
    {
        $params = I('get.', '');

        //获取系统消息
        $msg_list = $this->_model->getMessageList($params);

        if (!$msg_list) {
            $this->error('获取列表失败');
        }

        $this->assign('list', $msg_list['msg_list']);
        $this->assign('pageHtml', $msg_list['pagehtml']);

        $this->display();
    }

    /**
     * @author jiangling
     * 系统消息增加
     */
    public function add()
    {
        if (IS_POST) {
            //收集数据
            if ($this->_model->create() === false) {
                $this->error(get_error($this->_model), U('sys_message_add'));
            }
            //执行添加操作
            if ($this->_model->message_add() === false) {
                $this->error(get_error($this->_model), U('sys_message_add'));
            }

//            $message=$this->_model->data['content'];

            //TODO: 待测试目前
            //向所有的员工提交系统消息
//            $employee_ids=M('employee')->getField('id',true);
//
//            //向其他员工推送socket消息 ::socket::
//            if ($employee_ids) {
//                try {
//                    $socketPush = new SocketPush();
//                    $socketPush->pushOrderSocketMessage($employee_ids, 5, 0,$message);
//                } catch (\Exception $exception) {
//                    //记录错误日志
//                    Log::write($exception, Log::WARN);
//                }
//            }

            $this->success('添加成功', U('sys_message_list'));
        }

        $this->display();

    }

    /**
     * @author jiangling
     * 系统消息修改
     * @param  $id  系统消息id
     */
    public function edit($id)
    {
        //提交修改数据
        if (IS_POST) {
            //收集数据
            if ($this->_model->create() === false) {
                $this->error(get_error($this->_model), U('sys_message_add'));
            }

            //修改系统消息
            if ($this->_model->message_edit($id) === false) {
                $this->error(get_error($this->_model), U('sys_message_add'));
            }

            $this->success('编辑成功', U('sys_message_list'));

        } else {

            //查询该条系统消息
            $msgdata = $this->_model->where(['id' => $id])->find();

            if (!$msgdata) {
                $this->error('系统消息不存在', U('sys_message_list'));
            }

            //拼接组装返回结果
            if ($msgdata['toclient'] === 1) {
                $msgdata['client'] = '所有';
            } else if ($msgdata['toclient'] === 2) {
                $msgdata['client'] = '所有';
            }
        }

        $this->assign('detail', $msgdata);

        $this->display('add');

    }

    /**
     * @author jiangling
     * 系统消息删除
     * @param  $id int 系统消息ID
     */
    public function del($id)
    {
        //查询数据是否存在
        if (!$this->_model->where(['id' => $id])->find()) {
            $this->error('系统消息不存在');
        }

        //执行删除炒作
        $resData = D('Message')->message_delete($id);

        if ($resData === false) {
            $this->error('系统消息删除失败');
        }

        $this->success('系统消息删除成功', U('index'));

    }

    /**
     * @author jiangling
     * 消息详情页面
     * @param  $id  int  系统消息ID
     */
    public function detail($id)
    {
        //查看系统消息详情
        $Infodetail = $this->_model->where(['id' => $id])->find();

        if (!$Infodetail) {
            $this->error('系统消息不存在');
        }

        $this->assign('detail', $Infodetail);

        $this->display();

    }


}