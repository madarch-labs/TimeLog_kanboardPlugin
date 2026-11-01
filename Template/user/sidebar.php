<?php if ($this->user->isAdmin() || $this->user->isCurrentUser($user['id'])): ?>
    <li <?= $this->app->checkMenuSelection('WorklogReportController', 'user', 'TimeLog') ?>>
        <?= $this->url->link(t('Time logs'), 'WorklogReportController', 'user', array('user_id' => $user['id'], 'plugin' => 'TimeLog')) ?>
    </li>
<?php endif ?>
