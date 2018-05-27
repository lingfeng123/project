<?php
/**
 * FileName: MemberAuthRecordController.class.php
 * User: Comos
 * Date: 2018/2/26 10:44
 */

namespace V1_1\Controller;


use Org\Util\Response;
use Org\Util\ReturnCode;

class MemberAuthRecordController extends BaseController
{

    /**
     * 提交派对大使认证申请
     * @param $member_id int 当前登录用户ID
     * @param $realname string 真实姓名
     * @param $tel int 手机号码
     */
    public function apply()
    {
        $member_id = I('post.member_id', '');
        $realname = I('post.realname', '');
        $contacts_tel = I('post.contacts_tel', '');

        if (!is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //验证真实姓名是否合法
        if (mb_strlen($realname) > 10 || mb_strlen($realname) < 2) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '真实姓名为2-10个字');
        }

        //验证电话号码
        if (!preg_match("/^1[345789]\d{9}$/i", $contacts_tel)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '手机号码不正确');
        }

        $memberAuthRecordModel = D('member_auth_record');
        //验证是否已申请了派对大使
        if ($memberAuthRecordModel->findRecord(['member_id' => $member_id])){
            Response::error(ReturnCode::DATA_EXISTS, '您已申请过认证,请勿重复申请');
        }

        //验证电话号码是否被占用
        if ($memberAuthRecordModel->findRecord(['contacts_tel' => $contacts_tel])) {
            Response::error(ReturnCode::DATA_EXISTS, '该手机号码已被申请');
        }

        //调用方法处理数据
        $rs = $memberAuthRecordModel->addAuthData($member_id, $realname, $contacts_tel);
        if (!$rs) {
            Response::error(ReturnCode::DB_SAVE_ERROR, '认证提交失败');
        }

        Response::setSuccessMsg('认证提交成功');
        Response::success();
    }

}