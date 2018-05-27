<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/12
 * Time: 17:55
 */

namespace V1_1\Model;


use Org\Util\Response;
use Org\Util\Tools;
use Pheanstalk\Pheanstalk;
use Think\Model;

class BarModel extends Model
{
    /**
     * 获取正在发起派对的列表(根据金纬度)
     * @param $lat              string   经度
     * @param $lng              string   纬度
     * @param $page             int      页码
     * @param $page_size        int      页面大小
     * @param $keyword          string   关键词搜索
     * @param $pay_type         int      费用类型筛选
     * @param $bar_theme        int      拼吧主题筛选
     * @param $member_id
     * @return mixed
     */
    public function getBarList($lat, $lng, $page, $page_size, $keyword, $pay_type, $bar_theme, $member_id)
    {
        $array = [1,2,7];
        $condition = [
            'a.bar_status' => ['in',$array],
            'a.is_xu' => 0,
        ];

        //如果存在关键字搜索
        if (!empty($keyword)) {
            $map['c.title'] = ['like', '%' . $keyword . '%'];
            $map['b.nickname'] = ['like', '%' . $keyword . '%'];
            $map['_logic'] = 'OR';
        }

        //如果存在费用类型的筛选
        if (!empty($pay_type)) {
            $condition['a.cost_type'] = $pay_type;
        }

        //如果存在拼吧主题的筛选
        if (!empty($bar_theme)) {
            $condition['a.bar_theme'] = $bar_theme;
        }

        //如果存在我拉黑的用户
        $black_member_ids = M('member_blacklist')->where(['member_id' => $member_id])->getField('black_member_id', true);
        if (!empty($black_member_ids)) {
            $condition['a.member_id'] = ['not in', $black_member_ids];
        }

        if (!empty($map)) {
            $condition['_complex'] = $map;
        }

        //查询条件
        $bars = $this->alias('a')
            ->field('round(2 * 6378.137* ASIN(SQRT(POW(SIN(PI()*(' . $lat . '-c.lat)/360),2)+COS(PI()*' . $lat . '/180)* COS(c.lat * PI()/180)*POW(SIN(PI()*(' . $lng . '-c.lng)/360),2))),1) as juli,a.*,b.nickname,b.avatar,b.sex,b.age,b.is_auth,c.title,c.begin_time,d.average_star,(select count(*) from api_bar_member where bar_id=a.id and pay_status in (1,2)) as count,(a.woman_number + a.man_number) as total_number,((a.woman_number + a.man_number)-(select count(*) from api_bar_member where bar_id=a.id and pay_status <> 0)) as rest_number')
            ->join('left join api_member b ON b.id=a.member_id')
            ->join('left join api_merchant c ON c.id=a.merchant_id')
            ->join('left join api_comment_barstar d ON d.member_id = a.member_id')
            ->where($condition)
            ->order('juli asc, bar_status asc, id desc ')
            ->page($page, $page_size)->select();

        foreach ($bars as $key => $bar) {
            if ($bar['bar_status'] == 1) {
                $bars[$key]['now_time'] = time();
                $arr_time = $bar['obegin_time'];
                $total_time = $arr_time - C('BEFORE_TIME') - time();
                $bars[$key]['total_time'] = $total_time;
            }

            $bars[$key]['arrives_time'] = $bar['obegin_time'];

            $bar_member=M('bar_member')->field('pay_status,id')
                ->where(['bar_id'=>$bar['id'],'member_id'=>$member_id])->find();

            if(isset($bar_member) && $bar_member['pay_status'] == 1 && $bar['bar_status'] == 1){
                $bars[$key]['bar_status'] = '8';
                $bars[$key]['order_id']  = $bar_member['id'];
            }else{
                $bars[$key]['order_id']  = '0';
            }


           if($bar['bar_type'] == 1 && $bar['is_join'] ==1 && $bar['bar_status'] == 1){

                if(isset($bar_member)){
                    if($bar_member['pay_status'] == 0){
                        $bars[$key]['take_join'] = '1';
                    }else{
                        $bars[$key]['take_join'] = '0';
                    }
                }else{
                    $bars[$key]['take_join'] = '1';
                }
           }else{
               $bars[$key]['take_join'] = '0';
           }

           if($bar['bar_type'] == 2){
               if(isset($bar_member)){
                   if($bar_member['pay_status'] == 0){
                       $bars[$key]['take_join'] = '1';
                   }else{
                       $bars[$key]['take_join'] = '0';
                   }
               }else{
                   $bars[$key]['take_join'] = '1';
               }
           }


            if (!preg_match('/^(http|https)/ius', $bar['avatar'])) {
                $bars[$key]['avatar'] = C('attachment_url') . $bar['avatar'];
            }

            $bars[$key]['average_star'] = empty($bar['average_star']) ? '0' : $bar['average_star'];
        }

        $data['list'] = $bars;
        return $data;

    }

