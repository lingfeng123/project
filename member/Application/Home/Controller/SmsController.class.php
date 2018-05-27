<?php
/**
 * FileName: SmsController.class.php
 * User: Comos
 * Date: 2017/8/28 14:49
 */

namespace Home\Controller;

use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\YunpianSms;
use Think\Controller;

class SmsController extends Controller
{
    /**
     * 向用户输入手机号码发送短信验证码
     * @param $tel int 手机号码
     */
    public function sendSmsCode()
    {
        $tel = I('post.tel', '');
        if (!preg_match("/^1[34578]\d{9}$/", $tel)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '手机号码不正确');
        }

        //云片短信接口,获取验证码：：code
        $ypsms = new YunpianSms();
        $code = $ypsms->createSmsCode($tel);
        if ($code === false) {
            Response::error(ReturnCode::CACHE_READ_ERROR, $ypsms->errMsg);
        }

        $tpl = C('YUNPIAN');
        $tpl_id = $tpl['smscode'];
        $tpl_value = [
            "#code#" => $code,
        ];

        //调用云片发送短信接口
        $response = $ypsms->tplSingleSend($tel, $tpl_id, $tpl_value);
        if ($response === false) {
            Response::error(ReturnCode::CURL_ERROR, $ypsms->errMsg);
        }
        Response::setSuccessMsg('短信发送成功');
        Response::success();
    }

    /**
     * 短信验证码合法性校验
     * @param $tel int 手机号码
     * @param $smscode int 短信验证码
     */
    public function validate()
    {
        //获取参数
        $tel = I('post.tel', '');
        $smscode = I('post.smscode', '');
        //验证手机号码
        if (!preg_match("/^1[34578]\d{9}$/", $tel)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '手机号码不正确');
        }
        //验证短信验证码输入合法性
        if (!is_numeric($smscode) && strlen($smscode) != 6) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '短信验证码输入不正确');
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
}