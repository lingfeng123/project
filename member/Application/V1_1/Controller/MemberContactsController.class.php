<?php
/**
 * FileName: MemberContactsController.class.php
 * User: Comos
 * Date: 2017/8/23 14:49
 */

namespace V1_1\Controller;


use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\Tools;

class MemberContactsController extends BaseController
{
    //性别选择
    private $_sex = [1, 2];

    /**
     * 获取联系人列表
     * @param $member_id int 注册用户ID
     */
    public function contactsList()
    {
        $member_id = I('post.member_id', '');

        //验证用户ID
        if (empty($member_id) || !is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '输入参数不合法');
        }

        //查询用户联系人列表
        $memberContactsModel = D('MemberContacts');
        $contacts = $memberContactsModel->where(['member_id' => $member_id])->order('is_default desc,id')->select();
        if ($contacts === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '数据请求失败');
        }

        //返回成功数据
        Response::success(['list' => $contacts]);
    }


    /**
     * 添加新联系人
     * @param $member_id int 注册用户ID
     * @param $realname string 联系人姓名
     * @param $sex int 联系人性别 1男 2女
     * @param $tel int 联系人手机号码
     */
    public function createContacts()
    {
        $post_data = I('post.');
        $member_id = I('post.member_id', '');
        $realname = I('post.realname', '');
        $sex = I('post.sex', '');
        $tel = I('post.tel', '');

        //验证数据
        $this->_verifyPublicData($member_id, $realname, $sex, $tel);

        //写入数据到数据表中
        $memberContactsModel = D('MemberContacts');
        //判断是否此电话号码已添加联系人
        $res = $memberContactsModel->verifyTelExist($tel, $member_id);
        if ($res) {
            Response::error(ReturnCode::DATA_EXISTS, '联系人已存在');
        }

        //添加新联系人
        $rs = $memberContactsModel->createNewContact($post_data);
        if ($rs === false) {
            Response::error(ReturnCode::DB_SAVE_ERROR, 'code: ' . ReturnCode::DB_SAVE_ERROR . ' 添加失败');
        }

        //返回成功结果
        Response::success(['id' => $rs]);
    }


    /**
     * 修改联系人信息
     * @param $member_id int 注册用户ID
     * @param $contact_id int 联系人记录ID
     * @param $realname string 联系人姓名
     * @param $sex int 联系人性别 1男 2女
     * @param $tel int 联系人手机号码
     */
    public function updateContacts()
    {
        $data = I('post.');
        $member_id = I('post.member_id', '');
        $contact_id = I('post.contact_id', '');
        $realname = I('post.realname', '');
        $sex = I('post.sex', '');
        $tel = I('post.tel', '');

        //验证公共数据
        $this->_verifyPublicData($member_id, $realname, $sex, $tel);

        //验证用户ID
        if (!is_numeric($contact_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //修改用户数据
        $memberContactsModel = D('MemberContacts');
        //查询条件,根据联系人记录ID与用户ID匹配记录
        $where = ['id' => $contact_id, 'member_id' => $member_id];
        //查询数据是否存在
        $rs = $memberContactsModel->where($where)->count();
        if (!$rs) {
            Response::error(ReturnCode::NOT_FOUND, '联系人不存在');
        }

        //保存数据
        $res = $memberContactsModel->where($where)->save($data);
        if ($res === false) {
            Response::error(ReturnCode::DB_SAVE_ERROR, '修改联系人失败');
        }

        //返回成功结果
        Response::success("");
    }


    /**
     * 设置默认联系人
     * @param $member_id int    用户ID
     * @param $contact_id int   联系人ID
     */
    public function setDefaultContact()
    {
        $member_id = I('post.member_id', '');
        $contact_id = I('post.contact_id', '');

        //验证用户ID
        if (!is_numeric($member_id) || !is_numeric($contact_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //查询条件
        $where = ['id' => $contact_id, 'member_id' => $member_id];

        //查询当前数据是否存在
        $memberContactsModel = D('MemberContacts');
        $rs = $memberContactsModel->where($where)->count();
        if (!$rs) {
            Response::error(ReturnCode::NOT_FOUND, '联系人不存在');
        }

        //清除已设置默认联系人状态
        $res = $memberContactsModel->where(['member_id' => $member_id])->save(['is_default' => 0]);
        if ($res === false) {
            Response::error(ReturnCode::DB_SAVE_ERROR, '设置默认联系人失败');
        }

        //修改当前联系人为默认
        if ($memberContactsModel->where($where)->save(['is_default' => 1]) === false) {
            Response::error(ReturnCode::DB_SAVE_ERROR, '设置默认联系人失败');
        }

        //返回成功结果
        Response::success("");
    }


    /**
     * 根据联系人ID删除联系人
     * @param $member_id int    用户ID
     * @param $contact_id int   联系人ID
     */
    public function deleteContacts()
    {
        $member_id = I('post.member_id', '');
        $contact_id = I('post.contact_id', '');

        //验证用户ID
        if (!is_numeric($member_id) || !is_numeric($contact_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        $memberContactsModel = D('MemberContacts');
        $rs = $memberContactsModel->where(['member_id' => $member_id, 'id' => $contact_id])->delete();
        if (!$rs) {
            Response::error(ReturnCode::DB_SAVE_ERROR, '删除失败');
        }

        //返回成功结果
        Response::success("");
    }


    /**
     * 验证公共数据合法性
     * @param $member_id int 注册用户ID
     * @param $realname string 联系人姓名
     * @param $sex int 联系人性别 1男 2女
     * @param $tel int 联系人手机号码
     */
    private function _verifyPublicData($member_id, $realname, $sex, $tel)
    {
        //验证用户ID
        if (!is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        $realname = Tools::filterEmoji($realname);
        //验证姓名
        if (mb_strlen($realname, 'utf8') < 1 || mb_strlen($realname, 'utf8') > 10) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '联系人姓名只能是中英文组成的1-10位字符');
        }

        //验证性别
        if (!in_array($sex, $this->_sex)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '性别选择不正确');
        }
        //验证手机号码
        if (!preg_match("/^1[345789]\d{9}$/", $tel)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '手机号码不合法');
        }
    }


}