    /**
     * 参与派对
     * @param $order_id int  订单信息
     */
    public function takeBar($bar_id, $member_id)
    {
        //查找订单是否存在
        $order = $this->where(['id' => $bar_id])->find();
        if (empty($order)) {
            $this->error = '订单不存在';
            return false;
        }

        if($order['bar_status'] == 7){
            $this->error = '订单人数已拼满';
            return false;
        }
        
        //参与拼吧的用户
        $man_number = M('bar_member')->where(['sex' => 1, 'bar_id' => $bar_id, 'pay_status' => ['in',[1,2]]])->count();
        $woman_number = M('bar_member')->where(['sex' => 2, 'bar_id' => $bar_id, 'pay_status' => ['in',[1,2]]])->count();
        //已参与总人数
        $count = $man_number + $woman_number;
        //预定参与总人数
        $startCount = $order['man_number'] + $order['woman_number'];

        //将member表中的数据
        $member = M('member')->field("nickname,sex,age,avatar,tel")->where(['id' => $member_id])->find();

        //判断用户是否符合参与该拼吧的条件
        if ($count >= $startCount) {
            $this->error = '该拼吧订单已拼满';
            return false;
        }

        $pay_price = 0;
        //判断如果男生人数已经拼满,就不能拼了
        if ($man_number >= $order['man_number'] && $member['sex'] == 1) {
            $this->error = '您不符合拼吧的要求(男生已拼满)';
            return false;
        }

        //如果用户为女生,女生人数拼满之后,就不能拼了
        if ($woman_number >= $order['woman_number'] && $member['sex'] == 2) {
            $this->error = '您不符合拼吧的要求(女生已拼满)';
            return false;
        }

        //计算每一个参与人的费用 1 男A女免 2 女A男免 3 男女AA
        if ($order['cost_type'] == 1) {
            if ($member['sex'] == 1) {
                $pay_price = $order['average_cost'];
            } else {
                $pay_price = 0;
            }
        } else if ($order['cost_type'] == 3) {
            if ($member['sex'] == 2) {
                $pay_price = $order['average_cost'];
            } else {
                $pay_price = 0;
            }
        } else if ($order['cost_type'] == 2) {
            $pay_price = $order['average_cost'];
        }
        $this->startTrans();

        //删除用户表中的相关
        /*$rs=M('bar_member')->where(['bar_id'=>$bar_id,'member_id'=>$member_id,'pay_status'=>0])->delete();
        if($rs === false){
            $this->error = '参与拼吧失败1';
            $this->rollback();
            return false;
        }*/

        //存在就往订单用户表中写数据
        $barModel = D('bar_member');

        //判断是否存在已经取消的记录
        $bar_member_order =$barModel->where(['bar_id'=>$bar_id,'member_id'=>$member_id])->find();
        if($bar_member_order){
            $bar_save_rs = $barModel->where(['bar_id'=>$bar_id,'member_id'=>$member_id])->save(['pay_status'=>1,'updated_time'=>time(),'created_time'=>time()]);
            if($bar_save_rs === false){
                $this->error = '参与拼吧失败';
                $this->rollback();
                return false;
            }
            $bar_member = $bar_member_order['id'];
            $pay_no = $bar_member_order['pay_no'];
        } else {
            $pay_no = Tools::create_order_number(4);
            $data = [
                'bar_id' => $bar_id,
                'pay_no' => $pay_no,
                'member_id' => $member_id,
                'pay_price' => $pay_price,
                'pay_status' => 1,
                'pay_type' => 0,
                'created_time' => time(),
                'updated_time' => time(),
                'arrives_time' => $order['arrives_time'],
                'realname' => $member['nickname'],
                'sex' => $member['sex'],
                'tel' => $member['tel'],
                'avatar' => $member['avatar'],
                'age' => Tools::calculateAge($member['age']),
                'is_evaluate' => 0,
            ];

            $bar_member = $barModel->add($data);
            if ($bar_member === false) {
                $this->error = '参与拼吧失败';
                $this->rollback();
                return false;
            }

        }

        $cancel_time = C('ORDER_OVERTIME');
        $delayed_data = [
            'version' => 'v1.1',
            'order_id' => $bar_member,
            'order_no' => $pay_no,
            'buy_type' => 3,    //1普通下单 2普通续酒 3拼吧下单 4拼吧续酒
            'exc_type' => 4,    //执行类型 1订单取消 2订单作废 3订单逾期
        ];
        $beanConfig = C('BEANS_OPTIONS');
        $Pheanstalk = new Pheanstalk($beanConfig['HOST']);
        $tube_name = $beanConfig['TUBE_NAME'][0];
        $Pheanstalk->putInTube($tube_name, json_encode($delayed_data), 0, $cancel_time);
        $data = [
            'order_id' => $bar_member,
            'order_no' => $pay_no,
            'bar_id' => $bar_id,
            'buy_type' => 3,
            'pay_money' => $pay_price,
        ];

        $this->commit();
        return $data;
    }


