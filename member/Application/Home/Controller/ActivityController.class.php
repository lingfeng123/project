<?php
/**
 * FileName: ActivityController.class.php
 * User: Comos
 * Date: 2018/1/8 15:09
 */

namespace Home\Controller;


use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\Tools;
use Org\Util\Wechat;
use Think\Controller;

class ActivityController extends Controller
{
    const MIN_PEOPLE = 10;   //限制最小抽奖人数
    const INTERVAL = 3600;   //抽奖时间间隔

    /**
     * 注册抽奖
     * @param $activity_id int 1 第一次抽奖活动
     * http://member.app.sc-csj.cn/Home/Activity/registerLottery.html
     */
    public function registerLottery()
    {
        //微信用户授权
        $member_is_reg = $this->_getWechatUserInfo('Home/Activity/registerLottery');

        /**
         * 公共变量数据
         */
        $activity_id = 1;
        //$new_year_dinner = strtotime('2018-01-07 00:00:00');     //购买新年套餐的起点时间

        //获取活动数据
        $activity = M('activity_main')->where(['id' => $activity_id, 'status' => 2])->find();
        if (!$activity) {
            $this->error('活动不存在');
        }

        //判断是否在活动时间段内
        $time = time();

        //抽奖时间点
        $diff_number = ($activity['end_time'] - $activity['start_time']) / self::INTERVAL;
        $diff_number = (int)$diff_number;

        //当前时间整点
        $now_int_time = strtotime(date('Y-m-d H', $time) . ':00:00');

        //活动时间段
        $open_time = [];
        for ($i = 1; $i <= $diff_number; $i++) {
            if (!$open_time) {
                $open_time[0] = (int)$activity['start_time'];
            }
            $open_time[$i] = $activity['start_time'] + (self::INTERVAL * $i);
        }

        //当前时间段的键名
        $key = array_search($now_int_time, $open_time);

        //判断整点是否在时间点内
        if (in_array($now_int_time, $open_time)) {

            //判断是否是起点时间, 排除活动开始时间点
            if ($now_int_time != $activity['start_time']) {

                //当前时间在抽奖时间结束之后
                if ($now_int_time >= $activity['end_time']) {

                    $stop_time = end($open_time);   //活动结束时间点
                    //查询该时段是否有中奖用户
                    $win_count = M('activity_lottery')->where(['activity_id' => $activity_id, 'win_time' => $stop_time])->count();
                    if ($win_count < 1) {
                        $this->_getActivityMember($stop_time, $activity);       //抽取一位用户
                    }
                } else {

                    //当前时间在活动时间内, 获取抽奖用户数据条件
                    //查询该时段是否有中奖用户
                    $win_count = M('activity_lottery')->where(['activity_id' => $activity_id, 'win_time' => $now_int_time])->count();
                    if ($win_count < 1) {
                        $this->_getActivityMember($now_int_time, $activity);        //抽取一位用户
                    }

                }

            }
        }

        //获取已中奖用户数据
        $win_times = M('activity_lottery')->where(['activity_id' => $activity_id])->getField('win_time', true);
        if ($win_times === false) {
            $this->display();
            exit(); //获取中奖用户失败;
        }

        if (count($win_times) < count($open_time) - 1) {
            if ($win_times) {
                $has_times = array_unique($win_times);
                //检测每个整点是否有用户中奖
                foreach ($open_time as $key_for => $value) {
                    if ($key_for > 0) {
                        if (!in_array($value, $has_times)) {
                            //抽取一位用户
                            $this->_getActivityMember($value, $activity);
                        }
                    }
                }
            } else {
                //检测每个整点是否有用户中奖
                foreach ($open_time as $key_for => $value) {
                    if ($key_for > 0) {
                        $this->_getActivityMember($value, $activity);
                    }
                }
            }
        }

        //获取已中奖用户数据
        $list = M('activity_lottery')->field("id,activity_id,member_id,member_name,nickname,member_tel,prize,created_time,status,remark,from_unixtime(win_time, '%H:%i') as win_time")
            ->where(['activity_id' => $activity_id])->order('win_time desc')->select();
        if ($list === false) {
            $this->error('获取中奖用户失败');
        }

        //判断当前时间是否大于了活动结束时间
        if ($now_int_time > $activity['end_time']) {
            //当前时间超出活动结束时间, 下一个抽奖时间点为活动结束时间
            $next_open_time = $activity['end_time'];
        } else {
            //下一个抽奖时间节点
            $next_open_time = $open_time[$key + 1];
        }

        $this->assign('list', $list);
        $this->assign('now_time', (int)$time);
        $this->assign('start_time', (int)$activity['start_time']);
        $this->assign('stop_time', (int)$activity['end_time']);
        $this->assign('activity_content', $activity['description']);
        $this->assign('next_open_time', (int)$next_open_time);
        $this->assign('member_is_reg', $member_is_reg);
        $this->display();
    }

