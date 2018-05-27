<?php
/**
 * FileName: GoodsPackModel.php
 * User: Comos
 * Date: 2017/8/25 8:55
 */

namespace Admin\Model;


use Think\Log;
use Think\Model;
use Think\Page;
use Think\Upload\Driver\Qiniu;

class GoodsPackModel extends Model
{

    //开启批量验证
    protected $patchValidate = true;

    //数据合法验证
    protected $_validate = [
        //[验证字段1,验证规则,错误提示,验证条件,附加规则,验证时间]
        ['id', 'number', '商品ID不能为空且只能为数字', self::EXISTS_VALIDATE],
        ['merchant_id', 'verfiyMerchantId', '指定商户不存在', self::MUST_VALIDATE, 'callback'],
        ['type', '1,2,3', '商品类型选择不正确', self::MUST_VALIDATE, 'in'],
        ['title', '1,15', '商品名称最多15个字', self::MUST_VALIDATE, 'length'],
        ['image', 'require', '商品图片不能为空', self::MUST_VALIDATE],
        ['price', 'verifyPrice', '线上售价填写不正确', self::MUST_VALIDATE, 'callback'],
        ['market_price', 'verifyPrice', '市场售价填写不正确', self::MUST_VALIDATE, 'callback'],
        ['stock', 'number', '库存不能为空且只能为数字', self::MUST_VALIDATE],
        ['status', '0,1', '商品状态选择不正确', self::MUST_VALIDATE, 'in'],
        ['description', '1,120', '商品描述不能超过120个字符', self::MUST_VALIDATE,'length']
    ];

    //数据自动完成
    protected $_auto = [
        ['created_time', 'time', self::MODEL_INSERT, 'function']
    ];

    /**
     * 验证商户ID是否合法
     * @param $merchant_id
     */
    public function verfiyMerchantId($merchant_id)
    {
        if (!$merchant_id) {
            return false;
        }
        //检查是否存在指定商户
        $merchant = M('Merchant')->field('id')->find($merchant_id);
        if (!$merchant) {
            return false;
        }
        return true;
    }

    /**
     * 验证金额输入合法性
     * @param $accountPrice
     */
    public function verifyPrice($accountPrice)
    {
        if (preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $accountPrice)) {
            return true;
        }
        return false;
    }

    /**
     * 新增套餐数据
     */
    public function addGoodsPack()
    {
        //接收数据
        $data = $this->data;
        //插入数据
        $id = $this->add($data);
        //判断插入数据结果
        if ($id === false) {
            $this->error = '添加套餐失败';
            return false;
        }
        return true;
    }

    /**
     * 修改套餐信息
     * @return bool
     */
    public function updateGoodsPack()
    {
        //接收数据
        $data = $this->data;
        //获取图片原有信息
        $image=$this->where(['id' => $data['id']])->getField('image');

        $rs = $this->where(['id' => $data['id']])->save($data);
        //判断插入数据结果
        if ($rs === false) {
            $this->error = '修改套餐失败';
            return false;
        }
        //删除骑牛上面的图片
        //判断旧照片是否存在,准备执行删除文件操作
        if ($image != $data['image']) {
            //文件存在，则删除服务器中的文件
            $imageArray = [0=>$image];
            $config = C('QINIU_CONFIG');
            $qiniu = new Qiniu($config);
            $response_data = $qiniu->deleteFiles($imageArray);
            if ($response_data === false) {
                Log::write('delete merchant images for qiniu storage fail');
            }
        }
        return true;
    }

    /**
     * 获取套餐列表
     * @param $keyword
     * @param $merchant_id
     */
    public function getGoodsPackList($seachData, $merchant_id, $page)
    {
        //查询条件
        $where = [];
        if (!empty($seachData['keywords'])) {
            $where['api_goods_pack.title'] = ['like', '%' . $seachData['keywords'] . '%'];
        }
        if (!empty($seachData['type'])) {
            $where['api_goods_pack.type'] = ['eq', $seachData['type']];
        }
        if (in_array($seachData['status'], [0, 1]) && $seachData['status'] != '') {
            $where['api_goods_pack.status'] = ['eq', $seachData['status']];
        }

        $where['api_goods_pack.merchant_id'] = $merchant_id;

        $pagesize = C('PAGE.PAGESIZE');
        $count = $this->join("__MERCHANT__ ON __MERCHANT__.id = __GOODS_PACK__.merchant_id")->where($where)->count();

        $data['lists'] = $this->field("api_merchant.title as merchant_title, api_goods_pack.*")
            ->join("__MERCHANT__ ON __MERCHANT__.id = __GOODS_PACK__.merchant_id")
            ->where($where)
            ->page($page, $pagesize)
            ->order('id desc')
            ->select();

        $pages = new Page($count, $pagesize);
        $pages->setConfig('header', '共%TOTAL_ROW%条');
        $pages->setConfig('first', '首页');
        $pages->setConfig('last', '末页');
        $pages->setConfig('prev', '上一页');
        $pages->setConfig('next', '下一页');
        $pages->setConfig('theme', C('PAGE.THEME'));
        $data['pageHtml'] = $pages->show();

        if ($data === false) {
            return false;
        }

        return $data;
    }

    /**
     * 根据ID获取套餐详情
     * @param $id
     */
    public function getGoodsPackDetailById($id)
    {
        $detail = $this->field("api_merchant.title as merchant_title, api_goods_pack.*")
            ->join("__MERCHANT__ ON __MERCHANT__.id = __GOODS_PACK__.merchant_id")
            ->where(['api_goods_pack.id' => $id])
            ->find();

        return $detail;
    }
}