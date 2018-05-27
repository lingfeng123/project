<?php
/**
 * Created by PhpStorm.
 * User: MeBook
 * Date: 2017/9/8
 * Time: 21:09
 */

namespace Home\Controller;


use Org\Util\Http;
use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\String;
use Org\Util\Tools;
use Org\Util\Wechat;
use Org\Util\YunpianSms;
use Think\Controller;
use Think\Exception;
use Think\Log;

class WechatController extends Controller
{
    private $wechat;
    private $regtoken;
    private $userOpenId;

    //初始化微信接口类
    public function _initialize()
    {
        $options = C('WECHAT_OPTION');
        $this->wechat = new Wechat($options);
    }

    //微信服务器验证方法
    //关注回复响应
    public function index()
    {
        $obj = $this->wechat->getRev(); //获取微信返回数据对象
        $msgType = $obj->getRevType();  //获取微信用户发送消息类型
        $fromUserOpenId = $obj->getRevFrom(); //获取消息发送者的openid
        $this->userOpenId = $fromUserOpenId;

        //判断消息类型 EVENT TEXT  IMAGE VOICE VIDEO SHORTVIDEO  LOCATION LINK
        switch ($msgType) {
            case Wechat::MSGTYPE_TEXT:  //文字消息

                //获取用户消息内容
                $message = $obj->getRevContent();
                //预处理消息内容
                $message = str_replace(' ', '', trim($message));

                //在数据库中查询符合关键字的内容

                $reply_data = M('wechat_reply')->where(['keyword' => $message, 'status' => 1])->order('id desc')->select();
                if (!$reply_data) {
                    $this->wechat->text('对不起, 小空暂时无法识别您的指令!')->reply();
                }

                $textContent = '';
                $newsArr = [];
                foreach ($reply_data as $datum) {
                    //文字消息
                    if ($datum['msg_type'] == 'text') {
                        $textContent = $datum['content'];
                    }

                    //图片消息
                    if ($datum['msg_type'] == 'news') {
                        if (count($newsArr) < 8) {
                            $newsArr[] = [
                                'Title' => $datum['title'],
                                'Description' => $datum['content'],
                                'PicUrl' => $datum['image'],
                                'Url' => $datum['url']
                            ];
                        }
                    }
                }

                //文字回复
                if ($textContent) {
                    $this->wechat->text(htmlspecialchars_decode($textContent))->reply();
                }

                //图文消息
                if ($newsArr) {
                    $this->wechat->news($newsArr)->reply();
                }

                exit;
                break;

            case Wechat::MSGTYPE_EVENT:     //事件推送

                //获取事件内容, 包含event变量 可能存在key变量
                $event_data = $obj->getRevEvent();

                //统计微信扫码数据
                if ($event_data['event'] == "SCAN") {
                    if ($event_data['key']) {
                        $invite_code = str_replace('qrscene_', '', $event_data['key']);
                    } else {
                        $invite_code = 0;
                    }

                    try{
                        $userInfo = $this->getWechatUserInfo();
                        M('wechat_count')->add([
                            'nickname' => $userInfo['nickname'],
                            'sex' => $userInfo['sex'],
                            'province' => $userInfo['province'],
                            'city' => $userInfo['city'],
                            'country' => $userInfo['country'],
                            'privilege' => json_encode($userInfo['privilege']),
                            'unionid' => $userInfo['unionid'],
                            'source' => $invite_code,
                        ]);

                    }catch (Exception $exception){
                        Log::write($exception);
                    }
                }

                //携带EventKey的关注用户
                if (($event_data['event'] == "subscribe" || $event_data['event'] == "SCAN") && $event_data['key']) {

                    $invite_code = str_replace('qrscene_', '', $event_data['key']);

                    Log::write('Start event_data: ' . json_encode($event_data) . 'invite_code: ' . $invite_code);

                    //判断qrscene_id类型
                    if (strlen($invite_code) <= 5) {
                        $str = 'channel=' . $invite_code;
                    } else {
                        $str = 'invite_code=' . $invite_code;
                    }

                    Log::write('params: ' . $str);

                    $url = C('MEMBER_API_URL') . '/wechat/register?' . $str;

                    //修改的推送内容
                    $content = "欢迎来到空瓶子\n\n空瓶子小程序1.2版本强势发布\n新功能-拼吧组局正式上线，点燃夏日派对激情\n\n<a href='" . $url . "'>点击领取百元新人礼包</a>";
                    Log::write('content: ' . htmlspecialchars($content));

                    //记录已注册用户的openid
                    $this->_addMemberOpenid($fromUserOpenId);

                    //发送微信消息
                    $this->wechat->text($content)->reply();
                }

                //普通关注用户
                if ($event_data['event'] == "subscribe" && !$event_data['key']) {

                    $this->_addMemberOpenid($fromUserOpenId);

                    //修改的推送内容
                    $url = C('MEMBER_API_URL') . '/wechat/register';
                    $content = "欢迎来到空瓶子\n\n空瓶子小程序1.2版本强势发布\n新功能-拼吧组局正式上线，点燃夏日派对激情\n\n<a href='" . $url . "'>点击领取百元新人礼包</a>";

                    //发送微信消息
                    $this->wechat->text($content)->reply();
                }

                exit();
                break;
            case
            Wechat::MSGTYPE_IMAGE:     //图片消息

                $this->wechat->text("欢迎进入空瓶子服务号, 已收到您发送的图片")->reply();

                exit();
                break;
            default:

                //默认发送文字消息
                $this->wechat->text("感谢您关注空瓶子服务号, 敬请期待更对精彩活动吧")->reply();
        }
    }

