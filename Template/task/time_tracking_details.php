<?= $this->render('task/details', array(
    'task' => $task,
    'tags' => $tags,
    'project' => $project,
    'editable' => false,
)) ?>

<?= $this->render('task/time_tracking_summary', array('task' => $task)) ?>

<div class="page-header">
    <h2><?= t('Time logs') ?></h2>
</div>

<?php if ($this->user->hasProjectAccess('WorklogController', 'create', $task['project_id'])): ?>
    <p>
        <?= $this->modal->mediumButton('plus', t('Log time'), 'WorklogController', 'create', array('task_id' => $task['id'], 'plugin' => 'TimeLog')) ?>
    </p>
<?php endif ?>

<?php $worklog_paginator = $this->timelog->getTaskPaginator($task['id']); ?>

<?= $this->render('TimeLog:worklog/table', array(
    'task' => $task,
    'worklogs' => $worklog_paginator->getCollection(),
    'total' => $this->timelog->getTotalByTask($task['id']),
)) ?>

<?= $worklog_paginator ?>
