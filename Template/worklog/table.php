<?php if (empty($worklogs)): ?>
    <p class="alert"><?= t('There is no time logged.') ?></p>
<?php else: ?>
    <table class="table-striped table-scrolling">
        <thead>
            <tr>
                <th class="column-20"><?= t('Date') ?></th>
                <th class="column-20"><?= t('User') ?></th>
                <th class="column-10"><?= t('Hours') ?></th>
                <th><?= t('Comment') ?></th>
                <?php if (! isset($show_actions) || $show_actions): ?>
                    <th class="column-15"><?= t('Actions') ?></th>
                <?php endif ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($worklogs as $worklog): ?>
                <tr>
                    <td><?= $this->dt->date($worklog['work_date']) ?></td>
                    <td><?= $this->text->e($worklog['user_fullname'] ?: $worklog['username']) ?></td>
                    <td><?= n($worklog['hours']) ?></td>
                    <td>
                        <?= nl2br($this->text->e($worklog['comment'])) ?>
                        <?php if ($this->timelog->isSystemRecord($worklog)): ?>
                            <span class="text-muted"><?= t('Transferred') ?></span>
                        <?php endif ?>
                    </td>
                    <?php if (! isset($show_actions) || $show_actions): ?>
                        <td>
                            <?php if ($this->timelog->canModify($worklog)): ?>
                                <?= $this->modal->medium('edit', t('Edit'), 'WorklogController', 'edit', array('task_id' => $task['id'], 'worklog_id' => $worklog['id'], 'plugin' => 'TimeLog')) ?>
                                <?= $this->modal->confirm('trash-o', t('Remove'), 'WorklogController', 'confirm', array('task_id' => $task['id'], 'worklog_id' => $worklog['id'], 'plugin' => 'TimeLog')) ?>
                            <?php endif ?>
                        </td>
                    <?php endif ?>
                </tr>
            <?php endforeach ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">
                    <strong><?= t('Total spent on time logs') ?></strong>
                </td>
                <td><strong><?= n(isset($total) ? $total : $this->timelog->getTotalByTask($task['id'])) ?></strong></td>
                <td></td>
                <?php if (! isset($show_actions) || $show_actions): ?>
                    <td></td>
                <?php endif ?>
            </tr>
        </tfoot>
    </table>
<?php endif ?>
