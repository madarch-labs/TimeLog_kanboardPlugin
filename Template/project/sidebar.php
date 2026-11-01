<?php if ($this->user->isAdmin() || $this->projectRole->getProjectUserRole($project['id']) === \Kanboard\Core\Security\Role::PROJECT_MANAGER): ?>
<li <?= $this->app->checkMenuSelection('WorklogReportController', 'project', 'TimeLog') ?>>
    <?= $this->url->link(t('Time logs'), 'WorklogReportController', 'project', array('project_id' => $project['id'], 'plugin' => 'TimeLog')) ?>
</li>
<?php endif ?>
