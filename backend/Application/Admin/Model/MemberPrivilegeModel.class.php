<?php
/**
 * FileName: MemberPrivilegeModel.class.php
 * User: Comos
 * Date: 2017/8/24 9:11
 */

namespace Admin\Model;


use Think\Model;

class MemberPrivilegeModel extends Model
{

    //开启批量验证
    //protected $patchValidate = true;

    //自动验证
    protected $_validate = [
        //[验证字段1,验证规则,错误提示,验证条件,附加规则,验证时间]
        ['level', 'number', '会员等级只能是数字且唯一', self::EXISTS_VALIDATE, 'unique', self::MODEL_INSERT],
        ['title', '1,5', '会员等级名称长度为1-5个字符且唯一', self::EXISTS_VALIDATE, 'unique', self::MODEL_INSERT],
        ['overdue', 'number', '逾期次数只能是数字'],
        ['delayed', 'number', '卡套延期天数只能是数字'],
        ['birthday', '0,1', '生日特权不能为空且只能是规定值', self::EXISTS_VALIDATE, 'in'],
        ['coin', 'number', '赠送K币只能是数字'],
        ['free_seat', '0,1', '是否免预定金不能为空且只能是规定值', self::EXISTS_VALIDATE, 'in'],
        ['quota', 'number', '累计消费额度只能是数字'],
    ];


    /**
     * 添加新数据
     */
    public function insertVipData(){
        $data = $this->data;
        $res = $this->add($data);
        return $res;
    }


    /**
     * 根据主键修改指定数据
     */
    public function updateData(){
        $data = $this->data;
        $res = $this->where(['id'=>$data['id']])->save($data);
        if ($res === false){
            return false;
        }
        return true;
    }


    /**
     * 根据ID获取特权记录
     * @param $id
     */
    public function getVipInfoById($id){
        return $id = $this->find($id);
    }
}