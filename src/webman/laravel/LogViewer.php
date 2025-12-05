<?php

namespace Wolfcode\PhpLogviewer\webman\laravel;

use Illuminate\Support\Str;
use Jenssegers\Blade\Blade as BladeView;
use support\View;
use support\Request;
use support\Response;
use Wolfcode\PhpLogviewer\Base;
use Wolfcode\PhpLogviewer\LogViewerException;

class LogViewer extends Base
{
    protected static array  $vars      = [];
    protected static string $randomStr = '';

    protected function initialize()
    {
        $randomStr    = $this->randomStr();
        $moduleLogs   = $this->getModuleLogs();
        $logPath      = $moduleLogs['logPath'] ?? '';
        $logPath      = addslashes($logPath);
        $logs         = $moduleLogs['logs'] ?? [];
        $config       = config('logviewer', []);
        $module       = 'logs';
        $modules      = $config['modules'] ?? ['home', 'admin', 'index', 'api'];
        static::$vars = compact('logs', 'modules', 'logPath', 'randomStr', 'module', 'config');
    }

    protected function randomStr(): string
    {
        $filename = 'random.txt';
        $_path    = base_path('vendor') . DIRECTORY_SEPARATOR . $this->getPluginBasePath() . $filename;
        if (!is_file($_path)) {
            $randomStr = Str::random(16);
            @touch($_path);
            @file_put_contents($_path, $randomStr);
            static::$randomStr = $randomStr;
        }else {
            if (empty($randomStr)) {
                $randomStr         = file_get_contents($_path);
                static::$randomStr = $randomStr;
            }
        }
        return $randomStr;
    }

    protected function postData(Request $request): array
    {
        if (!$request->isAjax()) return ['code' => 0];
        $params = $request->all();
        foreach ($params as $key => $param) {
            if (empty($param)) continue;
            switch ($key) {
                case 'logviewer_file_path':
                    $file = $params['logviewer_file_path'] ?? '';
                    if (empty($file)) return ['code' => 0, 'msg' => '文件不能为空'];
                    try {
                        $info = $this->getFileLogs($file);
                    }catch (\Throwable $exception) {
                        return ['code' => 0, 'msg' => $exception->getMessage()];
                    }
                    return ['code' => 1, 'data' => compact('info')];
                    break;
                default:
                    break;
            }
        }
        return ['code' => 0, 'msg' => '未知错误'];
    }

    protected function getFileLogs(string $filePath): array
    {
        $fileObject = new \SplFileObject($filePath);
        $logs       = [];
        while (!$fileObject->eof()) {
            array_push($logs, $fileObject->fgets());
        }
        $fileObject = null;
        return $logs;
    }

    public function fetch()
    {
        $request  = \request();
        $response = \response();
        if ($request->isAjax()) {
            $result = $this->postData(\request());
            $code   = $result['code'] ?? 0;
            if ($code < 1) return $response->withBody(json_encode(['code' => 0, 'msg' => $result['msg'] ?? '']));
            return $response->withBody(json_encode(['code' => 1, 'msg' => '', 'data' => $result['data'] ?? []]));
        }

        $viewBasePath = $this->getPluginBaseViewPath('webman' . DIRECTORY_SEPARATOR . 'laravel');
        $view_path    = base_path() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . $viewBasePath;
        $content      = (new BladeView($view_path, runtime_path() . '/views'))->render('index', static::$vars);
        static::$vars = [];
        return $content;
    }

    protected function getModuleLogs(): array
    {
        $logPath = runtime_path() . DIRECTORY_SEPARATOR . 'logs';
        try {
            $files           = scandir($logPath);
            $logFiles        = array_filter($files, function ($file) {
                return $file != '.' && $file != '..';
            });
            $logFiles        = array_values($logFiles);
            $logFilesLastKey = array_key_last($logFiles);
            $_logs           = [];
            foreach ($logFiles as $key => $file) {
                $_logs['logs'][] = ['title' => $file, 'id' => (int)($key + 1)];
            }
        }catch (\Throwable $exception) {
            $_logs = [];
        }
        if ($_logs) krsort($_logs);
        $logs     = [];
        $firstKey = array_key_first($_logs);
        foreach ($_logs as $key => $log) {
            rsort($log);
            $logs[$key] = ['title' => $key, 'id' => 0, 'children' => array_values($log), 'spread' => $key == $firstKey];
        }
        $logs = array_values($logs);
        return compact('logPath', 'logs');
    }

}
