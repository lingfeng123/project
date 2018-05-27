<?php


namespace Admin\Controller;

use Org\Util\Tools;
use Think\Page;
use Think\Upload;

class MerchantController extends BaseController
{
    private $_model;

    public function _initialize()
    {
        parent::_initialize();
        $this->_model = D('Merchant');
    }

    /**
     * 添加商户
     */
    public function add()
    {
        if (IS_POST) {
            $list = I('post.');

            if ($this->_model->create($list) === false) {
                $this->error($this->_model->getError());
            }

            //添加数据到数据库
            $res = $this->_model->addMerchant($list);
            if (!$res) {
                $this->error('添加商户数据失败');
            }

            $this->success('添加商户成功', U('index'));
        } else {
            $this->assign('attachment_url', C('attachment_url'));
            $this->display('add');
        }
    }

    /**
     * 商户列表
     * @param int $p 分页ID
     */
    public function index($p = 1)
    {
        $data = I('get.', '');
        $data = $this->_model->getMerchantData($p, $data);
        if ($data === false) {
            $this->error('获取商户列表失败');
        }

        $this->assign('merchant_type', C('COUPON_MERCHANT_TYPE'));
        $this->assign('lists', $data['list']);
        $this->assign('pageHtml', $data['pageHtml']);
        $this->display();
    }

    /**
     * 根据商户ID获取商户详情
     * @param $id
     */
    public function detail($id)
    {
        if (!is_numeric($id)) $this->error('商户ID参数不合法');

        $merchant = $this->_model->find($id);
        if (!$merchant) $this->error('获取商户失败');

        $merchant['image'] = explode('|', $merchant['image']);
        $merchant['address'] = $merchant['province'] . $merchant['city'] . $merchant['area'] . $merchant['address'];
        $merchant['tags'] = explode('|', $merchant['tags']);
        $merchant['created_time'] = date('Y-m-d H:i', $merchant['created_time']);


        $this->assign('detail', $merchant);
        $this->assign('attachment_url', C('attachment_url'));
        $this->display();
    }

    /**
     * 商户相册图片上传
     * @param $dir string 商户图片保存根目录
     */
    public function upload($dir)
    {
        $upload = new Upload(); // 实例化上传类
        $upload->maxSize = 3145728; // 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
        $upload->rootPath = Tools::attachment_path(); // 设置附件上传根目录
        $upload->savePath = '/' . $dir . '/'; // 设置附件上传（子）目录
        $upload->saveName = array('get_filename', '');
        $upload->driver = 'Qiniu'; // 文件上传驱动本地上传不用填写 Qiniu
        $upload->driverConfig = C("QINIU_CONFIG");

        // 上传文件 
        $info = $upload->upload();
        $value = '';
        foreach ($info as $k) {
            //骑牛云存储直接返回地址并分割保留文件名
            if ($upload->driver == 'Qiniu') {
                $filePath = parse_url($k['url']);
                $filePath = $filePath['path'];
                if (empty($value)) {
                    $value = $value . $filePath;
                } else {
                    $value = $value . "|" . $filePath;
                }
            }
        }
        $data['code'] = 1;
        $data['msg'] = $value;
        if (!$info) {
            // 上传错误提示错误信息
            $this->error($upload->getError());
        } else {
            // 上传成功
            $this->ajaxReturn($data);
        }
    }


    /**
     * 修改商户数据
     * @param $id
     */
    public function edit($id)
    {
        if (IS_POST) {
            $data = I('post.');

            if ($this->_model->create($data) === false) {
                $this->error($this->_model->getError());
            }

            $result = $this->_model->updateMerchantData($data);
            if ($result === false) {
                $this->error('修改商户数据失败');
            }

            $this->success('修改商户成功', U('index'));

        } else {
            //查询商户数据
            $detail = $this->_model->where(['id' => $id])->find();

            //格式化数据
            $detail['image_view'] = explode('|', $detail['image']);
            foreach ($detail['image_view'] as $k => $v) {
                $detail['image_view'][$k] = C('attachment_url') . $v;
            }

            $detail['gps_address'] = $detail['province'] . $detail['city'] . $detail['area'] . $detail['address'];
            $detail['tags'] = explode('|', $detail['tags']);

            $index = [];
            $tags = $this->_model->tags;
            foreach ($tags as $key => $v) {
                foreach ($detail['tags'] as $s) {
                    if ($v == $s) {
                        $index[] = $key;
                    }
                }
            }

            $detail['tags'] = json_encode($index);

            $this->assign('detail', $detail);
            $this->assign('attachment_url', C('attachment_url'));
            $this->display('add');
        }
    }

