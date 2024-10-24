# PHP日志查看器

> 目前只支持Laravel、ThinkPHP5+、ThinkPHP6+、ThinkPHP8+、webman
> 要求 `php >= 7.4`

## 使用方法

```shell
composer require wolf-leo/phplogviewer
```

## 预览图

![](test/images/demo.png)

### ThinkPHP 框架中

```php
    public function test()
    {
        return (new \Wolfcode\PhpLogviewer\thinkphp\LogViewer())->fetch();
    }
    
    // Version in ThinkPHP 5.X
    public function test()
    {
        return (new \Wolfcode\PhpLogviewer\thinkphp\LogViewerOld())->fetch();
    }
```

### Laravel 框架中

```php
    public function test()
    {
        return (new \Wolfcode\PhpLogviewer\laravel\LogViewer())->fetch();
    }
```

### webman 框架中

```php
    // 如果项目中采用 laravel 组件
    public function test()
    {
        return (new \Wolfcode\PhpLogviewer\webman\laravel\LogViewer())->fetch();
    }
```

```php
    // 如果项目中采用 thinkphp 组件
    public function test()
    {
        return (new \Wolfcode\PhpLogviewer\webman\thinkphp\LogViewer())->fetch();
    }
```

> 可自定义配置
>
> 在 `config` 下新建 `logviewer.php` 文件

```php
<?php
return [

    // 日志标题
    'title'          => 'ThinkPHP 日志查看器',

    // 默认显示日志应用模块
    'default_module' => 'index',

    // 常用的日志应用模块
    'modules'        => [
        'admin',
        'home',
        'index',
        'api'
    ],
    
    // layui css 路径 如不设置，将默认调用公共CDN资源
    'layui_css_path' => '',
    // layui js 路径 如不设置，将默认调用公共CDN资源
    'layui_js_path'  => '',
    
];
```
