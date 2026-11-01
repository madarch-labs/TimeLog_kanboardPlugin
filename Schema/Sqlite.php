<?php

namespace Kanboard\Plugin\TimeLog\Schema;

use PDO;

const VERSION = 2;

function version_1(PDO $pdo)
{
    $pdo->exec("
        CREATE TABLE timelog_worklogs (
            id                INTEGER PRIMARY KEY AUTOINCREMENT,
            task_id           INTEGER NOT NULL REFERENCES tasks(id) ON DELETE CASCADE,
            user_id           INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            work_date         DATE NOT NULL,
            hours             NUMERIC NOT NULL CHECK (hours > 0),
            comment           TEXT DEFAULT '',
            is_system         INTEGER NOT NULL DEFAULT 0,
            creator_id        INTEGER REFERENCES users(id) ON DELETE SET NULL,
            date_creation     INTEGER NOT NULL,
            date_modification INTEGER NOT NULL
        )
    ");

    $pdo->exec('CREATE INDEX timelog_worklogs_task_idx ON timelog_worklogs(task_id)');
    $pdo->exec('CREATE INDEX timelog_worklogs_user_date_idx ON timelog_worklogs(user_id, work_date)');
    $pdo->exec('CREATE INDEX timelog_worklogs_date_idx ON timelog_worklogs(work_date)');
}

function version_2(PDO $pdo)
{
    $adminId = (int) $pdo->query("SELECT id FROM users WHERE role = 'app-admin' ORDER BY id LIMIT 1")->fetchColumn();
    $now = time();

    $tasks = $pdo->query("
        SELECT t.id, t.owner_id, t.creator_id, t.date_completed, t.date_modification, t.date_creation,
               t.time_spent, COALESCE(s.spent, 0) AS subtask_spent
        FROM tasks t
        LEFT JOIN (SELECT task_id, SUM(time_spent) AS spent FROM subtasks GROUP BY task_id) s ON s.task_id = t.id
        WHERE t.time_spent - COALESCE(s.spent, 0) >= 0.01
    ")->fetchAll(PDO::FETCH_ASSOC);

    $insert = $pdo->prepare("
        INSERT INTO timelog_worklogs
            (task_id, user_id, work_date, hours, comment, is_system, creator_id, date_creation, date_modification)
        VALUES (?, ?, ?, ?, ?, 1, NULL, ?, ?)
    ");

    foreach ($tasks as $task) {
        $userId = (int) $task['owner_id'];
        if ($userId === 0) {
            $userId = (int) $task['creator_id'];
        }
        if ($userId === 0) {
            $userId = $adminId;
        }
        if ($userId === 0) {
            continue;
        }

        $hours = round((float) $task['time_spent'] - (float) $task['subtask_spent'], 2);
        if ($hours < 0.01) {
            continue;
        }

        $timestamp = (int) $task['date_completed'];
        if ($timestamp === 0) {
            $timestamp = (int) $task['date_modification'];
        }
        if ($timestamp === 0) {
            $timestamp = (int) $task['date_creation'];
        }

        $insert->execute(array(
            (int) $task['id'],
            $userId,
            gmdate('Y-m-d', $timestamp > 0 ? $timestamp : $now),
            $hours,
            'Перенесено из поля «Затрачено» задачи при установке TimeLog',
            $now,
            $now,
        ));
    }

    $pdo->exec("UPDATE settings SET value = '0' WHERE option = 'subtask_time_tracking'");
}