    /**
     * 抽奖活动
     * @param $stop_time int 整点时间抽奖结束时间
     * @param $activity
     */
    private function _getActivityMember($stop_time, $activity)
    {
        /*//                                        购买用户开始时间       购买用户截止时间
        $buy_where['created_time'] = ['BETWEEN', [$new_year_dinner, $activity['start_time']]];
        $buy_where['status'] = 4;
        $buy_where['order_type'] = 3;

        //获取
        $buy_members = M('order')->where($buy_where)->getField('member_id', true);
        if ($buy_members) {
            $buy_members = array_unique($buy_members);  //已购买套餐用户
        } else {
            $buy_members = [];
        }*/

        $members = [];
        $where['created_time'] = ['BETWEEN', [$activity['start_time'], $stop_time]];
        //获取已中奖用户
        $have_members = M('activity_lottery')->where(['activity_id' => 1])->getField('member_id', true);

        if ($have_members) {
            $where['id'] = ['NOT IN', $have_members];
        }
        $where['tel'] = ['neq', 0];

        $reg_members = M('member')->where($where)->getField('id', true);
        if ($reg_members) {
            $members = $reg_members;
        }

        //判断数据是否满足条件
        if (count($members) < self::MIN_PEOPLE) {
            //小于最小抽奖人数, 组装上机器人
            $robots = explode('|', $activity['additional']);
            $members = array_merge($members, $robots);
        }

        if (!$members[0]) {
            return;
        }

        //随机获取一个中奖用户
        do {
            shuffle($members);
            $member_id = $members[0];

            //查询该用户对应的数据
            $member_info = M('member')->where(['id' => $member_id, 'status' => 1])->find();
            if ($member_info) {
                $do_status = false;
            } else {
                $do_status = true;
            }
        } while ($do_status);

        //查询该用户是否已中过奖
        $is_count = M('activity_lottery')->where(['activity_id' => 1, 'member_id' => $member_id])->count();
        if ($is_count > 0) {
            return;
        }

        $insert_data = [
            'activity_id' => $activity['id'],
            'member_id' => $member_id,
            'nickname' => $member_info['nickname'],
            'member_tel' => $member_info['tel'],
            'prize' => '666元新年大红包一个',
            'created_time' => time(),
            'win_time' => $stop_time,
        ];

        //插入中奖用户信息
        $rs = M('activity_lottery')->add($insert_data);
        $tpl=C('WEIXINTPL');
        if ($rs) {
            //向用户账号发送模板消息
            $temp_msg = [
                'touser' => $member_info['wx_openid'],
                'template_id' => $tpl['WINNING'],
                'url' => C('MEMBER_API_URL') . U('Activity/registerLottery'),
                'topcolor' => "#FF0000",
                "data" => [
                    "first" => ["value" => "恭喜您在空瓶子新年注册大抽奖活动中中奖了！", "color" => "#ff0000"],
                    "keyword1" => ["value" => '空瓶子新年注册大抽奖', "color" => "#efb33f"],
                    "keyword2" => ["value" => '666元新年大红包一个', "color" => "#efb33f"],
                    "remark" => ["value" => "空瓶子客服会尽快与您取得联系并发放奖品, 请注意保持手机通畅哦！", "color" => "#002200"],
                ]
            ];
            Tools::sendTmpMessage($member_info['wx_openid'], $temp_msg);
        }
    }

    /**
     * 获取微信用户授权
     */
    private function _getWechatUserInfo($action)
    {
        $option = C('WECHAT_OPTION');
        $wechat = new Wechat($option);

        $is_auth = I('get.is_auth', '');
        if ($is_auth != 'auth') {
            $callback = C('MEMBER_API_URL') . U($action, ['is_auth' => 'auth']);
            redirect($wechat->getOauthRedirect($callback));
        } else {
            //通过code换取accessToken
            $accessToken = $wechat->getOauthAccessToken();

            //通过accessToken与openID换取用户资料
            $userInfo = $wechat->getOauthUserinfo($accessToken['access_token'], $accessToken['openid']);

            //获取微信用户数据失败
            if (!$userInfo) {
                $this->error('用户信息失效, 请重新打开活动页面');
            }

            //根据用户的unionID查询用户是否已注册
            $member = M('member')->where(['status' => 1, 'unionid' => $accessToken['unionid']])->find();
            if ($member) {
                if ($member['tel']) {
                    //已绑定手机号码
                    return $member;
                }
                //未绑定手机号码
                return 2;
            } else {
                //未注册
                return 0;
            }
        }
    }