    /**
     * 设置商户每日库存
     * @param $kapack_stock int 卡座套餐库存
     * @param $sanpack_stock int 优惠套餐库存
     */
    public function setStock($merchant_id)
    {
        $merchantModel = D('merchant');
        if (IS_POST) {
            $kapack_stock = I('post.kapack_stock', 0);
            $sanpack_stock = I('post.sanpack_stock', 0);
            if (!is_numeric($sanpack_stock) || !is_numeric($kapack_stock)) {
                $this->error('库存必须是数字');
            }

            //修改商户每日库存
            $res = $merchantModel->updateMerchantPackStock($merchant_id, $kapack_stock, $sanpack_stock);
            if ($res === false) {
                $this->error($merchantModel->getError());
            }

            //成功提醒
            $this->success('修改每日库存成功');
        } else {
            //获取商户每日库存
            $pack_stock = $merchantModel->field('id, sanpack_stock, kapack_stock,san_wine_stock')->find($merchant_id);
            if (!$pack_stock) {
                $this->error('获取库存失败');
            }
            $this->assign('detail', $pack_stock);
            $this->display('stock');
        }

    }


    /////////////////////////////////******************************商户用户评论***************************////////////////////////////////////

    /**
     * @author jiangling
     * 商家用户评论表
     * @param  $id int 商户ID
     * @param  $p  int 当前页码
     * @param  $page_size  int 当前页码大小
     */
    public function comment($id, $p = 1)
    {
        $page = $p;
        $page_size = I('post.page_size', 10);
        $commentModel = M('comment_merchant');
        //获取所有评论,关联用户表，商户表，获取相关的商户信息，用户信息
        $commentData = $commentModel->alias('a')
            ->field('a.*,b.nickname,b.avatar,c.title,c.logo')
            ->join('left join api_member b ON b.id=a.member_id')
            ->join('left join api_merchant c ON c.id=a.merchant_id')
            ->where(['a.merchant_id' => $id])
            ->order('a.created_time desc')->page($page, $page_size)->select();

        //获取商户评论总数
        $count = $commentModel->where(['merchant_id' => $id])->count();
        $attrurl = C('ATTACHMENT_URL');

        //执行分页操作
        $pages = new Page($count, $page_size);
        $pages->setConfig('header', '共%TOTAL_ROW%条');
        $pages->setConfig('first', '首页');
        $pages->setConfig('last', '末页');
        $pages->setConfig('prev', '上一页');
        $pages->setConfig('next', '下一页');
        $pages->setConfig('theme', C('PAGE.THEME'));
        $pageHtml = $pages->show();

        $this->assign('attrurl', $attrurl);
        $this->assign('pageHtml', $pageHtml);
        $this->assign('comment', $commentData);
        $this->display();
    }

    /**
     * @author jiangling
     * 封禁商户
     * @param $id int 商户id
     */
    public function del($id)
    {
        $Model = M('merchant');
        $mrs = $Model->field('status')->where(['id' => $id])->find();
        if (!$mrs) {
            $this->error('商户不存在');
        }

        $status = 2;
        if ($mrs['status'] == 2){
            $status = 0;
        }
        $M_rs = $Model->where(['id' => $id])->save(['status' => $status]);
        if (!$M_rs) {
            $this->error('更新状态失败');
        }

        $this->success('更新状态成功');
    }

}
