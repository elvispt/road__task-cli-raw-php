<?php

use JetBrains\PhpStorm\NoReturn;

const FILE_NAME = 'task-cli.json';
const EXPECTED_ARGS = [
    'add',
    'update',
    'mark-todo',
    'mark-in-progress',
    'mark-done',
    'delete',
    null, 'list', // same as not sending any
    'help',
];

function getTasks(): array
{
    if (file_exists(FILE_NAME) === false) {
        return [];
    }

    $raw = file_get_contents(FILE_NAME);
    try {
        $tasks = json_decode($raw, false, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        $tasks = null;
    }

    return $tasks?->tasks ?? [];
}

function saveTasks(array $tasks): void
{
    // sort before saving
    usort($tasks, function ($a, $b) {
        return $a->updatedAt > $b->updatedAt
            ? -1
            : 1;
    });

    $wrapper = new stdClass();
    $wrapper->tasks = $tasks;
    $rawJson = json_encode($wrapper);
    file_put_contents(FILE_NAME, $rawJson);
}

function outputTasks(array $tasks): void {
    global $argv;

    if (count($tasks) === 0) {
        echo "🤷 No tasks found!";
        return;
    }


    $status = $argv[2] ?? 'all';
    if ($status !== 'all' && in_array($status, ['todo', 'in-progress', 'done'], true) === false) {
        echo "😒 Invalid status. Should be one of: todo | in-progress | done";
    }

    foreach ($tasks as $i => $task) {
        if ($status !== 'all' && $task->status !== $status) {
            continue;
        }

        $taskId = str_pad($task->id, 3);
        $taskStatus = str_pad($task->status, 11);
        $idAndStatusPadded = str_pad($taskId . "[$taskStatus]", 17);


        echo "$idAndStatusPadded: $task->description";

        if ($i < count($tasks) - 1) {
            echo PHP_EOL;
        }
    }
}

function outputHelp(): void
{
    echo 'Usage: php task-cli.php option nArgs' . PHP_EOL;

    echo 'php task-cli.php ?list (sending list of nothing has the same effect)' . PHP_EOL;
    echo 'php task-cli.php add "task description" -> add task' . PHP_EOL;
    echo 'php task-cli.php update <ID> "task description" -> update task' . PHP_EOL;
    echo 'php task-cli.php mark-todo <ID> -> update task status to todo' . PHP_EOL;
    echo 'php task-cli.php mark-in-progress <ID> -> update task status to in-progress' . PHP_EOL;
    echo 'php task-cli.php mark-done <ID> -> update task status to done' . PHP_EOL;
    echo 'php task-cli.php delete <ID> -> delete task' . PHP_EOL;
    echo 'php task-cli.php help -> shows this help screen' . PHP_EOL;
}

function getActionFromArguments(): string
{
    global $argv;

    $option = $argv[1] ?? null;
    // check if is valid option
    $isValid = in_array($option, EXPECTED_ARGS, true);
    if ($isValid === false) {
        echo "🛑 Invalid option" . PHP_EOL;

        return 'help';
    }

    if ($option === null) {
        $option = 'list';
        return $option;
    }

    return $option;
}

$tasks = getTasks();
$action = getActionFromArguments();

function appendTask(array $tasks): array
{
    global $argv;
    $description = $argv[2];
    // obtain highest id value
    $id = 1;
    if (count($tasks) >= 1) {
        $id = array_reduce($tasks, fn ($carry, $task) => max($task->id, $carry), 1) + 1;
    }

    $task = (object) [
        'id' => $id,
        'description' => $description,
        'status' => 'todo',
        'createdAt' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        'updatedAt' => (new DateTimeImmutable())->format('Y-m-d H:i:s')
    ];

    $tasks[] = $task;

    saveTasks($tasks);

    echo "⚠️ Added new task with id $task->id";

    return $tasks;
}

function findTaskById(array $tasks, int $id): ?stdClass
{
    $taskToEdit = null;
    foreach ($tasks as $task) {
        if ($task->id === $id) {
            $taskToEdit = $task;
            break;
        }
    }

    return $taskToEdit;
}

function updateTask(array $tasks): array
{
    global $argv;

    $id = intval($argv[2]);
    $description = $argv[3];

    $taskToEdit = findTaskById($tasks, $id);
    if ($taskToEdit === null) {
        echo "😱 Task not found";
        return $tasks;
    }

    $taskToEdit->description = $description;
    $taskToEdit->updatedAt = (new DateTimeImmutable())->format('Y-m-d H:i:s');

    echo "🤞 Updated task $taskToEdit->id";

    saveTasks($tasks);

    return $tasks;
}

function markStatus(array $tasks, string $status): array
{
    global $argv;

    $id = intval($argv[2]);

    $taskToEdit = findTaskById($tasks, $id);
    if ($taskToEdit === null) {
        echo "😱 Task not found";
        return $tasks;
    }

    $taskToEdit->status = $status;
    $taskToEdit->updatedAt = (new DateTimeImmutable())->format('Y-m-d H:i:s');

    echo "🤞 Updated task $taskToEdit->id to $status";

    saveTasks($tasks);

    return $tasks;
}

function deleteTask(array $tasks): array
{
    global $argv;

    $id = intval($argv[2]);
    $taskToEdit = findTaskById($tasks, $id);
    if ($taskToEdit === null) {
        echo "😱 Task not found";
        return $tasks;
    }

    $tasks = array_values(array_filter($tasks, fn ($task) => $task->id !== $id));

    saveTasks($tasks);

    echo "🚩 Deleted task $id";

    return $tasks;
}

switch ($action) {
    case 'list': outputTasks($tasks); break;
    case 'help': outputHelp(); break;
    case 'add': $tasks = appendTask($tasks); break;
    case 'update': $tasks = updateTask($tasks); break;
    case 'mark-todo': $tasks = markStatus($tasks, 'todo'); break;
    case 'mark-in-progress': $tasks = markStatus($tasks, 'in-progress'); break;
    case 'mark-done': $tasks = markStatus($tasks, 'done'); break;
    case 'delete': $tasks = deleteTask($tasks); break;
}

echo PHP_EOL;
