# PHP日志查看器

> 目前只支持ThinkPHP6+、ThinkPHP8+
> 要求 `php >= 7.4`

## 预览图

![](test/images/demo.png)

## 使用方法

> composer require wolf-leo/phplogviewer

### ThinkPHP 框架中

```php
    public function test()
    {
        return (new \Wolfcode\PhpLogviewer\thinkphp\LogViewer())->fetch();
    }
```
> 可自定配置
> 
> 在 `config` 下新建 `logviewer.php` 文件

```php
<?php
return [
    // 默认显示日志应用模块
    'default_module' => 'index',

    // 常用的日志应用模块
    'modules'        => [
        'admin',
        'home',
        'index',
        'api'
    ],
];
```
