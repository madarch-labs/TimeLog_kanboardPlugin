<li <?= $this->app->checkMenuSelection('WorklogReportController', 'all', 'TimeLog') ?>>
    <?= $this->url->link(t('Time logs'), 'WorklogReportController', 'all', array('plugin' => 'TimeLog')) ?>
</li>