    /**
     * 活动2  注册送酒 活动ID：2
     * https://member.app.sc-csj.com/Home/Activity/zhucesongjiu.html
     */
    public function zhucesongjiu()
    {
        $activity_goods = "1瓶百威啤酒";
        $days = 5;
        $activityLotteryModel = M('activity_lottery');
        //获取当前活动数据
        $activity = M('activity_main')->where(['id' => 2, 'status' => 2])->find();
        if (!$activity) {
            $this->error('活动不存在');
        }

        if (time() > $activity['end_time']) {
            $message = "活动已结束";
            $this->assign('message', $message);
            $this->display('zhucesongjiu_nostart');
            exit();
        }

        if (time() < $activity['start_time']) {
            $message = "活动尚未开始";
            $this->assign('message', $message);
            $this->display('zhucesongjiu_nostart');
            exit();
        }

        //获取存储的用户session
        $member_is_reg = session('memeber_info');

        //根据session数据获取用户ID
        if (is_array($member_is_reg) && $member_is_reg['tel']) {
            $member_info = M('member')->where(['tel' => $member_is_reg['tel']])->find();
            if (!$member_info) {
                $member_is_reg = 0;
            }

            if (!$member_info['tel']) {
                $member_is_reg = 2;
            }
        }

        //手机号码不正确
        if (!$member_is_reg['tel']) {
            //微信用户授权
            $member_is_reg = $this->_getWechatUserInfo('Home/Activity/zhucesongjiu');  //1正常 2未绑定手机 3未注册
            //写入session
            session('memeber_info', $member_is_reg);
        }

        //写入活动URL地址到session中
        session('activity_url', U('Home/Activity/zhucesongjiu', '', true, true));

        //将数据分配到视图中
        $this->assign('member_is_reg', $member_is_reg);

        //如果用户数据存在
        if (is_array($member_is_reg) && $member_is_reg['tel']) {
            //查询当前用户是否已获得酒
            $act_info = $activityLotteryModel->where(['activity_id' => 2, 'member_id' => $member_is_reg['id']])->find();
            if ($act_info) {
                $wine_lock = $act_info['status'] == 1 ? 0 : 1;

                //领取是否已过期
                if ($act_info['status'] == 1 && (time() - $act_info['created_time']) > $days) {
                    $is_expre = true;
                } else {
                    $is_expre = false;
                }

                //服务员是否已确认
                $this->assign('wine_lock', $wine_lock);
                $this->assign('is_expre', $is_expre);
                $this->assign('days', $days);
                $this->assign('activity_goods', $activity_goods);
                $this->assign('act_info', $act_info);
                $this->display('zhucesongjiu_getwine');
            } else {
                //写入用户活动数据
                $insert_data = [
                    'activity_id' => 2,
                    'member_id' => $member_is_reg['id'],
                    'nickname' => $member_is_reg['nickname'],
                    'member_tel' => $member_is_reg['tel'],
                    'prize' => $activity_goods,
                    'created_time' => time()
                ];

                //写入数据
                $activityLotteryModel->add($insert_data);
                $this->assign('activity_goods', $activity_goods);
                $this->display('zhucesongjiu_getwine');
            }

        } else {
            //未登录与注册
            $this->assign('member_is_reg', $member_is_reg);
            $this->assign('activity_goods', $activity_goods);
            $this->display('zhucesongjiu_index');
        }
    }

    /**
     * 送酒服务员确认
     * 活动2  注册送酒 活动ID：2
     */
    public function songjiuConfirm()
    {
        if (IS_POST) {
            $member_id = I('post.member_id', '');
            $job_id = I('post.job_id', '');

            if (!is_numeric($member_id)) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
            }
            if (empty($job_id)) {
                Response::error(ReturnCode::PARAM_WRONGFUL, '工号不正确');
            }

            //修改记录状态
            $rs = M('activity_lottery')->where(['status' => 1, 'activity_id' => 2, 'member_id' => $member_id])->save(['status' => 2, 'remark' => $job_id, 'win_time' => time()]);
            if ($rs === false) {
                Response::error(ReturnCode::INVALID_REQUEST, '服务员确认失败');
            }

            //操作成功
            Response::success();
        }
    }
}