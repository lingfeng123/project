<?php
/**
 * Created by PhpStorm.
 * User: nano
 * Date: 2017/10/24 0024
 * Time: 22:49
 */

namespace Admin\Model;


use Think\Model;
use Think\Page;

class VersionModel extends Model
{
    //自动验证
    protected $_validate = [
        ['version', '1,10', '版本号不能为空且长度为1-10字符', self::EXISTS_VALIDATE, 'length'],
        ['url', 'require', '连接地址不能为空'],
        ['client', 'ios,android', '终端选择不正确', self::MUST_VALIDATE, 'in'],
        ['is_force', '0,1', '是否强制更新选择不正确', self::MUST_VALIDATE, 'in'],
        ['content', 'require', '版本更新内容不能为空', self::MUST_VALIDATE],
        ['updated_time', 'require', '版本更新时间不能为空', self::MUST_VALIDATE],
        ['version_code', 'number', '版本code不能为且为数字', self::MUST_VALIDATE],
    ];

    /**
     * 获取版本列表数据
     * @param $p int 当前页码
     */
    public function getAppVersionList($page, $where)
    {
        $pagesize = C('PAGE.PAGESIZE');

        //统计数据总数
        $count = $this->where($where)->count();
        //获取版本列表数据
        $data['list'] = $this->where($where)->page($page, $pagesize)->order('id desc')->select();
        if ($data['list'] === false || $count === false) {
            return false;
        }

        //获取分页数据
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
     * 添加新应用版本
     */
    public function insertVersionData()
    {
        $data = $this->data;
        $data['updated_time'] = strtotime($data['updated_time']);
        return $this->add($data);
    }

    /**
     * 添加新应用版本
     */
    public function updateVersionData()
    {
        $data = $this->data;
        $data['updated_time'] = strtotime($data['updated_time']);
        return $this->save($data);
    }
}