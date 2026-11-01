<div class="page-header">
    <h2><?= t('Remove a time log') ?></h2>
</div>

<div class="confirm">
    <p class="alert alert-info">
        <?= t('Do you really want to remove this time log?') ?>
    </p>

    <?php if ($this->timelog->isSystemRecord($worklog)): ?>
        <p class="alert alert-info"><?= t('This record was transferred from the task time spent field.') ?></p>
    <?php endif ?>

    <ul>
        <li><?= t('Date') ?>: <strong><?= $this->dt->date($worklog['work_date']) ?></strong></li>
        <li><?= t('Hours') ?>: <strong><?= n($worklog['hours']) ?></strong></li>
        <?php if (! empty($worklog['comment'])): ?>
            <li><?= t('Comment') ?>: <strong><?= nl2br($this->text->e($worklog['comment'])) ?></strong></li>
        <?php endif ?>
    </ul>

    <?= $this->modal->confirmButtons(
        'WorklogController',
        'remove',
        array('task_id' => $task['id'], 'worklog_id' => $worklog['id'], 'plugin' => 'TimeLog')
    ) ?>
</div>
