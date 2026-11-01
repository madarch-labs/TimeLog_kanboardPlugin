<?php

namespace Kanboard\Plugin\TimeLog\Model;

class SubtaskTimeTrackingModel extends \Kanboard\Model\SubtaskTimeTrackingModel
{
    public function toggleTimer($subtask_id, $user_id, $status)
    {
        return false;
    }

    public function logStartTime($subtask_id, $user_id)
    {
        return false;
    }

    public function logEndTime($subtask_id, $user_id)
    {
        return false;
    }

    public function calculateSubtaskTime($task_id)
    {
        $values = parent::calculateSubtaskTime($task_id);

        if (! is_array($values)) {
            $values = array('time_spent' => 0, 'time_estimated' => 0);
        }

        $values['time_spent'] = (float) $values['time_spent'] + $this->worklogModel->getTotalByTask($task_id);
        $values['time_estimated'] = (float) $values['time_estimated'];

        return $values;
    }
}
