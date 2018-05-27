<?php
/**
 * FileName: MemberPrivilegeController.class.php
 * User: Comos
 * Date: 2017/8/24 9:06
 */

namespace Admin\Controller;


class MemberPrivilegeController extends BaseController
{
    private $_model;

    public function _initialize()
    {
        parent::_initialize();    //继承权限控制
        $this->_model = D('MemberPrivilege');
    }

    /**
     * 会员特权
     */
    public function index(){
        $lists = $this->_model->select();
        $this->assign('lists', $lists);
        $this->display();
    }

    /**
     * 添加会员特权
     */
    public function add(){
        if (IS_POST) {
            //收集数据
            if ($this->_model->create() === false) {
                $this->error(get_error($this->_model), U('add'));
            }
            //新增数据
            if ($this->_model->insertVipData() === false) {
                $this->error(get_error($this->_model));
            }
            //添加成功提示
            $this->success('添加成功', U('index'));
        }else{
            $this->display();
        }
    }

    /**
     * 修改会员特权
     */
    public function edit($id=0){
        if (IS_POST) {
            //收集数据
            if ($this->_model->create() === false) {
                $this->error(get_error($this->_model), U('add'));
            }
            //新增数据
            if ($this->_model->updateData() === false) {
                $this->error(get_error($this->_model));
            }
            //添加成功提示
            $this->success('修改特权成功', U('index'));
        }else{
            //根据ID查询特权记录
            if (!$vipInfo = $this->_model->getVipInfoById($id)) {
                $this->error('获取特权记录失败', U('index'));
            }
            //分配数据渲染视图
            $this->assign('detail', $vipInfo);
            $this->display('add');
        }
    }

}