<?php
/**
 * FileName: FileUpload.class.php
 * User: Comos
 * Date: 2018/1/31 16:41
 */

namespace Org\Util;

use Think\Upload;

class FileUpload
{

    /**
     * 图片上传分类保存
     * @param string $folder 保存文件夹名称
     * @param int $type 上传图片类型 0单图 1多图
     * @return array 返回状态数组，包括文件地址
     */
    public static function uplodImage($folder, $type = 0)
    {
        $rootPath = Tools::attachment_path() . DIRECTORY_SEPARATOR;
        $folder = $folder . '/';
        //设置上传文件配置
        $config = array(
            'mimes' => array('image/jpeg', 'image/png', 'image/gif'), //允许上传的文件MiMe类型
            'maxSize' => 0, //上传的文件大小限制 (0-不做限制)
            'exts' => array('jpg', 'jpeg', 'gif', 'png'), //允许上传的文件后缀
            'autoSub' => true, //自动子目录保存文件
            'subName' => array('date', 'YmdHis'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
            'rootPath' => $rootPath, //保存根路径
            'savePath' => $folder, //保存路径
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
        //type：1为多图 0为单图
        if ($type) {
            //上传多张图片
            $fileInfos = $uploads->upload();

            //上传失败
            if (!$fileInfos) {
                return [
                    'msg' => $uploads->getError(),
                    'status' => 0
                ];
            }

            //组装上传文件地址
            $files = [];
            //骑牛云存储直接返回地址并分割保留文件名
            if ($uploads->driver == 'Qiniu') {
                foreach ($fileInfos as $fileInfo) {
                    $filePath = parse_url($fileInfo['url']);
                    $filePath = $filePath['path'];
                    $files[] = $filePath;
                }
            }

            //上传成功返回图片路径
            return [
                'msg' => '',
                'status' => 1,
                'path' => $files
            ];

        } else {
            //上传单张图片
            //处理数据
            $file = $_FILES['image'];
            if (count($file['name']) > 1) {
                $file_arr = [];
                foreach ($file as $key => $item) {
                    $file_arr[$key] = $item[0];
                    foreach ($item as $value) {
                        $file_arr[$key] = $value;
                    }
                }
                //获得单张图片
                $file = $file_arr;
            }

            //执行上传图片
            $fileInfos = $uploads->uploadOne($file);
            //上传失败
            if (!$fileInfos) {
                return [
                    'msg' => $uploads->getError(),
                    'status' => 0
                ];
            }

            //骑牛云存储直接返回地址并分割保留文件名
            if ($uploads->driver == 'Qiniu') {
                $filePath = parse_url($fileInfos['url']);
                $filePath = $filePath['path'];
            }

            //上传成功返回图片路径
            return [
                'msg' => '',
                'status' => 1,
                'path' => $filePath
            ];
        }
    }
}