<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Acl;

use \Espo\Core\Exceptions\Error;

use \Espo\ORM\Entity;

class Table
{
    private $data = null; /*array(
        'table' => new \StdClass(),
        'fieldLevelTable' => array()
    );*/

    protected $cacheFile;

    protected $actionList = ['read', 'stream', 'edit', 'delete'];

    protected $levelList = ['all', 'team', 'own', 'no'];

    protected $fieldLevelActionList = ['read', 'edit'];

    protected $fieldLevelLevelList = ['yes', 'no'];

    protected $fileManager;

    protected $metadata;

    public function __construct(\Espo\Entities\User $user, $config = null, $fileManager = null, $metadata = null)
    {
        $this->data = (object) [
            'table' => (object) [],
            'fieldLevelTable' => (object) []
        ];

        $this->user = $user;

        $this->metadata = $metadata;

        if (!$this->user->isFetched()) {
            throw new Error('User must be fetched before ACL check.');
        }

        $this->user->loadLinkMultipleField('teams');

        if ($fileManager) {
            $this->fileManager = $fileManager;
        }

        $this->cacheFile = 'data/cache/application/acl/' . $user->id . '.php';

        if ($config && $config->get('useCache') && file_exists($this->cacheFile)) {
            $cached = include $this->cacheFile;
            $this->data = $cached;
        } else {
            $this->load();
            if ($config && $fileManager && $config->get('useCache')) {
                $this->buildCache();
            }
        }
    }

    public function getMap()
    {
        return $this->data;
    }

    public function getScopeData($scope)
    {
        if (isset($this->data->table->$scope)) {
            $data = $this->data->table->$scope;
            if (is_string($data)) {
                $data = $this->getScopeData($data);
                return $data;
            }
            return get_object_vars($data);
        }
        return null;
    }

    public function get($permission)
    {
        if ($permission == 'table') {
            return null;
        }

        if (isset($this->data->$permission)) {
            return $this->data->$permission;
        }
        return null;
    }

    public function getLevel($scope, $action)
    {
        if (isset($this->data->table->$scope)) {
            if (isset($this->table->$scope->$action)) {
                return $this->data->table->$scope->$action;
            }
        }
        return false;
    }

    private function load()
    {
        $aclTableList = [];
        $fieldLevelTableList = [];
        $assignmentPermissionList = [];
        $userPermissionList = [];

        if (!$this->user->isAdmin()) {
            $userRoles = $this->user->get('roles');

            foreach ($userRoles as $role) {
                $aclTableList[] = $role->get('data');
                $fieldLevelTableList[] = $role->get('fieldLevelData');
                $assignmentPermissionList[] = $role->get('assignmentPermission');
                $userPermissionList[] = $role->get('userPermission');
            }

            $teams = $this->user->get('teams');
            foreach ($teams as $team) {
                $teamRoles = $team->get('roles');
                foreach ($teamRoles as $role) {
                    $aclTableList[] = $role->get('data');
                    $fieldLevelTableList[] = $role->get('fieldLevelData');
                    $assignmentPermissionList[] = $role->get('assignmentPermission');
                    $userPermissionList[] = $role->get('userPermission');
                }
            }

            $aclTable = $this->mergeTableList($aclTableList);
            $fieldLevelTable = $this->mergeFieldLevelTableList($fieldLevelTableList);

            foreach ($this->getScopeList() as $scope) {
                if ($this->metadata->get('scopes.' . $scope . '.disabled')) {
                    $aclTable->$scope = false;
                    unset($fieldLevelTable->$scope);
                }
            }

            $this->applySolid($aclTable, $fieldLevelTable);
        } else {
            $aclTable = (object) [];
            foreach ($this->getScopeList() as $scope) {
                if ($this->metadata->get("scopes.{$scope}.acl") === 'boolean') {
                    $aclTable->$scope = true;
                } else {
                    if ($this->metadata->get("scopes.{$scope}.entity")) {
                        $aclTable->$scope = (object) [];
                        foreach ($this->actionList as $action) {
                            $aclTable->$scope->$action = 'all';
                        }
                    }
                }
            }
            $fieldLevelTable = (object) [];
        }

        foreach ($aclTable as $scope => $data) {
            if (is_string($data)) {
                if (isset($aclTable->$data)) {
                    $aclTable->$scope = $aclTable->$data;
                }
            }
        }

        $this->data->table = $aclTable;
        $this->data->fieldLevelTable = $fieldLevelTable;

        if (!$this->user->isAdmin()) {
            $this->data->assignmentPermission = $this->mergeValueList($assignmentPermissionList, $this->metadata->get('app.acl.valueDefaults.assignmentPermission', 'all'));
            $this->data->userPermission = $this->mergeValueList($userPermissionList, $this->metadata->get('app.acl.valueDefaults.userPermission', 'no'));
        } else {
            $this->data->assignmentPermission = 'all';
            $this->data->userPermission = 'all';
        }
    }

