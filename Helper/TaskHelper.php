<?php

namespace Kanboard\Plugin\TimeLog\Helper;

class TaskHelper extends \Kanboard\Helper\TaskHelper
{
    public function renderTimeSpentField(array $values, array $errors = array(), array $attributes = array())
    {
        $attributes = array_merge(array('tabindex="13"', 'readonly="readonly"'), $attributes);

        $html = $this->helper->form->label(t('Time spent'), 'time_spent');
        $html .= $this->helper->form->numeric('time_spent', $values, $errors, $attributes);
        $html .= ' '.t('hours');
        $html .= '<p><small class="text-muted">'.t('This value is calculated from subtasks and time logs.').'</small></p>';

        return $html;
    }
}
