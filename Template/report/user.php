<div class="page-header">
    <h2><?= t('Time logs') ?></h2>
</div>

<?= $this->render('TimeLog:report/filters', array(
    'scope' => $scope,
    'filters' => $filters,
    'projects' => $projects,
    'users' => $users,
    'action_params' => $action_params,
    'csv_url' => $csv_url,
)) ?>

<h3><?= t('Report for %s', $this->user->getFullname($user)) ?></h3>

<?= $this->render('TimeLog:report/table', array(
    'records' => $records,
    'totals' => $totals,
    'filters' => $filters,
)) ?>