    /**
     * 拼吧订单详情
     */
    public function getBarOrderInfo($bar_id,$member_id)
    {
        $bar = $this->where(['id' => $bar_id])->find();
        if (empty($bar)) {
            $this->error = '订单数据不存在';
            return false;
        }

        //获取拼吧订单信息
        $order = $this->field('api_bar.* ,api_merchant.title,api_merchant.address,api_merchant.begin_time,api_merchant.end_time,api_member.nickname,api_member.sex,api_member.is_auth,api_member.avatar,api_member.signature,api_comment_barstar.average_star,(api_bar.woman_number+api_bar.man_number) as total_number')
            ->join('left join api_merchant ON api_merchant.id = api_bar.merchant_id')
            ->join('left join api_member ON api_member.id = api_bar.member_id')
            ->join('left join api_comment_barstar ON api_comment_barstar.member_id = api_bar.member_id')
            ->where(['api_bar.id' => $bar_id])->find();

        if (!preg_match('/^(http|https)/ius', $order['avatar'])) {
            $order['avatar'] = C('attachment_url') . $order['avatar'];
        }

        $bar_order=M('bar_member')->field('pay_status,id as order_id')->where(['bar_id'=>$bar_id,'member_id'=>$member_id])->find();
        if ($order['bar_type'] == 1) {

            if($order['bar_status'] == 1){
                if ($bar_order['pay_status'] == 1) {
                    $order['share_status'] = '0';
                } else if($bar_order['pay_status'] ==0) {
                    $order['share_status'] = '0';
                }else {
                    $order['share_status'] = '1';
                }
            }else{
                $order['share_status'] = '0';
            }

            if (in_array($order['bar_status'], [0, 3, 5, 6])) {
                $order['bar_style'] = 0;
            } else if ($order['bar_status'] == 1) {
                $order['bar_style'] = 1;
                if($bar_order['pay_status'] == 1){
                    $order['bar_style'] = 5;
                    $order['order_id'] = $bar_order['order_id'];
                }
            } else if ($order['bar_status'] == 2) {
                $order['bar_style'] = 2;
            } elseif ($order['bar_status'] == 7) {
                $order['bar_style'] = 3;
            } else if ($order['bar_status'] == 4) {
                $order['bar_style'] = 4;
            }

            if ($order['is_join'] == 1 && $order['bar_status'] == 1) {
                if (isset($bar_order)) {
                    if ($bar_order['pay_status'] == 0) {
                        $order['take_join'] = '1';
                    } else {
                        $order['take_join'] = '0';
                    }
                } else {
                    $order['take_join'] = '1';
                }
            } else {
                $order['take_join'] = '0';
            }

        } else if ($order['bar_type'] == 2) {
            if (in_array($order['bar_status'], [0, 3, 5, 6])) {
                $order['bar_style'] = 0;
            } else if ($order['bar_status'] == 1) {
                $order['bar_style'] = 1;
                $order['share_status'] = '1';
                if($bar_order['pay_status'] == 1){
                    $order['bar_style'] = 5;
                    $order['order_id'] = $bar_order['order_id'];
                }
            } else if ($order['bar_status'] == 2) {
                $order['bar_style'] = 3;
            } elseif ($order['bar_status'] == 7) {
                $order['bar_style'] = 3;
            } else if ($order['bar_status'] == 4) {
                $order['bar_style'] = 4;
            }

            if (isset($bar_order) && in_array($bar_order['pay_status'], [1, 2, 3])) {
                $order['take_join'] = '0';
            } else {
                $order['take_join'] = '1';
            }
        }

        //获取用户信息(已经参加的用户)
        $member_bar = D('bar_member');
        $member = $member_bar->field('member_id,api_member.avatar,pay_status,api_member.sex')
            ->join('left join api_member ON api_member.id = api_bar_member.member_id')
            ->where(['bar_id' => $bar_id, 'pay_status' => ['neq', 0]])->select();

        //计算已经参与拼吧的人数
        $member_number = count($member);
        $member_diff = $order['man_number'] + $order['woman_number'] - $member_number;
        $order['member_diff'] = (int)$member_diff;
        $order['arrives_time'] = $order['obegin_time'];

        $curr_man_number = $member_bar->where(['bar_id' => $bar_id, 'pay_status' => ['neq', 0], 'sex' => 1])->count();
        $curr_woman_number = $member_bar->where(['bar_id' => $bar_id, 'pay_status' => ['neq', 0], 'sex' => 2])->count();

        $order['rest_man_number'] = $order['man_number'] - $curr_man_number;
        $order['rest_woman_number'] = $order['woman_number'] - $curr_woman_number;

        $order['total_number'] = (int)$order['total_number'];
        if ($order['bar_status'] == 1) {
            $order['now_time'] = time();
            $arr_time = $order['obegin_time'];
            $total_time = $arr_time - C('BEFORE_TIME') - time();
            if ($total_time < 0) {
                $total_time = 0;
            }
            $order['total_time'] = $total_time;
        } else {
            $order['total_time'] = 0;
        }

        foreach ($member as $key => $value) {
            if (!preg_match('/^(http|https)/ius', $value['avatar'])) {
                $member[$key]['avatar'] = C('attachment_url') . $value['avatar'];
            }
        }

        if (!$order['average_star']) {
            $order['average_star'] = 0;
        }

        $data['order'] = $order;
        $data['member'] = $member;

        return $data;
    }

