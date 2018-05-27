<?php
/**
 * FileName: BaseController.class.php
 * User: Comos
 * Date: 2017/8/15 17:25
 */

namespace Home\Controller;

use Org\Util\AuthSign;
use Org\Util\Response;
use Org\Util\ReturnCode;
use Think\Cache\Driver\Redis;
use Think\Controller;

class BaseController extends Controller
{
    public $log;

    public function _initialize()
    {
        //请求方式校验
        $this->verifySubmitMethod();

        //接口签名校验
        $this->verifyApiSign();

        //验证接口请求时间是否合法
        $this->verifyRequst();

        //引入log4php类
        vendor('log4php.Logger');

        //加载log4php配置文件
        \Logger::configure(CONF_PATH . 'log4php.xml');

        //获取记录器
        $this->log = \Logger::getLogger('Membera');
    }

    /**
     * 验证数据提交方式
     */
    public function verifySubmitMethod()
    {
        if (!IS_POST) {
            Response::error(ReturnCode::INVALID_REQUEST, '请求方式不被允许');
        }
    }

    /**
     * 验证接口签名
     */
    public function verifyApiSign()
    {
        //签名MD5字符串
        $sign = I('post.sign', '');
        //时间戳
        $timestamp = I('post.timestamp', '');
        $rs = AuthSign::getAuthSign($sign, $timestamp);
        if (!$rs) {
            Response::error(ReturnCode::AUTH_ERROR, '签名校验失败');
        }
    }

    /**
     * 验证请求时间是否允许
     */
    public function verifyRequst()
    {
        //实例化redis
        $redis = new Redis();
        //获取当前方法名称
        $action = CONTROLLER_NAME . '/' . ACTION_NAME;
        //获取限定方法名称
        $validate_action_name = C('VALIDATE_ACTION_NAME');
        //验证当前方法名是否在限定内
        if (in_array($action, $validate_action_name)) {
            $uid = I('post.uid', '');   //获取用户uid
            //验证UId是否存在
            if (!$uid) {
                Response::error(ReturnCode::INVALID_REQUEST, '非法请求, 不允许提交');
            }

            //redis键名
            $redis_name = 'request_xcx_' . $uid . '_' . $action;
            //在redis中获取当前用户请求缓存
            if ($request_str = $redis->get($redis_name)) {
                //如果当前方法名称与限定方法相等
                if ($request_str == $action) {
                    //获取提示
                    Response::error(ReturnCode::INVALID_REQUEST, '请求太频繁,请稍后再试!');
                }
            }
            //设定当前请求URL禁止请求时间
            $redis->set($redis_name, $action, 2);
        }

    }

    /**
     * 记录异常日志
     * @param $exception 抛出的异常对象
     */
    public function getException($exception, $method = '')
    {
        $this->log->error('method= ' . $method . ' message=' . $exception->getMessage() . ' | file=' . $exception->getFile() . ' | line=' . $exception->getLine());
    }
}