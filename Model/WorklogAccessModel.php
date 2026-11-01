<?php

namespace Kanboard\Plugin\TimeLog\Model;

use Kanboard\Core\Base;
use Kanboard\Core\Security\Role;

class WorklogAccessModel extends Base
{
    public function isProjectManager($project_id, $user_id = 0)
    {
        $user_id = $user_id ?: $this->userSession->getId();
        $user = $this->userModel->getById($user_id);

        if (empty($user)) {
            return false;
        }

        if ($user['role'] === Role::APP_ADMIN) {
            return true;
        }

        return $this->projectUserRoleModel->getUserRole($project_id, $user_id) === Role::PROJECT_MANAGER;
    }

    public function getAllowedUsers(array $task)
    {
        $user_id = $this->userSession->getId();

        if (! $this->userSession->isLogged()) {
            return array();
        }

        if ($this->userSession->isAdmin()) {
            return $this->userModel->getActiveUsersList();
        }

        if ($this->isProjectManager($task['project_id'], $user_id)) {
            return $this->projectUserRoleModel->getAssignableUsersList($task['project_id'], false);
        }

        return array($user_id => $this->helper->user->getFullname());
    }

    public function canChooseUser(array $task, $user_id)
    {
        $user_id = (int) $user_id;

        if ($user_id <= 0 || ! $this->userModel->isActive($user_id)) {
            return false;
        }

        if (! $this->userSession->isLogged()) {
            return true;
        }

        if ($this->userSession->isAdmin()) {
            return true;
        }

        if ($this->isProjectManager($task['project_id'], $this->userSession->getId())) {
            return $this->projectPermissionModel->isAssignable($task['project_id'], $user_id);
        }

        return $user_id === (int) $this->userSession->getId();
    }

    public function canModify(array $worklog)
    {
        if (! $this->userSession->isLogged()) {
            return true;
        }

        $user_id = (int) $this->userSession->getId();

        if ($this->isSystemRecord($worklog)) {
            return $this->userSession->isAdmin();
        }

        if ($this->userSession->isAdmin()) {
            return true;
        }

        if ((int) $worklog['user_id'] === $user_id || (int) $worklog['creator_id'] === $user_id) {
            return true;
        }

        $project_id = $this->taskFinderModel->getProjectId((int) $worklog['task_id']);

        return $project_id > 0 && $this->isProjectManager($project_id, $user_id);
    }

    public function canViewProjectReport($project_id, $user_id = 0)
    {
        if (! $this->userSession->isLogged()) {
            return true;
        }

        return $this->isProjectManager($project_id, $user_id);
    }

    public function getAllowedProjects($user_id = 0, $manager_only = false)
    {
        $user_id = $user_id ?: $this->userSession->getId();
        $user = $this->userModel->getById($user_id);

        if (! empty($user) && $user['role'] === Role::APP_ADMIN) {
            return $this->projectModel->getList(false, false);
        }

        $projects = $this->projectUserRoleModel->getProjectsByUser($user_id);

        if (! $manager_only) {
            return $projects;
        }

        $result = array();

        foreach ($projects as $project_id => $project_name) {
            if ($this->isProjectManager($project_id, $user_id)) {
                $result[$project_id] = $project_name;
            }
        }

        return $result;
    }

    public function getProjectUsers($project_id)
    {
        return $this->projectUserRoleModel->getAllUsers($project_id);
    }

    public function getActiveUsers()
    {
        return $this->userModel->getActiveUsersList();
    }

    public function isSystemRecord(array $worklog)
    {
        if (! isset($worklog['is_system'])) {
            return false;
        }

        return $worklog['is_system'] === true ||
            $worklog['is_system'] === 1 ||
            $worklog['is_system'] === '1' ||
            $worklog['is_system'] === 't';
    }
}
