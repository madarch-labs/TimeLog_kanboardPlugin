<?php

namespace Kanboard\Plugin\TimeLog;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Security\Role;
use Kanboard\Core\Translator;
use Pimple\Exception\FrozenServiceException;

class Plugin extends Base
{
    public function initialize()
    {
        try {
            $this->container['subtaskTimeTrackingModel'] = function ($c) {
                return new Model\SubtaskTimeTrackingModel($c);
            };
        } catch (FrozenServiceException $e) {
            $this->logger->error('TimeLog: unable to override subtaskTimeTrackingModel');
        }

        try {
            $this->helper->register('task', '\Kanboard\Plugin\TimeLog\Helper\TaskHelper');
        } catch (FrozenServiceException $e) {
            $this->logger->error('TimeLog: unable to override task helper');
        }

        try {
            $this->helper->register('timelog', '\Kanboard\Plugin\TimeLog\Helper\TimeLogHelper');
        } catch (FrozenServiceException $e) {
            $this->logger->error('TimeLog: unable to register timelog helper');
        }

        $stripTimeSpent = function (array &$values) {
            unset($values['time_spent']);
        };

        $this->hook->on('model:task:creation:prepare', $stripTimeSpent);
        $this->hook->on('model:task:modification:prepare', $stripTimeSpent);

        $this->projectAccessMap->add('WorklogController', '*', Role::PROJECT_MEMBER);
        $this->applicationAccessMap->add('WorklogReportController', array('user', 'project', 'csv'), Role::APP_USER);
        $this->applicationAccessMap->add('WorklogReportController', array('all'), Role::APP_ADMIN);
        $this->applicationAccessMap->add('UserViewController', 'timesheet', Role::APP_ADMIN);
        $this->apiProjectAccessMap->add('WorklogProcedure', '*', Role::PROJECT_MEMBER);

        $this->template->hook->attach('template:task:dropdown:after-basic-actions', 'TimeLog:task/dropdown');
        $this->template->hook->attach('template:task:sidebar:after-basic-actions', 'TimeLog:task/sidebar');
        $this->template->hook->attach('template:task:show:before-internal-links', 'TimeLog:task/show');
        $this->template->hook->attach('template:user:sidebar:information', 'TimeLog:user/sidebar');
        $this->template->hook->attach('template:project:sidebar', 'TimeLog:project/sidebar');
        $this->template->hook->attach('template:config:sidebar', 'TimeLog:config/sidebar');

        $this->template->setTemplateOverride('subtask/timer', 'TimeLog:subtask/timer');
        $this->template->setTemplateOverride('task/time_tracking_details', 'TimeLog:task/time_tracking_details');

        $this->route->addRoute('/timelog/user/:user_id', 'WorklogReportController', 'user', 'TimeLog');
        $this->route->addRoute('/timelog/project/:project_id', 'WorklogReportController', 'project', 'TimeLog');
        $this->route->addRoute('/timelog/all', 'WorklogReportController', 'all', 'TimeLog');

        $this->api->getProcedureHandler()->withObject(new Api\WorklogProcedure($this->container));
    }

    public function onStartup()
    {
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__.'/Locale');
    }

    public function getClasses()
    {
        return array(
            'Plugin\TimeLog\Model' => array('WorklogModel', 'WorklogAccessModel'),
            'Plugin\TimeLog\Validator' => array('WorklogValidator'),
        );
    }

    public function getPluginName()
    {
        return 'TimeLog';
    }

    public function getPluginDescription()
    {
        return t('Log spent time on tasks and daily reports');
    }

    public function getPluginAuthor()
    {
        return 'Fedrs';
    }

    public function getPluginVersion()
    {
        return '1.0.0';
    }

    public function getPluginHomepage()
    {
        return '';
    }

    public function getCompatibleVersion()
    {
        return '>=1.2.50';
    }
}
