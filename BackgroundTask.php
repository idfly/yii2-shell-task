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

    }

    static function _blockingRun($task, $options)
    {
        $yiiCmd = BackgroundTask::_getYiiCommand($task, $options);

        $lockFile = BackgroundTask::_getTaskLockFile($task);
        $logFile = BackgroundTask::_getTaskLogFile($task);
        $statusFile = BackgroundTask::_getTaskStatusFile($task);

        $cmd = "flock -n $lockFile bash -c " .
            "'$yiiCmd > $logFile 2>&1; echo $? > $statusFile " .
            ">/dev/null 2>/dev/null &";

        if(file_exists($logFile)) {
            unlink($logFile);
        }

        if(file_exists($statusFile)) {
            unlink($statusFile);
        }

        return exec($cmd);
    }

    public static function getLogs($task)
    {
        $logFile = BackgroundTask::_getTaskLogFile($task);

        if(!file_exists($logFile)) {
            return false;
        }

        return file_get_contents($logFile);
    }

    public static function getStatus($task)
    {
        $statusFile = BackgroundTask::_getTaskStatusFile($task);

        $code = exec("cat $statusFile");

        if($code === '') {
            return null;
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
            $php .= ' -d memory_limit=' . $options['mem_limit'];
        }

        if(isset($options['timeout'])) {
            $timeout = 'timeout ' . $options['timeout'];
        }

        $yii = \yii::getAlias('@app/yii');

        return "$timeout $php $yii " . escapeshellarg($task);
    }

    static function _getTasksPath()
    {
        return \yii::getAlias('@app/runtime/tasks');
    }

    static function _getTaskFile($task)
    {
        return BackgroundTask::_getTasksPath() . '/' . md5($task);
    }

    static function _getTaskLockFile($task)
    {
        return BackgroundTask::_getTaskFile($task) . '.lock';
    }

    static function _getTaskLogFile($task)
    {
        return BackgroundTask::_getTaskFile($task) . '.log';
    }

    static function _getTaskStatusFile($task)
    {
        return BackgroundTask::_getTaskFile($task) . '.status';
    }
}
