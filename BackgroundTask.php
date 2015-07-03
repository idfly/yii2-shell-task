<?php

namespace idfly\components;

class BackgroundTask
{

    public static function run($task, $options = [])
    {
        if(isset($options['concurrent']) &&
            $options['concurrent'] === true) {
            return BackgroundTask::_concurrentRun($task, $options);
        }

        return BackgroundTask::_blockingRun($task, $options);
    }

    static function _concurrentRun($task, $options)
    {
        $taskDir = BackgroundTask::_getTaskDir($task);

        if(!file_exists($taskDir)) {
            mkdir($taskDir);
        }

        $taskId = BackgroundTask::_generateTaskId();

        $taskFile = $taskDir . '/' . $taskId;

        $logFile = BackgroundTask::_getLogFile($task, $taskId);
        $statusFile = BackgroundTask::_getStatusFile($task, $taskId);

        $yiiCmd = BackgroundTask::_getYiiCommand($task, $options);

        $cmd = "flock -s $taskDir bash -c " .
            "'echo > $logFile && echo > $statusFile && " .
            "$yiiCmd > $logFile 2>&1; echo $? > $statusFile '" .
            ">/dev/null 2>/dev/null &";

        exec($cmd);

        return $taskId;
    }

    static function _blockingRun($task, $options)
    {
        $yiiCmd = BackgroundTask::_getYiiCommand($task, $options);

        $taskFile = BackgroundTask::_getTaskFile($task);
        $logFile = BackgroundTask::_getLogFile($task);

        $statusFile = BackgroundTask::_getStatusFile($task);

        $lockFile =  $taskFile . '.lock';

        $cmd = "flock -n $lockFile bash -c " .
            "'echo > $logFile && echo > $statusFile && " .
            "$yiiCmd > $logFile 2>&1; echo $? > $statusFile '" .
            ">/dev/null 2>/dev/null &";

        return exec($cmd);
    }

    public static function getTaskProcessesCount($task)
    {
        $taskDir = BackgroundTask::_getTaskDir($task);

        return exec("lsof $taskDir | grep flock | wc -l");
    }

    public static function getTaskLogs($task, $taskId = null)
    {
        $logFile = BackgroundTask::_getLogFile($task, $taskId);

        if(!file_exists($logFile)) {
            return false;
        }

        return file_get_contents($logFile);
    }

    public static function getTaskStatus($task, $taskId = null)
    {
        $statusFile = BackgroundTask::_getStatusFile($task, $taskId);

        $code = exec("cat $statusFile");

        if($code === '') {
            return 'running';
        }

        if($code === '0') {
            return 'completed';
        }

        return "error code $code";
    }

    static function _getYiiCommand($task, $options)
    {
        $php = 'php';
        $timeout = '';

        if(isset($options['mem_limit'])) {
            $php .= ' -d memory_limit=' . escapeshellarg($options['mem_limit']);
        }

        if(isset($options['timeout'])) {
            $timeout = 'timeout ' . escapeshellarg($options['timeout']);
        }

        $yii = \yii::getAlias('@app/yii');

        return "$timeout $php $yii " . escapeshellarg($task);
    }

    static function _getTaskDir($task)
    {
        return BackgroundTask::_getTaskFile($task);
    }

    static function _getTasksPath()
    {
        return \yii::getAlias('@app/runtime/tasks');
    }

    static function _getTaskFile($task)
    {
        return BackgroundTask::_getTasksPath() . '/' . md5($task);
    }

    static function _generateTaskId()
    {
        return md5(microtime(true));
    }

    static function _getStatusFile($task, $taskId = null)
    {
        if($taskId === null) {
            return BackgroundTask::_getTaskFile($task) . '.status';
        }

        $taskDir = BackgroundTask::_getTaskDir($task);
        $taskFile = $taskDir . '/' . $taskId;
        $statusFile = $taskFile . '.status';

        return $statusFile;
    }

    static function _getLogFile($task, $taskId = null)
    {
        if($taskId === null) {
            return BackgroundTask::_getTaskFile($task) . '.log';
        }

        $taskDir = BackgroundTask::_getTaskDir($task);
        $taskFile = $taskDir . '/' . $taskId;
        $logFile = $taskFile . '.log';

        return $logFile;
    }
}
