<?php
/**
 * FileName: EmployeeController.class.php
 * User: Comos
 * Date: 2017/10/19 13:31
 */

namespace Admin\Controller;


class EmployeeController extends BaseController
{
    private $_model;

    //性别
    public $sex = [1 => '男', 2 => '女',];
    private $status = [0 => '封禁', 1 => '正常', 3 => '删除'];
    private $type = [1 => '管理员', 2 => '普通', 3 => '服务员', 4 => '客户经理'];

    public function _initialize()
    {
        parent::_initialize();
        $this->_model = D('Employee');
    }

    /**
     * 员工列表
     */
    public function index($merchant_id, $p = 1)
    {
        $data = I('get.', '');
        //获取员工列表
        $employees = $this->_model->getEmployeeList($merchant_id, $p, $data);
        if (!$employees) {
            $this->error('获取员工列表失败');
        }

        //获取商户名称
        $merchant_name = M('merchant')->where(['id' => $merchant_id])->getField('title');
        //获取职位数据
        $jobs = $this->_getMerchantJobsList($merchant_id);
        if (!$jobs) {
            $jobs = array();
        }


        //分配数据,载入视图
        $this->assign('pageHtml', $employees['pageHtml']);
        $this->assign('list', $employees['list']);
        $this->assign('merchant_id', $merchant_id);
        $this->assign('merchant_name', $merchant_name);
        $this->assign('jobs', $jobs);
        $this->assign('sex', $this->sex);
        $this->assign('status', $this->status);
        $this->assign('type', $this->type);
        $this->display();
    }

    /**
     * 添加员工
     */
    public function add($merchant_id)
    {
        if (IS_POST) {
            //接收数据
            if ($this->_model->create() === false) {
                $this->error(get_error($this->_model), U('add'));
            }

            //获取职位ID
            $job_id = I('post.job_id');
            if (!is_numeric($job_id)) $this->error('职位不能为空');

            //插入数据
            if ($this->_model->addEmployee($job_id) === false) {
                $this->error(get_error($this->_model));
            }

            $this->success('添加成功', U('Employee/index', ['merchant_id' => $merchant_id]));

        } else {
            $jobs = $this->_getMerchantJobsList($merchant_id);

            //获取职位数据
            $this->assign('merchant_id', $merchant_id);
            $this->assign('jobs', $jobs);
            $this->display();
        }
    }

    /**
     * 修改员工数据
     * @param $merchant_id int 商户ID
     * @param $id int 员工ID
     */
    public function edit($merchant_id, $id)
    {
        if (IS_POST) {
            //接收数据
            if ($this->_model->create() === false) $this->error(get_error($this->_model));
            //获取职位ID
            $job_id = I('post.job_id');
            if (!is_numeric($job_id)) $this->error('职位不能为空');

            //修改数据
            if ($this->_model->updateEmployee($job_id) === false) {
                $this->error(get_error($this->_model));
            }

            $this->success('修改成功', U('Employee/index', ['merchant_id' => $merchant_id]));

        } else {
            //员工数据
            if (!$employee_data = $this->_model->field('api_employee.*, job_id')
                ->join('api_employee_andjob ON api_employee_andjob.employee_id = api_employee.id', 'LEFT')
                ->find($id)
            ) {
                $this->error('获取员工失败');
            }

            //职位数据
            $jobs = $this->_getMerchantJobsList($merchant_id);
            if (!$jobs) {
                $this->error('获取职位失败');
            }

            //获取职位数据
            $this->assign('merchant_id', $merchant_id);
            $this->assign('detail', $employee_data);
            $this->assign('jobs', $jobs);
            $this->display('add');
        }
    }

    /**
     * 查看详细资料
     * @param $merchant_id
     * @param $id
     */
    public function detail($id)
    {
        if (!$data = $this->_model->getSingleEmployeeInfo($id)) {
            $this->error('数据不存在');
        }
        //获取商户名称
        $data['merchant_name'] = M('merchant')->where(['id' => $data['merchant_id']])->getField('title');
        $data['image'] = explode('|', $data['image']);

        $this->assign('detail', $data);
        $this->assign('attachment_url', C('ATTACHMENT_URL'));
        $this->assign('sex', $this->sex);
        $this->assign('status', $this->status);
        $this->assign('type', $this->type);
        $this->display();
    }

    /**
     * 获取当前商户所有职位
     * @param $merchant_id
     * @return mixed
     */
    private function _getMerchantJobsList($merchant_id)
    {
        return D('employee_job')->field('id,job_name')->where(['merchant_id' => $merchant_id])->select();
    }

    /**
     * 删除员工
     * @param $id int 员工ID
     * @param $merchant_id int 商户ID
     * @param $login_employee_id int 当前登录用户ID
     */
    public function del($id, $merchant_id)
    {
        //执行删除操作
        $result = D('employee')->deleteEmployeeData($id, $merchant_id);
        if (!$result) {
            $this->error(D('employee')->getError());
        } else {
            $this->success('删除员工成功');
        }
    }
}