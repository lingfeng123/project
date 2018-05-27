<?php
/**
 * FileName: WechatController.class.php
 * User: Comos
 * Date: 2017/11/28 18:12
 */

namespace Admin\Controller;


use Org\Util\Response;
use Org\Util\ReturnCode;
use Org\Util\Wechat;
use Think\Page;

class WechatController extends BaseController
{
    //微信对象
    private $wechat;

    public function _initialize()
    {
        parent::_initialize();
        $options = C('WECHAT_OPTION');
        $this->wechat = new Wechat($options);
    }

    /**
     * 微信菜单列表
     */
    public function index()
    {
        $this->display();
    }

    /**
     * ajax请求微信菜单数据
     */
    public function getWechatMenu()
    {
        if (IS_GET) {
            //获取微信菜单
            $menu = $this->wechat->getMenu();
            //判断是否获取微信菜单成功
            if ($menu) {

                foreach ($menu['menu']['button'] as $key => $v) {
                    if (!isset($v['sub_button'])) {
                        $menu['menu']['button'][$key]['sub_button'] = '';
                    }
                }

                //输出菜单数据
                Response::success($menu);
            } else {
                Response::error(ReturnCode::DB_READ_ERROR, '获取微信菜单失败');
            }
        } else {
            Response::error(ReturnCode::DB_READ_ERROR, '请求类型不允许');
        }
    }

    /**
     * 设置微信菜单
     */
    public function add()
    {
        if (IS_POST) {
            $data = I('post.menu', '', 'strip_tags');
            //遍历数据
            foreach ($data['button'] as $key => $v) {
                if ($v['url']) {
                    $v['url'] = str_replace('&amp;', '&', $v['url']);
                }
                if ($v['sub_button']) {
                    unset($data['button'][$key]['url']);
                    unset($data['button'][$key]['appid']);
                    unset($data['button'][$key]['pagepath']);
                    unset($data['button'][$key]['key']);
                    unset($data['button'][$key]['type']);
                } else {
                    $data['button'][$key]['url'] = str_replace('&amp;', '&', $data['button'][$key]['url']);
                }
            }

            $menu = $this->wechat->createMenu($data);
            if (!$menu) {
                Response::error(ReturnCode::DB_SAVE_ERROR, '设置微信菜单失败');
            }
            Response::success();
        } else {
            Response::error(ReturnCode::INVALID, '请求失败');
        }
    }

    /**
     * 渠道二维码管理
     */
    public function qrcodeIndex()
    {
        $list = M('channel')->distinct(true)->field('api_channel.*, (select count(channel_id) from `api_member` where `channel_id` = api_channel.id) as total')
            ->join('api_member ON api_member.channel_id = api_channel.id', 'left')
            ->order('id desc')
            ->select();
        $this->assign('list', $list);
        $this->display('qrcodeIndex');
    }

    /**
     * 添加渠道二维码
     */
    public function qrcodeAdd()
    {
        if (IS_POST) {
            $title = I('post.title');
            $description = I('post.description');

            if (empty($title) || empty($description)) {
                $this->error('填写内容不能为空');
            }

            $id = M('channel')->add(['title' => $title, 'description' => $description, 'created_time' => time()]);
            if (!$id) {
                $this->error('添加渠道失败');
            }

            //获取永久二维码
            $ticket = $this->wechat->getQRCode($id, $type = 2);
            $url = $this->wechat->getQRUrl($ticket['ticket']);
            $rs = M('channel')->save(['id' => $id, 'qrcode' => $url]);
            if ($rs === false) {
                $this->error('获取微信二维码失败');
            }

            $this->redirect('Wechat/qrcodeIndex');
        } else {
            $this->display('qrcodeAdd');
        }
    }

    /**
     * 关键字回复
     */
    public function keywordreply($p = 0)
    {
        $wechat_reply_model = M('wechat_reply');

        $pagesize = C('PAGE.PAGESIZE');
        $list = $wechat_reply_model->page($p, $pagesize)->select();
        $count = $wechat_reply_model->count();

        if ($list === false || $count === false) {
            $this->error('获取数据列表失败');
        }

        $pages = new Page($count, $pagesize);
        $pages->setConfig('header', '共%TOTAL_ROW%条');
        $pages->setConfig('first', '首页');
        $pages->setConfig('last', '末页');
        $pages->setConfig('prev', '上一页');
        $pages->setConfig('next', '下一页');
        $pages->setConfig('theme', C('PAGE.THEME'));

        $pageHtml = urldecode($pages->show());
        $this->assign('pageHtml', $pageHtml);
        $this->assign('list', $list);

        $this->display('keywordreply');
    }

    /**
     * 新增关键字回复
     */
    public function keywordadd()
    {
        $wechat_reply_model = M('wechat_reply');
        if (IS_POST) {
            $data = I('post.', []);
            if (!$data) {
                $this->error('添加失败');
            }

            $data['created_time'] = time();
            if ($data['msg_type'] == 'news') {
                $count = $wechat_reply_model->where(['msg_type' => $data['msg_type'], 'keyword' => $data['keyword'], 'status' => 1])->count();
                if ($count > 8) {
                    $this->error('相同关键字图文消息不能大于8条');
                }
            }

            $res = $wechat_reply_model->add($data);
            if ($res === false) {
                $this->error('添加失败');
            }
            $this->success('添加成功');

        } else {
            $this->display('keywordadd');
        }

    }

    /**
     * 修改关键字回复
     * @param $id
     */
    public function keywordedit($id = 0)
    {
        $wechat_reply_model = M('wechat_reply');
        if (IS_POST) {
            $data = I('post.', []);
            if (!$data) {
                $this->error('修改失败');
            }

            $data['created_time'] = time();
            if ($data['msg_type'] == 'news') {
                $count = $wechat_reply_model->where(['msg_type' => $data['msg_type'], 'keyword' => $data['keyword'], 'status' => 1])->count();
                if ($count > 8) {
                    $this->error('相同关键字图文消息不能大于8条');
                }
            }

            $res = $wechat_reply_model->save($data);
            if ($res === false) {
                $this->error('修改失败');
            }

            $this->success('修改成功', U('Wechat/keywordreply'));

        } else {
            $detail = $wechat_reply_model->find($id);
            if (!$detail) {
                $this->error('获取数据失败');
            }

            $this->assign('detail', $detail);
            $this->display('keywordadd');
        }

    }

    /**
     * 删除关键字回复
     * @param int $id
     */
    public function keyworddel($id = 0)
    {
        $res = M('wechat_reply')->delete($id);
        if (!$res) {
            $this->error('删除失败');
        }
        $this->success('删除成功', U('Wechat/keywordreply'));
    }
}