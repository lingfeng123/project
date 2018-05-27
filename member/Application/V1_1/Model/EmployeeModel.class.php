<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace V1_1\Model;

use Think\Model;

class EmployeeModel extends Model
{

    public function isMobile($mobile)
    {
        if (!is_numeric($mobile)) {
            return false;
        }
        return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
    }

    /**
     * 商户员工列表
     * @param $merchant_id int 商户ID
     * @param $type 账户类型
     * @return bool|mixed
     */
    public function empList($merchant_id, $type)
    {

        if (is_numeric($merchant_id) && is_numeric($type)) {
            $result = $this->where("merchant_id = '$merchant_id' and type = '$type' and status = 1")
                ->field('api_employee.id as employee_id,api_employee.realname,api_employee.avatar,api_employee.type,api_comment_empstar.average,api_employee.tel')
                ->join('api_comment_empstar ON api_comment_empstar.employee_id = api_employee.id', 'LEFT')
                ->select();

            if ($result === false) {
                return false;

            }

            return $result;
        } else {
            return FALSE;
        }
    }

}
