<?php

namespace Kanboard\Plugin\TimeLog\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Core\Controller\AccessForbiddenException;
use Kanboard\Core\Controller\PageNotFoundException;

class WorklogController extends BaseController
{
    public function create(array $values = array(), array $errors = array())
    {
        $task = $this->getTask();

        if (empty($values)) {
            $values = $this->prepareValues($task);
        }

        $this->response->html($this->template->render('TimeLog:worklog/create', array(
            'task' => $task,
            'values' => $values,
            'errors' => $errors,
            'users_list' => $this->helper->timelog->getAllowedUsers($task),
            'show_user_field' => $this->helper->timelog->showUserField($task),
        )));
    }

    public function save()
    {
        $this->checkCSRFForm();

        $task = $this->getTask();
        $values = $this->request->getValues();
        $values['task_id'] = $task['id'];

        if (empty($values['user_id'])) {
            $values['user_id'] = $this->userSession->getId();
        }

        if (! $this->worklogAccessModel->canChooseUser($task, (int) $values['user_id'])) {
            throw new AccessForbiddenException();
        }

        list($valid, $errors) = $this->worklogValidator->validateCreation($values);

        if (! $valid) {
            $this->create($values, $errors);
            return;
        }

        $worklog_id = $this->worklogModel->create($values);

        if ($worklog_id === false) {
            $this->flash->failure(t('Unable to create your time log.'));
            $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task['id']), 'worklogs'), true);
            return;
        }

        if (isset($values['another_worklog']) && $values['another_worklog'] == 1) {
            $this->create(array(
                'task_id' => $task['id'],
                'user_id' => $values['user_id'],
                'work_date' => $this->dateParser->getTimestamp($values['work_date']),
                'another_worklog' => 1,
            ));
            return;
        }

        $this->flash->success(t('Time log added successfully.'));
        $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task['id']), 'worklogs'), true);
    }

    public function edit(array $values = array(), array $errors = array())
    {
        $task = $this->getTask();
        $worklog = $this->getWorklog($task);

        if (! $this->worklogAccessModel->canModify($worklog)) {
            throw new AccessForbiddenException();
        }

        if (empty($values)) {
            $values = $worklog;
            $values['work_date'] = $this->dateParser->getTimestamp($worklog['work_date']);
        }

        $users_list = $this->helper->timelog->getAllowedUsers($task);

        if (! isset($users_list[$worklog['user_id']])) {
            $user = $this->userModel->getById($worklog['user_id']);
            if (! empty($user)) {
                $users_list[$worklog['user_id']] = $this->helper->user->getFullname($user);
            }
        }

        $this->response->html($this->template->render('TimeLog:worklog/edit', array(
            'task' => $task,
            'worklog' => $worklog,
            'values' => $values,
            'errors' => $errors,
            'users_list' => $users_list,
            'show_user_field' => count($users_list) > 1 || (int) $worklog['user_id'] !== (int) $this->userSession->getId(),
        )));
    }

    public function update()
    {
        $this->checkCSRFForm();

        $task = $this->getTask();
        $worklog = $this->getWorklog($task);

        if (! $this->worklogAccessModel->canModify($worklog)) {
            throw new AccessForbiddenException();
        }

        $values = $this->request->getValues();
        $values['id'] = $worklog['id'];
        $values['task_id'] = $task['id'];

        if (empty($values['user_id'])) {
            $values['user_id'] = $worklog['user_id'];
        }

        if ((int) $values['user_id'] !== (int) $worklog['user_id'] && ! $this->worklogAccessModel->canChooseUser($task, (int) $values['user_id'])) {
            throw new AccessForbiddenException();
        }

        list($valid, $errors) = $this->worklogValidator->validateModification($values);

        if (! $valid) {
            $this->edit($values, $errors);
            return;
        }

        if ($this->worklogModel->update($values)) {
            $this->flash->success(t('Time log updated successfully.'));
        } else {
            $this->flash->failure(t('Unable to update this time log.'));
        }

        $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task['id']), 'worklogs'), true);
    }

    public function confirm()
    {
        $task = $this->getTask();
        $worklog = $this->getWorklog($task);

        if (! $this->worklogAccessModel->canModify($worklog)) {
            throw new AccessForbiddenException();
        }

        $this->response->html($this->template->render('TimeLog:worklog/remove', array(
            'task' => $task,
            'worklog' => $worklog,
        )));
    }

    public function remove()
    {
        $this->checkCSRFParam();

        $task = $this->getTask();
        $worklog = $this->getWorklog($task);

        if (! $this->worklogAccessModel->canModify($worklog)) {
            throw new AccessForbiddenException();
        }

        if ($this->worklogModel->remove($worklog['id'])) {
            $this->flash->success(t('Time log removed successfully.'));
        } else {
            $this->flash->failure(t('Unable to remove this time log.'));
        }

        $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task['id']), 'worklogs'), true);
    }

    protected function prepareValues(array $task)
    {
        return array(
            'task_id' => $task['id'],
            'user_id' => $this->userSession->getId(),
            'work_date' => time(),
            'hours' => '',
            'comment' => '',
            'another_worklog' => $this->request->getIntegerParam('another_worklog', 0),
        );
    }

    protected function getWorklog(array $task)
    {
        $worklog = $this->worklogModel->getById($this->request->getIntegerParam('worklog_id'));

        if (empty($worklog)) {
            throw new PageNotFoundException();
        }

        if ((int) $worklog['task_id'] !== (int) $task['id']) {
            throw new AccessForbiddenException();
        }

        return $worklog;
    }
}
