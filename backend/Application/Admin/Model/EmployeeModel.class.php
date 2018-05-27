<?php
/**
 * FileName: EmployeeModel.class.php
 * User: Comos
 * Date: 2017/10/19 13:36
 */

namespace Admin\Model;


use Org\Util\Tools;
use Think\Log;
use Think\Model;
use Think\Page;
use Think\Upload\Driver\Qiniu;

class EmployeeModel extends Model
{
    //自动验证
    protected $_validate = [
        ['realname', '1,10', '名字最多10个字符', self::MUST_VALIDATE, 'length'],
        ['type', '1,2,4', '账号类型不正确', self::MUST_VALIDATE, 'in'],
        ['sex', '1,2', '性别选择不正确', self::MUST_VALIDATE, 'in'],
        ['tel', 'validateTel', '手机号码不正确', self::MUST_VALIDATE, 'callback'],
        ['job_number', '2,10', '工号填写不合法', self::MUST_VALIDATE, 'length'],
        ['wechat_id', 'validateWechatNumer', '微信号不合法', self::MUST_VALIDATE, 'callback'],
        ['status', '0,1', '状态数值不合法', self::MUST_VALIDATE, 'in'],
    ];

    //数据自动完成
    protected $_auto = [
        ['created_time', 'time', self::MODEL_INSERT, 'function'],
        // ['updated_time', 'time', self::MODEL_BOTH, 'function']
    ];

    //验证手机号码
    public function validateTel($tel)
    {
        if (!preg_match('/^1[3578]\d{9}$/', $tel)) {
            return false;
        }
        return true;
    }

    //验证微信号合法性
    public function validateWechatNumer($wechat_id)
    {
        if (!preg_match('/^[a-zA-Z]{1}[-_a-zA-Z0-9]{5,19}$/', $wechat_id)) {
            return false;
        }
        return true;
    }

