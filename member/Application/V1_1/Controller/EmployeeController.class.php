<?php
/**
 * FileName: EmployeeController.class.php
 * User: Comos
 * Date: 2017/8/29 16:11
 */

namespace V1_1\Controller;


use Org\Util\Response;
use Org\Util\ReturnCode;

class EmployeeController extends BaseController
{

    /**
     * 根据员工ID获取员工详情
     * @param employee_id int 员工ID
     */
    public function employeeInfo()
    {
        $employee_id = I('post.employee_id', '');
        if (!is_numeric($employee_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '传入参数不合法');
        }

        //查询员工信息
        $employee_info = D('Employee')->field('api_employee.id as employee_id, tel, realname, sex, avatar,wechat_id, job_number, image, description, api_comment_empstar.average')
            ->join('api_comment_empstar ON api_employee.id = api_comment_empstar.employee_id', 'left')
            ->where(['api_employee.id' => $employee_id])
            ->find();

        //查询员工评价信息
        if (!empty($employee_info)) {

            if (empty($employee_info['image'])) {
                $employee_info['image'] = [];
            } else {
                $employee_info['image'] = explode('|', $employee_info['image']);

                //遍历数据添加图片前缀地址
                foreach ($employee_info['image'] as $key => $item) {
                    if ($item) {
                        $employee_info['image'][$key] = C('attachment_url') . $item;
                    }
                }
            }

            $employee_info['avatar'] = $employee_info['avatar'] ? C('attachment_url') . $employee_info['avatar'] : '';
            //返回数据结果
            Response::success($employee_info);
        }
        //请求失败提示
        Response::error(ReturnCode::NOT_FOUND, '请求的数据不存在');
    }

    /**
     * 获取商户服务员列表
     * @param type int 是否显示为客户经理标识状态
     * @param merchant_id int 商户ID
     */
    public function employeeList()
    {
        $merchant_id = I('param.merchant_id');
        $type = I('param.type');

        if (!is_numeric($merchant_id) || !is_numeric($type)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //获取客户显示开关
        $is_offer = D('merchant')->where(['id' => $merchant_id])->getField('is_offer');

        //如果客户开启客户显示
        if ($is_offer) {
            //查询员工信息
            $employee = D('Employee');
            $result = $employee->empList($merchant_id, $type);
            if ($result === false) {
                Response::error(ReturnCode::INVALID_REQUEST, '数据获取失败！', '');
            }

            //组装URL前缀域名
            foreach ($result as $key => $v) {
                $result[$key]['avatar'] = $result[$key]['avatar'] ? C('attachment_url') . $v['avatar'] : '';
                if ($v['average'] == null) {
                    $result[$key]['average'] = '0.0';
                }
            }
        } else {
            $result = [];
        }


        //返回成功数据
        Response::success(['is_offer' => $is_offer, 'list' => $result]);
    }
}