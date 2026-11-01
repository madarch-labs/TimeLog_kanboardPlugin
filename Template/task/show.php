<?php $worklogs = $this->timelog->getAllByTask($task['id']); ?>

<details class="accordion-section" <?= empty($worklogs) ? '' : 'open' ?>>
    <summary class="accordion-title"><?= t('Time logs') ?></summary>
    <div class="accordion-content">
        <?php if ($this->user->hasProjectAccess('WorklogController', 'create', $task['project_id'])): ?>
            <div class="comment-sorting">
                <small>
                    <?= $this->modal->medium('plus', t('Log time'), 'WorklogController', 'create', array('task_id' => $task['id'], 'plugin' => 'TimeLog')) ?>
                </small>
            </div>
        <?php endif ?>

        <?= $this->render('TimeLog:worklog/table', array(
            'task' => $task,
            'worklogs' => $worklogs,
            'total' => $this->timelog->getTotalByTask($task['id']),
        )) ?>
    </div>
</details>