    /**
     * @param $type int  我发起的/我参与的
     * @param $member_id 用户ID/我的ID
     * @param $bar_type  拼吧类型1 酒局 2 派对
     */
    public function myWinePartList($type, $member_id, $page, $pageSize)
    {
        if ($type == 1) {
            //我发起的
            $condition = [
                'api_bar.member_id' => $member_id,
                'api_bar.is_xu' => 0,
            ];

            $barLists = $this->field('api_bar.* ,api_member.nickname,api_member.is_auth, api_member.sex , api_member.avatar,api_merchant.begin_time,api_merchant.title,api_merchant.end_time,api_comment_barstar.average_star,api_bar_member.id as order_id,api_bar_member.pay_status,(select count(A.id) from api_bar as A inner join api_bar_member as B ON B.bar_id = A.id AND B.member_id ='.$member_id.' where A.top_bar_id = api_bar.id) winecount')
                ->join('left join api_merchant ON api_merchant.id = api_bar.merchant_id')
                ->join('left join api_member ON api_member.id = api_bar.member_id')
                ->join('left join api_bar_member ON api_bar_member.bar_id=api_bar.id AND api_bar_member.pay_status in (1,2) AND api_bar_member.member_id=' . $member_id)
                ->join('left join api_comment_barstar  ON api_comment_barstar.member_id = api_bar.member_id')
                ->where($condition)
                ->page($page, $pageSize)
                ->order('id desc')
                ->select();

            $partycount = $this->field('api_bar.* ,api_member.nickname , api_member.sex , api_member.avatar,api_merchant.begin_time,api_merchant.title,api_comment_barstar.average_star')
                ->join('left join api_merchant ON api_merchant.id = api_bar.merchant_id')
                ->join('left join api_member ON api_member.id = api_bar.member_id')
                ->join('left join api_comment_barstar  ON api_comment_barstar.member_id = api_bar.member_id')
                ->where($condition)->count();

        } else {
            //我参与的
            $barLists = M('bar_member')
                ->field('api_bar.*,api_member.nickname,api_member.is_auth,api_member.sex,api_member.avatar,api_merchant.begin_time,api_merchant.end_time,api_merchant.title,api_comment_barstar.average_star,api_bar_member.id as order_id,api_bar_member.is_evaluate,(select count(id) from api_comment_bar where bar_id = api_bar.id ) as comment_count,api_bar_member.pay_status,(select count(A.id) from api_bar as A inner join api_bar_member as B ON B.bar_id = A.id AND B.member_id ='.$member_id.' where A.top_bar_id = api_bar.id) winecount')
                ->join('inner join api_bar ON api_bar.id = api_bar_member.bar_id AND api_bar.member_id <>'.$member_id)
                ->join('left join api_member ON api_member.id = api_bar.member_id')
                ->join('left join api_merchant ON api_merchant.id = api_bar.merchant_id')
                ->join('left join api_comment_barstar  ON api_comment_barstar.member_id = api_bar.member_id')
                ->where(['api_bar_member.member_id' => $member_id,  'api_bar.is_xu' => 0, 'api_bar_member.pay_status' => ['in', [1, 2]]])
                ->page($page, $pageSize)
                ->order('id desc')
                ->select();

            $partycount = M('bar_member')
                ->field('api_bar.*,api_member.nickname,api_member.sex,api_member.avatar,api_merchant.begin_time,api_merchant.title,api_comment_barstar.average_star,api_bar_member.is_evaluate,(select count(id) from api_comment_bar where bar_id = api_bar.id ) as comment_count')
                ->join('inner join api_bar ON api_bar.id = api_bar_member.bar_id AND api_bar.member_id <>'.$member_id)
                ->join('left join api_member ON api_member.id = api_bar.member_id')
                ->join('left join api_merchant ON api_merchant.id = api_bar.merchant_id')
                ->join('left join api_comment_barstar  ON api_comment_barstar.member_id = api_bar.member_id')
                ->where(['api_bar_member.member_id' => $member_id , 'api_bar.is_xu' => 0, 'api_bar_member.pay_status' => ['in', [1, 2]]])
                ->count();

        }
        //循环遍历
        foreach ($barLists as $key => $barList) {
            //表示没有拼满
            if ($barList['bar_status'] == 1) {

                $curr_man_number = M('bar_member')->where(['bar_id' => $barList['id'], 'pay_status' => ['neq', 0], 'sex' => 1])->count();
                $curr_woman_number =  M('bar_member')->where(['bar_id' => $barList['id'], 'pay_status' => ['neq', 0], 'sex' => 2])->count();

                $barLists[$key]['rest_man_number'] = $barList['man_number'] - $curr_man_number;
                $barLists[$key]['rest_woman_number'] = $barList['woman_number'] - $curr_woman_number;
                $barLists[$key]['rest_number'] = $barLists[$key]['rest_man_number'] + $barLists[$key]['rest_woman_number'];

            }else{
                $barLists[$key]['rest_man_number'] = 0;
                $barLists[$key]['rest_woman_number'] = 0;
                $barLists[$key]['rest_number'] = 0;
            }


            //判断是否出示二维码
            if($barList['bar_status'] == 7 && $barList['order_type']==2 && $barList['bar_type'] == 1){
                $barLists[$key]['erQcode'] = 1;
            }else{
                $barLists[$key]['erQcode'] = 0;
            }

            //判断只有派对,且拼吧状态为已完成 并且未评价的转态is_evaluate == 0
            if ($type == 1) {
               if($barList['bar_status'] == 4 && $barList['bar_type'] == 2){
                   $comment = M('comment_bar')->where(['bar_id'=>$barList['id']])->count('id');
                   if($comment > 0){
                       $barLists[$key]['is_evaluate'] = 1;
                   }
               }
            }else{
                if ($barList['bar_status'] == 4 && $barList['is_evaluate'] == 0 && $barList['bar_type'] == 2) {
                    $barLists[$key]['is_evaluate'] = 0;
                } else if ($barList['bar_status'] == 4 && $barList['is_evaluate'] == 1 && $barList['bar_type'] ==2) {
                    $barLists[$key]['is_evaluate'] = 1;
                } else {
                    unset($barLists[$key]['is_evaluate']);
                }

            }

            if (!preg_match('/^(http|https)/ius', $barList['avatar'])) {
                $barLists[$key]['avatar'] = C('attachment_url') . $barList['avatar'];
            }

            //判断用户支付状态和订单总的状态
            if ($barList['pay_status'] == 1 && $barList['bar_status'] == 1) {
                $barLists[$key]['bar_status'] = '8';
            }

            //判断能否分享拼吧
            if( $barList['bar_status'] == 1){
                // || $barList['pay_status'] == 0
                if(($barList['pay_status'] == 1 || $barList['pay_status'] == 0)){
                    $barLists[$key]['share_status'] = '0';
                }else{
                    $barLists[$key]['share_status'] = '1';
                }
            }else{
                $barLists[$key]['share_status'] = '0';
            }


            //获取是否显示拼吧续酒按钮
            $begin_time= strtotime(date('Y-m-d',$barList['arrives_time']).$barList['begin_time']);
            if($barList['begin_time'] > $barList['end_time']){
                $end_time= strtotime(date('Y-m-d',$barList['arrives_time']).$barList['end_time']) + 86400;
            }else{
                $end_time= strtotime(date('Y-m-d',$barList['arrives_time']).$barList['end_time']);
            }
            if($barList['bar_type'] == 1 && $barList['bar_status'] == 4){
                if(time() >= $begin_time && time() <= $end_time){
                    $wine_button = '1';
                }else{
                    $wine_button = '0';
                }
            }else {
                $wine_button = '0';
            }

            $barLists[$key]['wine_button'] = $wine_button;
            $barLists[$key]['arrives_time'] = $barList['obegin_time'];
            $barLists[$key]['average_star'] = empty($barList['average_star']) ? 0 : $barList['average_star'];
        }
        $data['total'] = $partycount;
        $data['list'] = $barLists;

        return $data;
    }

