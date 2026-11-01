<?php

namespace Kanboard\Plugin\TimeLog\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Core\Controller\AccessForbiddenException;
use Kanboard\Core\Controller\PageNotFoundException;

class WorklogReportController extends BaseController
{
    public function user()
    {
        $user = $this->getUser();
        $allowed_projects = $this->worklogAccessModel->getAllowedProjects($user['id']);
        $filters = $this->parseFilters($allowed_projects, array(), true, false, $user['id']);
        $data = $this->buildReportData('user', $filters, $allowed_projects, array(), array('user_id' => $user['id']));
        $data['user'] = $user;
        $data['title'] = t('Time logs');

        $this->response->html($this->helper->layout->user('TimeLog:report/user', $data));
    }

    public function project()
    {
        $project = $this->getProject();

        if (! $this->worklogAccessModel->canViewProjectReport($project['id'])) {
            throw new AccessForbiddenException();
        }

        $allowed_projects = array($project['id'] => $project['name']);
        $allowed_users = $this->worklogAccessModel->getProjectUsers($project['id']);
        $filters = $this->parseFilters($allowed_projects, $allowed_users, false, true);
        $data = $this->buildReportData('project', $filters, $allowed_projects, $allowed_users, array('project_id' => $project['id']));
        $data['project'] = $project;
        $data['title'] = t('Time logs');

        $this->response->html($this->helper->layout->project('TimeLog:report/project', $data));
    }

    public function all()
    {
        if (! $this->userSession->isAdmin()) {
            throw new AccessForbiddenException();
        }

        $allowed_projects = $this->projectModel->getList(false, false);
        $allowed_users = $this->worklogAccessModel->getActiveUsers();
        $filters = $this->parseFilters($allowed_projects, $allowed_users, true, true);
        $data = $this->buildReportData('all', $filters, $allowed_projects, $allowed_users, array());
        $data['title'] = t('Time logs');

        $this->response->html($this->helper->layout->config('TimeLog:report/all', $data));
    }

    public function csv()
    {
        $scope = $this->request->getStringParam('scope', 'user');

        if ($scope === 'user') {
            $user = $this->getUser();
            $allowed_projects = $this->worklogAccessModel->getAllowedProjects($user['id']);
            $filters = $this->parseFilters($allowed_projects, array(), true, false, $user['id']);
        } elseif ($scope === 'project') {
            $project = $this->getProject();

            if (! $this->worklogAccessModel->canViewProjectReport($project['id'])) {
                throw new AccessForbiddenException();
            }

            $allowed_projects = array($project['id'] => $project['name']);
            $allowed_users = $this->worklogAccessModel->getProjectUsers($project['id']);
            $filters = $this->parseFilters($allowed_projects, $allowed_users, false, true);
        } elseif ($scope === 'all') {
            if (! $this->userSession->isAdmin()) {
                throw new AccessForbiddenException();
            }

            $allowed_projects = $this->projectModel->getList(false, false);
            $allowed_users = $this->worklogAccessModel->getActiveUsers();
            $filters = $this->parseFilters($allowed_projects, $allowed_users, true, true);
        } else {
            throw new PageNotFoundException();
        }

        $records = $this->worklogModel->getReportQuery($filters['query_filters'])->findAll();
        $this->response->withFileDownload('timelog-'.$scope.'-'.date('Ymd').'.csv')->csv($this->buildCsvRows($records), true);
    }

