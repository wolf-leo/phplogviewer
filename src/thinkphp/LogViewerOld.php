<?php

namespace Wolfcode\PhpLogviewer\thinkphp;

use think\helper\Str;
use think\Request;
use think\Response;
use think\response\Json;
use Wolfcode\PhpLogviewer\Base;
use Wolfcode\PhpLogviewer\LogViewerException;
use think\Env;
use think\View;

class LogViewerOld extends Base
{
    protected ?array $config;

    protected ?string $root_path;
    protected ?string $vendor_path;

    protected function initialize()
    {

        $this->root_path   = defined('ROOT_PATH') ? ROOT_PATH : Env::get('root_path');
        $this->vendor_path = defined('VENDOR_PATH') ? VENDOR_PATH : Env::get('root_path') . 'vendor/';

        $randomStr    = $this->randomStr();
        $module       = cookie('phplogviewer-ThinkPHP-module');
        $moduleLogs   = $this->getModuleLogs($module);
        $logPath      = $moduleLogs['logPath'] ?? '';
        $logPath      = addslashes($logPath);
        $logs         = $moduleLogs['logs'] ?? [];
        $this->config = $config = config('logviewer');
        $modules      = $config['modules'] ?? ['home', 'admin', 'index', 'api'];
        View::instance()->assign(compact('logs', 'modules', 'logPath', 'randomStr', 'module', 'config'));
    }

    protected function randomStr(): string
    {
        $filename = 'random.txt';
        $_path    = $this->vendor_path . $this->getPluginBasePath() . $filename;
        if (!is_file($_path)) {
            $randomStr = Str::random(16);
            @touch($_path);
            @file_put_contents($_path, $randomStr);
        }else {
            $randomStr = cookie('phplogviewer-ThinkPHP', '');
            if (empty($randomStr)) {
                $randomStr = file_get_contents($_path);
                cookie('phplogviewer-ThinkPHP', $randomStr);
            }
        }
        return $randomStr;
    }

    protected function postData(Request $request): array
    {
        if (!$request->isAjax()) return ['code' => 0];
        $params = $request->param();
        foreach ($params as $key => $param) {
            if (empty($param)) continue;
            switch ($key) {
                case 'logviewer_module';
                    $list = $this->getModuleLogs($params['logviewer_module']);
                    return ['code' => 1, 'data' => compact('list')];
                    break;
                case 'logviewer_file_path';
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
            $str = $fileObject->fgets();
            $str = mb_convert_encoding($str, 'UTF-8');
            array_push($logs, $str);
        }
        $fileObject = null;
        return $logs;
    }

    public function fetch()
    {
        if (\request()->isAjax()) {
            $result = $this->postData(request());
            $code   = $result['code'] ?? 0;
            if ($code < 1) return json(['code' => 0, 'msg' => $result['msg'] ?? '']);
            return json(['code' => 1, 'data' => $result['data'] ?? []]);
        }
        $viewBasePath = $this->getPluginBaseViewPath();
        View::instance()->config([
            'view_dir_name' => '',
            'view_path'     => $this->root_path . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR
        ]);
        View::instance()->engine->layout($viewBasePath . 'layout');
        return View::instance()->fetch($viewBasePath . 'index_old');
    }

    protected function getModuleLogs(?string $name): array
    {
        $name     = $name ?: ($this->config['default_module'] ?? 'log');
        $basePath = $this->root_path . 'runtime' . DIRECTORY_SEPARATOR;
        $logPath  = $basePath . $name . DIRECTORY_SEPARATOR;
        if (file_exists($basePath . $name . DIRECTORY_SEPARATOR . 'log')) {
            $logPath = $basePath . $name . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR;
        }
        try {
            $files           = scandir($logPath);
            $logFiles        = array_filter($files, function ($file) {
                return $file != '.' && $file != '..';
            });
            $logFiles        = array_values($logFiles);
            $logFilesLastKey = array_key_last($logFiles);
            $_logs           = [];
            foreach ($logFiles as $key => $file) {
                if (!Str::endsWith($file, '.log')) {
                    $glob = glob($logPath . $file . '/*');
                    array_map(function ($value) use ($logPath, $key, $file, $logFilesLastKey, &$_logs) {
                        $arr                     = explode($logPath . $file, $value);
                        $filename                = str_replace('/', '', $arr[1] ?? $value);
                        $_logs[$file][$filename] = ['title' => $filename, 'id' => (int)$filename];
                    }, $glob);
                }else {
                    $_logs[$name][] = ['title' => $file, 'id' => (int)$file];
                }

            }
            cookie('phplogviewer-ThinkPHP-module', $name);
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
