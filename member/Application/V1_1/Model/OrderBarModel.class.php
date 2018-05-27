<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/12
 * Time: 17:55
 */

namespace V1_1\Model;


use Org\Util\Response;
use Think\Model;

class OrderBarModel extends Model
{

    /**
     * 获取订单对应的用户评论(订单对应的评论)
     * @param $order_id
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getOrderComment($order_id,$page,$pageSize)
    {
        //循环查找订单对应的评价
        $comLists=M('comment_bar')->field('api_comment_bar.*,api_member.nickname,api_member.sex,api_member.avatar,api_member.age')
            ->join('left join api_member ON api_member.id = api_comment_bar.member_id')
            ->where(['order_id'=>$order_id])->page($page,$pageSize)->select();
        $tag_star=C('STAR_TAG');
        $tag_arr=[];

        foreach ($comLists as $key=> $list){
            $tags=explode(',',$list['tag']);
            foreach ($tags as $tag){
                 $tag_arr[]=$tag_star[$tag];
            }
            $comLists[$key]['tag']=$tag_arr;
        }

        $data['list']=$comLists;
        return $data;
    }


    /**
     * 获取他的评论(个人中心里面的他的评论)
     * @param $member_id
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getMemberComment($member_id,$page,$pageSize)
    {
        $comLists=M('comment_bar')->field('api_comment_bar.*,api_member.nickname,api_member.sex,api_member.avatar,api_member.age')
            ->join('left join api_member ON api_member.id = api_comment_bar.member_id')
            ->where(['member_id'=>$member_id])->page($page,$pageSize)->select();
        $tag_star=C('STAR_TAG');
        $tag_string='';
        foreach ($comLists as $key=> $list){
            $tags=explode(',',$list['tag']);
            foreach ($tags as $tag){
                $tag_string.='，'.$tag_star[$tag];
            }
            $comLists[$key]['tag']=$tag_string;
        }
        $data['list']=$comLists;
        return $data;
    }

    /**
     * 添加评论
     * @param $data
     * @return bool
     */
    public function addComment($data)
    {
        $comModel=M('comment_bar');
        $comStar=M('comment_barstar');

        //查看订单是否存在
        $order=M('order')->where(['id'=>$data['order_id']])->find();
        if(empty($order)){
            $this->error='该订单不存在';
            return false;
        }

        //核实评论人的信息
        $ping=M('member')->where(['id'=>$data['member_id']])->find();
        if(empty($ping)){
            $this->error='评论用户不存在';
            return false;
        }

        $this->startTrans();
        //开始添加数据
        $cdata=[
            'order_id'=>$data['order_id'],
            'member_id'=>$data['member_id'],
            'bar_member_id'=>$order['member_id'],
            'star'=>$data['star'],
            'tag'=>$data['tag'],
            'created_time'=>time(),
            'is_show'=>$data['is_show'],
        ];
        $cid=$comModel->add($cdata);
        if($cid===false){
            $this->error='评论记录添加成功';
            $this->rollback();
            return false;
        }

        //执行计算总分数
        $barstar=$comStar->where(['member_id'=>$order['member_id']])->find();

        $total_star=$data['star']+$barstar['total_star'];
        $total_time=$barstar['total_time']+1;

        $average_star=$total_star/$total_time;
        $average_star=sprintf("%.2f",substr(sprintf("%.3f", $average_star), 0, -2));

        $sdata=['total_star'=>$total_star,'total_time'=>$total_time,'average_star'=>$average_star];

        $sRes=$comStar->where(['member_id'=>$order['member_id']])->save($sdata);
        if($sRes===false){
            $this->error='评论分数记录失败';
            $this->rollback();
            return false;
        }
        $this->commit();
        return true;

    }


}