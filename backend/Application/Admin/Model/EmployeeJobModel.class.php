<?php
/**
 * Created by PhpStorm.
 * User: nano
 * Date: 2017/10/21 0021
 * Time: 22:42
 */

namespace Admin\Model;


use Think\Model;

class EmployeeJobModel extends Model
{

    //添加新职位
    public function addNewJob($data)
    {
        //组装数据
        $job_data = ['merchant_id' => $data['merchant_id'], 'job_name' => $data['job_name']];
        //开启事务
        $this->startTrans();
        $job_id = $this->add($job_data);
        if (!$job_id) {
            $this->rollback();
            return false;
        }
        //职位权限关联数据
        $permission_job = [];
        foreach ($data['permissions'] as $permission) {
            $permission_job[] = ['job_id' => $job_id, 'permission_id' => $permission, 'merchant_id' => $data['merchant_id']];
        }
        //添加职位权限关联数据
        $res = M('employee_job_permission')->addAll($permission_job);
        if (!$res) {
            $this->rollback();
            return false;
        }
        $this->commit();
        return true;
    }

    /**
     * 修改职位
     * @param $data
     * @return bool
     */
    public function updateJob($data)
    {
        //开启事务
        $this->startTrans();
        $res = $this->save(['id' => $data['id'], 'merchant_id' => $data['merchant_id'], 'job_name' => $data['job_name']]);
        if ($res === false) {
            $this->rollback();
            return false;
        }

        //职位权限关联数据
        $permission_job = [];
        foreach ($data['permissions'] as $permission) {
            $permission_job[] = ['job_id' => $data['id'], 'permission_id' => $permission, 'merchant_id' => $data['merchant_id']];
        }

        $jobPermissionModel = M('employee_job_permission');
        //删除旧权限关联数据
        $res = $jobPermissionModel->where(['job_id' => $data['id']])->delete();
        if ($res === false) {
            $this->rollback();
            return false;
        }

        //添加职位权限关联数据
        $res = $jobPermissionModel->addAll($permission_job);
        if (!$res) {
            $this->rollback();
            return false;
        }
        $this->commit();
        return true;
    }
}