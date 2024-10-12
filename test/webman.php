<?php

class Hello
{
    /**
     * 如果项目中采用 laravel 组件
     * @return mixed
     */
    public function laravel()
    {
        return (new \Wolfcode\PhpLogviewer\webman\laravel\LogViewer())->fetch();
    }

    /**
     * 如果项目中采用 thinkphp 组件
     * @return mixed
     */
    public function thinkphp()
    {
        return (new \Wolfcode\PhpLogviewer\webman\thinkphp\LogViewer())->fetch();
    }

}