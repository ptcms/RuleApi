<?php

namespace App\Controller;


use App\Rule\Yc\Qidian;

use App\Provider\Format;
use Kuxin\Controller;
use Kuxin\DI;
use Kuxin\Helper\Json;
use Kuxin\Helper\Math;
use Kuxin\Input;
use Kuxin\Loader;
use Kuxin\Config;

class Novel extends Controller
{

    /**
     * 原创站规则
     *
     * @var array
     */
    public $rule = [
        'qidian' => Qidian::class,

    ];

    /**
     * @var \App\Rule\Kernel
     */
    protected $model;


    public function init()
    {
        Config::set('http.user_agent', 'Yisouspider');
        $site = strtolower(Input::get('site', 'str', ''));
        if (isset($this->rule[$site])) {
            $classname = $this->rule[$site];
        } else {
            if (is_file(KX_ROOT . '/app/model/custom/' . $site . '.php')) {
                Loader::import(KX_ROOT . '/app/model/custom/' . $site . '.php');
                $classname = '\\App\\Rule\\Custom\\' . $site;
            } else {
                return [
                    'status' => 0,
                    'info'   => 'error',
                    'data'   => '不存在的规则',
                ];
            }
        }
        $this->model = Loader::instance($classname);
    }

    public function getlist(): array
    {
        $page   = Input::get('page', 'int', 1);
        $result = $this->model->getList($page);
        if (is_array($result)) {
            return [
                'status' => 1,
                'info'   => 'success',
                'data'   => $result,
            ];
        } else {
            return [
                'status' => 0,
                'info'   => 'error',
                'data'   => $result,
            ];
        }
    }

    /**
     * 获取小说信息
     *
     * @return array
     */
    public function getinfo(): array
    {
        $novelid = Input::request('novelid', 'str');
        $result  = $this->model->getInfo($novelid);
        if (is_array($result)) {
            if ($result['novelname'] && $result['author']) {
                $result['intro']       = $result['intro'] ?? "";
                $result['cover']       = $result['cover'] ?? "";
                $result['channel']     = $result['channel'] ?? "";
                $result['category']    = $result['category'] ?? "其他";
                $result['subcategory'] = $result['subcategory'] ?? "其他";
                $result['isover']      = $result['isover'] ?? "连载";
                $result['num_words']   = $result['num_words'] ?? "0";
                $result['tag']         = $result['tag'] ?? "";
                return [
                    'status' => 1,
                    'info'   => 'success',
                    'data'   => $result,
                ];
            } else {
                return [
                    'status' => 0,
                    'info'   => 'error',
                    'data'   => '书名或者作者名为空',
                ];
            }
        } else {
            return [
                'status' => 0,
                'info'   => 'error',
                'data'   => $result,
            ];
        }
    }

    /**
     * 获取目录结果
     * @return array
     */
    public function getdir(): array
    {
        $novelid = Input::request('novelid', 'str');
        $result  = $this->model->getDir($novelid);
        if (is_array($result)) {
            $data = [];
            foreach ($result as $v) {
                $item = [
                    'chapterid'   => (string)$v['id'],
                    'chaptername' => Format::name($v['name']),
                    'chapterurl'  => $v['id'],
                ];
                if ($item['chaptername'] && !in_array($item['chaptername'], ['更新公告', '本站通知'])) {
                    $data[] = $item;
                }
            }
            return [
                'status' => 1,
                'info'   => 'success',
                'data'   => $data,
            ];
        } else {
            return [
                'status' => 0,
                'info'   => 'error',
                'data'   => $result,
            ];
        }
    }

    /**
     * 获取章节结果
     * @return array
     */
    public function getchapter(): array
    {
        $url       = Input::request('url', 'str', '');
        $novelid   = Input::request('novelid', 'str', '');
        $chapterid = Input::request('chapterid', 'str', Input::request('url', 'str', ''));
        if (Config::get('chapter_cache_power')) {
            $cache  = DI::Cache('txt');
            $param  = ['url' => $url, 'novelid' => $novelid, 'chapterid' => $chapterid];
            $key    = md5(Json::encode($param));
            $result = $cache->get($key, function () use ($param, $cache, $key) {
                $result = $this->_getChapter($param);
                if (mb_strlen($result['content']) < 100) {
                    $time = 60;
                } elseif (strpos($result['content'], '手打') || strpos($result['content'], '防盗') || mb_strlen($result['content']) < 500) {
                    $time = 600;
                } else {
                    $cache->set($key, $result, 0);
                    $time = 3600 * 24 * 365;
                }
                header('Cache-Control: max-age=' . $time . ',must-revalidate');
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $time) . ' GMT');
                return $result;
            });
        } else {
            $result = $this->_getChapter(['url' => $url, 'novelid' => $novelid, 'chapterid' => $chapterid]);
        }

        if (is_array($result)) {
            return [
                'status' => 1,
                'info'   => 'success',
                'data'   => $result,
            ];
        } else {
            return [
                'status' => 0,
                'info'   => 'error',
                'data'   => $result,
            ];
        }
    }

    /**
     *
     * @param $param
     * @return array
     */
    protected function _getChapter($param): array
    {
        $param['subnovelid'] = Math::subid($param['novelid']);
        $result              = $this->model->getChapter($param);
        $result['isvip']     = $result['isvip'] ?? 0;
        $result['name']      = $result['name'] ?? '';
        $result['url']       = $result['url'] ?? $param['url'];
        return $result;
    }

    /**
     * 获取搜索结果
     *
     * @return array
     */
    public function getSearch(): array
    {
        $name   = Input::request('name', 'str', '');
        $author = Input::request('author', 'str', '');
        $result = $this->model->getSearch($name, $author);
        if ($result) {
            return [
                'status' => 1,
                'info'   => 'success',
                'data'   => ['id' => $result],
            ];
        } else {
            return [
                'status' => 0,
                'info'   => '没有找到',
            ];
        }
    }

    /**
     * 获取下载结果
     *
     * @return array
     */
    public function getDown(): array
    {
        $novelid = Input::request('novelid', 'str', '');
        $result  = $this->model->getDown($novelid);
        if (is_array($result)) {
            foreach ($result as $format => $item) {
                $result[$format]['size'] = $this->model->getDownFileSize($item['url']);
            }
            return [
                'status' => 1,
                'info'   => 'success',
                'data'   => $result,
            ];
        } else {
            return [
                'status' => 0,
                'info'   => 'error',
                'data'   => $result,
            ];
        }
    }
}