    /**
     * 添加员工
     * @return bool int
     */
    public function addEmployee($job_id)
    {
        $data = $this->data;

        //查询是否存在管理员
        $super_admin = $this->_validateSuperAdmin();
        if ($super_admin && $data['type'] == 1) {
            $this->error = '已存在管理员,管理员只能有一个';
            return false;
        }

        $data['password'] = create_employee_password($data['tel']);  //生成密码

        //执行事务操作
        $this->startTrans();
        //添加员工数据
        $employee_id = $this->add($data);
        if (!$employee_id) {
            $this->error = '添加新员工失败';
            $this->rollback();
            return false;
        }

        //添加员工职位关联数据
        $res = M('employee_andjob')->add(['job_id' => $job_id, 'merchant_id' => $data['merchant_id'], 'employee_id' => $employee_id]);
        if (!$res) {
            $this->error = '添加新员工职位关联失败';
            $this->rollback();
            return false;
        }

        //生成推广码
        $invite_code = Tools::create_invite_code(C('INVITE_CODE_PREFIX.EMPLOYEE'), $employee_id);

        //修改员工数据
        $rs = $this->save(['id' => $employee_id, 'invite_code' => $invite_code]);
        if ($rs === false) {
            $this->error = '添加新员工推广码失败';
            $this->rollback();
            return false;
        }

        //添加员工评价记录
        $star_rs = M('comment_empstar')->add(['employee_id' => $employee_id]);
        if ($star_rs === false) {
            $this->error = '添加新员工评价数据';
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }

    /**
     * 修改员工
     * @param $job_id
     * @return bool
     */
    public function updateEmployee($job_id)
    {
        $data = $this->data;

        //查询是否存在管理员
        if ($super_admin = $this->_validateSuperAdmin()) {
            if ($super_admin['id'] != $data['id'] && $data['type'] == 1) {
                $this->error = '已存在管理员,管理员只能有一个';
                return false;
            }
        }

        //执行事务操作
        $this->startTrans();
        //修改员工数据
        $res = $this->save($data);
        if ($res === false) {
            $this->error = '修改员工资料失败';
            $this->rollback();
            return false;
        }

        //删除职位关联数据
        $employeeAndjobModel = M('employee_andjob');
        $res = $employeeAndjobModel->where(['employee_id' => $data['id']])->delete();
        if ($res === false) {
            $this->error = '修改员工职位关联失败';
            $this->rollback();
            return false;
        }

        //添加员工职位关联数据
        $res = $employeeAndjobModel->add(['job_id' => $job_id, 'merchant_id' => $data['merchant_id'], 'employee_id' => $data['id']]);
        if (!$res) {
            $this->error = '修改员工职位关联失败';
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }


    /**
     * 根据商户ID获取员工列表
     * @param $merchant_id int 商户ID
     * @param $page int 页码
     * @return bool
     */
    public function getEmployeeList($merchant_id, $page, $data)
    {
        if ($data['keywords'] != '') {
            switch ($data['search_type']) {
                case 1:
                    $where['api_employee.realname'] = ['like', '%' . $data['keywords'] . '%'];
                    break;
                case 2:
                    $where['api_employee.tel'] = $data['keywords'];
                    break;
            }
        }

        if ($data['status'] != '') $where['api_employee.status'] = $data['status'];    //账号状态
        if ($data['type'] != '') $where['api_employee.type'] = $data['type'];   //账号类型
        if ($data['job_id'] != '') $where['api_employee_job.id'] = $data['job_id'];  //职位ID

        //商户ID
        $where['api_employee.merchant_id'] = $merchant_id;

        //数据总数
        $count = $this->join('api_employee_andjob ON api_employee_andjob.employee_id = api_employee.id', 'LEFT')
            ->join('api_employee_job ON api_employee_job.id = api_employee_andjob.job_id', 'LEFT')
            ->where($where)
            ->count();

        //列表数据
        $pagesize = I('PAGE.PAGESIZE');
        $data['list'] = $this->field('api_employee.id,
            api_employee_job.job_name,
            api_employee.merchant_id,
            api_employee.tel,
            api_employee.status,
            api_employee.realname,
            api_employee.sex,
            api_employee.avatar,
            api_employee.job_number,
            api_employee.type,
            from_unixtime(api_employee.created_time) as created_time,
            api_employee.alipay_account,
            api_employee.wechat_id')
            ->join('api_employee_andjob ON api_employee_andjob.employee_id = api_employee.id', 'LEFT')
            ->join('api_employee_job ON api_employee_job.id = api_employee_andjob.job_id', 'LEFT')
            ->page($page, $pagesize)
            ->where($where)
            ->order('id desc')
            ->select();

        //判断sql执行结果
        if ($count === false || $data['list'] === false) {
            return false;
        }

        //获取分页
        $pages = new Page($count, $pagesize);
        $pages->setConfig('header', '共%TOTAL_ROW%条');
        $pages->setConfig('first', '首页');
        $pages->setConfig('last', '末页');
        $pages->setConfig('prev', '上一页');
        $pages->setConfig('next', '下一页');
        $pages->setConfig('theme', C('PAGE.THEME'));
        $data['pageHtml'] = $pages->show();

        return $data;
    }

    /**
     * 获取单个员工信息
     * @param $id
     * @return mixed
     */
    public function getSingleEmployeeInfo($id)
    {
        return $this->field('api_employee.id,
        api_employee.merchant_id,
        api_employee.tel,
        api_employee.status,
        api_employee.realname,
        api_employee.sex,
        api_employee.avatar,
        api_employee.job_number,
        api_employee.image,
        api_employee.type,
        api_employee.invite_code,
        api_employee.description,
        from_unixtime(api_employee.created_time) as created_time,
        api_employee.alipay_account,
        api_employee.wechat_id,
        job_name')
            ->join('api_employee_andjob ON api_employee_andjob.employee_id = api_employee.id', 'LEFT')
            ->join('api_employee_job ON api_employee_job.id = api_employee_andjob.job_id', 'LEFT')
            ->where(['api_employee.id' => $id])
            ->find();
    }

    /**
     * 检测是否存在超级管理员
     */
    private function _validateSuperAdmin()
    {
        return $this->field('id,type')->where(['type' => 1])->find();
    }

    /**
     * 删除员工数据
     */
    public function deleteEmployeeData($employee_id, $merchant_id)
    {
        $where = ['employee_id' => $employee_id];
        //判断该员工是否存在 ;2017年12月12日11:58:39
        $em_rs = $this->where(['id' => $employee_id, 'status' => ['in', [0, 1]]])->find();
        if (!$em_rs) {
            $this->error = '该员工已经被删除';
            return false;
        }

        //开启事务
        $this->startTrans();

        //修改员工表数据
        $employee = $this->where(['id' => $employee_id, 'merchant_id' => $merchant_id])->save([
            'tel' => 0,
            'realname' => '匿名用户',
            'wechat_id' => '',
            'image' => '',
            'password' => '',
            'avatar' => '',
            'job_number' => '',
            'description' => '',
            'alipay_account' => '',
            'status' => 3,
        ]);
        if ($employee === false) {
            $this->rollback();
            return false;
        }

        //创建骑牛对象 2017年12月12日11:57:38
        $config = C('QINIU_CONFIG');
        $qiniu = new Qiniu($config);
        //删除七牛云上面员工头像
        $avatar = ['0' => $em_rs['avatar']];
        $avatar_rs = $qiniu->deleteFiles($avatar);
        if ($avatar_rs === false) {
            Log::write('delete employee avatar for qiniu storage fail');
        }
        //删除七牛云上面员工 照片
        $images = explode('|', $em_rs['image']);
        $image_rs = $qiniu->deleteFiles($images);
        if ($image_rs === false) {
            Log::write('delete employee image for qiniu storage fail');
        }

        //删除员工系统消息关联表 api_message_empsystem
        $empsystem = M('message_empsystem')->where($where)->delete();
        if ($empsystem === false) {
            $this->rollback();
            return false;
        }

        //删除员工消息检测时间记录表 api_message_detection
        $detection = M('message_detection')->where($where)->delete();
        if ($detection === false) {
            $this->rollback();
            return false;
        }

        //删除员员工订单与交互消息表 api_message_employee
        $message = M('message_employee')->where($where)->delete();
        if ($message === false) {
            $this->rollback();
            return false;
        }

        //删除员工对应的职位表中对应的数据 update : 2017年12月11日15:51:03
        $employeeJob = M('employee_andjob')->where($where)->delete();
        if ($employeeJob === false) {
            $this->rollback();
            return false;
        }

        //提交事务
        $this->commit();
        return true;
    }
}