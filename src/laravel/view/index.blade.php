<!DOCTYPE>
<html>
<head>
    <meta charset="utf-8">
    <title>{{$config['title']??'Laravel 日志查看器'}}</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="{{$config['layui_css_path']??'//unpkg.com/layui/dist/css/layui.css'}}" rel="stylesheet">
    <script src="{{$config['layui_js_path']??'//unpkg.com/layui/dist/layui.js'}}"></script>
</head>
<body>

<style>
    .layui-layout-admin .layui-header {
        border-bottom: 1px solid #f5f5f5;
        box-sizing: border-box;
        background-color: #fff;
    }

    .layui-layout-admin .layui-logo {
        position: fixed;
        top: 0;
        height: 60px;
        overflow: hidden;
        cursor: pointer;
    }

    .layui-layout-admin .log-module-select {
        position: fixed;
        top: 60px;
        width: 175px;
        z-index: 9999;
    }

    .layui-side-menu .layui-nav > .layui-nav-item .layui-icon:first-child {
        position: absolute;
        top: 50%;
        left: 20px;
        margin-top: -19px;
        color: #fff;
    }

    .layui-side-menu .layui-nav .layui-nav-item a {
        padding-left: 45px;
        padding-right: 30px;
    }

    .layui-nav {
        color: #333;
    }

    .layui-side {
        background-color: #2f363c !important;
        z-index: 9999;
    }

    .layui-nav .layui-nav-item a {
        color: #FFFFFF !important;
    }

    .layui-nav-tree .layui-nav-child dd.layui-this, .layui-nav-tree .layui-nav-child dd.layui-this a, .layui-nav-tree .layui-this, .layui-nav-tree .layui-this > a, .layui-nav-tree .layui-this > a:hover {
        background: #ff4900;
        color: #FFf !important;
        border: 0;
    }

    .layui-nav .layui-nav-item a:hover, .layui-nav .layui-this a {
        border-left-color: #ff4900 !important;
    }

    .log-body {
        font-size: .92rem !important;
    }

    .log-code .layui-code-item {
        font-family: sans-serif;
        line-height: 2.5;
    }

    .log-code .layui-code-line-content {
        font-family: sans-serif;
        line-height: 2.5;
        word-break: break-all;
    }

    .code-main {
        height: 94%;
        top: 60px;
        overflow: hidden;
        overflow-y: scroll;
    }

    .log-module-select {
        margin: 0 12px;
    }

    .layui-tree {
        margin-top: 1px;
    }

    .layui-tree .layui-tree-emptyText {
        margin: 15px 0;
    }

    .layui-tree .layui-tree-txt {
        color: #FFFFFF;
    }

    .layui-tree .layui-tree-entry {
        line-height: 35px;
        height: 35px;
        width: 100%;
        background: #2f363c !important;
        user-select: none;
    }

    .layui-tree .layui-tree-entry.layui-tree-entry-this {
        background: #FFFFFF !important;
        border: 0;
        border-radius: 1px;
        width: 100%;
    }

    .layui-tree .layui-tree-entry.layui-tree-entry-this .layui-tree-txt {
        color: #2f363c !important;
    }

    .layui-tree .layui-tree-entry:hover {
        background: #FFFFFF !important;
        color: #2f363c !important;
    }

    .layui-tree .layui-tree-entry:hover .layui-tree-txt {
        background: transparent !important;
        color: #2f363c !important;
    }

    .layui-elem-quote {
        position: fixed;
        z-index: 9999;
        word-break: break-all;
        margin-left: 15px;
        width: 100%;
    }

    .toTop {
        position: fixed;
        right: 20px;
        bottom: 35px;
        width: 42px;
        height: 42px;
        line-height: 42px;
        background: #333333;
        border-radius: 50px;
        border: 1px solid #6d6d6d;
        text-align: center;
        font-size: 1rem;
        z-index: 9999;
        cursor: pointer;
    }

    .toTop .layui-icon {
        font-size: 30px;
        color: #FFFFFF;
    }
</style>

<div class="layui-layout layui-layout-admin">
    <div class="layui-header">
        <ul class="layui-nav layui-layout-left ">
            <li class="layui-nav-item layui-show-xs-inline-block" lay-on="menuLeft">
                <i class="layui-icon layui-icon-spread-left"></i>
            </li>
        </ul>
    </div>

    <div class="layui-side layui-side-menu layui-bg-black">
        <div class="layui-logo layui-bg-black" lay-on="reloaPage">{{$config['title']??'Laravel 日志查看器'}}</div>

        <div class="layui-side-scroll">
            <div id="menu"></div>
        </div>
    </div>
    <div class="layui-body">
        <blockquote class="layui-elem-quote layui-text">
            <span class="now-log-path">暂无日志内容，请选择日志文件</span>
        </blockquote>
        <div class="layui-main code-main">
            <pre class="layui-code log-body layui-hide" lay-options="{}"></pre>
        </div>
    </div>
    <div class="toTop" lay-on="toTop">
        <i class="layui-icon layui-icon-up"></i>
    </div>
