<?php
/**
 * FileName: AdController.class.php
 * User: Comos
 * Date: 2017/8/22 9:12
 */

namespace V1_1\Controller;


use Org\Util\Response;
use Org\Util\ReturnCode;

class AdController extends BaseController
{

    /**
     * 获取广告列表
     * @param $client string 客户端标识
     * @param $flag string 广告位标识
     * @param $type int 广告类型 1图片 2文字
     * @param $size int 广告显示数量
     */
    public function advert()
    {
        $postData = I('post.');

        //接收数据
        $flag = isset($postData['flag']) ? $postData['flag'] : '';
        $type = isset($postData['type']) ? $postData['type'] : '';
        $size = isset($postData['size']) ? $postData['size'] : '';

        //验证数据
        if (empty($flag)) Response::error(ReturnCode::INVALID_REQUEST, '请求数据非法');
        if (empty($size) || !is_numeric($type)) {
            Response::error(ReturnCode::INVALID_REQUEST, '请求数据非法');
        }
        if (empty($type) || !is_numeric($type)) {
            Response::error(ReturnCode::INVALID_REQUEST, '请求数据非法');
        }

        //查询广告数据
        $where = [
            'flag' => $flag,
            'status' => 1,
            'type' => $type,
            'end_time' => ['EGT', NOW_TIME]
        ];
        $adList = D('Ad')->field('id, title, url, img, end_time, sort ')->where($where)->limit($size)->order('sort asc, id')->select();

        //判断查询结果
        if ($adList !== false) {
            //处理数据
            foreach ($adList as $key => $v) {
                $adList[$key]['img'] = C('ATTACHMENT_URL') . $v['img'];
                unset($adList[$key]['end_time']);
                unset($adList[$key]['sort']);
            }
            //返回响应结果
            Response::success($adList);
        }

        //请求失败错误提示
        Response::error(ReturnCode::INVALID_REQUEST, '数据请求失败');
    }
}