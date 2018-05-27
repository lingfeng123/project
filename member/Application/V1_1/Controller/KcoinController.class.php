<?php
/**
 * FileName: KcoinController.class.php
 * User: Comos
 * Date: 2018/3/5 9:52
 */

namespace V1_1\Controller;


use Org\Util\Response;
use Org\Util\ReturnCode;

class KcoinController extends BaseController
{

    /**
     * K币交易记录
     */
    public function coinList()
    {
        $member_id = I('post.member_id', '');
        $page = I('post.page', 1);
        $pagesize = I('post.page_size', C('PAGE.PAGESIZE'));

        if (!is_numeric($member_id)) {
            Response::error(ReturnCode::PARAM_WRONGFUL, '请求参数不合法');
        }

        $data = D('member_kcoin_record')->getCoinList($member_id, $page, $pagesize);
        if ($data === false) {
            Response::error(ReturnCode::DB_READ_ERROR, '获取K币记录失败');
        }

        Response::setSuccessMsg('获取K币记录成功');
        Response::success($data);
    }


}