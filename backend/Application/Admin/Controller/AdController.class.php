<?php
/**
 * FileName: AdController.class.php
 * User: Comos
 * Date: 2017/8/21 12:54
 */

namespace Admin\Controller;


use Org\Util\Tools;

class AdController extends BaseController
{
    private $_model;

    public function _initialize()
    {
        $this->_model = D('Ad');
    }

    /**
     * 显示广告列表
     */
    public function index()
    {
        $cond = '';
        //调用model数据
        $adList = $this->_model->getAdList($cond);
        //如果查询失败,返回错误信息
        if (!$adList) {
            $this->error($this->_model);
        }
        //输出数据
        $this->assign('lists', $adList['rows']);
        $this->assign('pageHtml', $adList['pages']);
        $this->display();
    }

    /**
     * 添加广告
     */
    public function add()
    {
        if (IS_POST) {
            //接收数据
            if ($this->_model->create() === false) {
                $this->error(get_error($this->_model), U('add'));
            }
            //插入数据
            if ($this->_model->insertAdData() === false) {
                $this->error(get_error($this->_model));
            }
            $this->success('添加成功', U('index'));
        } else {
            //渲染视图
            $this->assign('attachment_url', C('attachment_url'));
            $this->display();
        }
    }


    /**
     * 修改广告
     * @param $id
     */
    public function edit($id)
    {
        if (IS_POST) {
            //收集数据
            if ($this->_model->create() === false) {
                $this->error(get_error($this->_model), U('edit', ['id' => $id]));
            }
            //保存数据
            if ($this->_model->saveAdData() === false) {
                $this->error(get_error($this->_model), U('edit', ['id' => $id]));
            }
            //成功跳转
            $this->success('修改广告成功', U('index'));
        } else {
            //查询对应ID数据
            $row = $this->_model->find($id);
            if (!$row) {
                $this->error('数据非法', U('index'));
            }
            //转换时间格式
            $row['start_time'] = date('Y-m-d H:i:s', $row['start_time']);
            $row['end_time'] = date('Y-m-d H:i:s', $row['end_time']);
            $row['price'] = (int)$row['price'];
            //输出数据
            $this->assign('attachment_url', C('attachment_url'));
            $this->assign('detail', $row);
            $this->display('add');
        }

    }

    /**
     * 根据id删除广告
     * @param $id
     */
    public function del($id = 0)
    {
        if (!$id) {
            $this->error('非法访问', U('index'));
        }
        //查询符合条件的数据
        $data = $this->_model->find($id);
        if (!$data) {
            $this->error('数据不存在');
        }
        //判断是否删除成功
        if ($this->_model->delete($id) === false) {
            $this->error('删除广告失败');
        }
        $this->success('删除广告成功');
    }


}