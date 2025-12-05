<?php

namespace Wolfcode\PhpLogviewer\laravel;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\FileViewFinder;
use Wolfcode\PhpLogviewer\Base;
use Wolfcode\PhpLogviewer\LogViewerException;
use Illuminate\Support\Facades\View;

class LogViewer extends Base
{

    protected function initialize()
    {
        $randomStr  = $this->randomStr();
        $module     = '';
        $moduleLogs = $this->getModuleLogs();
        $logPath    = $moduleLogs['logPath'] ?? '';
        $logPath    = addslashes($logPath);
        $logs       = $moduleLogs['logs'] ?? [];
        $config     = config('logviewer', []);
        $modules    = $config['modules'] ?? ['home', 'admin', 'index', 'api'];
        View::share(compact('logs', 'modules', 'logPath', 'randomStr', 'module', 'config'));
    }

    protected function randomStr(): string
    {
        $cacheKey = 'phplogviewer_' . session('laravel_session');
        $filename = 'random.txt';
        $_path    = base_path('vendor') . DIRECTORY_SEPARATOR . $this->getPluginBasePath() . $filename;
        if (!is_file($_path)) {
            $randomStr = Str::random(16);
            @touch($_path);
            @file_put_contents($_path, $randomStr);
        }else {
            $randomStr = Cache::get($cacheKey);
            if (empty($randomStr)) {
                $randomStr = file_get_contents($_path);
                Cache::set($cacheKey, $randomStr);
            }
        }
        if (\request()->ajax()) {
            $result = $this->postData(request());
            $code   = $result['code'] ?? 0;
            if ($code < 1) throw new LogViewerException($result['msg'] ?? '', 0);
            exit(json_encode(['code' => 1, 'data' => $result['data'] ?? []], JSON_UNESCAPED_UNICODE));
        }
        return $randomStr;
    }

    protected function postData(Request $request): array
    {
        if (!$request->ajax()) return ['code' => 0];
        $params = $request->all();
        foreach ($params as $key => $param) {
            if (empty($param)) continue;
            switch ($key) {
                case 'logviewer_module':
                    $list = $this->getModuleLogs($params['logviewer_module']);
                    return ['code' => 1, 'data' => compact('list')];
                    break;
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
        $viewBasePath = $this->getPluginBaseViewPath('laravel');
        $paths        = [base_path() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR];
        //设置视图路径
        View::setFinder(new FileViewFinder(App::make('files'), $paths));
        return view($viewBasePath . 'index');
    }

    protected function getModuleLogs(): array
    {
        $logPath = storage_path() . DIRECTORY_SEPARATOR . 'logs';
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
