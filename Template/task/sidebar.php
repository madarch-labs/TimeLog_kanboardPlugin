<?php if ($this->user->hasProjectAccess('WorklogController', 'create', $task['project_id'])): ?>
    <li>
        <?= $this->modal->medium('clock-o', t('Log time'), 'WorklogController', 'create', array('task_id' => $task['id'], 'plugin' => 'TimeLog')) ?>
    </li>
<?php endif ?>