    /**
     * 获取微信授权用户信息
     */
    private function getWechatUserInfo()
    {
        //获取用户数据
        $appid = C('WECHAT_OPTION.appid');
        $appsecret = C('WECHAT_OPTION.appsecret');
        $fromUserOpenId = $this->userOpenId;
        $url = "https://api.weixin.qq.com/cgi-bin/";

        //获取全局accesstoken
        $accessToken = Http::get($url . "token?grant_type=client_credential&appid=$appid&secret=$appsecret");

        //根据accessToken获取用户信息
        $userInfo = Http::get($url . "user/info?access_token={$accessToken['access_token']}&openid={$fromUserOpenId}&lang=zh_CN");
        return $userInfo;
    }

    /**
     * 获取用户unionID并写入注册会员的微信openid
     */
    private function _addMemberOpenid()
    {
        $userInfo = $this->getWechatUserInfo();

        //根据unionid获取一条记录
        $memberModel = D('member');
        $member_infos = $memberModel->field('id, wx_openid, tel')->where(['unionid' => $userInfo['unionid']])->find();
        if (!$member_infos['wx_openid']) {
            $memberModel->where(['id' => $member_infos['id']])->save(['wx_openid' => $userInfo['openid']]);
        }
    }

    /**
     * 通过accesstoken换取微信用户信息
     */
    public function register()
    {
        $redis = Tools::redisInstance();
        $this->regtoken = String::randString(32, 0);
        $redis->set($this->regtoken, $this->regtoken, 600);

        $referrer = I('get.referrer', 'wechat');        //wechat, h5, qq
        //判断注册来源
        switch ($referrer) {
            case 'wechat':
                /*
                 * 微信方式注册账号
                 */
                $this->wechatRegUser($referrer);

                break;
            case 'h5':
                /**
                 * 普通H5注册方式
                 */
                $this->html5RegUser($referrer);

                break;
            case 'qq':
                /**
                 * QQ注册渠道
                 */
                $this->html5RegUser($referrer);
                break;
        }
    }