</div>

<script>

    window.CONFIG = {
        CSRF_TOKEN: '{{ csrf_token() }}',
    };

    let firstLoad = layer.load(2, {time: 30000, shade: 0.3, shadeClose: true, scrollbar: false})

    var logFolder = '{{$logPath}}'
    let randomStr = '{{$randomStr}}'
    let todayLog = 'laravel-{{date("Y-m-d")}}.log'
    var module = '{{$module}}'
    var isShow = true

    layui.use(['element', 'layer', 'util'], function () {

        let element = layui.element;
        let layer = layui.layer;
        let util = layui.util;
        let $ = layui.$;
        let tree = layui.tree;
        let form = layui.form;

        $(function () {
            layer.close(firstLoad)
        })

        let treeData = JSON.parse('{!! json_encode($logs,256) !!}');
        tree.render({
            id: 'menu',
            elem: '#menu',
            data: treeData,
            showLine: false,
            accordion: true,
            click: (obj) => {
                let _data = obj.data
                if (_data.id < 1) return
                $('.layui-tree-entry').removeClass('layui-tree-entry-this')
                $(obj.elem).find('.layui-tree-entry').addClass('layui-tree-entry-this')
                let logPath = _data.title
                let file_path = logFolder + '/' + logPath
                let localInfo = layui.sessionData(randomStr);
                let localData = localInfo[module + '/' + logPath] || '';
                $('.now-log-path').text(' 当前项目日志路径：' + file_path)
                $('.log-body').removeClass('layui-hide')
                if (localData != '') {
                    let load = layer.msg('数据获取中...', {icon: 16, time: 30000, shade: 0.3, shadeClose: false, scrollbar: false})
                    layui.code({
                        elem: '.log-body', code: localData.join(''), className: 'log-code', langMarker: true, header: true, theme: 'dark',
                        done: function () {
                            layer.close(load)
                            $('.code-main').animate({scrollTop: $('.code-main').prop('scrollHeight')}, 200)
                        }
                    });
                } else {
                    ajaxPost(location.href, {'logviewer_file_path': file_path}, function (res) {
                        let info = res.data?.info || ''
                        if (logPath != todayLog) {
                            try {
                                layui.sessionData(randomStr, {key: logPath, value: info});
                            } catch (e) {
                                console.error(e)
                            }
                        }
                        layui.code({
                            elem: '.log-body', code: info.join(''), className: 'log-code', langMarker: true, header: true, theme: 'dark',
                            done: function () {
                                $('.code-main').animate({scrollTop: $('.code-main').prop('scrollHeight')}, 200)
                            }
                        });
                    })
                }
            }
        });

        form.on('select(module)', function (data) {
            $('.now-log-path').text('暂无日志内容，请选择日志文件')
            $('.log-body').addClass('layui-hide')
            let value = data.value
            if (value != '') {
                document.cookie = "phplogviewer-ThinkPHP-module=" + value;
                module = value
                ajaxPost(location.href, {'logviewer_module': value}, function (res) {
                    let list = res.data?.list || ''
                    logFolder = list?.logPath || ''
                    let treeData = list?.logs || {}
                    tree.reload('menu', {data: treeData});
                })
            }
        })

        util.on({
            reloaPage: function () {
                location.reload()
            },
            toTop: function () {
                $(".code-main").animate({scrollTop: 0}, 100);
            },
            menuLeft: function (othis) {
                if (isShow) {
                    $('.layui-side.layui-bg-black').width(60); //设置宽度
                    $('.layui-logo').width(60);
                    $('.log-module-select').width(30);
                    $('.layui-layout-left').css('left', 60 + 'px');
                    $('.layui-body').css('left', 60 + 'px');
                    $('.layui-footer').css('left', 60 + 'px');
                    isShow = false;
                } else {
                    $('.layui-side.layui-bg-black').width(200);
                    $('.layui-logo').width(200);
                    $('.log-module-select').width(175);
                    $('.layui-layout-left').css('left', 200 + 'px');
                    $('.layui-body').css('left', 200 + 'px');
                    $('.layui-footer').css('left', 200 + 'px');
                    isShow = true;
                }
            },
        });

        function ajaxPost(url, data, callback) {
            let load = layer.msg('数据获取中...', {icon: 16, time: 30000, shade: 0.3, shadeClose: false, scrollbar: false})
            $.ajax({
                method: 'POST', url: url, dataType: 'json', timeout: 5000, data: data,
                headers: {'X-CSRF-TOKEN': window.CONFIG.CSRF_TOKEN},
                success: (res) => callback(res),
                error: (error) => layer.alert(error.responseJSON?.message || '未知错误', {shade: 0.3, shadeClose: true, time: 7000, icon: 0}),
                complete: () => layer.close(load),
            })
        }

    });

</script>

</body>
</html>
