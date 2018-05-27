<?php
/**
 * Created by PhpStorm.
 * User: nano
 * Date: 2017/10/24 0024
 * Time: 22:46
 */

namespace Admin\Controller;


class VersionController extends BaseController
{
    private $_model;

    public function _initialize()
    {
        parent::_initialize();
        $this->_model = D('version');
    }

    /**
     * 应用版本列表
     */
    public function index($p = 1, $client = null, $is_force = null, $platform = null)
    {
        if ($client) $where['client'] = $client;
        if ($is_force !== null) $where['is_force'] = $is_force;
        if ($platform) $where['platform'] = $platform;

        $list = $this->_model->getAppVersionList($p, $where);
        if ($list === false) {
            $this->error('获取版本列表数据失败');
        }
        $this->assign('list', $list['list']);
        $this->assign('pageHtml', $list['pageHtml']);
        $this->display();
    }

    /**
     * 添加新版本
     */
    public function add()
    {
        if (IS_POST) {
            //接收数据
            if ($this->_model->create() === false) {
                $this->error(get_error($this->_model), U('add'));
            }
            //插入数据
            if ($this->_model->insertVersionData() === false) {
                $this->error(get_error($this->_model));
            }
            $this->success('添加成功', U('index'));
        } else {
            $this->assign('time', strtotime(time()));
            $this->display();
        }
    }

    /**
     * 修改版本
     */
    public function edit($id)
    {
        if (IS_POST) {
            //接收数据
            if ($this->_model->create() === false) {
                $this->error(get_error($this->_model), U('add'));
            }
            //插入数据
            if ($this->_model->updateVersionData() === false) {
                $this->error(get_error($this->_model));
            }
            $this->success('修改成功', U('index'));
        } else {

            $detail = $this->_model->find($id);
            $detail['updated_time'] = date('Y-m-d H:i:s', $detail['updated_time']);

            $this->assign('detail', $detail);
            $this->assign('time', strtotime(time()));
            $this->display('add');
        }
    }

    /**
     * 删除版本号
     * @param $id
     */
    public function del($id)
    {
        if (!$rs = $this->_model->delete($id)) {
            $this->error('删除失败');
        }
        $this->success('删除成功', U('index'));
    }
}