    protected function parseFilters(array $allowed_projects, array $allowed_users, $show_projects, $show_users, $forced_user_id = 0)
    {
        $errors = array();
        $has_query = $this->request->getStringParam('start') !== '' ||
            $this->request->getStringParam('end') !== '' ||
            $this->request->getStringParam('all_projects') !== '' ||
            $this->request->getStringParam('all_users') !== '' ||
            ! empty($_GET['project_ids']) ||
            ! empty($_GET['user_ids']);

        $start_input = $this->request->getStringParam('start');
        $end_input = $this->request->getStringParam('end');

        if ($start_input === '') {
            $start_input = date('Y-m-01');
        }

        if ($end_input === '') {
            $end_input = date('Y-m-t');
        }

        $start_ts = $this->dateParser->getTimestamp($start_input);
        $end_ts = $this->dateParser->getTimestamp($end_input);

        if ($start_ts <= 0) {
            $errors['start'][] = t('The start date is invalid');
            $start_ts = strtotime(date('Y-m-01'));
        }

        if ($end_ts <= 0) {
            $errors['end'][] = t('The end date is invalid');
            $end_ts = strtotime(date('Y-m-t'));
        }

        if ($start_ts > $end_ts) {
            $errors['end'][] = t('The end date must be after the start date');
            $tmp = $start_ts;
            $start_ts = $end_ts;
            $end_ts = $tmp;
        }

        $all_projects = ! $show_projects ? false : (! $has_query || $this->request->getStringParam('all_projects') === '1');
        $selected_projects = $this->getArrayParam('project_ids');
        $selected_projects = array_values(array_intersect($selected_projects, array_map('intval', array_keys($allowed_projects))));

        if (! $show_projects) {
            $selected_projects = array_map('intval', array_keys($allowed_projects));
        }

        if (empty($allowed_projects)) {
            $all_projects = false;
            $selected_projects = array(0);
        } elseif (! $all_projects && empty($selected_projects)) {
            $selected_projects = array(0);
        }

        $all_users = ! $show_users ? true : (! $has_query || $this->request->getStringParam('all_users') === '1');
        $selected_users = $this->getArrayParam('user_ids');
        $selected_users = array_values(array_intersect($selected_users, array_map('intval', array_keys($allowed_users))));

        if ($forced_user_id > 0) {
            $all_users = false;
            $selected_users = array((int) $forced_user_id);
        } elseif (! $show_users) {
            $all_users = true;
            $selected_users = array();
        } elseif (empty($allowed_users)) {
            $all_users = false;
            $selected_users = array(0);
        } elseif (! $all_users && empty($selected_users)) {
            $selected_users = array(0);
        }

        $query_filters = array(
            'date_from' => date('Y-m-d', $start_ts),
            'date_to' => date('Y-m-d', $end_ts),
        );

        if (! $all_projects) {
            $query_filters['project_ids'] = $selected_projects;
        }

        if ($forced_user_id > 0) {
            $query_filters['user_ids'] = array((int) $forced_user_id);
        } elseif ($show_users && ! $all_users) {
            $query_filters['user_ids'] = $selected_users;
        }

        return array(
            'start' => $start_input,
            'end' => $end_input,
            'all_projects' => $all_projects,
            'all_users' => $all_users,
            'project_ids' => $selected_projects,
            'user_ids' => $selected_users,
            'show_projects' => $show_projects,
            'show_users' => $show_users,
            'query_filters' => $query_filters,
            'errors' => $errors,
        );
    }

    protected function buildReportData($scope, array $filters, array $allowed_projects, array $allowed_users, array $action_params)
    {
        return array(
            'scope' => $scope,
            'filters' => $filters,
            'projects' => $allowed_projects,
            'users' => $allowed_users,
            'records' => $this->worklogModel->getReportQuery($filters['query_filters'])->findAll(),
            'totals' => $this->worklogModel->getTotals($filters['query_filters']),
            'action_params' => $action_params,
            'csv_url' => $this->buildCsvUrl($scope, $filters, $action_params),
        );
    }

    protected function buildCsvUrl($scope, array $filters, array $action_params)
    {
        $params = array_merge($action_params, array(
            'plugin' => 'TimeLog',
            'scope' => $scope,
            'start' => $filters['start'],
            'end' => $filters['end'],
            'all_projects' => $filters['all_projects'] ? 1 : 0,
            'all_users' => $filters['all_users'] ? 1 : 0,
        ));

        if (! $filters['all_projects']) {
            $params['project_ids'] = $filters['project_ids'];
        }

        if ($filters['show_users'] && ! $filters['all_users']) {
            $params['user_ids'] = $filters['user_ids'];
        }

        return $this->helper->url->to('WorklogReportController', 'csv', $params);
    }

    protected function buildCsvRows(array $records)
    {
        $rows = array(
            array(
                t('Date'),
                t('User'),
                t('Project'),
                t('Task'),
                t('Time spent'),
                t('Comment'),
                t('System'),
            ),
        );

        foreach ($records as $record) {
            $rows[] = array(
                $record['work_date'],
                $record['user_fullname'] ?: $record['username'],
                $record['project_name'],
                '#'.$record['task_id'].' '.$record['task_title'],
                $record['hours'],
                $record['comment'],
                $this->worklogAccessModel->isSystemRecord($record) ? t('Yes') : t('No'),
            );
        }

        return $rows;
    }

    protected function getArrayParam($name)
    {
        if (! isset($_GET[$name])) {
            return array();
        }

        $values = is_array($_GET[$name]) ? $_GET[$name] : array($_GET[$name]);

        return array_map('intval', $values);
    }
}
