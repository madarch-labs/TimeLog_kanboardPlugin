<?php

namespace Kanboard\Plugin\TimeLog\Api;

use Kanboard\Api\Authorization\TaskAuthorization;
use Kanboard\Api\Procedure\BaseProcedure;

class WorklogProcedure extends BaseProcedure
{
    public function createWorklog($task_id, $hours, $work_date, $user_id = null, $comment = '')
    {
        TaskAuthorization::getInstance($this->container)->check($this->getClassName(), 'createWorklog', $task_id);

        $task = $this->taskFinderModel->getDetails($task_id);

        if (empty($task)) {
            return false;
        }

        $user_id = $this->resolveUserId($user_id);

        if ($user_id === false) {
            return false;
        }

        if (! $this->worklogAccessModel->canChooseUser($task, $user_id)) {
            return false;
        }

        $values = array(
            'task_id' => (int) $task_id,
            'user_id' => $user_id,
            'work_date' => $work_date,
            'hours' => $hours,
            'comment' => $comment,
        );

        list($valid) = $this->worklogValidator->validateCreation($values);

        return $valid ? $this->worklogModel->create($values) : false;
    }

    public function updateWorklog($id, $hours = null, $work_date = null, $user_id = null, $comment = null)
    {
        $worklog = $this->worklogModel->getById($id);

        if (empty($worklog)) {
            return false;
        }

        TaskAuthorization::getInstance($this->container)->check($this->getClassName(), 'updateWorklog', $worklog['task_id']);

        if (! $this->worklogAccessModel->canModify($worklog)) {
            return false;
        }

        $values = array(
            'id' => (int) $worklog['id'],
            'task_id' => (int) $worklog['task_id'],
            'user_id' => (int) $worklog['user_id'],
            'work_date' => $worklog['work_date'],
            'hours' => $worklog['hours'],
            'comment' => $worklog['comment'],
        );

        if ($hours !== null) {
            $values['hours'] = $hours;
        }

        if ($work_date !== null) {
            $values['work_date'] = $work_date;
        }

        if ($user_id !== null) {
            $values['user_id'] = (int) $user_id;
        }

        if ($comment !== null) {
            $values['comment'] = $comment;
        }

        if ((int) $values['user_id'] !== (int) $worklog['user_id']) {
            $task = $this->taskFinderModel->getDetails($worklog['task_id']);

            if (empty($task) || ! $this->worklogAccessModel->canChooseUser($task, (int) $values['user_id'])) {
                return false;
            }
        }

        list($valid) = $this->worklogValidator->validateModification($values);

        return $valid ? $this->worklogModel->update($values) : false;
    }

    public function removeWorklog($id)
    {
        $worklog = $this->worklogModel->getById($id);

        if (empty($worklog)) {
            return false;
        }

        TaskAuthorization::getInstance($this->container)->check($this->getClassName(), 'removeWorklog', $worklog['task_id']);

        if (! $this->worklogAccessModel->canModify($worklog)) {
            return false;
        }

        return $this->worklogModel->remove($worklog['id']);
    }

    public function getWorklog($id)
    {
        $worklog = $this->worklogModel->getById($id);

        if (empty($worklog)) {
            return false;
        }

        TaskAuthorization::getInstance($this->container)->check($this->getClassName(), 'getWorklog', $worklog['task_id']);

        return $worklog;
    }

    public function getTaskWorklogs($task_id)
    {
        TaskAuthorization::getInstance($this->container)->check($this->getClassName(), 'getTaskWorklogs', $task_id);

        return $this->worklogModel->getAllByTask($task_id);
    }

    public function getWorklogReport($date_from, $date_to, $project_ids = array(), $user_ids = array())
    {
        $from = $this->dateParser->getTimestamp($date_from);
        $to = $this->dateParser->getTimestamp($date_to);

        if ($from <= 0 || $to <= 0 || $from > $to) {
            return false;
        }

        $project_ids = $this->normalizeIds($project_ids);
        $user_ids = $this->normalizeIds($user_ids);
        $filters = array(
            'date_from' => date('Y-m-d', $from),
            'date_to' => date('Y-m-d', $to),
        );

        if ($this->userSession->isLogged() && ! $this->userSession->isAdmin()) {
            $user_id = (int) $this->userSession->getId();
            $manager_projects = $this->worklogAccessModel->getAllowedProjects($user_id, true);

            if (! empty($manager_projects)) {
                $allowed_projects = array_map('intval', array_keys($manager_projects));

                if (! empty($user_ids)) {
                    $filters['user_ids'] = $user_ids;
                }
            } else {
                $allowed_projects = array_map('intval', array_keys($this->worklogAccessModel->getAllowedProjects($user_id)));
                $filters['user_ids'] = array($user_id);
            }

            if (! empty($project_ids)) {
                $project_ids = array_values(array_intersect($project_ids, $allowed_projects));
            } else {
                $project_ids = $allowed_projects;
            }

            if (empty($project_ids)) {
                $project_ids = array(0);
            }

            $filters['project_ids'] = $project_ids;
        } else {
            if (! empty($project_ids)) {
                $filters['project_ids'] = $project_ids;
            }

            if (! empty($user_ids)) {
                $filters['user_ids'] = $user_ids;
            }
        }

        return array(
            'records' => $this->worklogModel->getReportQuery($filters)->findAll(),
            'totals' => $this->worklogModel->getTotals($filters),
        );
    }

    protected function resolveUserId($user_id)
    {
        if (! $this->userSession->isLogged()) {
            return empty($user_id) ? false : (int) $user_id;
        }

        return empty($user_id) ? (int) $this->userSession->getId() : (int) $user_id;
    }

    protected function normalizeIds($values)
    {
        if (! is_array($values)) {
            $values = empty($values) ? array() : array($values);
        }

        return array_map('intval', $values);
    }
}
