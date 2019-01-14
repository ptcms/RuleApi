<?php

namespace App\Rule\Yc;

use App\Component\Format;
use App\Rule\Kernel;
use Kuxin\Helper\Collect;
use Kuxin\Helper\Json;

class Qidian extends Kernel
{
    
    /**
     * 代理开关
     */
    protected $useProxy = 0;

    public function getList($page)
    {
        $url     = 'https://r.qidian.com/vipup?style=2&t=[timestamp]&page=' . $page;
        $content = Collect::getContent($url);
        if (!$content || strlen($content) < 1000) {
            return '获取内容失败：链接' . $url . ' 长度' . strlen($content);
        }

        $novelname = Collect::getMatchAll('target="_blank" data-bid="\d+">(.+?)</', $content);
        $novelid   = Collect::getMatchAll('href="//book.qidian.com/info/(\d+)"', $content);
        $chapterid = Collect::getMatchAll('cid="//\w+.qidian.com/chapter/\w+/(\w+)"', $content);

        if ($novelname && count($novelname) == count($novelid) && count($novelname) == count($chapterid)) {
            $list = [];
            foreach ($novelname as $k => $v) {
                $list[] = [
                    'novelid'   => $novelid[$k],
                    'novelname' => str_replace('(书坊)', '', $novelname[$k]),
                    'chapterid' => $chapterid[$k],
                ];
            }
            return $list;
        } else {
            if (isset($_GET['debug'])) {
                var_dump($content, $novelid, $novelname, $chapterid);
            }
            return sprintf('获取数目不一致：novelname %s novelid %s chapterid %s 长度 %s', count($novelname), count($novelid), count($chapterid), strlen($content));
        }
    }

    public function getInfo($novelid)
    {
        $url     = 'https://m.qidian.com/book/' . $novelid;
        $content = Collect::getContent($url);
        if (!$content || strlen($content) < 1000) {
            return '获取内容失败：链接' . $url . ' 长度' . strlen($content);
        }
        $content       = str_replace('<!-- 限免，更新之类的角标 -->', '', $content);
        $data          = [
            'novelname'   => Format::clearNovelName(strip_tags(Collect::getMatch('<h1 class="header-back-title">(.+?)</h1>', $content))),
            'author'      => Format::clearNovelName(strip_tags(Collect::getMatch('<h4 class="book-title">(.+?)<', $content))),
            'intro'       => Format::intro(Collect::getMatch(['rule' => '<textarea hidden>(.+?)</textarea>', 'option' => 'is'], $content)),
            'cover'       => strip_tags(Collect::getMatch('<div class="book-layout">\s*<img src="(.+?)" class="book-cover"', $content)),
            'channel'     => strpos($content, '起点女生网') ? '女生' : '男生',
            'category'    => strip_tags(Collect::getMatch('<p class="book-meta" role="option">(.+?)/.+?</p>\s*<p class="book-meta" role="option">', $content)),
            'subcategory' => strip_tags(Collect::getMatch('<p class="book-meta" role="option">.+?/(.+?)</p>\s*<p class="book-meta" role="option">', $content)),
            'isover'      => strip_tags(Collect::getMatch('<span class="char-pipe">\|</span>(.+?)</p>', $content)),
            'num_words'   => floor((Collect::getMatch('</p>\s*<p class="book-meta" role="option">([\d\.]*?)万字<span class="char-pipe">', $content)) * 10000),
            'tag'         => implode(',', Collect::getMatchAll('/search\?kw=&tag=(.+?)"', $content)),
        ];
        $data['cover'] = (substr($data['cover'], 0, 2) == '//' ? 'https:' : '') . $data['cover'];
        if ($data['cover'] && substr($data['cover'], -4) == '/300') {
            $data['cover'] = substr($data['cover'], 0, -4) . '/180';
        }
        if (empty($data['novelname'])) {
            return '获取小说书名失败：url ' . $url . ' 内容长度：' . strlen($content);
        }
        return $data;
    }

    public function getDir($novelid)
    {
        $dir     = [];
        $url     = "https://m.qidian.com/book/{$novelid}/catalog";
        $content = Collect::getContent($url);
        if (empty($content)) {
            return '获取内容失败：链接 ' . $url;
        }
        $jscontent = Collect::getMatch(['rule' => 'g_data\.volumes\s*\=\s+(.+?)\];\s+</script>', 'option' => 'isU'], $content) . ']';

        if (!$jscontent) {
            return '获取js代码失败：链接 ' . $url;
        }
        $data = Json::decode($jscontent);
        if (empty($data)) {
            return '解析内容失败：链接 ' . $url;
        } else {
            if ($data[0]['vN'] == '作品相关') {
                array_shift($data);
            }
            foreach ($data as $k => $v) {
                if (isset($v['cs'])) {
                    foreach ($v['cs'] as $c) {
                        $dir[] = [
                            'id'   => $c['id'],
                            'name' => $c['cN'],
                            'url'  => $c['id'],
                        ];
                    }
                }
            }
            return $dir;
        }

    }

    public function getChapter($param)
    {
        $url            = "https://m.qidian.com/book/{$param['novelid']}/{$param['chapterid']}";
        $content        = Collect::getContent($url);
        $jscontent      = collect::getMatch('g_data.chapter = (.+?);\s*g_data', $content);
        $jscontent      = Json::decode($jscontent);
        $chapterContent = $jscontent['content'] . "\n\n" . $jscontent['authorWords']['content'];
        return [
            'name'    => $jscontent['chapterName'],
            'content' => Format::chapter($chapterContent),
            'url'     => $url,
            'isvip'   => $jscontent['vipStatus'],
        ];
    }

    public function getDown($novelid)
    {
        return [
            'epub' => [
                'url' => 'http://download.qidian.com/epub/' . $novelid . '.epub',
            ],
        ];
    }

}