    /**
     * 公共拼吧订单详情
     * @param $order_id
     * @return bool|mixed
     */
    public function getOrderInfo($bar_id, $member_id)
    {
        $bar = $this->where(['id' => $bar_id])->find();
        if (empty($bar)) {
            $this->error = '订单数据不存在';
            return false;
        }

        //获取拼吧订单信息
        $bar = $this->field('api_bar.*,api_merchant.title,api_merchant.address,api_merchant.begin_time,api_merchant.end_time,api_member.nickname,api_member.sex,api_member.is_auth,api_member.avatar,api_member.signature,api_comment_barstar.average_star,(api_bar.woman_number+api_bar.man_number) as total_number,api_bar_member.pay_status,api_bar_member.pay_type,api_bar_member.id as order_id,api_bar_member.pay_no as order_no,api_bar_member.created_time as pay_time,api_bar_member.is_evaluate')
            ->join('left join api_merchant ON api_merchant.id = api_bar.merchant_id')
            ->join('left join api_member ON api_member.id = api_bar.member_id')
            ->join('left join api_bar_member ON api_bar_member.bar_id = api_bar.id AND api_bar_member.member_id = ' . $member_id)
            ->join('left join api_comment_barstar ON api_comment_barstar.member_id = api_bar.member_id')
            ->where(['api_bar.id' => $bar_id])->find();

        if (!preg_match('/^(http|https)/ius', $bar['avatar'])) {
            $bar['avatar'] = C('attachment_url') . $bar['avatar'];
        }
        $bar['begin_time'] = Tools::formatTimeStr($bar['begin_time']);
        $bar['end_time'] = Tools::formatTimeStr($bar['end_time']);

        switch ($bar['bar_type']) {
            case 1:
                if ($bar['bar_status'] == 1) {
                    if ($bar['pay_status'] == 1) {
                        $bar['bar_status'] = '8';
                        $bar['share_status'] = '0';
                    } else if ($bar['pay_status'] == 0) {
                        $bar['share_status'] = '0';
                    } else {
                        $bar['share_status'] = '1';
                    }
                } else {
                    $bar['share_status'] = '0';
                }

                unset($bar['is_evaluate']);
                break;
            case 2:

                if ($bar['bar_status'] == 1) {
                    if ($bar['pay_status'] == 1) {
                        $bar['bar_status'] = '8';
                        $bar['share_status'] = '0';
                    } else {
                        $bar['share_status'] = '1';
                    }
                } else {
                    $bar['share_status'] = '1';
                }
                if ($bar['bar_status'] == 4) {
                    if ($bar['is_evaluate'] == 0) {
                        $bar['is_evaluate'] = 0;
                    } else if ($bar['is_evaluate'] == 1) {
                        $bar['is_evaluate'] = 1;
                    }
                }else{
                    unset($bar['is_evaluate']);
                }

                break;
        }


        if ($bar['pay_status'] == 1) {
            $have_time = $bar['pay_time'] + C('ORDER_OVERTIME') - time();
            if ($have_time < 0) {
                $have_time = 0;
            }
        } else {
            $have_time = 0;
        }

        if ($bar['order_type'] ==2 && ($bar['bar_status'] == 7 || $bar['bar_status'] == 4)) {
            $order_id = M('bar_order')->where(['bar_id'=>$bar_id])->getField('order_id');
            $employee = M('order')->field('employee_id,employee_realname,employee_avatar,employee_tel')->where(['id'=>$order_id])->find();
            $employee['employee_avatar'] = C('attachment_url') . $employee['employee_avatar'];
            $bar['employee'][] = $employee;

            //获取作为信息
            $seat = M('order_seat')->field('seat_number,goods_seat_id,max_people,floor_price,set_price,total_people')->where(['order_id'=>$order_id])->find();
            $bar['seat'][] = $seat;

        }else{
            $bar['employee'] = [];
            $bar['seat'] = [];
        }

        $bar['average_star'] = empty($bar['average_star']) ? 0 : $bar['average_star'];
        $bar['have_time'] = $have_time;
        $bar['arrives_time'] = $bar['obegin_time'];
        unset($bar['pay_time']);
        return $bar;
    }

