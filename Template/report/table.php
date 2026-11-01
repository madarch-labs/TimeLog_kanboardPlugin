<?php if (empty($records)): ?>
    <p class="alert"><?= t('There is no time logged for this period.') ?></p>
<?php else: ?>
    <?php
    $day_totals = array();
    foreach ($totals['days'] as $day) {
        $day_totals[$day['date']] = $day['hours'];
    }
    $columns = 3 + ($filters['show_users'] ? 1 : 0) + ($filters['show_projects'] ? 1 : 0);
    $current_day = null;
    ?>
    <table class="table-fixed table-scrolling">
        <thead>
            <tr>
                <th><?= t('Task') ?></th>
                <?php if ($filters['show_users']): ?>
                    <th class="column-15"><?= t('User') ?></th>
                <?php endif ?>
                <?php if ($filters['show_projects']): ?>
                    <th class="column-15"><?= t('Project') ?></th>
                <?php endif ?>
                <th class="column-10"><?= t('Hours') ?></th>
                <th><?= t('Comment') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $record): ?>
                <?php if ($current_day !== $record['work_date']): ?>
                    <?php $current_day = $record['work_date']; ?>
                    <tr class="table-row-header">
                        <td colspan="<?= $columns ?>">
                            <strong><?= $this->dt->date($current_day) ?></strong>
                            <span class="pull-right"><?= t('Total for the day') ?>: <?= n(isset($day_totals[$current_day]) ? $day_totals[$current_day] : 0) ?> <?= t('hours') ?></span>
                        </td>
                    </tr>
                <?php endif ?>
                <tr>
                    <td>
                        <?= $this->url->link(
                            '#'.$record['task_id'].' '.$this->text->e($record['task_title']),
                            'TaskViewController',
                            'show',
                            array('task_id' => $record['task_id'])
                        ) ?>
                    </td>
                    <?php if ($filters['show_users']): ?>
                        <td><?= $this->text->e($record['user_fullname'] ?: $record['username']) ?></td>
                    <?php endif ?>
                    <?php if ($filters['show_projects']): ?>
                        <td><?= $this->text->e($record['project_name']) ?></td>
                    <?php endif ?>
                    <td><?= n($record['hours']) ?></td>
                    <td>
                        <?= nl2br($this->text->e($record['comment'])) ?>
                        <?php if ($this->timelog->isSystemRecord($record)): ?>
                            <span class="text-muted"><?= t('Transferred') ?></span>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="<?= $columns - 2 ?>"><strong><?= t('Total for the period') ?></strong></td>
                <td><strong><?= n($totals['total']) ?></strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
<?php endif ?>
