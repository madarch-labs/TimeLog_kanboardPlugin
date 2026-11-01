<form method="get" action="<?= $this->url->to('WorklogReportController', $scope, array_merge($action_params, array('plugin' => 'TimeLog'))) ?>">
    <div class="form-group">
        <?= $this->form->date(t('Date from'), 'start', $filters, $filters['errors']) ?>
    </div>

    <div class="form-group">
        <?= $this->form->date(t('Date to'), 'end', $filters, $filters['errors']) ?>
    </div>

    <?php if ($filters['show_projects']): ?>
        <div class="form-group">
            <label>
                <input type="checkbox" name="all_projects" value="1" <?= $filters['all_projects'] ? 'checked="checked"' : '' ?> onchange="document.getElementById('form-project_ids').disabled=this.checked;">
                <?= t('All projects') ?>
            </label>

            <select name="project_ids[]" id="form-project_ids" multiple="multiple" size="8" <?= $filters['all_projects'] ? 'disabled="disabled"' : '' ?>>
                <?php foreach ($projects as $project_id => $project_name): ?>
                    <option value="<?= $this->text->e($project_id) ?>" <?= in_array((int) $project_id, $filters['project_ids'], true) ? 'selected="selected"' : '' ?>>
                        <?= $this->text->e($project_name) ?>
                    </option>
                <?php endforeach ?>
            </select>
        </div>
    <?php endif ?>

    <?php if ($filters['show_users']): ?>
        <div class="form-group">
            <label>
                <input type="checkbox" name="all_users" value="1" <?= $filters['all_users'] ? 'checked="checked"' : '' ?> onchange="document.getElementById('form-user_ids').disabled=this.checked;">
                <?= t('All users') ?>
            </label>

            <select name="user_ids[]" id="form-user_ids" multiple="multiple" size="8" <?= $filters['all_users'] ? 'disabled="disabled"' : '' ?>>
                <?php foreach ($users as $user_id => $user_name): ?>
                    <option value="<?= $this->text->e($user_id) ?>" <?= in_array((int) $user_id, $filters['user_ids'], true) ? 'selected="selected"' : '' ?>>
                        <?= $this->text->e($user_name) ?>
                    </option>
                <?php endforeach ?>
            </select>
        </div>
    <?php endif ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-blue"><?= t('Show') ?></button>
        <a href="<?= $this->text->e($csv_url) ?>" class="btn"><?= t('Export CSV') ?></a>
    </div>
</form>
