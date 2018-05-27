<?php
/**
 * FileName: UploadController.class.php
 * User: Comos
 * Date: 2017/7/25 11:41
 */

namespace Admin\Controller;


use Org\Util\Response;
use Org\Util\Tools;
use Think\Controller;
use Think\Upload;
use Think\Upload\Driver\Qiniu;

class UploadController extends Controller
{
    /**
     * 文件上传方法
     *
     */
    public function index($mold)
    {
        if (!isset($mold) || empty($mold)) {
            $json = json_encode([
                "code" => 1,
                "msg" => "上传图片模型不正确",
                "data" => ["src" => ""]
            ]);
            exit($json);
        }

        $rootPath = Tools::attachment_path() . DIRECTORY_SEPARATOR;
        $mold = $mold . '/';

        //设置上传文件配置
        $config = array(
            'mimes' => array('image/jpeg', 'image/png', 'image/gif'), //允许上传的文件MiMe类型
            'maxSize' => 3145728, //上传的文件大小限制 (0-不做限制)
            'exts' => array('jpg', 'jpeg', 'gif', 'png'), //允许上传的文件后缀
            'autoSub' => true, //自动子目录保存文件
            'subName' => array('date', 'Ymd'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
            'rootPath' => $rootPath, //保存根路径
            'savePath' => $mold, //保存路径
            'saveName' => array('get_filename', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
            'saveExt' => '', //文件保存后缀，空则使用原后缀
            'replace' => true, //存在同名是否覆盖
            'hash' => false, //是否生成hash编码
            'callback' => false, //检测文件是否存在回调，如果存在返回文件信息数组
            'driver' => 'Qiniu', // 文件上传驱动本地上传不用填写 Qiniu
            'driverConfig' => C('QINIU_CONFIG'),
        );

        //实例化上传类
        $uploads = new Upload($config);
        //开始上传文件
        $fileInfos = $uploads->upload();
        //获得一维数组
        //$fileInfos = $fileInfos['Filedata'];
        //弹出上传后的一组数据
        $fileInfos = array_pop($fileInfos);
        //上传数据
        if (!$fileInfos) {
            Response::error(1, $uploads->getError(), ["src" => ""]);
        } else {

            /*$filePath = $fileInfos['savepath'] . $fileInfos['savename'];
            $json['code'] = 0;
            $json['msg'] = "";
            $json['data'] = ["src" => $filePath];*/

            $file = $rootPath . $fileInfos['savepath'] . $fileInfos['savename'];
            $file = str_replace('/', DIRECTORY_SEPARATOR, $file);
            //删除本地文件
            chmod($file, 0777);
            unlink($file);

            //骑牛云存储直接返回地址并分割保留文件名
            if ($uploads->driver == 'Qiniu') {
                $filePath = parse_url($fileInfos['url']);
                $filePath = $filePath['path'];
            }

            Response::success(["src" => $filePath, 'rm' => $file], 0);
            //如果是骑牛上传,直接返回url,不需要组装url
            /*if($uploads->driver == 'Qiniu'){
                $filePath = $fileInfos['url'];
                //获取本地文件,并删除
                $rmurl = '/'.$fileInfos['savepath'].$fileInfos['savename'];
                //删除本地文件
                unlink($rmurl);
            }else{
                $filePath = $fileInfos['savepath'].$fileInfos['savename'];
            }*/
        }

        $json = json_encode([
            "code" => 0,
            "msg" => "",
            "data" => ["src" => $filePath]
        ]);
        exit($json);
    }

}