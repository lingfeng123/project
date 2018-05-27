<?php
/**
 * FileName: ActivityMainModel.class.php
 * User: Comos
 * Date: 2018/1/9 10:00
 */

namespace Admin\Model;


use Think\Model;
use Think\Page;

class ActivityMainModel extends Model
{

    //自动验证
    protected $_validate = [
        ['title', '1,100', '活动名称最多100个字符', self::MUST_VALIDATE, 'length'],
        ['description', 'require', '活动描述不能为空', self::MUST_VALIDATE],
        ['start_time', 'require', '开始时间不能为空', self::MUST_VALIDATE],
        ['end_time', 'require', '结束时间不能为空', self::MUST_VALIDATE],
        ['status', '1,2', '活动状态不合法', self::MUST_VALIDATE, 'in'],
    ];

    //数据自动完成
    protected $_auto = [
        ['created_time', 'time', self::MODEL_INSERT, 'function'],
    ];

    /**
     * 获取活动列表
     * @param int $p 页码
     */
    public function getActivityList($p)
    {
        $pagesize = I('PAGE.PAGESIZE');

        //数据总数
        $count = $this->count();
        $data['list'] = $this->page($p, $pagesize)->order('id desc')->select();

        //判断sql执行结果
        if ($count === false || $data['list'] === false) {
            return false;
        }

        //获取分页
        $data['pageHtml'] = $this->_getPage($count, $pagesize);

        //返回数据
        return $data;
    }

    /**
     * 添加活动信息
     */
    public function insertNewData()
    {
        //得到数据
        $data = $this->data;
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);

        //添加数据
        $res = $this->add($data);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * 修改活动数据
     */
    public function updateOldData()
    {
        //得到数据
        $data = $this->data;
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);

        //添加数据
        $res = $this->save($data);
        if ($res === false) {
            return false;
        }

        return true;
    }

    /**
     * 获取中奖信息列表
     * @param $id
     */
    public function getWinRecord($id, $p)
    {
        $pagesize = C('PAGE.PAGESIZE');
        $count = M('activity_lottery')->where(['activity_id' => $id])->count();
        $data['list'] = M('activity_lottery')->where(['activity_id' => $id])->page($p, $pagesize)->order('id asc')->select();
        if ($count === false || $data['list'] === false) {
            return false;
        }

        //获取分页
        $data['pageHtml'] = $this->_getPage($count, $pagesize);

        return $data;
    }

    private function _getPage($count, $pagesize)
    {
        //获取分页
        $pages = new Page($count, $pagesize);
        $pages->setConfig('header', '共%TOTAL_ROW%条');
        $pages->setConfig('first', '首页');
        $pages->setConfig('last', '末页');
        $pages->setConfig('prev', '上一页');
        $pages->setConfig('next', '下一页');
        $pages->setConfig('theme', C('PAGE.THEME'));
        return $pages->show();
    }
}