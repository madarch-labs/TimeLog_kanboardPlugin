<div class="page-header">
    <h2><?= t('Edit time log') ?></h2>
</div>

<?php if ($this->timelog->isSystemRecord($worklog)): ?>
    <p class="alert alert-info"><?= t('This record was transferred from the task time spent field.') ?></p>
<?php endif ?>

<form method="post" action="<?= $this->url->href('WorklogController', 'update', array('task_id' => $task['id'], 'worklog_id' => $worklog['id'], 'plugin' => 'TimeLog')) ?>" autocomplete="off">
    <?= $this->form->csrf() ?>

    <?= $this->form->date(t('Date'), 'work_date', $values, $errors, array('autofocus')) ?>

    <?= $this->form->label(t('Hours'), 'hours') ?>
    <?= $this->form->numeric('hours', $values, $errors, array('tabindex="2"')) ?>
    <p><small class="text-muted"><?= t('Fractional values are allowed.') ?></small></p>

    <?php if ($show_user_field): ?>
        <?= $this->form->label(t('User'), 'user_id') ?>
        <?= $this->form->select('user_id', $users_list, $values, $errors, array('tabindex="3"')) ?>
    <?php else: ?>
        <?= $this->form->hidden('user_id', $values) ?>
        <p><small class="text-muted"><?= t('User') ?>: <?= $this->text->e(isset($users_list[$values['user_id']]) ? $users_list[$values['user_id']] : '') ?></small></p>
    <?php endif ?>

    <?= $this->form->label(t('Comment'), 'comment') ?>
    <?= $this->form->textarea('comment', $values, $errors, array('tabindex="4"')) ?>

    <?= $this->modal->submitButtons() ?>
</form>
