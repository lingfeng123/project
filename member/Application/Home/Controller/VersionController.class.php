<?php
/**
 * 用户端APP版本请求接口
 */

namespace Home\Controller;


use Org\Util\Response;
use Org\Util\ReturnCode;

class VersionController extends BaseController
{
    /**
     * 用户端APP版本号
     * @param $client int 终端标识
     * @param $platform
     */
    public function version()
    {
        $client = I('post.client', '');

        //判断传入版本终端是否在指定值
        if (!in_array($client, ['ios', 'android'])) {
            Response::error(ReturnCode::PARAM_INVALID, '请求参数不合法');
        }

        //获取当前最新版本数据
        $version = M('version')->where(['client' => $client, 'platform' => 1])->order('id desc')->find();
        if ($version === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取应用最新版本信息失败');
        }

        $version['id'] = (int)$version['id'];

        //更新版本内容解析 :updatetime 2017年12月19日15:09:10
        $string=(string)htmlspecialchars_decode($version['content']);

        //将</p><p>标签替换成换行符\n
        $string=preg_replace("/<\/p><p.*?>/is","\n", $string);
        $version['content']=(string)strip_tags(htmlspecialchars_decode($string));

        Response::setSuccessMsg('请求版本成功');
        Response::success($version);
    }
}