# yii2-shell-task

The module for running yii2 console commands.

## Set

1. To the project file `composer.json` add to the `require` section:

      `"idfly/yii2-shell-task": "dev-master"`

2. To the `repositories` section:
      ```
      {
           "type": "git",
           "url": "git@bitbucket.org:idfly/yii2-shell-task.git"
      }
      ```

3. Run `composer update`

4. Add this module to the project configuration list:

      `$config['modules']['shellTask'] = ['class' => 'idfly\shellTask\Module'];`


### Description

The module provides an abstract class `idfly\ShellTask` with functions for 
launching and checking the yii2 console command status.

The command may be run in asynchronously or in blocking mode.

Method `run()` takes 2 arguments: `$task` and `$options`.

Argument `$task` contains yii2 console command name, for example 
`subscribers/send-forecast-email`.

Argument `$options` is an array with some fields (all fields are optional): 

* `timeout` - type `int`, is a limit on execution time in seconds;

* `memoryLimit` - type `string`, is a limit for memory, has a format a like
 php-commands, for example 128M;

* `args` - type `array`, the arguments for yii-command;

* `appendToLogs` - type `bool`, is an opportunity to append command`s log-file
without file rewriting;

* `concurrent` - type `bool`, command launching asynchronously;

The status of the task is checked by method `getInfo()`, which takes 2 
arguments: `task` and optional `taskId`.

`getInfo()` return the array with following fields:

* `log` - log-file text;

* `status_code` - shell-code of the task completion;

* `is_running` - in blocking call will show whether the command is executing 
at the moment;

* `processes_count` - if there is an `taskId` argument the field will contain
 the number of asynchronous processes executing at the moment;
 
While using `concurrent` option, the method `run()` returns task ID;

### Example

1. Run the task with a limit on execution time and memory:

        $options = [
            'timeout' => 10,
            'memoryLimit' => '128M',
        ];

        ShellTask::run('wares/update-similar', $options);

2. Get a result of the command execution:

        $result = ShellTask::getInfo('wares/update-similar');

3. Run two tasks asynchronously:

        $options = [
            'concurrent' => true,
            'args' => [
                '1',
            ]
        ];

        $taskOneId = ShellTask::run('do/something', $options);

        $options = [
            'concurrent' => true,
            'args' => [
                '2',
            ]
        ];

        $taskTwoId = ShellTask::run('do/something', $options);

4. Take result of first command from paragraph 3 execution:

        $result = ShellTask::getInfo('do/something', $taskOneId);
