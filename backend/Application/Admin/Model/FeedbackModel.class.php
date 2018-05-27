<?php
/**
 * FileName: FeedbackModel.class.php
 * User: Comos
 * Date: 2018/2/26 17:25
 */

namespace Admin\Model;


use Think\Model;
use Think\Page;

class FeedbackModel extends Model
{
    /**
     *
     */
    public function getFeedList($where, $page)
    {
        $pagesize = C('PAGE.PAGESIZE');
        $count = $this->where($where)->count();
        $data['list'] = $this->where($where)->page($page, $pagesize)->order('status, id desc')->select();
        if ($data['list'] === false || $count === false ){
            return false;
        }

        //获取分页
        $pages = new Page($count, $pagesize);
        $pages->setConfig('header', '共%TOTAL_ROW%条');
        $pages->setConfig('first', '首页');
        $pages->setConfig('last', '末页');
        $pages->setConfig('prev', '上一页');
        $pages->setConfig('next', '下一页');
        $pages->setConfig('theme', C('PAGE.THEME'));
        $data['pageHtml'] = $pages->show();

        return $data;
    }

    /**
     * 修改反馈状态
     * @param $id int 反馈记录ID
     */
    public function updateFeedStatus($id)
    {
        //获取反馈记录
        $data = $this->find($id);
        if (!$data){
            return false;
        }

        $status = $data['status'] == 0 ? 1 : 0;
        if ($this->save(['id' => $id, 'status' => $status]) === false){
            return false;
        }

        return true;
    }

}