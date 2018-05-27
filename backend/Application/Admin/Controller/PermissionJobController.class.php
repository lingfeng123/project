<?php
/**
 * FileName: PermissionJobController.class.php
 * User: Comos
 * Date: 2017/9/30 11:10
 */

namespace Admin\Controller;


class PermissionJobController extends BaseController
{
    private $_model;

    public function _initialize()
    {
        parent::_initialize();
        $this->_model = D('employee_permission');
    }

    /**
     * 商户权限列表
     */
    public function index()
    {
        $list = $this->_model->select();
        $list = self::tree($list);

        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 添加权限
     */
    public function add()
    {
        if (IS_POST) {
            //接收数据
            $data = I('post.', '');
            //验证数据
            if (empty($data['name']) || !is_numeric($data['parent_id']) || $data['parent_id'] < 0) {
                $this->error('权限名不能为空');
            }
            //失败提示
            if (!$res = $this->_model->add($data)) {
                $this->error('添加权限失败');
            }
            //成功提示
            $this->success('添加权限成功');

        } else {
//            $list = self::tree($this->_model->select());
            $list = $this->_model->where(['parent_id' => 0])->select();
            $this->assign('permission', $list);
            //渲染视图
            $this->display();
        }
    }

    /**
     * 修改权限
     * @param $id
     */
    public function edit($id)
    {
        //获取格式化权限列表
//        $list = self::tree($this->_model->select());
        $list = $this->_model->where(['parent_id' => 0])->select();
        if (!$detail = $this->_model->find($id)) {
            $this->error('获取权限数据错误');
        }

        if (IS_POST) {
            //接收数据
            $data = I('post.', '');
            //验证数据
            if (empty($data['name']) || !is_numeric($data['parent_id']) || $data['parent_id'] < 0) {
                $this->error('权限名不能为空');
            }

            //$detail['parent_id'] = $data['parent_id'];
            //TODO::这里根据业务场景只判断了两级,未做无限极判断
            $res = $this->verifySubPermission($data['id'], $data['parent_id']);
            if (!$res) {
                $this->error('不能修改到自己的子权限');
            }

            //失败提示
            $res = $this->_model->save($data);
            if ($res === false) {
                $this->error('修改权限失败');
            }
            //成功提示
            $this->success('修改权限成功');

        } else {

            $this->assign('permission', $list);
            $this->assign('detail', $detail);
            //渲染视图
            $this->display('add');
        }
    }

    /**
     * 根据权限ID删除权限
     * @param $id
     */
    public function delete($id)
    {
        //查询是否有职位与权限绑定
        $permission_id = M('employee_job_permission')->where(['permission_id' => $id])->getField('permission_id');
        if ($permission_id){
            $this->error('存在关联职位,不允许删除此权限');
        }

        //删除权限
        $rs = $this->_model->delete($id);
        if ($rs === false){
            $this->error('权限删除失败');
        }

        //删除权限成功提示
        $this->success('删除权限成功');
    }

    /**
     * 验证当前修改的权限ID是否移动到了子权限下
     * @param $id int 权限ID
     * @param $parent_id int  父级权限ID
     * @return bool
     */
    public function verifySubPermission($id, $parent_id)
    {
        if ($parent_id == $id) {
            return false;
        }

        //查询当前权限的子权限id
        if ($parent_id != 0){
            $son_permission = $this->_model->where(['parent_id' => $id])->getField('id', true);
            if ($son_permission || in_array($parent_id ,$son_permission)){
                return false;
            }
        }

        return true;
    }

    /**
     * 权限列表
     * @param $data 权限数据列表
     * @param int $pid 父级权限
     * @param int $count 层级
     * @return mixed
     */
    static public function tree(&$data, $pid = 0, $deep = 0)
    {
        static $tree = [];
        foreach ($data as $key => $value) {
            if ($value['parent_id'] == $pid) {
                $value['deep'] = $deep;
                $tree[] = $value;
                self::tree($data, $value['id'], $deep + 1);
            }
        }
        return $tree;
    }
}