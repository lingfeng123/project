<?php
/**
 * FileName: CaptchaController.class.php
 * User: Comos
 * Date: 2017/12/20 16:13
 */

namespace Home\Controller;


use Think\Verify;

class CaptchaController
{

    public function code()
    {
        $config = array(
            'fontSize' => 20,    // 验证码字体大小
            'length' => 3,     // 验证码位数
            'imageH'    =>  45,               // 验证码图片高度
            'imageW'    =>  110,               // 验证码图片宽度
            'useCurve'  =>  false,            // 是否画混淆曲线
            'useNoise'  =>  false,            // 是否添加杂点
        );
        $Verify = new Verify($config);
        $Verify->entry(1);
    }

    // 检测输入的验证码是否正确，$code为用户输入的验证码字符串
    public static function check_verify($code, $id = '')
    {
        $verify = new Verify();
        return $verify->check($code, $id);
    }
}