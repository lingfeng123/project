<?php
/**
 * FileName: MemberController.class.php
 * User: Comos
 * Date: 2017/8/17 13:57
 */

namespace V1_1\Controller;


use Org\Util\AuthSign;
use Org\Util\FileUpload;
use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\Sms;
use Org\Util\String;
use Org\Util\Tools;
use Org\Util\YunpianSms;
use Think\Cache\Driver\Redis;
use Think\Upload\Driver\Qiniu;

class MemberController extends BaseController
{

    /**
     * 判断是否绑定手机号
     * 根据微信unionid检测该账号是否绑定手机号
     * @param $unionid string 用户unionID
     */
    public function verifyBindPhoneNumber()
    {
        $unionid = I('post.unionid', '');
        $member_id = I('post.member_id', '');
        if (!$unionid && !$member_id) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不正确');
        }

        $where = array();
        if ($unionid) {
            $where['unionid'] = $unionid;
        }

        if ($member_id) {
            $where['id'] = $member_id;
        }

        //验证电话号码
        $res = D('Member')->field('tel')->where($where)->find();
        if ($res['tel']) {
            Response::success($res);
        }
        Response::error(ReturnCode::NOT_EXISTS, '需要您验证并绑定手机号');
    }

    /**
     * 绑定用户手机号码
     * @param $member_id int 用户ID
     * @param $tel int  手机号码
     */
    public function bidPhoneNumber()
    {
        $unionid = I('post.unionid', '');
        $member_id = I('post.member_id', '');
        $tel = I('post.tel', '');

        if (!preg_match("/^1[345789]\d{9}$/", $tel)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '手机号码不合法');
        }

        $where = array();
        if ($unionid) {
            $where['unionid'] = $unionid;
        }

        if ($member_id) {
            $where['id'] = $member_id;
        }

        //查询当前号码是否已被绑定
        $memberModel = D('Member');
        if($unionid){
            $member_data = $memberModel->field('wx_openid,xcx_openid,unionid,id')->where(['unionid'=>$unionid])->find();
        }

        $rs = $memberModel->field('id')->where(['tel' => $tel])->find();

        if ($rs) {
            $save_data = [
                'wx_openid' => $member_data['wx_openid'],
                'xcx_openid' => $member_data['xcx_openid'],
                'unionid' => $member_data['unionid'],
            ];

            $res = $memberModel->where(['id' => $rs['id']])->save($save_data);
            if ($res === false) {
                Response::error(ReturnCode::DB_SAVE_ERROR, '手机号重新绑定失败1');
            }

            $res2 = $memberModel->where(['id' => $member_data['id']])->save(['wx_openid' => '', 'xcx_openid' => '', 'unionid' => '']);
            if ($res2 === false) {
                Response::error(ReturnCode::DB_SAVE_ERROR, '手机号重新绑定失败2');
            }
            Response::success('',100);
        }else{
            //将号码绑定到用户
            $res = $memberModel->where($where)->save(['tel' => $tel]);
            if ($res === false) {
                Response::error(ReturnCode::DB_SAVE_ERROR, '绑定手机号码失败');
            }
            $data['tel'] = $tel;
            Response::success($data);
        }
    }

    /**
     * 验证登录token/3rd_session
     */
    public function verifyToken()
    {
        $session_id = I('post.token', '');  //获取小程序端传入的sessionid
        $redis = new Redis();
        $session = $redis->get($session_id);    //根据sessionid获取session值

        //判断数据是否拿到
        if (!empty($session)) {
            //将过期时间重置延长
            if ($redis->set($session_id, $session, 3600) === false) {
                Response::error(ReturnCode::INVALID_REQUEST, '');
            }

            //返回匹配结果
            Response::success();     //数据存在,返回其发送的sessionid.
        }

        Response::error(ReturnCode::INVALID_REQUEST, '您尚未登录');
    }

    /**
     * 保持微信小程序登录状态
     */
    public function wxlogin()
    {
        //获取微信用户解密信息
        $msg = $this->getWxUserInfo();

        //判断数据是否拿到
        if ($msg['errCode'] == 0) {
            if(!$msg['data']->unionId){
                Response::error(ReturnCode::INVALID_REQUEST, '获取微信授权失败');
            }
            //实例化模型
            $memberModel = D('Member');
            //从数据库获取对应用户数据
            $member_infos = $memberModel->getMemberByUnionId($msg['data']->unionId);

            //如果查询数据不存在
            if (!$member_infos || empty($member_infos)) {

                //下载用户头像并保存
                /* $avatar_url = $this->downloadImage($msg['data']->avatarUrl);
                 if ($avatar_url){
                     $msg['data']->avatarUrl = $avatar_url;
                 }else{
                     $msg['data']->avatarUrl = '';
                 }*/

                //没有获取到用户数据,执行添加新用户数据方法
                $member_infos = $memberModel->addMember($msg['data']);
                if ($member_infos === false) {
                    Response::error(ReturnCode::INVALID_REQUEST, '用户注册失败');
                }
            }

            //如果小程序的openid不存在,就将获取的小程序openid写入用户表中
            if (!$member_infos['xcx_openid']) {
                $xcx_openid = $msg['data']->openId;
                $unionid = $msg['data']->unionId;
                //修改用户的小程序openid
                $update_rs = $memberModel->where(['unionid' => $unionid])->save(['xcx_openid' => $xcx_openid]);
                if ($update_rs === false) {
                    Response::error(ReturnCode::DB_SAVE_ERROR, '登录失败');
                }
            }

            //生成3rd_session
            $session_id = 'xcx_' . $this->randomFromDev(32);
            $member_infos['token'] = $session_id;

            $redis = new Redis();
            //保存3rd_session并设置过期时间
            $redis->set($session_id, $msg['data']->openId . $msg['session_key'], 3600);
            if (!$member_infos['tel']) {
                $member_infos['tel'] = '';
            }

            //检测是否为微信头像
            if (!preg_match('/^(http|https)/ius', $member_infos['avatar'])) {
                $member_infos['avatar'] = C('attachment_url') . $member_infos['avatar'];
            }

            //如果用户修改了头像就重新设置头像
            if ($member_infos['avatar'] != $msg['data']->avatarUrl) {
                $memberModel->where(['id' => $member_infos['member_id']])->save(['avatar' => $msg['data']->avatarUrl]);
                $member_infos['avatar'] = $msg['data']->avatarUrl;
            }

            $member_infos['uid'] = AuthSign::getUserUid($member_infos['member_id'], C('ACCOUNT_TYPE.MEMBER'));
            $member_infos['kpzkf_phone'] = C('KPZKF_PHONE');

            //返回用户数据
            Response::success($member_infos);
        }

        Response::error(ReturnCode::LOGIN_ERROR, '登录失败');
    }

    /**
     * 获取所有会员等级列表
     */
    public function vipList()
    {
        $vip_data = M()->query("SELECT * FROM `api_member_privilege`");
        if (!$vip_data) {
            Response::error(ReturnCode::NOT_FOUND, '数据请求失败');
        }
        Response::success($vip_data);
    }

    /**
     * 获取当前用户的会员等级  v1.1修改
     * @param $member_id int 用户ID
     */
    public function vipInfo()
    {
        $member_id = I('post.member_id', '');
        if (!is_numeric($member_id)) Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');

        $memberModel = D('Member');
        //获取用户会员级别, 获取累计消费金额, 获取会员基础数据
        $vipInfo = $memberModel->getMemberVipInfos($member_id);
        if (!$vipInfo) {
            Response::error(ReturnCode::NOT_FOUND, '数据请求失败');
        }
        if (empty($vipInfo['signature'])) {
            $vipInfo['signature'] = '此人很懒，暂未留下任何内容';
        }


        //获取距离下一等级的消费额度差额
        $level = $vipInfo['level'] + 1;

        //判断下一等级是否大于最大会员等级
        $next_level = $memberModel->getMemberNextLevelMoney($level);
        if ($next_level === false) {
            Response::error(ReturnCode::NOT_FOUND, '数据请求失败');
        }

        if (!$next_level) {
            $vipInfo['next_vip_title'] = '';
            $vipInfo['diff_money'] = 0;
        } else {
            //计算差额
            $vipInfo['next_vip_title'] = $next_level['next_vip_title'];
            $diff_money = $next_level['quota'] - $vipInfo['consume_money'];
            $vipInfo['diff_money'] = $diff_money;
        }

        //获取会员的uid
        $vipInfo['uid'] = AuthSign::getUserUid($vipInfo['id'], C('ACCOUNT_TYPE.MEMBER'));
        $vipInfo['kpzkf_phone'] = C('KPZKF_PHONE');

        //返回用户会员数据
        Response::success($vipInfo);
    }


    /**
     * 根据传入的参数修改用户数据  v1.1
     * @param $member_id int 用户ID
     * @param $param string 参数名
     * @param $value string 参数值
     */
    public function editDatum()
    {
        $member_id = I('post.member_id', '');
        $param = I('post.param', '');
        $value = I('post.value', '');

        $memberModel = D('member');
        //验证用户名
        if (!is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        $values = ['age', 'signature', 'sex', 'nickname'];
        if (!in_array($param, $values)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数名称非法');
        }

        //验证数据是否合法 年龄
        if ($param == $values[0]) {
            if (!preg_match('/\d{4}-\d{2}-\d{2}/', $value)) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '生日输入不正确');
            }
            $value = strtotime($value);
            $now_date = strtotime(date('Y-m-d', time()));
            if ($value >= $now_date) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '出生日期不能大于今天');
            }
            $value = date('Ymd', $value);
        }

        //验证是签名是否合法
        if ($param == $values[1]) {
            $value = Tools::filterEmoji($value);
            if (mb_strlen($value) > 40) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '签名长度为40个字以内');
            }
        }

        //验证性别是否合法
        if ($param == $values[2]) {
            if (!in_array($value, [1, 2])) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '性别选择不正确');
            }

            //查询之前是否已设置过性别
            if ($memberModel->valiSexIsUpdated($member_id)) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '您已修改过性别,无法再次修改');
            }

            $data['is_edit_sex'] = 1;
        }

        //验证昵称
        if ($param == $values[4]) {
            $value = Tools::filterEmoji($value);
            if (mb_strlen($value) > 8 || mb_strlen($value) < 2) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '昵称长度为1~10个字以内');
            }
        }

        //将数据写入数据库
        $data[$param] = $value;
        $rs = D('member')->where(['id' => $member_id])->save($data);
        if ($rs === false) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '修改失败');
        }

        //将返回数据年龄为数字
        if ($param == 'age') {
            $data[$param] = Tools::calculateAge($value);
        }

        Response::setSuccessMsg('修改成功');
        Response::success($data);
    }


    /**
     * 上传会员头像 v1.1
     * 上传文件字段名为image
     *
     */
    public function editAvatar()
    {
        $member_id = I('post.member_id', '');
        if (!is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        $image = FileUpload::uplodImage('member');
        //判断上传结果
        if (!$image['status']) {
            Response::error(ReturnCode::FILE_SAVE_ERROR, $image['msg']);
        }

        //保存图片到数据表中
        $memberModel = D('member');
        //获取原用户头像
        $where = ['id' => $member_id];
        $avatar = $memberModel->where($where)->getField('avatar');
        $rs = $memberModel->where($where)->save(['avatar' => $image['path']]);
        if ($rs === false) {
            Response::error(ReturnCode::FILE_SAVE_ERROR, '修改头像失败');
        }

        //检测是否为微信头像
        if (!preg_match('/^(http|https)/ius', $avatar)) {
            //删除原用户头像
            $config = C('QINIU_CONFIG');
            $qiniu = new Qiniu($config);
            $qiniu->deleteFiles([$avatar]);
        }

        //验证图片是否为空
        if (!empty($image['path'])) {
            $avatar = C('ATTACHMENT_URL') . $image['path'];
        } else {
            $avatar = '';
        }

        Response::setSuccessMsg('修改头像成功');
        Response::success(['avatar' => $avatar]);
    }


    /**
     * 用户相册上传  v1.1
     * 上传字段名为image
     */
    public function editAlbums()
    {
        $member_id = I('post.member_id', '');
        if (!is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //上传图片
        $images = FileUpload::uplodImage('member', 1);
        //判断上传结果
        if (!$images['status']) {
            Response::error(ReturnCode::FILE_SAVE_ERROR, $images['msg']);
        }

        $merchantModel = D('member');
        $merchantModel->startTrans();
        //查询用户历史照片
        $where = ['id' => $member_id];
        $old_images = $merchantModel->where($where)->getField('image');
        if ($old_images) {
            $old_images = explode('|', $old_images);  //将图册转换为数组
            $number = count($old_images) + count($_FILES['image']['name']);
            if ($number > 8) {
                Response::error(ReturnCode::FILE_SAVE_ERROR, '最多只能上传8张图片');
            }
        } else {
            $old_images = [];
        }

        $new_files = $images['path'];
        $files = array_merge($old_images, $images['path']);   //合并数组

        //转换为字符串以便写入数据库
        $data_fileds = implode('|', $files);
        //保存图片到数据表中
        $result = $merchantModel->where($where)->save(['image' => $data_fileds]);
        if ($result === false) {
            $merchantModel->rollback();
            Response::error(ReturnCode::FILE_SAVE_ERROR, '上传图片失败');
        }

        //组装返回数据
        $attachment_url = C('ATTACHMENT_URL');
        $new_files = Tools::albumsFormat($new_files, '', $attachment_url);
        $files = Tools::albumsFormat($files, '', $attachment_url);
        $merchantModel->commit();
        //成功返回数据
        Response::setSuccessMsg('上传图片成功');
        Response::success([
            'upload_image' => $new_files,
            'image' => $files,
        ]);
    }

    /**
     * APP 单张删除用户照片 v1.1
     * @param $member_id int 用户ID
     * @param $image string 要删除的图片
     */
    public function delAlbum()
    {
        $member_id = I('post.member_id', '');
        $image = I('post.image', '');///member_20180327135449_sfi1522130089yjsp782.jpg|/member_20180327141810_kcg1522131490jgmh392.jpg|/
        if (!is_numeric($member_id) || !$image) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //格式化图片文件地址
        $image = str_replace(C('ATTACHMENT_URL'), '', $image);

        //查询用户的图片
        $memberModel = D('member');
        $where = ['id' => $member_id];
        $photos = $memberModel->where($where)->getField('image');
        $photos = explode('|', $photos);

        $new_photos = [];
        $new_images = [];
        foreach ($photos as $key => $photo) {
            if (strpos($image, $photo) === false) {
                $new_photos[] = $photo;
                $new_images[] = C('ATTACHMENT_URL') . $photo;
            }
        }

        //修改会员的相册数据
        $new_photos = implode('|', $new_photos);
        $rs = $memberModel->where($where)->save(['image' => $new_photos]);
        if ($rs === false) {
            Response::error(ReturnCode::DB_SAVE_ERROR, '图片删除失败');
        }

        //删除骑牛云中原图片
        $config = C('QINIU_CONFIG');
        $qiniu = new Qiniu($config);
        $qiniu->deleteFiles([$image]);

        Response::setSuccessMsg('图片删除成功');
        Response::success([
            'upload_image' => [],
            'image' => $new_images
        ]);
    }

    /**
     * 删除用户照片 v1.1
     * @param $member_id int 用户ID
     * @param $image string 要删除的图片
     */
    public function delAlbumxcx()
    {
        $member_id = I('post.member_id', '');
        $image = I('post.image', '');
        $image = explode(',', $image);
        if (!is_numeric($member_id) || !$image || !is_array($image)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //查询用户的图片
        $memberModel = D('member');
        $where = ['id' => $member_id];
        $photos = $memberModel->where($where)->getField('image');
        $photos = explode('|', $photos);

        //删除对应图片
        $images = [];
        foreach ($image as $item) {
            //格式化图片文件地址
            $image = str_replace(C('ATTACHMENT_URL'), '', $item);
            $images[] = str_replace(C('ATTACHMENT_URL'), '', $item);
            foreach ($photos as $key => $photo) {
                if ($photo == $image) {
                    unset($photos[$key]);
                }
            }
        }

        //修改会员的相册数据
        $new_photos = implode('|', $photos);
        $rs = $memberModel->where($where)->save(['image' => $new_photos]);
        if ($rs === false) {
            Response::error(ReturnCode::DB_SAVE_ERROR, '图片删除失败');
        }

        //删除骑牛云中原图片
        $config = C('QINIU_CONFIG');
        $qiniu = new Qiniu($config);
        $qiniu->deleteFiles($images);

        Response::setSuccessMsg('图片删除成功');
        Response::success();
    }


    /**
     * 我的钱包
     * @param $member_id int 用户ID
     */
    public function capital()
    {
        $member_id = I('post.member_id', '');
        if (!is_numeric($member_id)) Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        //获取钱包数据
        $wallet = M('MemberCapital')->field('member_id, give_money,recharge_money, password')->where(['member_id' => $member_id])->find();

        //判断查询数据是否成功
        if (!$wallet) {
            Response::error(ReturnCode::NOT_FOUND, '数据请求失败');
        }
        //计算用户总余额
        $wallet['money'] = $wallet['give_money'] + $wallet['recharge_money'];
        $wallet['money'] = (string)$wallet['money'];
        unset($wallet['give_money']);
        unset($wallet['recharge_money']);

        //判断密码是否存在
        if ($wallet['password']) {
            $wallet['is_password'] = 1;
        } else {
            $wallet['is_password'] = 0;
        }
        unset($wallet['password']);
        //返回数据
        Response::success($wallet);
    }

    /**
     * 获取会员钱包交易记录
     * @param $member_id int 用户ID
     * @param $page int int 当前页码
     * @param $page_size int 每页显示数量
     */
    public function transactionDetails()
    {
        $member_id = I('post.member_id', '');
        $page = I('post.page', 1);
        $page_size = I('post.page_size', C('PAGE.PAGESIZE'));
        if (!is_numeric($member_id) || !is_numeric($page) || !is_numeric($page_size)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //获取交易记录
        $memberRecordModel = D('MemberRecord');
        $data = $memberRecordModel->getMemberRecordList($member_id, $page, $page_size);
        if ($data === false) {
            Response::error(ReturnCode::NOT_FOUND, '数据请求失败');
        }

        //返回数据
        Response::success($data);
    }

    /**
     * 设置/修改支付密码
     * @param $member_id int 用户ID
     */
    public function setPassword()
    {
        //接收参数
        $member_id = I('post.member_id', '');
        $password = I('post.password', '');

        //验证数据合法性
        if (!is_numeric($member_id) || empty($password)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //执行密码设置操作
        $memberModel = D('Member');
        $result = $memberModel->setMemberPayPasswordById($member_id, $password);
        if (!$result) {
            Response::error(ReturnCode::DB_SAVE_ERROR, $memberModel->getError());
        }
        Response::success();
    }

    /**
     * 验证原支付密码是否正确
     * @param $member_id int 用户ID
     */
    public function verifyPayPassword()
    {
        $member_id = I('post.member_id', '');
        $password = I('post.password', '');

        if (!is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }
        //获取用户信息
        $result = D('Member')->getMemberCapitalInfoByMemberId($member_id, $password);
        if ($result) {
            Response::success(['is_success' => 1]);
        } else {
            Response::success(['is_success' => 0]);
        }
    }

    /**
     * 验证手机号码修改支付密码
     * @param $member_id int 用户ID
     */
    public function sendCode()
    {
        //获取会员绑定手机
        $tel = $this->_getMemberTel();
        if (!$tel) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取数据失败');
        }

        //发送短信
        $this->_sendSmsByTel($tel);
    }

    /**
     * 支付密码短信验证码合法性校验
     * @param $tel int 手机号码
     * @param $smscode int 短信验证码
     */
    public function validateCode()
    {
        $smscode = I('post.smscode', '');
        //验证手机号码
        $tel = $this->_getMemberTel();

        //验证短信验证码输入合法性
        if (!is_numeric($smscode) && strlen($smscode) != 6) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '短信验证码输入不合法');
        }
        //根据手机号码在redis中取出短信验证码
        $Yunpian = new YunpianSms();
        $res = $Yunpian->valiCode($tel, $smscode);
        if ($res === false) {
            Response::error(ReturnCode::CACHE_READ_ERROR, $Yunpian->errMsg);
        }
        Response::setSuccessMsg('验证通过');
        Response::success();
    }

    /**
     * 获取用户手机号码
     */
    private function _getMemberTel()
    {
        $member_id = I('post.member_id', '');
        if (!is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //获取会员绑定手机
        $tel = D('Member')->where(['id' => $member_id])->getField('tel');
        return $tel;
    }

    /**
     * 向系统手机号码发送验证码短信
     * @param $tel 手机号码
     */
    private function _sendSmsByTel($tel)
    {

        //云片短信接口,获取验证码：：code
        $YPSMS = new YunpianSms();
        $code = $YPSMS->createSmsCode($tel);

        $tpl = C('YUNPIAN');
        $tpl_id = $tpl['xiugaimima'];
        $tpl_value = [
            "#code#" => $code,
        ];
        //调用云片发送短信接口
        $response = $YPSMS->tplSingleSend($tel, $tpl_id, $tpl_value);
        if ($response === false) {
            Response::error(ReturnCode::CURL_ERROR, $YPSMS->errMsg);
        }
        Response::setSuccessMsg('短信发送成功');
        Response::success();
    }

    /**
     * 读取/dev/urandom获取随机数
     */
    private function randomFromDev($len)
    {
        $fp = @fopen('/dev/urandom', 'rb');
        $result = '';
        if ($fp !== FALSE) {
            $result .= @fread($fp, $len);
            @fclose($fp);
        } else {
            trigger_error('Can not open /dev/urandom.');
        }
        // convert from binary to string
        $result = base64_encode($result);
        // remove none url chars
        $result = strtr($result, '+/', '-_');
        return substr($result, 0, $len);
    }

    /**
     * 获取微信小程序用户的openID
     */
    private function getWxUserInfo()
    {
        $iv = I('post.iv');
        $encryptedData = I('post.encryptedData');
        $code = I('post.code');

        if (!$iv) {
            Response::error(ReturnCode::PARAM_WRONGFUL, 'iv不合法');
        }
        if (!$encryptedData) {
            Response::error(ReturnCode::PARAM_WRONGFUL, 'encryptedData不合法');
        }
        if (!$code) {
            Response::error(ReturnCode::PARAM_WRONGFUL, 'code不合法');
        }

        //组装请求地址
        $appid = C('MINI_PROGRAM.APPID');
        $secret = C('MINI_PROGRAM.SECRET');

        //数据过滤
        $iv = $this->define_str_replace($iv); //把空格转成+
        $encryptedData = urldecode($encryptedData);  //解码
        $code = $this->define_str_replace($code); //把空格转成+

        $grant_type = 'authorization_code';
        $url = 'https://api.weixin.qq.com/sns/jscode2session';
        $url = sprintf("%s?appid=%s&secret=%s&js_code=%s&grant_type=%s", $url, $appid, $secret, $code, $grant_type);
        //根据code获取数据
        $user_data = json_decode(file_get_contents($url));
        if (isset($user_data->errcode) && $user_data->errcode) {
            Response::error(ReturnCode::INVALID_REQUEST, $user_data->errcode . ': ' . $user_data->errmsg);
        }

        //获取sessionkey
        $session_key = $this->define_str_replace($user_data->session_key);

        //引入微信解密文件
        vendor('wxapp.wxBizDataCrypt');
        $wxBizDataCrypt = new \WXBizDataCrypt(C('MINI_PROGRAM.APPID'), $session_key);
        $data = "";
        //解密数据
        $errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $data);

        //得到数据并返回
        return array(
            'errCode' => $errCode,
            'data' => json_decode($data),
            'session_key' => $session_key
        );
    }

    /**
     * 用户手册
     */
    public function userguide()
    {
        $member_api_url = C('MEMBER_API_URL');
        $dir = '/html/v1.1/image/xcx_guide/';
        $data = [
            ['url' => $member_api_url . $dir . '1.png'],
            ['url' => $member_api_url . $dir . '2.png'],
            ['url' => $member_api_url . $dir . '3.png'],
            ['url' => $member_api_url . $dir . '4.png'],
            ['url' => $member_api_url . $dir . '5.png'],
            ['url' => $member_api_url . $dir . '6.png'],
            ['url' => $member_api_url . $dir . '7.png'],
            ['url' => $member_api_url . $dir . '8.png'],
            ['url' => $member_api_url . $dir . '9.png'],
            ['url' => $member_api_url . $dir . '10.png'],
            ['url' => $member_api_url . $dir . '11.png'],
            ['url' => $member_api_url . $dir . '12.png']
        ];
        Response::success($data);
    }

    /**
     * 请求过程中因为编码原因+号变成了空格,需要用下面的方法转换回来
     */
    private function define_str_replace($data)
    {
        return str_replace(' ', '+', $data);
    }

    /**
     * 抓取微信用户头像
     */
    private function downloadImage($url, $dirname = 'member')
    {
        $ds = DIRECTORY_SEPARATOR;
        $path = Tools::attachment_path() . $ds;

        //获得微信用户头像文件流
        $file = $this->curlAvatar($url);

        //文件扩展名
        $extension = 'jpg';
        //获取文件流头信息
        if (($headers = get_headers($url, 1)) !== false) {
            //构建图片格式
            $mimes = array(
                'image/bmp' => 'bmp',
                'image/gif' => 'gif',
                'image/jpeg' => 'jpg',
                'image/png' => 'png'
            );

            // 获取响应的类型
            $type = $headers['Content-Type'];
            $extension = $mimes[$type];
        }

        //图片保存路径获取
        $savepath = $dirname . $ds . date('Ymd', NOW_TIME) . $ds;
        $rootPath = $path . $savepath;

        //创建文件夹
        if (!file_exists($rootPath)) {
            mkdir($rootPath, 0777, TRUE);
        }

        //添加权限
        chmod($rootPath, 0777);

        //文件名组装
        $fileName = Tools::file_name() . '.' . $extension;
        $filePath = $savepath . $fileName;

        //保存图片到本地指定路径
        $fp2 = @fopen($path . $filePath, 'a');
        fwrite($fp2, $file);
        fclose($fp2);
        return $filePath;
    }


    /**
     * curl抓取微信用户头像
     */
    private function curlAvatar($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $file = curl_exec($ch);
        curl_close($ch);
        return $file;
    }

    /**
     * 第一次登陆填写用户的信息
     * @param  member_id int  用户ID
     * @param nickname string 用户昵称
     * @param sex int  性别 1 男 2 女
     */
    public function perfectPersonalInfo()
    {
        $member_id = I('post.member_id', '');
        $nickname = I('post.nickname', '');
        $sex = I('post.sex', 1);

        //验证用户是否正常登陆
        if (!is_numeric($member_id) || empty($member_id)) {
            Response::error(ReturnCode::INVALID, '请求参数不合法');
        }
        //判断用户昵称是否为空
        $nickname = Tools::filterEmoji($nickname);
        if (mb_strlen($nickname) > 8 || mb_strlen($nickname) < 2) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '昵称长度为1~10个字以内');
        }

        //获取member模型
        $member_model = D('member');
        $data = ['nickname' => $nickname, 'sex' => $sex, 'updated_time' => time()];
        $rs = $member_model->where(['id' => $member_id])->save($data);
        if ($rs === false) {
            Response::error(ReturnCode::DB_SAVE_ERROR, '保存资料失败');
        }
        Response::setSuccessMsg('保存资料成功');
        Response::success();
    }

    /**
     * 用户黑名单 v2.0
     * @param $member_id int 当前登录用户的ID
     * @param $page int 当前页码
     * @param $pagesize int 每页显示数量
     */
    public function blacklist()
    {
        $member_id = I('post.member_id', '');
        $page = I('post.page', 1);
        $pagesize = I('post.page_size', C('PAGE.PAGESIZE'));

        if (!is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //查询当前用户的黑名单列表
        $blacklist = D('member_blacklist')->getBlackListData($member_id, $page, $pagesize);
        if (!$blacklist) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取黑名单失败');
        }

        Response::setSuccessMsg('获取黑名单数据成功');
        Response::success($blacklist);
    }

    /**
     * 查看用户祁在黑名单中 v2.0
     * @param $member_id int 当前登录用户ID
     * @param $black_member_id int 要添加到黑名单的用户ID
     */
    public function isBlackUser()
    {
        $member_id = I('post.member_id', '');
        $black_member_id = I('post.black_member_id', '');

        if (!is_numeric($member_id) || !is_numeric($black_member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        $res = D('member_blacklist')->where(['member_id' => $member_id, 'black_member_id' => $black_member_id])->find();
        if (!$res) {
            $is_backuser = 0;
        } else {
            $is_backuser = 1;
        }

        Response::setSuccessMsg('获取记录成功');
        Response::success(['is_backuser' => $is_backuser]);
    }

    /**
     * 添加黑名单会员 v2.0
     * @param $member_id int 当前登录用户ID
     * @param $black_member_id int 要添加到黑名单的用户ID
     */
    public function addBlackUser()
    {
        $member_id = I('post.member_id', '');
        $black_member_id = I('post.black_member_id', '');

        if (!is_numeric($member_id) || !is_numeric($black_member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        if ($member_id == $black_member_id) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '自己不能拉黑自己');
        }

        //检查是否该用户已加入黑名单
        $memberBlacklistModel = D('member_blacklist');
        if (!$memberBlacklistModel->addBlackUser($member_id, $black_member_id)) {
            Response::error(ReturnCode::DB_SAVE_ERROR, $memberBlacklistModel->getError());
        }

        Response::setSuccessMsg('添加黑名单成功');
        Response::success();
    }

    /**
     * 删除黑名单用户 v2.0
     * @param $member_id int 当前登录用户ID
     * @param $black_member_id int 要添加到黑名单的用户ID
     */
    public function delBlackUser()
    {
        $member_id = I('post.member_id', '');
        $black_member_id = I('post.black_member_id', '');

        if (!is_numeric($member_id) || !is_numeric($black_member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //删除黑名单
        $rs = D('member_blacklist')->where(['member_id' => $member_id, 'black_member_id' => $black_member_id])->delete();
        if ($rs === false) {
            Response::error(ReturnCode::DEL_DATA_FAIL, '移除黑名单失败');
        }

        Response::setSuccessMsg('移除黑名单成功');
        Response::success();
    }

    /**
     * 账号安全,用户修改更换手机号
     */
    public function modifyTel()
    {
        $tel = I('post.tel', '');
        $member_id = I('post.member_id');
        $code = I('post.code', '');

        if (!preg_match("/^1[345789]\d{9}$/", $tel)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '手机号码不合法');
        }

        if (empty($code) || !is_numeric($code) || !is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        //调用云片的方法验证短信
        $yunpian = new YunpianSms();
        //验证验证码是否正确
        $YRS = $yunpian->valiCode($tel, $code);
        if ($YRS === false) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '验证码输入不正确');
        };

        $memberModel = D('member');
        //验证该电话号码是否存在
        $tel_rs = $memberModel->where(['tel' => $tel])->count();
        if ($tel_rs) {
            Response::error(ReturnCode::DB_READ_ERROR, '该手机号已绑定账号');
        }

        $member_rs = $memberModel->where(['id' => $member_id])->save(['tel' => $tel]);
        if ($member_rs === false) {
            Response::error(ReturnCode::DB_SAVE_ERROR, '更换手机号码失败');
        }

        Response::setSuccessMsg('修改手机号码成功');
        Response::success();
    }


}