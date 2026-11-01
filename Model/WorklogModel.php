<?php

namespace Kanboard\Plugin\TimeLog\Model;

use Kanboard\Core\Base;
use Kanboard\Event\GenericEvent;
use Kanboard\Model\ProjectModel;
use Kanboard\Model\TaskModel;
use Kanboard\Model\UserModel;

class WorklogModel extends Base
{
    const TABLE = 'timelog_worklogs';
    const EVENT_CREATE = 'timelog.worklog.create';
    const EVENT_UPDATE = 'timelog.worklog.update';
    const EVENT_DELETE = 'timelog.worklog.delete';

    public function getById($id)
    {
        return $this->db->table(self::TABLE)->eq('id', $id)->findOne();
    }

    public function getTaskQuery($task_id)
    {
        return $this->db
            ->table(self::TABLE)
            ->columns(
                self::TABLE.'.id',
                self::TABLE.'.task_id',
                self::TABLE.'.user_id',
                self::TABLE.'.work_date',
                self::TABLE.'.hours',
                self::TABLE.'.comment',
                self::TABLE.'.is_system',
                self::TABLE.'.creator_id',
                self::TABLE.'.date_creation',
                self::TABLE.'.date_modification',
                UserModel::TABLE.'.username',
                UserModel::TABLE.'.name AS user_fullname'
            )
            ->join(UserModel::TABLE, 'id', 'user_id')
            ->eq(self::TABLE.'.task_id', $task_id)
            ->orderBy(self::TABLE.'.work_date', 'DESC')
            ->orderBy(self::TABLE.'.id', 'DESC');
    }

    public function getAllByTask($task_id)
    {
        return $this->getTaskQuery($task_id)->findAll();
    }

    public function getTotalByTask($task_id)
    {
        return (float) $this->db->table(self::TABLE)->eq('task_id', $task_id)->sum('hours');
    }

    public function create(array $values)
    {
        $values = $this->filterValues($values);

        if (array_key_exists('comment', $values)) {
            $values['comment'] = (string) $values['comment'];
        }

        $values['creator_id'] = $this->userSession->getId() ?: null;
        $values['date_creation'] = time();
        $values['date_modification'] = $values['date_creation'];

        $id = $this->db->table(self::TABLE)->persist($values);

        if ($id === false) {
            return false;
        }

        $task_id = (int) $values['task_id'];
        $this->subtaskTimeTrackingModel->updateTaskTimeTracking($task_id);
        $this->dispatch(self::EVENT_CREATE, (int) $id, $task_id);

        return (int) $id;
    }

    public function update(array $values)
    {
        if (empty($values['id'])) {
            return false;
        }

        $worklog = $this->getById($values['id']);

        if (empty($worklog)) {
            return false;
        }

        $values = $this->filterValues($values);
        $values = array_merge(array(
            'task_id' => $worklog['task_id'],
            'user_id' => $worklog['user_id'],
            'work_date' => $worklog['work_date'],
            'hours' => $worklog['hours'],
            'comment' => $worklog['comment'],
        ), $values);
        $values['id'] = (int) $worklog['id'];
        $values['date_modification'] = time();

        $updateValues = $values;
        unset($updateValues['id']);
        $result = $this->db->table(self::TABLE)->eq('id', $worklog['id'])->update($updateValues);

        if ($result) {
            $this->subtaskTimeTrackingModel->updateTaskTimeTracking((int) $worklog['task_id']);
            if ((int) $values['task_id'] !== (int) $worklog['task_id']) {
                $this->subtaskTimeTrackingModel->updateTaskTimeTracking((int) $values['task_id']);
            }
            $this->dispatch(self::EVENT_UPDATE, (int) $worklog['id'], (int) $values['task_id']);
        }

        return (bool) $result;
    }

    public function remove($id)
    {
        $worklog = $this->getById($id);

        if (empty($worklog)) {
            return false;
        }

        $result = $this->db->table(self::TABLE)->eq('id', $worklog['id'])->remove();

        if ($result) {
            $this->subtaskTimeTrackingModel->updateTaskTimeTracking((int) $worklog['task_id']);
            $this->dispatch(self::EVENT_DELETE, (int) $worklog['id'], (int) $worklog['task_id']);
        }

        return (bool) $result;
    }

    public function getReportQuery(array $filters)
    {
        return $this->buildFilteredQuery($filters)
            ->columns(
                self::TABLE.'.id',
                self::TABLE.'.task_id',
                self::TABLE.'.user_id',
                self::TABLE.'.work_date',
                self::TABLE.'.hours',
                self::TABLE.'.comment',
                self::TABLE.'.is_system',
                TaskModel::TABLE.'.title AS task_title',
                TaskModel::TABLE.'.project_id',
                ProjectModel::TABLE.'.name AS project_name',
                UserModel::TABLE.'.username',
                UserModel::TABLE.'.name AS user_fullname'
            )
            ->orderBy(self::TABLE.'.work_date', 'DESC')
            ->orderBy(UserModel::TABLE.'.username', 'ASC')
            ->orderBy(ProjectModel::TABLE.'.name', 'ASC')
            ->orderBy(TaskModel::TABLE.'.id', 'ASC');
    }

    public function getTotals(array $filters)
    {
        $rows = $this->buildFilteredQuery($filters)
            ->columns(self::TABLE.'.work_date', 'SUM('.self::TABLE.'.hours) AS total_hours')
            ->groupBy(self::TABLE.'.work_date')
            ->orderBy(self::TABLE.'.work_date', 'DESC')
            ->findAll();

        $days = array();
        $total = 0.0;

        foreach ($rows as $row) {
            $hours = round((float) $row['total_hours'], 2);
            $days[] = array('date' => $row['work_date'], 'hours' => $hours);
            $total += $hours;
        }

        return array('days' => $days, 'total' => round($total, 2));
    }

    private function buildFilteredQuery(array $filters)
    {
        $query = $this->db
            ->table(self::TABLE)
            ->join(TaskModel::TABLE, 'id', 'task_id')
            ->join(ProjectModel::TABLE, 'id', 'project_id', TaskModel::TABLE)
            ->join(UserModel::TABLE, 'id', 'user_id');

        if (! empty($filters['date_from'])) {
            $query->gte(self::TABLE.'.work_date', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->lte(self::TABLE.'.work_date', $filters['date_to']);
        }

        if (! empty($filters['project_ids'])) {
            $query->in(TaskModel::TABLE.'.project_id', $filters['project_ids']);
        }

        if (! empty($filters['user_ids'])) {
            $query->in(self::TABLE.'.user_id', $filters['user_ids']);
        }

        return $query;
    }

    private function filterValues(array $values)
    {
        return array_intersect_key($values, array_flip(array(
            'task_id',
            'user_id',
            'work_date',
            'hours',
            'comment',
        )));
    }

    private function dispatch($eventName, $id, $task_id)
    {
        $worklog = $this->getById($id);
        $event = new GenericEvent(array('worklog' => $worklog, 'task_id' => $task_id));
        $this->dispatcher->dispatch($event, $eventName);
    }
}
