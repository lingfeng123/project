<?php
/**
 * FileName: GoodsPackController.class.php
 * User: Comos
 * Date: 2017/8/24 14:27
 */

namespace Admin\Controller;


class GoodsPackController extends BaseController
{

    private $_model;

    public function _initialize()
    {
        parent::_initialize();
        $this->_model = D('GoodsPack');
    }

    /**
     * 商品列表
     * @param $merchant_id
     * @param int $p
     */
    public function index($merchant_id, $p = 1)
    {
        $data = I('get.', '');
        $lists = $this->_model->getGoodsPackList($data, $merchant_id, $p);
        if (!$lists) {
            $this->error('获取套餐列表失败');
        }

        //获取商户ID与名称
        $merchant_name = D('merchant')->where(['id' => $merchant_id])->getField('title');
        $this->assign('merchant_name', $merchant_name);
        $this->assign('lists', $lists['lists']);
        $this->assign('merchant_id', $merchant_id);
        $this->assign('pageHtml', $lists['pageHtml']);
        $this->display();
    }

    /**
     * 添加套餐商品信息
     * @param $merchant_id   int 商户ID
     */
    public function add($merchant_id)
    {
        if (IS_POST) {
            //收集数据
            if ($this->_model->create() === false) {
                $this->error(get_error($this->_model));
            }

            //新增数据
            if ($this->_model->addGoodsPack() === false) {
                $this->error(get_error($this->_model));
            }
            $this->success('添加套餐成功');
        } else {
            //获取商户ID
            $detail = ['merchant_id' => $merchant_id];
            $this->assign('attachment_url', C('attachment_url'));
            $this->assign('detail', $detail);
            $this->display();
        }
    }

    /**
     * 修改套餐信息
     * @param int $id
     */
    public function edit($id)
    {
        //获取套餐信息
        $detail = $this->_model->find($id);
        if (!$detail) {
            $this->error('指定套餐未找到');
        }

        if (IS_POST) {
            //收集数据
            if ($this->_model->create() === false) {
                $this->error(get_error($this->_model), U('edit', ['id' => $id, 'merchant_id' => $detail['merchant_id']]));
            }
            //新增数据
            if ($this->_model->updateGoodsPack() === false) {
                $this->error(get_error($this->_model), U('edit', ['id' => $id, 'merchant_id' => $detail['merchant_id']]));
            }
            $this->success('修改套餐成功', U('index', ['merchant_id' => $detail['merchant_id']]));
        } else {

            $this->assign('detail', $detail);
            $this->assign('attachment_url', C('attachment_url'));
            $this->display('add');
        }
    }


    /**
     * 删除套餐
     * @param $id   套餐ID
     */
    public function delete($id, $merchant_id)
    {
        if (!$id) {
            $this->error('参数不合法', U('index'));
        }
        //删除套餐
        if (!$rs = $this->_model->delete($id)) {
            $this->error('删除套餐失败');
        }
        $this->success('删除套餐成功', U('index', ['merchant_id' => $merchant_id]));
    }

    /**
     * 获取套餐详情
     * @param $id
     */
    public function detail($id)
    {
        $detail = $this->_model->getGoodsPackDetailById($id);
        if (!$detail) {
            $this->error('指定套餐不存在或查询失败');
        }
        $this->assign('detail', $detail);
        $this->assign('attachment_url', C('attachment_url'));
        $this->display();
    }
}