<?php

class Hello
{
    public function test()
    {
        return (new \Wolfcode\PhpLogviewer\laravel\LogViewer())->fetch();
    }

}