<?php
/**
 * FileName: CouponModel.class.php
 * User: Comos
 * Date: 2018/1/18 15:55
 */

namespace V1_1\Model;


use Org\Util\JPushNotify;
use Org\Util\Tools;
use Think\Model;

class CouponModel extends Model
{
    public $couponNumber = 0;

    /**
     * 更新优惠券状态
     */
    private function updateCardStatus($member_id, $page, $pagesize)
    {
        $condition = ['card_status' => 0, 'member_id' => $member_id];
        //获取用户现在可用的卡券
        $canuseCards = $this->join('api_coupon_member ON api_coupon_member.card_id = api_coupon.id')->where($condition)->page($page, $pagesize)->select();
        //判断更新卡券状态(更新过期的卡券)
        $this->_changeExpiredCards($canuseCards);
    }


    /**
     * 根据用户ID获取卡券列表
     * $is_effective int 0全部 1有效 2已使用/过期
     */
    public function getCardsListByMemberId($member_id, $is_effective, $pagesize, $page)
    {
        $this->updateCardStatus($member_id, $page, $pagesize);
        //获取优惠券列表
        $where['member_id'] = $member_id;
        if ($is_effective == 1) {
            //未使用
            $where['card_status'] = 0;
        } elseif ($is_effective == 2) {
            //已使用
            $where['card_status'] = ['in', [1, 2]];
        }

        //获取优惠券总数
        $count = D('couponMember')->where($where)->count();

        //获取优惠券列表
        $cards = $this->join('api_coupon_member ON api_coupon_member.card_id = api_coupon.id')->where($where)->page($page, $pagesize)->select();

        if ($cards === false || $count === false) {
            return false;
        }
        foreach ($cards as $key => $card) {
            if ($card['effective_time'] != 0 && $card['end_time'] == 0) {
                $cards[$key]['end_time'] = $card['get_time'] + $card['effective_time'] * 24 * 60 * 60;
            } else if ($card['end_time'] != 0) {
                $cards[$key]['end_time'] = $card['end_time'];
            }
        }

        //返回数据结果
        return [
            'total' => $count,
            'list' => $cards
        ];
    }


    /**
     * 将已过期优惠券改为已过期状态
     */
    private function _changeExpiredCards($cards)
    {
        //遍历判断卡券是否过期
        foreach ($cards as $card) {
            if ($card['effective_time'] != 0) {
                //计算卡券过期时间节点
                $last_time = $card['get_time'] + $card['effective_time'] * 24 * 60 * 60;

                //判断叠加时间后是否过期,过期后将卡券ID记录
                if ($last_time < time()) {
                    //修改卡券状态
                    M('coupon_member')->where(['card_id' => $card['id'], 'member_id' => $card['member_id']])->save(['card_status' => 2]);
                }

            } elseif ($card['end_time'] < time()) {
                echo 1111;
                //修改卡券状态
                M('coupon_member')->where(['card_id' => $card['id'], 'member_id' => $card['member_id']])->save(['card_status' => 2]);
            }
        }
    }

    /**
     * 所有卡券列表
     */
    public function getAllCardsList($member_id, $pagesize, $page)
    {
        //当前时间
        $time = time();

        //查询字段
        $fileds = "id as card_id,card_name,merchant_id,deductible,high_amount,card_type,effective_time,is_sex,marks,start_time,end_time,member_id as have";

        //获取所有可以领取的优惠券
        $cards_where = "(case when `end_time` !=0  THEN  `end_time` > {$time} else `effective_time` !=0  END) AND `status`= 1 AND `flag`= 1";


        //所有可领取卡券
        $count = $this->field($fileds)
            ->join('api_coupon_member ON api_coupon_member.card_id = api_coupon.id AND api_coupon_member.member_id =' . $member_id, 'LEFT')
            ->where($cards_where)
            ->count();

        $cards = $this->field($fileds)
            ->join('api_coupon_member ON api_coupon_member.card_id = api_coupon.id AND api_coupon_member.member_id =' . $member_id, 'LEFT')
            ->where($cards_where)
            ->page($page, $pagesize)
            ->order('id desc')
            ->select();

        if ($cards === false || $count === false) {
            return false;
        }

        //遍历数据,判断是否可领
        foreach ($cards as $key => $card) {
            if ($card['have']) {
                $cards[$key]['have'] = 1;
            } else {
                $cards[$key]['have'] = 0;
            }
            if ($card['effective_time'] != 0 && $card['end_time'] == 0) {
                $cards[$key]['end_time'] = time() + $card['effective_time'] * 24 * 60 * 60;
            } else if ($card['end_time'] != 0) {
                $cards[$key]['end_time'] = $card['end_time'];
            }
        }

        //返回卡券数据
        return [
            'list' => $cards,
            'total' => $count
        ];
    }


