<?php
/**
 * Created by PhpStorm.
 * User: nano
 * Date: 2017/10/21 0021
 * Time: 21:11
 */

namespace Admin\Controller;


class EmployeeJobController extends BaseController
{
    private $_model;    //实例模型

    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->_model = D('employee_job');
    }

    /**
     * 添加职位
     */
    public function add($merchant_id)
    {
        if (IS_POST){
            $data = I('post.','');
            //验证商户ID
            if (!is_numeric($data['merchant_id'])) $this->error('商户参数不正确');
            //职位填写验证
            if (strlen($data['job_name']) < 2) $this->error('职位名称不正确');
            //验证权限选择
            if (!$data['permissions']) $this->error('权限未选择');
            //添加新职位
            if (!$res = $this->_model->addNewJob($data)) $this->error('添加职位失败');
            //成功提醒
            $this->success('添加职位成功', U('index', ['merchant_id' => $merchant_id]));
        }else{
            //取得权限列表
            $permissions = $this->_permissionList();

            $this->assign('permissions', $permissions);
            $this->assign('merchant_id', $merchant_id);
            $this->display();
        }
    }

    /**
     * 修改职位
     * @param $merchant_id int 商户ID
     */
    public function edit($merchant_id,$id)
    {
        if (IS_POST){
            $data = I('post.','');
            //验证商户ID
            if (!is_numeric($data['merchant_id'])) $this->error('商户参数不正确');
            //职位填写验证
            if (strlen($data['job_name']) < 2) $this->error('职位名称不正确');
            //验证权限选择
            if (!$data['permissions']) $this->error('权限未选择');
            //添加新职位
            if (!$res = $this->_model->updateJob($data)) $this->error('修改职位失败');

            //添加成功跳转
            $this->success('修改职位成功', U('index', ['merchant_id' => $merchant_id]));
        }else{

            $permissions = $this->_permissionList();
            //获取职位数据
            $job_data = $this->_model->find($id);
            if (!$job_data) $this->error('获取职位数据失败', U('index', ['merchant_id' => $merchant_id]));

            //获取职位权限数据
            $jobs_perm = M('employee_job_permission')->where(['job_id' => $id])->getField('permission_id',true);
            if (!$jobs_perm) $this->error('获取职位数据失败', U('index', ['merchant_id' => $merchant_id]));

            //分配数据
            $this->assign('permissions', $permissions); //总权限列表
            $this->assign('merchant_id', $merchant_id); //商户ID
            $this->assign('jobs_perm', $jobs_perm); //已选择的权限
            $this->assign('detail', $job_data); //职位信息
            $this->display('add');
        }
    }

    /**
     * 获取职位列表
     * @param $merchant_id
     */
    public function index($merchant_id)
    {
        //查询商户职位
        $list = $this->_model->field('api_employee_job.id, job_name, api_merchant.title as merchant_title')
            ->join('api_merchant ON api_employee_job.merchant_id = api_merchant.id','LEFT')
            ->where(['merchant_id' => $merchant_id])
            ->select();

        if ($list === false){
            $this->error('获取职位列表数据失败');
        }

        //分配数据
        $this->assign('list', $list);
        $this->assign('merchant_id', $merchant_id);
        $this->display();
    }

    /**
     * 获取所有权限
     */
    private function _permissionList(){
        $list = D('employee_permission')->select();
        $list = PermissionJobController::tree($list);
        return $list ? $list : false;
    }
}