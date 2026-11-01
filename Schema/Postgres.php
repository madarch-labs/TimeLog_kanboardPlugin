<?php

namespace Kanboard\Plugin\TimeLog\Schema;

use PDO;

const VERSION = 2;

function version_1(PDO $pdo)
{
    $pdo->exec("
        CREATE TABLE timelog_worklogs (
            id                SERIAL PRIMARY KEY,
            task_id           INTEGER NOT NULL REFERENCES tasks(id) ON DELETE CASCADE,
            user_id           INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            work_date         DATE NOT NULL,
            hours             NUMERIC(8,2) NOT NULL CHECK (hours > 0),
            comment           TEXT DEFAULT '',
            is_system         BOOLEAN NOT NULL DEFAULT FALSE,
            creator_id        INTEGER REFERENCES users(id) ON DELETE SET NULL,
            date_creation     BIGINT NOT NULL,
            date_modification BIGINT NOT NULL
        )
    ");

    $pdo->exec('CREATE INDEX timelog_worklogs_task_idx ON timelog_worklogs(task_id)');
    $pdo->exec('CREATE INDEX timelog_worklogs_user_date_idx ON timelog_worklogs(user_id, work_date)');
    $pdo->exec('CREATE INDEX timelog_worklogs_date_idx ON timelog_worklogs(work_date)');
}

function version_2(PDO $pdo)
{
    $pdo->exec("
        INSERT INTO timelog_worklogs
            (task_id, user_id, work_date, hours, comment, is_system, creator_id, date_creation, date_modification)
        SELECT t.id,
               COALESCE(NULLIF(t.owner_id, 0), NULLIF(t.creator_id, 0),
                        (SELECT id FROM users WHERE role = 'app-admin' ORDER BY id LIMIT 1)),
               to_timestamp(COALESCE(NULLIF(t.date_completed, 0), NULLIF(t.date_modification, 0), t.date_creation))::date,
               ROUND((t.time_spent - COALESCE(s.spent, 0))::numeric, 2),
               'Перенесено из поля «Затрачено» задачи при установке TimeLog',
               TRUE, NULL,
               EXTRACT(EPOCH FROM now())::bigint, EXTRACT(EPOCH FROM now())::bigint
        FROM tasks t
        LEFT JOIN (SELECT task_id, SUM(time_spent) AS spent FROM subtasks GROUP BY task_id) s ON s.task_id = t.id
        WHERE t.time_spent - COALESCE(s.spent, 0) >= 0.01
    ");

    $pdo->exec("UPDATE settings SET value = '0' WHERE option = 'subtask_time_tracking'");
}
