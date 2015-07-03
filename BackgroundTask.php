<?php

namespace idfly;

abstract class BackgroundTask
{

    public static function run($task, $options = [])
    {
        $isConcurrentRun =
            isset($options['concurrent']) &&
            $options['concurrent'] === true;

        if($isConcurrentRun) {
            return BackgroundTask::_concurrentRun($task, $options);
        }

        return BackgroundTask::_blockingRun($task, $options);
    }

    protected static function _concurrentRun($task, $options)
    {
        $taskDir = BackgroundTask::_getTaskDir($task);

        if(!file_exists($taskDir)) {
            mkdir($taskDir);
        }

        $taskId = BackgroundTask::_generateTaskId();

        $logFile = BackgroundTask::_getLogFile($task, $taskId);
        $statusFile = BackgroundTask::_getStatusFile($task, $taskId);

        $yiiCmd = BackgroundTask::_getYiiCommandShellSafe($task, $options);

        $flock = 'flock -s ' . escapeshellarg($taskDir) . ' bash -c';

        $flockCmd = escapeshellarg(
            "echo > " . escapeshellarg($logFile) . " && " .
            "echo > " . escapeshellarg($statusFile) . " && " .
            "$yiiCmd > " . escapeshellarg($logFile) ." 2>&1; " .
            "echo $? > " . escapeshellarg($statusFile)
        );

        $cmd = "$flock $flockCmd" . ">/dev/null 2>/dev/null &";

        exec($cmd);

        return $taskId;
    }

    protected static function _blockingRun($task, $options)
    {
        $yiiCmd = BackgroundTask::_getYiiCommandShellSafe($task, $options);

        $lockFile =  BackgroundTask::_getLockFile($task);
        $logFile = BackgroundTask::_getLogFile($task);
        $statusFile = BackgroundTask::_getStatusFile($task);

        $flock = 'flock -n ' . escapeshellarg($lockFile) . ' bash -c';

        $needToAppendToLogs =
            isset($options['appendToLogs']) &&
            $options['appendToLogs'] === true;

        if($needToAppendToLogs) {
            exec("echo `date` >> " . escapeshellarg($logFile));
            $yiiCmd .= '>';
        }

        $flockCmd = escapeshellarg(
            "echo > " . escapeshellarg($statusFile) . " && " .
            "$yiiCmd> " . escapeshellarg($logFile) ." 2>&1; " .
            "echo $? > " . escapeshellarg($statusFile)
        );

        $cmd = "$flock $flockCmd" . ">/dev/null 2>/dev/null &";

        return exec($cmd);
    }

    public static function getInfo($task, $taskId = null)
    {
        $info = [];
        $logFile = BackgroundTask::_getLogFile($task, $taskId);

        if(file_exists($logFile)) {
            $info['log'] = file_get_contents($logFile);
        }

        $statusFile = BackgroundTask::_getStatusFile($task, $taskId);

        if(file_exists($statusFile)) {
            $info['status_code'] = exec("cat $statusFile");
        }

        if(!$taskId) {
            $lockFile =  BackgroundTask::_getLockFile($task);

            if(file_exists($lockFile)) {
                $isTaskRunning =
                    exec("lsof $lockFile | grep flock | wc -l") === '1';

                $info['is_running'] = $isTaskRunning;
            }
        } else {
            $taskDir = BackgroundTask::_getTaskDir($task);

            $isTaskExists =
                file_exists($logFile) &&
                file_exists($statusFile);

            if($isTaskExists) {
                $info['processes_count'] =
                    exec("lsof $taskDir | grep flock | wc -l");
            }
        }

        return $info;
    }

    protected static function _getYiiCommandShellSafe($task, $options)
    {
        $php = 'php';
        $timeout = '';

        if(isset($options['memoryLimit'])) {
            $php .= ' -d memory_limit=' .
                escapeshellarg($options['memoryLimit']);
        }

        if(isset($options['timeout'])) {
            $timeout = 'timeout ' . escapeshellarg($options['timeout']);
        }

        $isValidArgs =
            isset($options['args']) &&
            is_array($options['args']);

        $args = '';
        if($isValidArgs) {
            foreach($options['args'] as $argument) {
                $args .= ' ' . escapeshellarg($argument);
            }
        }

        $yii = \yii::getAlias('@app/yii');

        return "$timeout $php $yii " . escapeshellarg($task) . " $args";
    }

    protected static function _getTaskDir($task)
    {
        return BackgroundTask::_getTaskFile($task) . '.async';
    }

    protected static function _getTasksPath()
    {
        return \yii::getAlias('@app/runtime/tasks');
    }

    protected static function _getTaskFile($task)
    {
        return
            BackgroundTask::_getTasksPath() . '/' .
            str_replace('/', '--', $task);
    }

    protected static function _generateTaskId()
    {
        return md5(microtime(true));
    }

    protected static function _getStatusFile($task, $taskId = null)
    {
        if($taskId === null) {
            return BackgroundTask::_getTaskFile($task) . '.status';
        }

        $taskDir = BackgroundTask::_getTaskDir($task);
        $taskFile = $taskDir . '/' . $taskId;
        $statusFile = $taskFile . '.status';

        return $statusFile;
    }

    protected static function _getLogFile($task, $taskId = null)
    {
        if($taskId === null) {
            return BackgroundTask::_getTaskFile($task) . '.log';
        }

        $taskDir = BackgroundTask::_getTaskDir($task);
        $taskFile = $taskDir . '/' . $taskId;
        $logFile = $taskFile . '.log';

        return $logFile;
    }

    protected static function _getLockFile($task)
    {
        return BackgroundTask::_getTaskFile($task) . '.lock';
    }
}
