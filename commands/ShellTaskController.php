<?php

namespace idfly\shellTask\commands;

class ShellTaskController extends \yii\console\Controller
{
    public function actionRun($task, $args)
    {
        $options = [
            'args' => $args,
        ];

        return \idfly\shellTask\ShellTask::run($task, $options);
    }
}
