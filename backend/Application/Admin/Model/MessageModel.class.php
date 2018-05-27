<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/14
 * Time: 9:48
 */

namespace Admin\Model;


use Think\Model;
use Think\Page;

class MessageModel extends Model
{
    //开启批量验证
    protected $patchValidate = true;

    //自动验证
    protected $_validate = [
        ['title', '1,50', '名称不能为空,且为1-50个字符', self::EXISTS_VALIDATE, 'length'],
        ['content','require','消息内容不能为空']
    ];
    
    /**
     * @author jiangling
     * 系统消息列表
     * @param $params  传递参数
     * @return array   返回结果
     */
    public function getMessageList($params)
    {
        $page=$params['page']?$params['page']:1;
        $keyword=$params['key'];
        //拼接查询条件
        $condition=[];
        if($keyword){
            $condition['title | content']=array('like','%'.$keyword.'%');
        }

        $page_size= C('PAGE.PAGESIZE');
        //数据总条数
        $count=$this->where($condition)->count();
        //获取分页数据
        $data['msg_list']=$this->where($condition)->page($page,$page_size)->order('created_time desc')->select();

        if($data['msg_list'] ===false || $count ===false){
            return false;
        }
        //执行分页操作
        $pages = new Page($count, $page_size);
        $pages->setConfig('header', '共%TOTAL_ROW%条');
        $pages->setConfig('first', '首页');
        $pages->setConfig('last', '末页');
        $pages->setConfig('prev', '上一页');
        $pages->setConfig('next', '下一页');
        $pages->setConfig('theme', C('PAGE.THEME'));
        $data['pagehtml']=$pages->show();

        //返回结果
        return $data;
    }


    /**
     * @author jiangling
     * 消息写入数据表中
     */
    public function message_add()
    {
        $this->data['created_time']=time();

        //开启事wu
        $this->startTrans();
        //执行新增操作
        $id= $this->add();
       if(!$id){
           $this->rollback();
          $this->error='消息写入失败';
           return false;
       }
       //获取所有员工的id；
        $employee_ids=M('employee')->getField('id',true);
       $data=[];
       //写入系统表中
        foreach ($employee_ids as $employee_id){
            $data[]=[
                'employee_id'=>$employee_id,
                'message_id' =>$id,
                'is_read'    =>0
            ];
        }
        //执行批量写入数据
        $res=M('message_empsystem')->addAll($data);
        if(!$res){
            $this->rollback();
            $this->error='消息写入失败';
            return false;
        }

        $this->commit();
        return $employee_ids;
    }

    /**
     * @author jiangling
     * 编辑消息
     * @param $id   int 消息ID
     * @return bool  返回结果
     */
    public function message_edit($id)
    {
        //执行修改操作
        if($this->where(['id'=>$id])->save() ===false ){
            $this->error='消息编辑失败';
            return false;
        };
        return true;
    }

    /**
     * @author jiangling
     * 消息删除,同时需要删除系统-员工消息表中的关联数据
     * @param $msg_id   int  消息ID
     * @return bool  返回结果
     */
    public function message_delete($msg_id)
    {
        //开启事物
        $this->startTrans();
        //删除系统消息
        $res1=$this->where(['id'=>$msg_id])->delete();
        if($res1 ===false){
            $this->error='系统消息删除失败';
            $this->rollback();
            return false;
        }
        //删除员工系统消息关联表中的数据
        $res2=M('message_empsystem')->where(['message_id'=>$msg_id])->delete();
        if($res2 ===false ){
            $this->error='系统消息删除失败';
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }



}