    /**
     * 微信方式注册账号
     */
    private function wechatRegUser($referrer)
    {
        //用户推广码
        $invite_code = I('get.invite_code', '');
        $channel = I('get.channel', '');
        $is_auth = I('get.is_auth', '');

        //如果推广码为空不执行注册
        if ($invite_code) {
            if (!is_numeric($invite_code) && strlen($invite_code) != 10) {
                $this->error('推广码不合法, 请尝试重新打开页面');
            }
            $agrument = ['invite_code' => $invite_code, 'is_auth' => 'auth', 'referrer' => $referrer, 'channel' => $channel];

        } else {
            $agrument = ['is_auth' => 'auth', 'referrer' => $referrer, 'invite_code' => $invite_code, 'channel' => $channel];
        }

        Log::write(json_encode($agrument));

        if ($is_auth != 'auth') {

            //跳转到微信网页授权认证页面
            $callback = C('MEMBER_API_URL') . U('Home/Wechat/register', $agrument);
            Log::write('no auth : ' . $callback);
            redirect($this->wechat->getOauthRedirect($callback));

        } else {

            //通过code换取accessToken
            $accessToken = $this->wechat->getOauthAccessToken();

            //通过accessToken与openID换取用户资料
            $userInfo = $this->wechat->getOauthUserinfo($accessToken['access_token'], $accessToken['openid']);

            //获取微信用户数据失败
            if (!$userInfo) {
                $this->error('获取微信用户数据失败');
            }

            //组装数据
            $member['openid'] = $accessToken['openid'];
            $member['unionid'] = $accessToken['unionid'];
            $member['nickname'] = Tools::filterEmoji($userInfo['nickname']);
            $member['sex'] = $userInfo['sex'] ? $userInfo['sex'] : 1;
            $member['avatar'] = $userInfo['headimgurl'];
            $member['invite_code'] = $invite_code;
            $member['channel'] = $channel;

            Log::write('member data: ' . json_encode($member));

            //判断该unionid是否已绑定或注册
            $member_rs = M('member')->where(['unionid' => $member['unionid']])->find();
            if ($member_rs) {
                $this->display('isregister');
                exit();
            }

            //分配数据
            $this->assign('member', $member);
            $this->assign('referrer', $referrer);
            $this->assign('regtoken', $this->regtoken);

            //渲染视图
            $this->display();
        }
    }

    /**
     * html5页注册
     */
    private function html5RegUser($referrer)
    {
        $invite_code = I('get.invite_code', '');
        $channel = I('get.channel', '');

        if ($invite_code) {
            if (!is_numeric($invite_code)) {
                $this->error('邀请码不合法');
            }
            if (strlen($invite_code) != 10) {
                $this->error('邀请码不合法');
            }
        }

        if ($channel) {
            if (!is_numeric($channel)) {
                $this->error('推广渠道不合法');
            }
            if (strlen($channel) > 5) {
                $this->error('推广渠道不合法');
            }
        }

        //赋值推广码
        $member['invite_code'] = $invite_code;
        $member['channel'] = $channel;

        //分配数据
        $this->assign('member', $member);
        $this->assign('referrer', $referrer);
        $this->assign('regtoken', $this->regtoken);

        //渲染视图
        $this->display();
    }

    /**
     * AJAX 提交注册用户数据
     */
    public function addSpreadMember()
    {
        if (IS_POST) {
            $data = I('post.');
            $invite_code = I('post.invite_code', 0);
            $channel = I('post.channel', 0);
            $regstoken = I('post.regtoken', 0);

            Log::write('register post data: ' . json_encode($data));

            $redis = Tools::redisInstance();
            if (!$redis->get($regstoken)) {
                Response::error(ReturnCode::PARAM_WRONGFUL, 'token检验失败');
            } else {
                $redis->del($regstoken);
            }

            //验证数据是否存在
            if ($invite_code) {
                if (!is_numeric($invite_code)) {
                    Response::error(ReturnCode::PARAM_WRONGFUL, '邀请码不合法');
                }
                if (strlen($invite_code) != 10) {
                    Response::error(ReturnCode::PARAM_WRONGFUL, '邀请码不合法');
                }
            }

            if ($channel) {
                $result = M('channel')->where(['id' => $channel])->find();
                if (!$result) {
                    Response::error(ReturnCode::PARAM_WRONGFUL, '未知推广渠道');
                }
            }

            //验证注册来源
            if ($data['referrer'] == 'wechat') {
                //验证数据是否为空
                foreach ($data as $key => $v) {
                    if ($key == 'invite_code' || $key == 'channel') {
                        continue;
                    }
                    if (!$v) {
                        Response::error(ReturnCode::PARAM_WRONGFUL, '缺少参数, 请重新进入注册');
                    }
                }
            }

            //验证手机号码
            if (!preg_match('/^1[35789]\d{9}$/', $data['tel'])) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '手机号码不正确');
            }

