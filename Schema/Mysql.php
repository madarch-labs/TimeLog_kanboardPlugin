<?php

namespace Kanboard\Plugin\TimeLog\Schema;

use PDO;

const VERSION = 2;

function version_1(PDO $pdo)
{
    $pdo->exec("
        CREATE TABLE timelog_worklogs (
            id                INT AUTO_INCREMENT PRIMARY KEY,
            task_id           INT NOT NULL,
            user_id           INT NOT NULL,
            work_date         DATE NOT NULL,
            hours             DECIMAL(8,2) NOT NULL,
            comment           TEXT,
            is_system         TINYINT(1) NOT NULL DEFAULT 0,
            creator_id        INT NULL,
            date_creation     BIGINT NOT NULL,
            date_modification BIGINT NOT NULL,
            CONSTRAINT timelog_worklogs_task_fk FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            CONSTRAINT timelog_worklogs_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT timelog_worklogs_creator_fk FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE SET NULL,
            CONSTRAINT timelog_worklogs_hours_check CHECK (hours > 0)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
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

    $pdo->exec("UPDATE settings SET value = '0' WHERE `option` = 'subtask_time_tracking'");
}