    /**
     * 写入用户新优惠券数据
     */
    public function addMemberNewCard($member_id, $card_id)
    {
        //优惠券数据
        $card_data = [
            'member_id' => $member_id,
            'card_id' => $card_id,
            'card_status' => 0,
            'get_time' => time(),
        ];
        //写入用户优惠券数据
        $res = M('coupon_member')->add($card_data);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * 首页领取优惠券并写入数据库
     */
    public function indexCardsAdd($member_id)
    {
        $time = time();
        //查询字段
        $fileds = "id as card_id,card_name,merchant_id,deductible,high_amount,card_type,effective_time,is_sex,marks,start_time,end_time,member_id as have,get_start_time,get_end_time";

        //获取用户信息
        $sex = M('member')->where(['id' => $member_id])->getField('sex');

        //所有可领取首页发放卡券  flag
        $cards_where = "(case when `end_time` !=0  THEN  `end_time` > {$time} else `effective_time` !=0 END) AND ( `is_sex` = 0 OR `is_sex`={$sex}) AND `status`= 1 AND `flag`= 2";

        //查询数据
        $cards = $this->field($fileds)
            ->join('api_coupon_member ON api_coupon_member.card_id = api_coupon.id AND api_coupon_member.member_id=' . $member_id, 'LEFT')
            ->where($cards_where)
            ->limit(3)
            ->order('id desc')
            ->select();

        //如果存在发布的优惠券才进行数据写入
        if ($cards) {
            $add_cards = [];
            //遍历数据,判断是否可领
            foreach ($cards as $key => $card) {
                //领取时间限定
                if ($card['get_start_time'] && $card['get_end_time'] && ($time < $card['get_start_time'] || $time > $card['get_end_time'])) {
                    unset($cards);
                    continue;
                }

                if ($card['effective_time'] != 0 && $card['end_time'] == 0) {
                    $cards[$key]['end_time'] = $time + $card['effective_time'] * 24 * 60 * 60;
                } else if ($card['end_time'] != 0) {
                    $cards[$key]['end_time'] = $card['end_time'];
                }
                if (!$card['have']) {
                    $add_cards[] = ['member_id' => $member_id, 'card_id' => $card['card_id'], 'card_status' => 0, 'get_time' => $time];
                    unset($cards[$key]['have']);
                } else {
                    unset($cards[$key]['have']);
                }
            }

            //如果优惠券不为空
            $count = count($add_cards);
            if ($count > 0) {

                $msg_title = '优惠券到账通知';
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
                JPushNotify::toAliasNotify($member_id, $message);

                //批量写入用户优惠券
                M('coupon_member')->addAll($add_cards);
            }

            //如果总数为0
            if ($count == 0) {
                $cards = [];
            }

        } else {
            $count = 0;
            $cards = [];
        }

        return [
            'total' => $count,
            'list' => $cards
        ];
    }

    /**
     * 获取所有我的可用的,不可用的所有卡券
     */
    public function useFulCard($member_id, $page, $pagesize, $merchant_id, $money, $goods_ids)
    {
        //更新卡券状态
        $this->updateCardStatus($member_id, $page, $pagesize);

        //获取所有还未使用和未过期的卡券
        $where = ['member_id' => $member_id, 'card_status' => 0];
        $cards = $this->field('api_coupon.*,api_coupon_member.card_status,api_coupon_member.get_time,api_coupon_member.member_id')
            ->join('left join api_coupon_member ON api_coupon_member.card_id=api_coupon.id')
            ->where($where)
            ->page($page, $pagesize)
            ->select();

        $sex = M('member')->where(['id' => $member_id])->getField('sex');   //获取用户信息
        $goods = M('goods_pack')->field('id,type')->where(['id' => ['in', $goods_ids]])->select();  //获取商品信息

        $useArr = []; //可用的
        $unUseArr = []; //不可用的

        //根据条件判断
        foreach ($cards as $key => $card) {
            //判断
            if ($card['effective_time'] != 0 && $card['end_time'] == 0) {
                $card['end_time'] = $card['get_time'] + $card['effective_time'] * 24 * 60 * 60;
            } else if ($card['end_time'] != 0) {
                $card['end_time'] = $card['end_time'];
            }

            //第一,判断是否是商户可用的卡券
            if ($card['merchant_id'] != 0 && $card['merchant_id'] != $merchant_id) {
                $unUseArr[] = $card;
                continue;
            }

            //第二,判断是否达到使用条件
            if ($card['card_type'] == 1 && $money < $card['high_amount']) {
                $unUseArr[] = $card;
                continue;
            }

            //第三,判断是否符合男女限制条件
            if ($card['is_sex'] != 0 && $card['is_sex'] != $sex) {
                $unUseArr[] = $card;
                continue;
            }

            //第四.判断是否到达优惠活动开始的时间
            if ($card['start_time'] != 0 && $card['start_time'] > time()) {
                $unUseArr[] = $card;
                continue;
            }

            //第五, 判断是否符合商户分类
            if ($card['merchant_type'] != 0) {
                $merchant_type = M('merchant')->where(['id' => $merchant_id])->getField('merchant_type');
                if ($merchant_type != $card['merchant_type']) {
                    $unUseArr[] = $card;
                    continue;
                }
            }

            //第六, 判断商品是否在分类中
            if ($card['goods_type'] != 0) {
                if (!$this->couponPackValidate($goods, $card['goods_type'])) {
                    $unUseArr[] = $card;
                    continue;
                }
            }

            $useArr[] = $card;

        }

        //计算可用优惠券的总数
        $data['count'] = count($useArr);
        $data['useful'] = $useArr;
        $data['unuseful'] = $unUseArr;
        return $data;
    }

    /**
     * 验证优惠券的合法性,判断是否符合条件
     * @param $card_id int 优惠券ID
     * @param $member_id int 用户ID
     * @param $order_price float 价格
     * @param $merchant_id int 商户ID
     * @param $goods array 商户ID
     * @return bool
     */
    public function checkCardIsUseful($card_id, $member_id, $order_price, $merchant_id, $goods)
    {
        //1 验证该优惠券是否和用户绑定
        $rs = M('coupon_member')->field('card_id')->where(['member_id' => $member_id, 'card_id' => $card_id])->find();
        if (!$rs) {
            $this->error = '该用户不存在该优惠券';
            return false;
        }

        //获取卡券信息
        $card = $this->where(['id' => $card_id, 'status' => 1])->find();
        //获取用户信息
        $sex = M('member')->where(['id' => $member_id])->getField('sex');
        if (!$sex) {
            $this->error = '用户信息获取失败';
            return false;
        }

        //2 验证优惠条件是否满足
        //a 是不是该商户的可用的
        //b 判断是否达到优惠的消费条件
        //c 判断是否符合男女限制
        //d 是否在活动时间范围内
        //e 是否满足酒吧分类条件
        //f 是否满足套餐类型
        if ($card) {
            //判断是否在卡券的活动时间范围内
            if (($card['start_time'] != 0 && $card['start_time'] > time()) || ($card['end_time'] != 0 && $card['end_time'] < time())) {
                $this->error = '请在活动时间范围内使用优惠券';
                return false;
            }

            //是否是该商户优惠券
            if ($card['merchant_id'] != 0 && $merchant_id != $card['merchant_id']) {
                $this->error = '该优惠券不能在该商户使用';
                return false;
            }

            //判断是否是满减
            if ($card['card_type'] == 1 && $order_price < $card['high_amount']) {
                $this->error = '优惠券不满足优惠条件';
                return false;
            }

            //判断是否符合男女选择
            if ($card['is_sex'] != 0 && $sex != $card['is_sex']) {
                $this->error = '优惠券使用性别不符合';
                return false;
            }

            //是否满足酒吧分类条件
            if ($card['merchant_type'] != 0) {
                $merchant = M('merchant')->field('merchant_type')->find($merchant_id);
                if ($merchant['merchant_type'] != $card['merchant_type']) {
                    $this->error = '优惠券不符合酒吧类别使用条件';
                    return false;
                }
            }

            //是否满足套餐类型
            if ($card['goods_type'] != 0) {
                if (!$this->couponPackValidate($goods, $card['goods_type'])) {
                    $this->error = '优惠券不符合套餐类型';
                    return false;
                }
            }

        } else {
            $this->error = '优惠券不存在';
            return false;
        }

        return true;
    }

    /**
     * 验证套餐是否满足优惠
     */
    private function couponPackValidate($goods, $goods_type)
    {
        foreach ($goods as $good) {
            if ($goods_type == $good['type']) {
                return true;
            }
        }
        return false;
    }


    /**
     * 获取所有我的可用的1.2版本使用
     */
    public function getGoodsListUseFulCards($merchant_id, $goods_info, $sex, $cards)
    {
        $useArr = []; //可用的

        //根据条件判断
        foreach ($cards as $key => $card) {
            //判断
            if ($card['effective_time'] != 0 && $card['end_time'] == 0) {
                $card['end_time'] = $card['get_time'] + $card['effective_time'] * 24 * 60 * 60;
            } else if ($card['end_time'] != 0) {
                $card['end_time'] = $card['end_time'];
            }

            //第一,判断是否是商户可用的卡券
            if ($card['merchant_id'] != 0 && $card['merchant_id'] != $merchant_id) {
                continue;
            }

            //第二,判断是否达到使用条件
            if ($card['card_type'] == 1 && $goods_info['price'] < $card['high_amount']) {
                continue;
            }

            //第三,判断是否符合男女限制条件
            if ($card['is_sex'] != 0 && $card['is_sex'] != $sex) {
                continue;
            }

            //第四.判断是否到达优惠活动开始的时间
            if ($card['start_time'] != 0 && $card['start_time'] > time()) {
                continue;
            }

            //第五, 判断是否符合商户分类
            if ($card['merchant_type'] != 0) {
                $merchant_type = M('merchant')->where(['id' => $merchant_id])->getField('merchant_type');
                if ($merchant_type != $card['merchant_type']) {
                    continue;
                }
            }

            //第六, 判断商品是否在分类中
            if ($card['goods_type'] != 0) {
                if ($goods_info['type'] != $card['goods_type']) {
                    continue;
                }
            }

            $useArr[] = [
                'deductible' => $card['deductible'],
                'card_id' => $card['id'],
            ];
        }

        if (count($useArr) > 0) {
            $arr = Tools::multiArraySort($useArr, 'deductible', SORT_DESC);
            return $arr[0];
        } else {
            return [
                'deductible' => '0.00',
                'card_id' => '0',
            ];
        }

    }


    /**
     * ===================================================================
     *  活    动   优  惠    券  领   取
     * ===================================================================
     *
     * 通用 - 新人注册券
     * 立减99元（有效期15天）领取时间4月17日~5月20日）预计1000张~3000张
     */
    public function newUserGetCard($member_id)
    {
        $cards = M('coupon')->field('id,total,get_start_time,get_end_time')->where(['flag' => 4, 'status' => 1])->order('id desc')->select();
        if (!$cards) {
            return false;
        }

        foreach ($cards as $card) {
            $member_coupon = M('coupon_member')->where(['member_id' => $member_id, 'card_id' => $card['id']])->find();
            if ($member_coupon) {
                continue;
            }

            $time = time();
            if ($card['total'] > 0) {
                if ($time >= $card['get_start_time'] || $time < $card['get_end_time']) {
                    $this->startTrans();
                    $res = M('coupon_member')->add(['get_time' => $time, 'card_id' => $card['id'], 'member_id' => $member_id]);
                    if (!$res) {
                        $this->rollback();
                        continue;
                    }
                    $rs = M('coupon')->where(['id' => $card['id']])->setDec('total');
                    if ($rs === false) {
                        $this->rollback();
                        continue;
                    }
                    $this->commit();
                    $this->couponNumber += 1;
                }
            }
        }

        return true;
    }

    /**
     * 通用 - 老用户回馈券
     * 立减100元（有效期1个月），预计400张
     */
    public function oldUserGetCard($member_id)
    {
        $member = M('member')->field('id,created_time')->find($member_id);
        $cards = M('coupon')->field('id,status,total,get_start_time,get_end_time,attach_time')->where(['flag' => 3, 'status' => 1])->order('id desc')->select();
        if (!$cards) return false;

        foreach ($cards as $card) {
            //判断时间
            if ($member['created_time'] < $card['attach_time']) {
                $member_coupon = M('coupon_member')->where(['member_id' => $member_id, 'card_id' => $card['id']])->find();
                if ($member_coupon) {
                    continue;
                }

                if ($card['total'] > 0) {
                    $this->startTrans();
                    $res = M('coupon_member')->add(['get_time' => time(), 'card_id' => $card['id'], 'member_id' => $member_id]);
                    if (!$res) {
                        $this->rollback();
                        continue;
                    }

                    $rs = M('coupon')->where(['id' => $card['id']])->setDec('total');
                    if ($rs === false) {
                        $this->rollback();
                        continue;
                    }
                    $this->commit();
                    $this->couponNumber += 1;
                }
            }
        }

        /*$member = M('member')->field('id,created_time')->find($member_id);
        $time = strtotime('2018-04-15');

        if ($member['created_time'] < $time) {
            $cards = M('coupon')->field('id,status,total,get_start_time,get_end_time')->where(['flag' => 3, 'status' => 1])->order('id desc')->select();
            if (!$cards) return false;

            foreach ($cards as $card) {
                $member_coupon = M('coupon_member')->where(['member_id' => $member_id, 'card_id' => $card['id']])->find();
                if ($member_coupon) {
                    continue;
                }

                if ($card['total'] > 0) {
                    $this->startTrans();
                    $res = M('coupon_member')->add(['get_time' => $time, 'card_id' => $card['id'], 'member_id' => $member_id]);
                    if (!$res) {
                        $this->rollback();
                        continue;
                    }

                    $rs = M('coupon')->where(['id' => $card['id']])->setDec('total');
                    if ($rs === false) {
                        $this->rollback();
                        continue;
                    }
                    $this->commit();
                }
            }
        }*/

        return true;
    }

    /**
     * 通用 - 首单返利券
     * 满600立减100元（4月17日~5月20日可领取，有效期3个月）预计600张
     */
    public function consumerGetCard($member_id)
    {
        $order = M('order')->field('id,created_time')->where(['member_id' => $member_id, 'status' => 4])->limit(5)->order('created_time asc')->select();
        if (!$order){
            return false;
        }
        $cards = M('coupon')->field('id,status,total,get_start_time,get_end_time')->where(['status' => 1, 'flag' => 5])->order('id desc')->select();
        if (!$cards) {
            return false;
        }

        foreach ($cards as $card) {
            $member_coupon = M('coupon_member')->where(['member_id' => $member_id, 'card_id' => $card['id']])->find();
            if ($member_coupon) {
                continue;
            }

            if ($order[0]['created_time'] > $card['attach_time'] && $card['total'] > 0) {
                $this->startTrans();
                $res = M('coupon_member')->add(['get_time' => time(), 'card_id' => $card['id'], 'member_id' => $member_id]);
                if (!$res) {
                    $this->rollback();
                    continue;
                }

                $rs = M('coupon')->where(['id' => $card['id']])->setDec('total');
                if ($rs === false) {
                    $this->rollback();
                    continue;
                }
                $this->commit();
                $this->couponNumber += 1;
            }
        }
    }

    /**
     * 店铺 - 首单返利券
     * 判定类型值: 8
     */
    public function dianpuFirstOrderCard($member_id)
    {
        //查询所有商户不为0的优惠券
        $cards = M('coupon')->field('id,merchant_id,total,created_time,get_start_time,get_end_time,attach_time')
            ->where(['status' => 1, 'flag' => 8, 'merchant_id' => ['neq', 0]])->select();

        if (!$cards) {
            return false;
        }

        $card_ids = [];
        foreach ($cards as $card) {
            $card_ids[] = $card['id'];
        }

        $memberCoupons = M('coupon_member')->where(['member_id' => $member_id, 'card_id' => ['in', $card_ids]])->getField('card_id', true);
        $memberCoupons = is_array($memberCoupons) ? $memberCoupons : [];
        foreach ($cards as $card) {
            //判断用户是否已拥有此优惠券
            if ($memberCoupons && in_array($card['id'], $memberCoupons)) {
                continue;
            }

            //用户在此商户下的订单数量
            $orders = M('order')->field('id, created_time')->where(['member_id' => $member_id, 'status' => 4, $card['merchant_id']])->order('created_time ASC')->limit(3)->select();
            if (!$orders) {
                continue;
            }

            if ($card['total'] > 0 && $orders[0]['created_time'] >= $card['attach_time']) {
                $this->startTrans();
                $res = M('coupon_member')->add(['get_time' => time(), 'card_id' => $card['id'], 'member_id' => $member_id]);
                if (!$res) {
                    $this->rollback();
                    continue;
                }

                $rs = M('coupon')->where(['id' => $card['id']])->setDec('total');
                if ($rs === false) {
                    $this->rollback();
                    continue;
                }
                $this->commit();
                $this->couponNumber += 1;
            }
        }
    }

    /**
     * 店铺 - 新人注册券
     * 判定类型值: 7
     */
    public function dianpuNewUserCard($member_id)
    {
        //查询所有商户不为0的优惠券
        $cards = M('coupon')->where(['status' => 1, 'flag' => 7])->select();
        if (!$cards) {
            return false;
        }

        foreach ($cards as $card) {
            $member_coupon = M('coupon_member')->where(['member_id' => $member_id, 'card_id' => $card['id']])->find();
            if ($member_coupon) {
                continue;
            }

            $time = time();
            if ($card['total'] > 0) {
                if ($time >= $card['get_start_time'] || $time < $card['get_end_time']) {
                    $this->startTrans();
                    $res = M('coupon_member')->add(['get_time' => $time, 'card_id' => $card['id'], 'member_id' => $member_id]);
                    if (!$res) {
                        $this->rollback();
                        continue;
                    }
                    $rs = M('coupon')->where(['id' => $card['id']])->setDec('total');
                    if ($rs === false) {
                        $this->rollback();
                        continue;
                    }
                    $this->commit();
                    $this->couponNumber += 1;
                }
            }
        }

        return true;
    }

    /**
     * 店铺 - 老用户回馈券
     * 判定类型值: 6
     */
    public function dianpuOldUserCard($member_id)
    {
        $member = M('member')->field('id,created_time')->find($member_id);
        $cards = M('coupon')->field('id,status,total,get_start_time,get_end_time,attach_time')->where(['flag' => 6, 'status' => 1])->order('id desc')->select();
        if (!$cards) return false;

        foreach ($cards as $card) {
            if ($member['created_time'] < $card['attach_time']) {
                $member_coupon = M('coupon_member')->where(['member_id' => $member_id, 'card_id' => $card['id']])->find();
                if ($member_coupon) {
                    continue;
                }

                if ($card['total'] > 0) {
                    $this->startTrans();
                    $res = M('coupon_member')->add(['get_time' => time(), 'card_id' => $card['id'], 'member_id' => $member_id]);
                    if (!$res) {
                        $this->rollback();
                        continue;
                    }

                    $rs = M('coupon')->where(['id' => $card['id']])->setDec('total');
                    if ($rs === false) {
                        $this->rollback();
                        continue;
                    }
                    $this->commit();
                    $this->couponNumber += 1;
                }
            }
        }

        return true;
    }
}