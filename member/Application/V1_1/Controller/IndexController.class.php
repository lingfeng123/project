<?php

namespace V1_1\Controller;

use Org\Util\JPushNotify;
use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\Tools;
use Org\Util\YunpianSms;
use Think\Controller;

class IndexController extends Controller
{
    public function queue()
    {
        echo '<pre>';
        $serverName = 'delayedServer.php';
        /*$cmd = "ps -ef|grep $serverName|grep -v grep|grep -v PPID|awk '{print $2}'";
        exec($cmd, $ppids);
        exec("ps -aux|grep $serverName", $str);

        echo '-----------PPID-----------' . PHP_EOL;
        print_r($ppids);

        echo '-----------进程信息-----------' . PHP_EOL;
        print_r($str);*/

        $beanConfig = C('BEANS_OPTIONS');
        $pheanstalk = Tools::pheanstalk();
        $status = $pheanstalk->stats();

        echo '-----------消息队列服务状态-----------' . PHP_EOL;
        print_r($status);
        $phobj = $pheanstalk->statsTube($beanConfig['TUBE_NAME'][0]);
        echo '--------' . $beanConfig['TUBE_NAME'][0] . '对列状态--------------' . PHP_EOL;
        print_r($phobj);

    }

    public function card()
    {
        D('coupon')->oldUserGetCard(6);
    }

    public function index()
    {
        $ypsms = new YunpianSms();
        $memberTels = ['13730686533', '15883700780'];
        $tpl_value = [
            '#product#' => '阿萨德阿萨德',
            '#time#' => date('Y-m-d'),
            '#telphone#' => C('KPZKF_PHONE'),
        ];

        $rs = $ypsms->tplBatchSend($memberTels, 2255398, $tpl_value);
        var_dump($rs);die;
        die;
        //$rs = JPushNotify::setAlias('1507bfd3f7ccea4f670', 14);
        //$rs = JPushNotify::delAlias(14);

        /*$msg_title = '优惠券到账通知';
        $alert = '您已收到很劲爆的优惠券，快去使用吧!';
        $message = [
            'alert' => $alert,
            'title' => $msg_title,
            'extras' => [
                'msg_type' => 'system',  //system order bar
                'title' => $msg_title,
                'content' => $alert,
                'icon' => C('MEMBER_API_URL') . '/Public/images/message/message_coupons.png',
                'order_id' => 0
            ]
        ];

        JPushNotify::toAliasNotify(14, $message);
        //var_dump($rs);
        die;*/
        $message = [
            'alert' => '',
            'title' => '',
            'extras' => [
                'msg_type' => 'bar',  //system order bar
                'title' => '',
                'content' => '',
                'icon' => '',
                'order_id' => 11
            ]
        ];

        $message['title'] = $message['extras']['title'] = ' asd asd sa';
        var_dump($message);
        die;
        echo posix_getpid();
        echo "404 NOT FOUND";
    }

    public function jpush()
    {
        $alert = I('param.alert');
        $title = I('param.title');
        $msg_type = I('param.msg_type');
        $msg_title = I('param.msg_title');
        $msg_content = I('param.msg_content');
        $order_id = I('param.order_id');
        $is_all = I('param.is_all', '');
        $member_id = I('param.member_id', '');
        $msg_icon = I('param.msg_icon', '');

        if (!$alert || !$title || !$msg_type || !$msg_title || !$msg_content || !$msg_icon) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '字段值不能为空');
        }

        $message = [
            'alert' => $alert,
            'title' => $title,
            'extras' => [
                'msg_type' => $msg_type,  //system order bar
                'title' => $msg_title,
                'content' => $msg_content,
                'icon' => $msg_icon,
                'order_id' => $order_id
            ]
        ];

        if ($is_all) {
            $rs = JPushNotify::allNotify($message);
            $msg = '全局广播';
        } else {
            $rs = JPushNotify::tosingleNotify($member_id, $message);
            $msg = '单条推送';
        }

        if ($rs === 200) {
            Response::setSuccessMsg('推送成功');
            Response::success(['response_code' => $rs, 'msg' => $msg]);
        } else {
            Response::error(ReturnCode::INVALID, '推送失败');
        }
    }
}
