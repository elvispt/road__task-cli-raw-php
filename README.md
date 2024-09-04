# task-cli (raw-php)

Example project of using raw php (framework free) to create a command line application to manage tasks.

List tasks:
```shell
$ php task-cli.php list
# or
$ php task-cli.php
```

Add task:
```shell
$ php task-cli.php add "task description"
```

Update task:
```shell
$ php task-cli.php update 1 "task description"
```

Update status of task to **todo**:
```shell
$ php task-cli.php mark-todo 1 
```

Update status of task to **in-progress**:
```shell
$ php task-cli.php mark-in-progress 1
```

Update status of task to **done**:
```shell
$ php task-cli.php mark-done 1
```

Delete task:
```shell
$ php task-cli.php delete 1
```

Help screen:
```shell
$ php task-cli.php help
```