    /**
     * 获取他的派对
     * @param $member_id
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getMemberPart($member_id, $page, $pageSize)
    {
        $condition = [
            'api_bar.member_id' => $member_id,
            'api_bar.bar_type' => 2,
        ];
        $partLists = $this->field('api_bar.* ,api_merchant.title,api_merchant.address,api_merchant.begin_time,api_merchant.end_time,api_member.nickname,api_member.sex,api_member.is_auth,api_member.age,api_member.avatar,api_member.signature,api_comment_barstar.average_star')
            ->join('left join api_merchant ON api_merchant.id = api_bar.merchant_id')
            ->join('left join api_member ON api_member.id = api_bar.member_id')
            ->join('left join api_comment_barstar ON api_comment_barstar.member_id = api_bar.member_id')
            ->where($condition)->page($page, $pageSize)->order('api_bar.id desc')->select();


        foreach ($partLists as$key => $partList){
            if (!preg_match('/^(http|https)/ius', $partList['avatar'])) {
                $partLists[$key]['avatar'] = C('attachment_url') . $partList['avatar'];
            }
            $partLists[$key]['average_star'] = empty($partList['average_star']) ? 0 : $partList['average_star'];
            $partLists[$key]['age'] = Tools::calculateAge($partList['age']);
            $partLists[$key]['arrives_time'] = $partList['obegin_time'];
        }

        $data['list'] = $partLists;
        return $data;

    }

    /**
     * 获取订单对应的用户评论(订单对应的评论)
     * @param $order_id
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getOrderComment($bar_id, $page, $pageSize)
    {
        //循环查找订单对应的评价
        $comLists = M('comment_bar')->field('api_comment_bar.*,api_member.nickname,api_member.sex,api_member.avatar,api_member.age')
            ->join('left join api_member ON api_member.id = api_comment_bar.member_id')
            ->where(['bar_id' => $bar_id])->page($page, $pageSize)->select();

        //查找所有的标签
        $tag_star = M('comment_tags')->select();

        foreach ($comLists as $key => $list) {
            $tags = explode(',', $list['tag']);

            $tag_arr = [];
            foreach ($tags as $tag) {
                $tag_arr[] = $tag_star[(int)$tag - 1];
            }

            $ages = Tools::calculateAge($list['age']);
            $comLists[$key]['tag'] = $tag_arr;
            $comLists[$key]['age'] = $ages;

            if (!preg_match('/^(http|https)/ius', $list['avatar'])) {
                $comLists[$key]['avatar'] = C('attachment_url') . $list['avatar'];
            }
        }
        $data['list'] = $comLists;
        return $data;
    }


    /**
     * 获取他的评论(个人中心里面的他的评论)
     * @param $member_id
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getMemberComment($member_id, $page, $pageSize)
    {
        $comLists = M('comment_bar')->field('api_comment_bar.*,api_member.nickname,api_member.sex,api_member.avatar,api_member.age')
            ->join('left join api_member ON api_member.id = api_comment_bar.member_id')
            ->where(['bar_member_id' => $member_id])->page($page, $pageSize)->select();
        //查找所有的标签
        $tag_star = M('comment_tags')->field('tags')->select();

        foreach ($comLists as $key => $list) {
            $tags = explode(',', $list['tag']);

            $tag_arr = [];
            foreach ($tags as $tag) {
                $tag_arr[]= $tag_star[(int)$tag - 1]['tags'];
            }

            $ages = Tools::calculateAge($list['age']);
            $comLists[$key]['tag'] = implode(',',$tag_arr);
            $comLists[$key]['age'] = $ages;

            if (!preg_match('/^(http|https)/ius', $list['avatar'])) {
                $comLists[$key]['avatar'] = C('attachment_url') . $list['avatar'];
            }
        }
        $data['list'] = $comLists;
        return $data;
    }

    /**
     * 添加评论
     * @param $data
     * @return bool
     */
    public function addComment($data)
    {
        $comModel = M('comment_bar');
        $comStar = M('comment_barstar');

        //查看订单是否存在
        $order = M('bar')->where(['id' => $data['bar_id']])->find();
        if (empty($order)) {
            $this->error = '该订单不存在';
            return false;
        }

        //核实评论人的信息
        $ping = M('member')->where(['id' => $data['member_id']])->find();
        if (empty($ping)) {
            $this->error = '评论用户不存在';
            return false;
        }

        $this->startTrans();
        //开始添加数据
        $cdata = [
            'bar_id' => $data['bar_id'],
            'member_id' => $data['member_id'],
            'bar_member_id' => $order['member_id'],
            'star' => $data['star'],
            'tag' => $data['tag'],
            'created_time' => time(),
            'is_show' => $data['is_show'],
        ];
        $cid = $comModel->add($cdata);
        if ($cid === false) {
            $this->error = '评论记录添加成功';
            $this->rollback();
            return false;
        }

        //执行计算总分数
        $barstar = $comStar->where(['member_id' => $order['member_id']])->find();
        if (!$barstar) {
            $star = [
                'member_id' => $order['member_id'],
                'total_star' => $data['star'],
                'total_time' => 1,
                'average_star' => $data['star'],
            ];
            $starid = $comStar->add($star);
            if ($starid === false) {
                $this->error = '评论分数统计失败';
                $this->rollback();
                return false;
            }
        } else {
            $total_star = $data['star'] + $barstar['total_star'];
            $total_time = $barstar['total_time'] + 1;

            $average_star = $total_star / $total_time;
            $average_star = sprintf("%.2f", substr(sprintf("%.3f", $average_star), 0, -2));

            $sdata = ['total_star' => $total_star, 'total_time' => $total_time, 'average_star' => $average_star];

            $sRes = $comStar->where(['member_id' => $order['member_id']])->save($sdata);
            if ($sRes === false) {
                $this->error = '评论分数记录失败';
                $this->rollback();
                return false;
            }
        }

        //更新拼吧用户表中的 is_evaluate = 1
        $mrs = M('bar_member')->where(['bar_id' => $data['bar_id'], 'member_id' => $data['member_id']])->save(['is_evaluate' => 1]);
        if ($mrs === false) {
            $this->error = '更新拼吧用户表失败';
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }


    /**
     * @param $bar_id int 主拼吧ID
     * @return mixed
     */
    public function getBarWineList($bar_id, $member_id, $page, $pageSize)
    {
        $where = [
            'top_bar_id' => $bar_id,
            'is_xu' => 1,
            'api_bar_member.member_id' => $member_id
        ];
        $wines = $this->field('api_bar_member.bar_id ,api_bar.total_price,api_bar.pay_price,api_bar.average_cost,api_bar.created_time,api_bar.description,api_bar.bar_status,api_bar_member.pay_type,api_bar_member.pay_status,api_bar_member.id as order_id')
            ->join('left join api_bar_member ON api_bar_member.bar_id = api_bar.id')
            ->where($where)
            ->page($page, $pageSize)
            ->order('bar_id desc')
            ->select();

        $count =$this->join('left join api_bar_member ON api_bar_member.bar_id = api_bar.id')->where($where)->count();

        foreach ($wines as $key => $wine) {
            $wines[$key]['buy_type'] = 4;
            //获取商品信息
            $goods_info = M('bar_pack')->field('goods_pack_id,title,amount,price,image,pack_description,market_price,goods_type')->where(['bar_id' => $wine['bar_id']])->select();
            foreach ($goods_info as $k => $good) {
                $goods_info[$k]['image'] = C('attachment_url') . $good['image'];
            }

            //用户信息
            $member_info = M('bar_member')->field('member_id,pay_status,avatar')->where(['bar_id' => $wine['bar_id']])->select();

            foreach ($member_info as $ke => $item) {
                if (!preg_match('/^(http|https)/ius', $item['avatar'])) {
                    $member_info[$ke]['avatar'] = C('attachment_url') . $item['avatar'];
                }
            }
            //拼接每一个续酒的用户信息
            $wines[$key]['goods'] = $goods_info;
            $wines[$key]['member'] = $member_info;
            $have_time = $wine['created_time'] + C('ORDER_OVERTIME') - time();

            $wine[$key]['have_time'] = $have_time>0 ?$have_time:0;
        }

        $data['total'] = $count;
        $data['list'] = $wines;

        return $data;
    }


    /**
     * 完成拼吧
     * @param $bar_id  int
     */
    public function finishOneBar($bar_id)
    {
        $bar_type = M('bar')->where(['id' => $bar_id])->getField('bar_type');
    /*  if ($bar_type == 1) {
            //获取拼吧ID 对应的订单ID
            $order_id = M('bar_order')->join('left join api_order ON api_order.id = api_bar_order.order_id AND api_order.status = 7')->where(['bar_id' => $bar_id])->getField('order_id');
            if ($order_id == false) {
                $this->error = '拼吧对应订单不存在';
                return false;
            }
            $this->startTrans();
            //修改拼吧表中的订单状态 bar_status
            $bar_rs = $this->where(['id' => $bar_id])->save(['bar_status' => 4, 'updated_time' => time()]);
            if ($bar_rs === false) {
                $this->error = '修改拼吧状态失败';
                $this->rollback();
                return false;
            }
            //修改订单表中的订单状态 status
            $order_rs = M('order')->where(['id' => $order_id])->save(['status' => 4, 'updated_time' => time()]);
            if ($order_rs === false) {
                $this->error = '修改拼吧状态失败';
                $this->rollback();
                return false;
            }
            //删除对应商户端的员工消息
            $del_rs = M('message_employee')->where(['order_id' => $order_id])->delete();
            if ($del_rs === false) {
                $this->error = '删除相关员工消息失败';
                $this->rollback();
                return false;
            }
        }*/

        if($bar_type == 2){
            $bar_rs = $this->where(['id' => $bar_id])->save(['bar_status' => 4, 'updated_time' => time()]);
            if ($bar_rs === false) {
                $this->error = '修改拼吧状态失败';
                $this->rollback();
                return false;
            }

            //获取所有人参与人的信息
            $memberAll = M('bar_member')->field('member_id,pay_price,realname')
                ->where(['bar_id'=>$bar_id])->select();


            $record_name = '参与派对';

            foreach ($memberAll as $member) {
                //KB计算并写入积分兑换规则记录表
                $consumedata = M('member_capital')->where(['member_id' => $member['member_id']])->find();
                //将消费总额度写入会员消费记录表中
                $consumeres = M('member_capital')->where(['member_id' => $member['member_id']])->setInc('consume_money', $member['pay_price']);
                if ($consumeres === false) {
                    $this->error = '更新消费额度失败';
                    $this->rollback();
                    return false;
                }
                //根据用户的消费情况，获取KB,和提升会员等级
                $M_merber = M('member');
                //查找到用户表中对应的用户
                $mer_data = $M_merber->where(['id' => $member['member_id']])->find();
                // 积分计算规则 消费的总额*0.1
                $coin = $member['pay_price'] * C('COIN_RULE');
                $total_coin = $coin + $mer_data['coin'];

                if ($coin > 0) {
                    // 获取用户当前的消费总额
                    $total_free = $consumedata['consume_money'] + $member['pay_price'];
                    //获取当前会员等级对应的权益
                    $pril_data = $this->memberLevelData($mer_data['level'], $total_free, $total_coin);
                    //更新用户表
                    $res4 = $M_merber->where(['id' => $member['member_id']])->save($pril_data);
                    if ($res4 === false) {
                        $this->error = '更新会员权益失败' . $member['member_id'];
                        $this->rollback();
                        return false;
                    }

                    //写入KB记录表中 api_member_kcoin_record()
                    if(floor($coin)>0){
                        $kb_data = [
                            'member_id' => $member['member_id'],
                            'record_name' => $record_name,
                            'number' => $coin,
                            'type' => 1,
                            'before_number' => $mer_data['coin'],
                            'after_number' => $total_coin,
                            'created_time' => time(),
                        ];

                        $kb_record = M('member_kcoin_record')->add($kb_data);
                        if ($kb_record === false) {
                            $this->error = '更新会员KB记录失败' . $member['member_id'];
                            $this->rollback();
                            return false;
                        }
                    }

                    //用户推广收益增加
                    $reslut = $this->spreedSum($member['member_id'], $member['realname']);
                    if ($reslut === false) {
                        $this->error = '推广收益数据更新失败';
                        $this->rollback();
                        return false;
                    }
                }
            }
        }

        $this->commit();
        return true;
    }


    private function memberLevelData($level, $total_free, $total_coin)
    {
        $pril_model = M('member_privilege');
        //获取当前客户的等级对应的优惠
        $pr_data = $pril_model->where(['level' => $level])->find();

        //总K币
        $total_coin = $total_coin + $pr_data['coin'];
        //获取所有等级对应的优惠
        $pr_data1 = $pril_model->field('level,quota')->select();

        if ($total_free >= $pr_data1[0]['quota'] && $total_free < $pr_data1[1]['quota']) {
            $level = $pr_data1[0]['level'];
        } else if ($total_free >= $pr_data1[1]['quota'] && $total_free < $pr_data1[2]['quota']) {
            $level = $pr_data1[1]['level'];
        } else if ($total_free >= $pr_data1[2]['quota'] && $total_free < $pr_data1[3]['quota']) {
            $level = $pr_data1[2]['level'];
        } else if ($total_free >= $pr_data1[3]['quota'] && $total_free < $pr_data1[4]['quota']) {
            $level = $pr_data1[3]['level'];
        } else if ($total_free >= $pr_data1[4]['quota'] && $total_free < $pr_data1[5]['quota']) {
            $level = $pr_data1[4]['level'];
        } else {
            $level = $pr_data1[5]['level'];
        }
        $p_arr = ['coin' => $total_coin, 'level' => $level];

        return $p_arr;
    }

    /**
     * @param $member_id
     * @param $realname
     * @return bool
     */
    private function spreedSum($member_id, $realname)
    {
        $promoter_code = M('member')->field('promoter_code')->find($member_id);
        if ($promoter_code) {
            //根据推广码查询用户数据
            $prefix = substr($promoter_code['promoter_code'], 0, 1);
            $account_type = 1;
            switch ($prefix) {
                case 1: //用户端推广码
                    $account_type = 1;
                    break;
                case 2: //商户端推广码
                    $account_type = 2;
                    break;
            }

            $data = [
                'profit_time' => time(),
                'money' => C('PROMOTION_QUOTA'),
                'is_consume' => 1,
                'member_realname' => $realname
            ];

            //更新员工总收益
            $rs = M('spread_record')->where(['account_type' => $account_type, 'member_id' => $member_id, 'profit_time' => 0])->save($data);
            if ($rs === false) {
                $this->error = '推广增益记录更新失败';
                return false;
            }
        }

        return true;
    }


}