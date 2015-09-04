# yii2-shell-task

Модуль для запуска команд yii2.

## Установка

1. В проектный `composer.json` добавить в секцию `require`:

        "idfly/yii2-shell-task": "dev-master"

2. В секцию `repositories`:

        {
            "type": "git",
            "url": "git@bitbucket.org:idfly/yii2-shell-task.git"
        }

3. Выполнить `composer update`

4. Добавить модуль в проектный конфиг:

       `$config['modules']['shellTask'] = ['class' => 'idfly\shellTask\Module'];`

### Описание

Модуль предоставляет абстрактный класс `idfly\ShellTask` c функциями для
запуска и проверки статуса команд yii2.

Команды могут быть запущены в асинхронном или блокирующем режиме.

Метод `run()` принимает 2 аргумента: `$task` и `$options`.

Аргумент `$task` содержит имя команды yii2,
например `subscribers/send-forecast-email`.

Аргумент `$options` является массивом с полями (все поля необязательные):

* `timeout` - тип `int`, ограничение на время выполнения в секундах;

* `memoryLimit` - тип `string`, ограничение на память, формат как для команды php, например 128M;

* `args` - тип `array`, аргументы для команды yii;

* `appendToLogs` - тип `bool`, возможность дописывать файл лога команды без перетирания файла;

* `concurrent` - тип `bool`, запуск команды в асинхронном режиме.

Статус задачи проверяется методом `getInfo()`, который принимает
2 аргумента: `task` и необязательный `taskId`.

`getInfo()` возвращает массив со следующими полями:

* `log` - текст файла-лога;

* `status_code` - shell-код завершения процесса задачи;

* `is_running` - при блокирующем вызове покажет, выполняется ли команда в данный момент;

* `processes_count` - при передаче аргумента `taskId` поле будет содержать
количество выполняемых в данный момент асинхронных процессов;

При использовании опции `concurrent`, метод `run()` вернёт идентификатор
задачи.

### Использование

1. Выполнить задачу с лимитом по времени выполнения и памяти:

        $options = [
            'timeout' => 10,
            'memoryLimit' => '128M',
        ];

        ShellTask::run('wares/update-similar', $options);

2. Получить результат выполнения команды:

        $result = ShellTask::getInfo('wares/update-similar');

3. Выполнить две задачи асинхронно :

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

4. Получить результат выполнения первой команды из пункта 3:

        $result = ShellTask::getInfo('do/something', $taskOneId);
