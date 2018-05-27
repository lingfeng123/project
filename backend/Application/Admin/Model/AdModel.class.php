<?php
/**
 * FileName: AdModel.class.php
 * User: Comos
 * Date: 2017/8/21 13:40
 */

namespace Admin\Model;


use Think\Model;
use Think\Page;

class AdModel extends Model
{

    //开启批量验证
    protected $patchValidate = true;

    //自动验证
    protected $_validate = [
        ['title', '1,50', '名称不能为空且为1-50个字符', self::EXISTS_VALIDATE, 'length'],
        ['flag', 'require', '广告名称不能为空'],
        ['flag', 'verifyFlag', '广告位标识只能是英文', self::EXISTS_VALIDATE, 'callback'],
        ['type', '1,2', '广告类型选择不正确', self::MUST_VALIDATE, 'in'],
        ['status', '0,1', '广告状态选择不正确', self::MUST_VALIDATE, 'in'],
        ['start_time', 'require', '广告开始时间不合法', self::MUST_VALIDATE],
        ['end_time', 'require', '广告结束时间不合法', self::MUST_VALIDATE],
        ['sort', 'number', '排序只能为数字', self::MUST_VALIDATE],
        ['price', 'verifyPrice', '价格只能是数字', self::MUST_VALIDATE, 'callback']
    ];

    //数据自动完成
    protected $_auto = [
        ['created_time','time',self::MODEL_INSERT,'function'],
        ['updated_time','time',self::MODEL_BOTH,'function']
    ];

    /**
     * 验证广告位标识是否合法
     * @param $flag 标识
     * @return bool 返回值 true/false
     */
    public function verifyFlag($flag)
    {
        if (!$rs = preg_match('/[a-z_]/i', $flag)) {
            return false;
        }
    }

    /**
     * 验证金额输入合法性
     * @param $accountPrice
     */
    public function verifyPrice($accountPrice){
        if (preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $accountPrice)) {
            return true;
        }
        return false;
    }

    /**
     * 新增广告数据
     * @return bool
     */
    public function insertAdData()
    {
        //验证广告开始时间是否大于结束时间
        $rs = $this->verifyTime($this->data['start_time'], $this->data['end_time']);
        if (!$rs) return false;
        //添加广告数据到数据库
        if ($id= $this->add() === false) {
            $this->error = '添加广告失败';
            return false;
        }
        return true;
    }

    /**
     * 修改广告数据
     */
    public function saveAdData(){
        //验证广告开始时间是否大于结束时间
        $rs = $this->verifyTime($this->data['start_time'], $this->data['end_time']);
        if (!$rs) return false;
        if ($this->save() === false) {
            $this->error = '广告修改失败';
            return false;
        }
        return true;
    }

    /**
     * 获取转换后的开始和结束时间
     * @param $start_time   开始时间
     * @param int $end_time 结束时间
     * @return array|bool   成功返回数组,失败返回false
     */
    private function verifyTime($start_time, $end_time)
    {
        //将start_time转换成时间戳
        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);
        //转换时间格式后保存数据
        $this->data['start_time'] = $start_time;
        $this->data['end_time'] = $end_time;
        //判断结束时间是否小于开始时间
        $time = $end_time - $start_time;
        if ($time < 0) {
            $this->error = '广告投放开始时间不能大于结束时间';
            return false;
        }
        return true;
    }

    /**
     * 获取广告列表数据
     * @return mixed
     */
    public function getAdList($cond){
        //数据总数
        $total = $this->count();
        //查询需要的数据
        $data = $this->where($cond)->order("id desc")->page(I('get.p'),C('PAGE.PAGESIZE'))->select();
        //分页
        $pages = new Page($total,C('PAGE.PAGESIZE'));
        //设置分页工具条的样式
        $pages -> setConfig('header','共%TOTAL_ROW%条');
        $pages -> setConfig('first','首页');
        $pages -> setConfig('last','末页');
        $pages -> setConfig('prev','上一页');
        $pages -> setConfig('next','下一页');
        $pages -> setConfig('theme',C('PAGE.THEME'));
        $pageHtml = $pages->show();
        $arr['pages'] = $pageHtml;
        //如果查询失败,输出错误信息
        if(!$data){
            $this->error = '数据查询失败';
        }
        //获取数据
        $arr['rows'] = $data;
        //返回结果
        return $arr;
    }


}