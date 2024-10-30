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

    @if (!empty($config['customize']['css']))
        <link href="{{$config['customize']['css']}}" rel="stylesheet">
    @endif
    @if (!empty($config['customize']['js']))
        <script src="{{$config['customize']['js']}}"></script>
    @endif

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

    .layui-side-menu .layui-nav .layui-nav-item a {
        padding-left: 45px;
        padding-right: 30px;
    }

    .layui-nav {
        color: #333;
    }

    .layui-nav a {
        padding-left: 20px !important;
    }

    .layui-side {
        background-color: #2f363c !important;
        z-index: 9999;
    }

    .layui-nav-tree .layui-nav-child dd.layui-this, .layui-nav-tree .layui-nav-child dd.layui-this a, .layui-nav-tree .layui-this, .layui-nav-tree .layui-this > a, .layui-nav-tree .layui-this > a:hover {
        background: #FFFFFF;
        color: #333333 !important;
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

    .tree-nav {
        margin-top: 1px;
    }

    .tree-nav .layui-tree-emptyText {
        margin: 15px 0;
    }

    .tree-nav .layui-tree-txt {
        color: #FFFFFF;
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

    <div class="layui-side layui-side-menu">
        <div class="layui-logo layui-bg-black" lay-on="reloaPage">{{$config['title']??'Laravel 日志查看器'}}</div>

        <div class="layui-side tree-nav">
            <div class="layui-side-scroll">
                <ul class="layui-nav layui-nav-tree" id="nav" layui-filter="nav">
                </ul>
            </div>
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

        let logsData = JSON.parse('{!! json_encode($logs,256) !!}');

        renderMenu(logsData)

        $('.layui-nav-tree').on('click', 'li', function () {
            if ($(this).hasClass('layui-nav-itemed')) {
                $(this).addClass('layui-nav-itemed').siblings('li').removeClass('layui-nav-itemed')
            }
        })

        form.on('select(module)', function (data) {
            $('.now-log-path').text('暂无日志内容，请选择日志文件')
            $('.log-body').addClass('layui-hide')
            let value = data.value
            if (value != '') {
                module = value
                ajaxPost(location.href, {'logviewer_module': value}, function (res) {
                    let list = res.data?.list || ''
                    logFolder = list?.logPath || ''
                    renderMenu(list?.logs || {})
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
                    $('.layui-side').width(60); //设置宽度
                    $('.layui-logo').width(60);
                    $('.log-module-select').width(30);
                    $('.layui-layout-left').css('left', 60 + 'px');
                    $('.layui-body').css('left', 60 + 'px');
                    $('.layui-footer').css('left', 60 + 'px');
                    isShow = false;
                } else {
                    $('.layui-side').width(200);
                    $('.layui-logo').width(200);
                    $('.log-module-select').width(175);
                    $('.layui-layout-left').css('left', 200 + 'px');
                    $('.layui-body').css('left', 200 + 'px');
                    $('.layui-footer').css('left', 200 + 'px');
                    isShow = true;
                }
            },
            logClick: function () {
                let folder = $(this).parents('.layui-nav-itemed').find('a:eq(0)').text()
                if (isNaN(folder)) folder = '';
                let logPath = (folder != '' ? folder + '/' : '') + ($(this).text())
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
                                layui.sessionData(randomStr, {key: module + '/' + logPath, value: info});
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

        function renderMenu(logsData) {
            $('#nav').empty()
            let _html = ``
            if (logsData.length < 1) {
                _html += `<div class="layui-tree-emptyText">暂无数据</div>`
                $('#nav').append(_html)
                return
            }
            $.each(logsData, function (index, value) {
                let _open = ''
                let children = value.children
                if (index < 1) _open = 'layui-nav-itemed'
                _html += `<li class="layui-nav-item ${_open}"><a class="" href="javascript:;">${value.title}</a><dl class="layui-nav-child">`
                if (children.length > 0) {
                    $.each(children, function (idx, val) {
                        _html += `<dd><a href="javascript:;" lay-on="logClick" title="${val.title}">${val.title}</a></dd>`
                    })
                }
                _html += `</dl></li>`
            })
            $('#nav').append(_html)
            let layFilter = $("#nav").attr('lay-filter');
            layui.element.render('nav', layFilter);

        }

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
