<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2016 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace tests\integration\Core;

class Tester
{
    protected $configPath = 'tests/integration/config.php';

    protected $buildedPath = 'build';

    protected $installPath = 'build/test';

    protected $testDataPath = 'tests/integration/testData';

    private $application;

    private $apiClient;

    private $dataLoader;

    protected $params;

    /**
     * Espo username which is used for authentication
     *
     * @var null
     */
    protected $userName = null;

    /**
     * Espo user password which is used for authentication
     *
     * @var null
     */
    protected $password = null;

    protected $defaultUserPassword = '1';

    public function __construct(array $params)
    {
        $this->params = $this->normalizeParams($params);
    }

    protected function normalizeParams(array $params)
    {
        $namespaceToRemove = 'tests\\integration\\Espo';
        $classPath = preg_replace('/^'.preg_quote($namespaceToRemove).'\\\\(.+)Test$/', '${1}', $params['className']);

        if (isset($params['dataFile'])) {
            $params['dataFile'] = realpath($this->testDataPath) . '/' . $params['dataFile'];
            if (!file_exists($params['dataFile'])) {
                die('"dataFile" is not found, path: '.$params['dataFile'].'.');
            }
        } else {
            $params['dataFile'] = realpath($this->testDataPath) . '/' . str_replace('\\', '/', $classPath) . '.php';
        }

        if (isset($params['pathToFiles'])) {
            $params['pathToFiles'] = realpath($this->testDataPath) . '/' . $params['pathToFiles'];
            if (!file_exists($params['pathToFiles'])) {
                die('"pathToFiles" is not found, path: '.$params['pathToFiles'].'.');
            }
        } else {
            $params['pathToFiles'] = realpath($this->testDataPath) . '/' . str_replace('\\', '/', $classPath);
        }

        return $params;
    }

    protected function getParam($name, $returns = null)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }

        return $returns;
    }

    public function auth($userName, $password = null)
    {
        $this->userName = $userName;
        $this->password = $password;
    }

    public function getApplication($reload = false, $clearCache = true)
    {
        if (!isset($this->application) || $reload)  {

            if ($clearCache) {
                $this->clearCache();
            }

            $this->application = new \Espo\Core\Application();
            $auth = new \Espo\Core\Utils\Auth($this->application->getContainer());

            if (isset($this->userName)) {
                $this->password = isset($this->password) ? $this->password : $this->defaultUserPassword;
                $auth->login($this->userName, $this->password);
            } else {
                $auth->useNoAuth();
            }
        }

        return $this->application;
    }

    protected function getApiClient()
    {
        if (!isset($this->apiClient)) {
            $this->apiClient = new ApiClient($this->getParam('siteUrl'));
        }

        return $this->apiClient;
    }

    protected function getDataLoader()
    {
        if (!isset($this->dataLoader)) {
            $this->dataLoader = new DataLoader($this->getApplication());
        }

        return $this->dataLoader;
    }

    public function initialize()
    {
        $this->install();
        $this->loadData();
    }

    public function terminate()
    {
        $baseDir = str_replace('/' . $this->installPath, '', getcwd());

        chdir($baseDir);
        set_include_path($baseDir);
    }

    protected function install()
    {
        $mainApplication = new \Espo\Core\Application();
        $fileManager = $mainApplication->getContainer()->get('fileManager');

        $latestEspo = Utils::getLatestBuildedPath($this->buildedPath);

        $configData = include($this->configPath);
        $configData['siteUrl'] = $mainApplication->getContainer()->get('config')->get('siteUrl') . '/' . $this->installPath;
        $this->params['siteUrl'] = $configData['siteUrl'];

        //remove and copy Espo files
        Utils::dropTables($configData['database']);
        $fileManager->removeInDir($this->installPath);
        $fileManager->copy($latestEspo, $this->installPath, true);

        Utils::fixUndefinedVariables();

        chdir($this->installPath);
        set_include_path($this->installPath);

        require_once('install/core/Installer.php');

        $installer = new \Installer();
        $installer->saveData(array(), 'en_US');
        $installer->saveConfig($configData);

        $installer = new \Installer(); //reload installer to get all config data
        $installer->buildDatabase();
        $installer->setSuccess();
    }

    protected function loadData()
    {
        if (!empty($this->params['dataFile'])) {
            $this->getDataLoader()->loadData($this->params['dataFile']);
        }

        if (!empty($this->params['pathToFiles'])) {
            $this->getDataLoader()->loadFiles($this->params['pathToFiles']);
        }
    }

    public function clearCache()
    {
        $fileManager = new \Espo\Core\Utils\File\Manager();

        return $fileManager->removeInDir('data/cache');
    }

    public function sendRequest($method, $action, $data = null)
    {
        $apiClient = $this->getApiClient();
        $apiClient->setUserName($this->userName);
        $apiClient->setPassword(isset($this->password) ? $this->password : $this->defaultUserPassword);

        return $apiClient->request($method, $action, $data);
    }
}