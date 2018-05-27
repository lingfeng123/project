<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Admin\Model;

use Think\Log;
use Think\Model;
use Think\Page;
use Think\Upload\Driver\Qiniu;

class MerchantModel extends Model
{

    public $tags = [1 => 'WiFi', 2 => '可刷卡', 3 => '沙发位', 4 => '无烟区', 5 => '可停车', 6 => '可吸烟', 7 => '包厢', 8 => '可存酒'];

    //自动验证
    protected $_validate = [
        ['title', '1,50', '名称不能为空且为1-50个字符', self::MUST_VALIDATE, 'length'],
        ['logo', 'require', 'logo不能为空', self::EXISTS_VALIDATE],
        ['description', '1,80', '商家简介不能为空且最多80个字', self::MUST_VALIDATE, 'length'],
        ['province', 'require', '省份不能为空', self::MUST_VALIDATE],
        ['city', 'require', '城市不能为空', self::MUST_VALIDATE],
        ['area', 'require', '行政区域不能为空', self::MUST_VALIDATE],
        ['address', 'require', '具体地址不能为空', self::MUST_VALIDATE],
        ['lng', 'require', '百度地图经度不能为空', self::MUST_VALIDATE],
        ['lat', 'require', '百度地图纬度不能为空', self::MUST_VALIDATE],
        ['status', '0,1,2', '商家状态选择不合法', self::MUST_VALIDATE, 'in'],
        ['begin_time', 'require', '营业开始时间必填', self::MUST_VALIDATE],
        ['end_time', 'require', '营业截止时间必填', self::MUST_VALIDATE],
        ['avg_consume', '/^[0-9]+(\.[0-9]{0,2})?$/', '平均消费不合法', self::MUST_VALIDATE, 'regex'],
    ];


    //添加商户数据
    public function addMerchant($list)
    {
        $list['created_time'] = time();

        $this->startTrans();
        $merchant_id = $this->add($list);
        $order_result = M('order_total')->add(['merchant_id' => $merchant_id]);

        if ($merchant_id  && $order_result) {
            $this->commit();
            return true;
        } else {
            $this->rollback();
            return false;
        }
    }

    /**
     * 获取商户数据
     */
    public function getMerchantData($page, $data)
    {
        //商户名称搜索词
        if (!empty($data['keywords'])) {
            $where['title'] = ['like', '%' . $data['keywords'] . '%'];
        }

        //筛选商户状态
        if (!($data['status'] == null) && in_array($data['status'], [0, 1, 2])) {
            $where['status'] = $data['status'];
        }

        //商户总数
        $pagesize = C('PAGE.PAGESIZE');
        $total = $this->where($where)->count();
        $list['list'] = $this->field('id,title,tel,status,begin_time,end_time,avg_consume,from_unixtime(created_time) as created_time')
            ->where($where)
            ->page($page, $pagesize)
            ->order('id desc')
            ->select();

        if ($total === false || $list === false) {
            return false;
        }

        $pages = new Page($total, $pagesize);
        $pages->setConfig('header', '共%TOTAL_ROW%条');
        $pages->setConfig('first', '首页');
        $pages->setConfig('last', '末页');
        $pages->setConfig('prev', '上一页');
        $pages->setConfig('next', '下一页');
        $pages->setConfig('theme', C('PAGE.THEME'));
        $list['pageHtml'] = $pages->show();

        return $list;
    }

    /**
     * 修改商户数据
     * @param $list
     * @return bool
     */
    public function updateMerchantData($list)
    {
        $tag_list = $list['tags'];
        $tags = [];
        foreach ($tag_list as $value) {
            $tags[] = $this->tags[$value];
        }

        $list['tags'] = implode('|', $tags);
        $list['absadd'] = bcadd($list['merchant_lng'], $list['merchant_lat'], 12);

        //查看原有图片
        $images = $this->field('logo,image,lng,lat')->where(['id' => $list['id']])->find();
        $update_merchant = $this->save($list);
        if ($update_merchant === false) {
            return false;
        }

        //删除骑牛上面的东西
        if ($images['logo'] != $list['logo']) {
            $imageArray = [0 => $images['logo']];
            $config = C('QINIU_CONFIG');
            $qiniu = new Qiniu($config);
            $response_data = $qiniu->deleteFiles($imageArray);
            if ($response_data === false) {
                Log::write('delete merchant images for qiniu storage fail');
            }
        }

        //删除骑牛上面的东西
        if ($images['image'] != $list['image']) {
            $imageArray = explode('|', $images['image']);
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
     * 修改商户的每日库存
     * @param $merchant_id
     * @param $kapack_stock
     * @param $sanpack_stock
     * @return bool
     */
    public function updateMerchantPackStock($merchant_id, $kapack_stock, $sanpack_stock)
    {
        //查询修改之前商户的每日预设库存
        $pack_stock = $this->field('id, sanpack_stock, kapack_stock')->find($merchant_id);

        //修改商户表中每日库存数据
        $res = $this->where(['id' => $merchant_id])->save(['sanpack_stock' => $sanpack_stock, 'kapack_stock' => $kapack_stock]);
        if ($res === false) {
            $this->error = '修改商户每日库存失败';
            $this->rollback();
            return false;
        }

        return true;
    }

    /**
     * update sort
     * @param $merchant_id
     * @param $sort_number
     * @return bool
     */
    public function updateSort($merchant_id, $sort_number)
    {
        $rs = $this->where(['id' => $merchant_id])->save(['sort' => $sort_number]);
        if ($rs === false) {
            return false;
        }
        return true;
    }
}