            //验证短信验证码
            if (!preg_match('/^\d{6}$/', $data['smscode'])) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '短信验证码不正确');
            }

            //验证输入密码
            if (isset($data['password'])) {
                if (!$data['password']) {
                    Response::error(ReturnCode::PARAM_WRONGFUL, '请输入密码');
                }
                $data['password'] = md5($data['password']);
            }

            //验证短信验证码
            $yumpian = new YunpianSms();
            if (!$yumpian->valiCode($data['tel'], $data['smscode'])) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '短信验证码不正确');
            }

            $memberModel = D('member');

            //注册类型为微信时
            if ($data['referrer'] == 'wechat') {
                //判断该手机和unionid是否已绑定或注册
                $where['_string'] = "`tel` = '{$data['tel']}' OR `unionid` = '{$data['unionid']}' OR `wx_openid` = '{$data['wx_openid']}'";
                if ($memberModel->where($where)->find()) {
                    Response::error(ReturnCode::PARAM_WRONGFUL, '手机号码或微信号已被注册');
                }
            } else {
                //判断该手机是否已绑定或注册
                $where['_string'] = "`tel` = '{$data['tel']}'";
                if ($memberModel->where($where)->find()) {
                    Response::error(ReturnCode::PARAM_WRONGFUL, '手机号码已被注册');
                }
            }

            //写入新会员数据并且添加推广员工或用户的推广记录
            $rs = $memberModel->addMemberByEmployeeInviteCode($data);
            if (!$rs) {
                $message = $memberModel->getError() ? $memberModel->getError() : '';
                Response::error(ReturnCode::PARAM_WRONGFUL, '注册会员失败' . $message);
            }

            //注册成功提醒
            Response::setSuccessMsg('新用户注册成功');
            Response::success();
        } else {
            $this->error('请求不合法');
        }
    }

    /**
     * 注册成功提醒
     */
    public function registerOk()
    {
        $activity_url = session('activity_url');
        if ($activity_url) {
            redirect($activity_url);
        }
        $this->display('register_ok');
    }

    /**
     * 商户加盟
     */
    public function merchantUnion()
    {
        if (IS_POST) {
            $data = I('post.', '');
            if (!$data) {
                $this->error('提交信息不正确');
            }
            //验证码验证
            if (!CaptchaController::check_verify($data['code'], 1)) {
                $this->error('验证码不正确');
            }
            if (!$data['name']) {
                $this->error('商户名称不能为空');
            }
            if (!$data['city']) {
                $this->error('商户名称不能为空');
            }
            if (!preg_match('/^1[345789]\d{9}$/', $data['phone'])) {
                $this->error('商户名称不能为空');
            }
            if (mb_strlen($data['contacter']) < 2) {
                $this->error('商户名称不能为空');
            }
            //组装数据
            $merchant = [
                'name' => $data['name'],
                'city' => $data['city'],
                'phone' => $data['phone'],
                'contacter' => $data['contacter'],
                'created_time' => time(),
                'is_ok' => 1,
            ];

            //写入数据到数据表
            $res = M('union')->add($merchant);
            if (!$res) {
                $this->error('提交信息失败');
            }

            $http_referer = $_SERVER['HTTP_REFERER'];
            redirect($http_referer, 3, '提交信息成功,请耐心等待客服人员联系');
        } else {
            $this->display();
        }
    }

}