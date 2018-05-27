<?php

//加载公共文件
require_once __DIR__ . '/../common.php';


class OverDue
{
    protected $ypsms;
    protected $smsTpl;

    public function __construct()
    {
        $this->ypsms = new YunpianSms();
        $this->smsTpl = $GLOBALS['config']['YUNPIAN'];
    }

    /**
     * 获取所有即将逾期过期的7天之内的所有订单
     * 每天给用户推送一条即将过期的消息，提醒用户到酒吧消费
     * 判断即将过期的方式：就是按照用户逾期的更新时间作为开始，加上用户等级的卡套逾期保护时间
     */
    public function overdue7day()
    {
        //获取逾期订单
        $orders = M('order')->alias('a')->field('a.order_no,
                a.merchant_id,
                a.member_id,
                a.pay_price,
                a.status,
                a.order_type,
                a.updated_time,
                a.contacts_sex,
                a.contacts_realname,
                a.contacts_tel,
                d.title,
                d.tel,
                d.begin_time,
                d.end_time,
                c.delayed')
            ->join('left join api_member b ON b.id=a.member_id')
            ->join('left join api_member_privilege c ON c.level = b.level')
            ->join('left join api_merchant d ON d.id=a.merchant_id')
            ->where(['a.order_type' => 2, 'a.status' => 3])
            ->select();

        //遍历发送短信
        foreach ($orders as $order) {
            $overOrdertime = $order['updated_time'] + $order['delayed'] * 60 * 24 * 60;

            //即将到期的时间
            $weekordertime = $overOrdertime - 7 * 60 * 24 * 60;

            //如果当前时间已经大于等于即将到期的时间,并且小于订单逾期过期时间
            if (time() >= $weekordertime && time() <= $overOrdertime) {

                //计算出时间差,提醒客户
                $time_diff = ($overOrdertime - time()) / (24 * 60 * 60);
                $date_time = ceil($time_diff);

                $sex = $order['contacts_sex'] == 1 ? '先生' : '女士';
                $arr = [
                    "#name#" => $order['contacts_realname'] . $sex,
                    "#product#" => $order['title'] . $order['pay_price'],
                    '#day#' => $date_time,
                    "#date#" => date('Y-m-d'),
                    '#tel#' => $order['tel']
                ];

                //发送短信
                $this->ypsms->tplSingleSend($order['contacts_tel'], $this->smsTpl['jijiangyuqizuofei'], $arr);
            }
        }
    }


    /**
     * 商户的营业时间开始的前20分钟就给用户发送消息
     */
    public function come20minutes()
    {
        $condition = [
            'a.status' => 7,
            'a.arrives_time' => strtotime(date('Y-m-d', time())),
            'a.is_xu' => 0,
            'a.is_bar' => 0,
        ];

        $orders = M('order')->alias('a')
            ->field('a.order_no,
            a.merchant_id,
            a.member_id,
            a.pay_price,
            a.status,
            a.order_type,
            a.arrives_time,
            a.contacts_sex,
            a.contacts_realname,
            a.contacts_tel,
            d.title,
            d.tel,
            d.begin_time,
            d.end_time,
            b.wx_openid')
            ->join('left join api_member b ON b.id=a.member_id')
            ->join('left join api_merchant d ON d.id=a.merchant_id')
            ->where($condition)->select();

        foreach ($orders as $key => $order) {
            $beginTime = strtotime(date('Y-m-d', $order['arrives_time']) . $order['begin_time']);
            $diffTime = $beginTime - time();

            if ($diffTime <= 1200) {
                //尊敬的#name#，您购买的#product#将在#begintime#开始生效，请留意时间准时到店消费，如有疑问请拨打空瓶子客服电话或与酒吧联系。
                $sex = $order['contacts_sex'] == 1 ? '先生' : '女士';
                $arr = [
                    "#name#" => $order['contacts_realname'] . $sex,
                    "#product#" => $order['title'] . $order['pay_price'] . '元订单',
                    '#begintime#' => $order['begin_time']
                ];

                //发送短信
                $this->ypsms->tplSingleSend($order['contacts_tel'], $this->smsTpl['ershifenzhongtixing'], $arr);
            }
        }
    }

}