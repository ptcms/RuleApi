# RuleApi 
PTCMS 小说规则API接口程序

# 适用场景
- Json格式为PTCMS专用采集格式
- Xml规则可以用于任何程序的采集，如关关、杰奇、YGB


# 安装说明
 - 开箱即用 ，只需要把 `env.example.php` 改名为 `env.php` 修改对应配置项
 
# 使用

## Json格式

### 列表接口
- 请求方式：GET
- 请求路径：`/novel/getlist.json`
- 请求参数：
```
{
    site:qidian
}
```
- 请求示例
`http://api.ptcms.com/novel/getlist.json?site=qidian`

### 信息接口
- 请求方式：GET
- 请求路径：`/novel/getinfo.json`
- 请求参数：
```
{
    site:qidian,
    novelid:书号,
}
```
- 请求示例
`http://api.ptcms.com/novel/getinfo.json?site=qidian&novelid=1`

### 目录接口
- 请求方式：GET
- 请求路径：`/novel/getinfo.json`
- 请求参数：
```
{
    site:qidian,
    novelid:书号,
}
```
- 请求示例
`http://api.ptcms.com/novel/getdir.json?site=qidian&novelid=1`

### 章节接口
- 请求方式：GET
- 请求路径：`/novel/getchapter.json`
- 请求参数：
```
{
    site:qidian,
    novelid:书号,
    chapterid:章节ID,
}
```
- 请求示例
`http://api.ptcms.com/novel/getchapter.json?site=qidian&novelid=1&chapterid=1`

### 下载接口
- 请求方式：GET
- 请求路径：`/novel/getdown.json`
- 请求参数：
```
{
    site:qidian,
    novelid:书号,
}
```
- 请求示例
`http://api.ptcms.com/novel/getdown.json?site=qidian&novelid=1`

### 搜索接口
- 请求方式：GET
- 请求路径：`/novel/getsearch.json`
- 请求参数：
```
{
    site:qidian,
    name:书名,
    author:作者,
}
```
`name`,`author` 两个需要有一个填写
- 请求示例
`http://api.ptcms.com/novel/getsearch.json?site=qidian&name=极品家丁&author=`
 
## Xml格式
把Json格式接口地址后缀`.json`改为`.xml`即可

# 新增规则
参考`app\rule\custom\customqidian.php` 文件新增规则，以此为例，增加的新规则`site`的值为`customqidian`

# 代理使用
目前已支持芝麻代理  
使用前请先修改`app\controller\index.php`文件`proxy`方法的代理获取url  
在规则文件中增加`protected $useProxy=1;` 即可对规则启用代理功能  
更新代理则是定时访问 `http://www.ptcms.com/index/proxy`    
