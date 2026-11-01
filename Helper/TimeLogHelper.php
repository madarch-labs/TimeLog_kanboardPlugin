<?php

namespace Kanboard\Plugin\TimeLog\Helper;

use Kanboard\Core\Base;

class TimeLogHelper extends Base
{
    public function getAllByTask($task_id)
    {
        return $this->worklogModel->getAllByTask($task_id);
    }

    public function getTotalByTask($task_id)
    {
        return $this->worklogModel->getTotalByTask($task_id);
    }

    public function getTaskPaginator($task_id)
    {
        return $this->paginator
            ->setUrl('TaskViewController', 'timetracking', array('task_id' => $task_id))
            ->setMax(15)
            ->setQuery($this->worklogModel->getTaskQuery($task_id))
            ->calculate();
    }

    public function canModify(array $worklog)
    {
        return $this->worklogAccessModel->canModify($worklog);
    }

    public function isSystemRecord(array $worklog)
    {
        return $this->worklogAccessModel->isSystemRecord($worklog);
    }

    public function getAllowedUsers(array $task)
    {
        return $this->worklogAccessModel->getAllowedUsers($task);
    }

    public function showUserField(array $task)
    {
        $users = $this->getAllowedUsers($task);

        return count($users) > 1 || ! isset($users[$this->userSession->getId()]);
    }
}
