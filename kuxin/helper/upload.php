<?php

namespace Kuxin\Helper;

/**
 * Class Upload
 *
 * @package Kuxin\Helper
 * @author  Pakey <pakey@qq.com>
 */
class Upload
{

    /**
     * @var \Kuxin\Storage
     */
    public $storage = null;
    //$_FILES的文件信息
    public $fileinfo;
    //自定义文件名
    public $fileName;
    //自定义文件存放目录
    public $fileDir;
    //自定义文件存放完整路径
    public $filePath;
    //自定义允许文件后缀
    public $allowType = "jpg|png|gif|txt|bmp|ico|doc|xls|jpeg|zip|rar";
    //自定义允许文件mime
    public $allowMime = [];
    //自定义文件大小 Kb
    public $allowMaxSize = 2048;

    public function __construct($storage)
    {
        $this->storage = $storage;
    }

    /**
     * 临时文件
     *
     * @param $fileinfo
     */
    public function setFile($fileinfo)
    {
        $this->fileinfo = $fileinfo;
    }

    /**
     * 设置上传的文件名
     *
     * @param $filename
     */
    public function setName($filename)
    {
        $this->fileName = $filename;
    }

    /**
     * 设置上传的文件路径
     *
     * @param $filedir
     */
    public function setDir($filedir)
    {
        $this->fileDir = $filedir;
    }

    /**
     * 设置上传的文件后缀
     *
     * @param $filetype
     */
    public function setType($filetype)
    {
        $this->allowType = $filetype;
    }

    /**
     * 设置上传的文件大小
     *
     * @param $filesize
     */
    public function setSize($filesize)
    {
        $this->allowMaxSize = $filesize;
    }

    /**
     *检测文件大小
     */
    private function checkSize()
    {
        return $this->fileinfo['size'] > 0 && ($this->fileinfo['size'] <= $this->allowMaxSize * 1024);
    }

    /**
     *检测文件后缀
     */
    private function checkType()
    {
        return in_array($this->getType(), explode("|", strtolower($this->allowType)));
    }

    /**
     *获取文件后缀
     */
    private function getType()
    {
        return strtolower(pathinfo($this->fileinfo['name'], PATHINFO_EXTENSION));
    }


    /**
     *获取文件完整路径
     */
    private function getPath()
    {
        if (empty($this->fileDir))
            $this->fileDir = date('Ym') . '/' . date('d');
        if (!$this->fileName)
            $this->fileName = md5($this->fileinfo['name'] . $this->fileinfo['size']);
        $this->filePath = $this->fileDir . '/' . $this->fileName . "." . $this->getType();
    }

    /**
     * 检测mime类型
     *
     * @return bool
     */
    protected function checkMime()
    {
        return !(!empty($this->allowMime) && !in_array($this->fileinfo['type'], $this->allowMime));
    }

    /**
     * 错误返回
     *
     * @param $info
     * @return array
     */
    private function error($info)
    {
        return ['status' => 0, 'info' => $info];
    }

    /**
     *上传文件
     */
    public function save()
    {
        if ($this->fileinfo['error'] !== 0) {
            $this->error($this->geterrorinfo($this->fileinfo['error']));
        }
        //检测文件大小
        if (!$this->checkSize()) {
            return $this->error("上传附件不得超过" . $this->allowMaxSize . "KB");
        }
        //校验mime信息
        if (!$this->checkMime()) {
            return $this->error("上传文件MIME类型不允许！");
        }
        //不符则警告
        if (!$this->checkType()) {
            return $this->error("正确的扩展名必须为" . $this->allowType . "其中的一种！");
        }
        //检查是否合法上传
        if (!is_uploaded_file($this->fileinfo['tmp_name'])) {
            return $this->error("非法上传文件！");
        }
        // 获取上传文件的保存信息
        $this->getpath();


        if ($this->write(file_get_contents($this->fileinfo['tmp_name']))) {
            $info['ext']      = $this->getType();
            $info['fileurl']  = $this->storage->getUrl($this->filePath);
            $info['filepath'] = $this->filePath;
            $info['filename'] = $this->fileinfo['name'];
            $info['hash']     = md5_file($this->fileinfo['tmp_name']);
            $info['size']     = $this->fileinfo['size'];
            return ['status' => 1, 'info' => $info];
        } else {
            return $this->error("上传失败！");
        }
    }

    protected function getErrorInfo($num)
    {
        switch ($num) {
            case 1:
                return '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值';
            case 2:
                return '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值';
            case 3:
                return '文件只有部分被上传';
            case 4:
                return '没有文件被上传';
            case 6:
                return '找不到临时文件夹';
            case 7:
                return '文件写入失败';
            default:
                return '未知上传错误！';
        }
    }

    public function write($content)
    {
        // 上传操作
        if (in_array($this->getType(), ['gif', 'jpg', 'jpeg', 'bmp', 'png'])) {
            //$imginfo = getimagesize($this->fileinfo['tmp_name']);
            $img     = new image($this->fileinfo['tmp_name']);
            $content = $img->save();
        }
        return $this->storage->write($this->filePath, $content);
    }

    public function saveFromUrl($url, $content = '')
    {
        $this->fileName = $this->filePath = '';
        if ($content == '') {
            $content = http::get($url);
        }
        $this->fileinfo = [
            'name'     => basename(parse_url($url, PHP_URL_PATH)),
            'size'     => strlen($content),
            'tmp_name' => $url,
        ];
        $this->getpath();

        if ($this->write($content)) {
            $info['ext']      = $this->getType();
            $info['fileurl']  = $this->storage->getUrl($this->filePath);
            $info['filepath'] = $this->filePath;
            $info['filename'] = $this->fileinfo['name'];
            $info['hash']     = md5($content);
            $info['size']     = $this->fileinfo['size'];
            return ['status' => 1, 'info' => $info];
        } else {
            return $this->error("上传失败！");
        }
    }
}