    private function applySolid(&$table, &$fieldLevelTable)
    {
        if (!$this->metadata) {
            return;
        }

        if ($this->user->isAdmin()) {
            return;
        }

        $data = $this->metadata->get('app.acl.solid', array());

        foreach ($data as $scope => $item) {
            $value = $item;
            if (is_array($item)) {
                $value = (object) $item;
            }
            $table->$scope = $value;
        }
    }

    private function mergeValueList(array $list, $defaultValue)
    {
        $result = null;
        foreach ($list as $level) {
            if ($level != 'not-set') {
                if (is_null($result)) {
                    $result = $level;
                    continue;
                }
                if (array_search($result, $this->levelList) > array_search($level, $this->levelList)) {
                    $result = $level;
                }
            }
        }
        if (is_null($result)) {
            $result = $defaultValue;
        }
        return $result;
    }

    private function getScopeWithAclList()
    {
        $scopeList = [];
        $scopes = $this->metadata->get('scopes');
        foreach ($scopes as $scope => $d) {
        	if (!empty($d['acl'])) {
        		$scopeList[] = $scope;
        	}
        }
        return $scopeList;
    }

    private function getScopeList()
    {
        $scopeList = [];
        $scopes = $this->metadata->get('scopes');
        foreach ($scopes as $scope => $d) {
            $scopeList[] = $scope;
        }
        return $scopeList;
    }

    private function mergeTableList(array $tableList)
    {
        $data = (object) [];
        $scopeList = $this->getScopeWithAclList();

        foreach ($tableList as $table) {
            foreach ($scopeList as $scope) {
            	if (!isset($table->$scope)) continue;

            	$row = $table->$scope;

                if ($row == false) {
                    if (!isset($data->$scope)) {
                        $data->$scope = false;
                    }
                } else if ($row === true) {
                    $data->$scope = true;
                } else {
                    if (!isset($data->$scope)) {
                        $data->$scope = (object) [];
                    }
                    if ($data->$scope === false) {
                        $data->$scope = (object) [];
                    }

                    if (!is_object($row)) continue;

                    foreach ($this->actionList as $i => $action) {
                        if (isset($row->$action)) {
                            $level = $row->$action;
                            if (!isset($data->$scope->$action)) {
                                $data->$scope->$action = $level;
                            } else {
                                if (array_search($data->$scope->$action, $this->levelList) > array_search($level, $this->levelList)) {
                                    $data->$scope->$action = $level;
                                }
                            }
                        } else {
                            if ($i > 0) {
                                // TODO remove it
                                $previousAction = $this->actionList[$i - 1];
                                if (isset($data->$scope->$previousAction)) {
                                    $data->$scope->$action = $data->$scope->$previousAction;
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($scopeList as $scope) {
        	if (!isset($data->$scope)) {
        		$aclType = $this->metadata->get('scopes.' . $scope . '.acl');
                if ($aclType === true) {
                    $aclType = 'recordAllTeamOwnNo';
                }
        		if (!empty($aclType)) {
                    $defaultValue = $this->metadata->get('app.acl.defaults.' . $aclType, true);
                    if (is_array($defaultValue)) {
                        $defaultValue = (object) $defaultValue;
                    }
	        		$data->$scope = $defaultValue;
        		}
        	}
        }
        return $data;
    }

    private function mergeFieldLevelTableList(array $tableList)
    {
        $data = (object) [];
        $scopeList = $this->getScopeWithAclList();

        foreach ($tableList as $table) {
            foreach ($scopeList as $scope) {
                if (!isset($table->$scope)) continue;

                if (!isset($data->$scope)) {
                    $data->$scope = (object) [];
                }

                if (!is_object($table->$scope)) continue;

                foreach (get_object_vars($table->$scope) as $field => $row) {
                    if (!is_object($row)) continue;

                    if (!isset($data->$scope->$field)) {
                        $data->$scope->$field = (object) [];
                    }

                    foreach ($this->fieldLevelActionList as $i => $action) {
                        if (!isset($row->$action)) continue;

                        $level = $row->$action;
                        if (!isset($data->$scope->$field->$action)) {
                            $data->$scope->$field->$action = $level;
                        } else {
                            if (array_search($data->$scope->$field->$action, $this->fieldLevelLevelList) > array_search($level, $this->fieldLevelLevelList)) {
                                $data->$scope->$field->$action = $level;
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }

    private function buildCache()
    {
        $contents = '<' . '?'. 'php return ' .  $this->varExport($this->data)  . ';';
        $this->fileManager->putContents($this->cacheFile, $contents);
    }

    private function varExport($variable)
    {
        if ($variable instanceof \StdClass) {
            $result = '(object) ' . $this->varExport(get_object_vars($variable), true);
        } else if (is_array($variable)) {
            $array = array();
            foreach ($variable as $key => $value) {
                $array[] = var_export($key, true).' => ' . $this->varExport($value, true);
            }
            $result = '['.implode(', ', $array).']';
        } else {
            $result = var_export($variable, true);
        }

        return $result;
    }
}

