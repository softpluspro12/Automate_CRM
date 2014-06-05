<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 ************************************************************************/


class SystemHelper extends \Espo\Core\Utils\System
{

	protected $requirements = array(
		'phpVersion' => '5.4',

		'exts' => array(
			'json',
			'mcrypt',
		),
	);

	protected $modRewriteUrl = '/api/v1/Metadata';

	protected $writableDir = 'data';

	protected $combineOperator = '&&';


	public function initWritable()
	{
		if (is_writable($this->writableDir)) {
			return true;
		}

		return false;
	}

	public function getWritableDir()
	{
		return $this->writableDir;
	}


	public function checkRequirements()
	{
		$result['success'] = true;
		if (!empty($this->requirements)) {
			if (!empty($this->requirements['phpVersion']) && version_compare(PHP_VERSION, $this->requirements['phpVersion']) == -1) {
				$result['errors']['phpVersion'] = $this->requirements['phpVersion'];
				$result['success'] = false;
			}
			if (!empty($this->requirements['exts'])) {
				foreach ($this->requirements['exts'] as $extName) {
					if (!extension_loaded($extName)) {
						$result['errors']['exts'][] = $extName;
						$result['success'] = false;
					}
				}
			}
		}

		return $result;
	}

	public function checkDbConnection($hostName, $dbUserName, $dbUserPass, $dbName, $dbDriver = 'pdo_mysql')
	{
		$result['success'] = true;

		switch ($dbDriver) {
			case 'mysqli':
				$mysqli = new mysqli($hostName, $dbUserName, $dbUserPass, $dbName);
				if (!$mysqli->connect_errno) {
					$mysqli->close();
				}
				else {
					$result['errors']['dbConnect']['errorCode'] = $mysqli->connect_errno;
					$result['errors']['dbConnect']['errorMsg'] = $mysqli->connect_error;
					$result['success'] = false;
				}
				break;

			case 'pdo_mysql':
				try {
					$dbh = new PDO("mysql:host={$hostName};dbname={$dbName}", $dbUserName, $dbUserPass);
					$dbh = null;
				} catch (PDOException $e) {

					$result['errors']['dbConnect']['errorCode'] = $e->getCode();
					$result['errors']['dbConnect']['errorMsg'] = $e->getMessage();
					$result['success'] = false;
				}
				break;
		}

		return $result;
	}

	public function getBaseUrl()
	{
		$pageUrl = ($_SERVER["HTTPS"] == 'on') ? 'https://' : 'http://';

		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageUrl .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$pageUrl .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}

		$baseUrl = str_ireplace('/install/index.php', '', $pageUrl);

		return $baseUrl;
	}

	public function getModRewriteUrl()
	{
		return $this->modRewriteUrl;
	}

	public function getChmodCommand($path, $permissions = array('755'), $isSudo = false, $isFile = null, $isCd = true)
	{
		//$path = $this->getFullPath($path);

		$path = empty($path) ? '*' : $path;
		if (is_array($path)) {
			$path = implode(' ', $path);
		}

		$sudoStr = $isSudo ? 'sudo ' : '';

		$cd = $isCd ? $this->getCd(true) : '';

		if (is_string($permissions)) {
			$permissions = (array) $permissions;
		}

		if (!isset($isFile) && count($permissions) == 1) {
			return $cd.$sudoStr.'chmod -R '.$permissions[0].' '.$path;
		}

		$bufPerm = (count($permissions) == 1) ?  array_fill(0, 2, $permissions[0]) : $permissions;

		$commands = array();

		if ($isCd) {
			$commands[] = $this->getCd();
		}

		$commands[] = $sudoStr.'chmod '.$bufPerm[0].' $(find '.$path.' -type f)';
		$commands[] = $sudoStr.'chmod '.$bufPerm[1].' $(find '.$path.' -type d)';

		if (count($permissions) >= 2) {
			return implode(' ' . $this->combineOperator . ' ', $commands);
		}

		return $isFile ? $commands[0] : $commands[1];
	}

	public function getChownCommand($path, $isSudo = false, $isCd = true)
	{
		$path = empty($path) ? '*' : $path;
		if (is_array($path)) {
			$path = implode(' ', $path);
		}

		$owner = posix_getuid();
		$group = posix_getegid();

		$sudoStr = $isSudo ? 'sudo ' : '';

		if (empty($owner) || empty($group)) {
			return null;
		}

		$cd = '';
		if ($isCd) {
			$cd = $this->getCd(true);
		}

		//$path = $this->getFullPath($path;
		return $cd.$sudoStr.'chown -R '.$owner.':'.$group.' '.$path;
	}

	public function getFullPath($path)
	{
		if (is_array($path)) {
			$pathList = array();
			foreach ($path as $pathItem) {
				$pathList[] = $this->getFullPath($pathItem);
			}
			return $pathList;
		}

		if (!empty($path)) {
			$path = DIRECTORY_SEPARATOR . $path;
		}

		return $this->getRootDir() . $path;
	}

	/**
	 * Get permission commands
	 *
	 * @param  string | array  $path
	 * @param  string | array  $permissions
	 * @param  boolean $isSudo
	 * @param  bool  $isFile
	 * @return string
	 */
	public function getPermissionCommands($path, $permissions = array('644', '755'), $isSudo = false, $isFile = null)
	{
		if (is_string($path)) {
			$path = array_fill(0, 2, $path);
		}
		list($chmodPath, $chownPath) = $path;

		$commands = array();
		$commands[] = $this->getChmodCommand($chmodPath, $permissions, $isSudo, $isFile);

		$chown = $this->getChownCommand($chownPath, $isSudo, false);
		if (isset($chown)) {
			$commands[] = $chown;
		}

		return implode(' ' . $this->combineOperator . ' ', $commands);
	}

	protected function getCd($isCombineOperator = false)
	{
		$cd = 'cd '.$this->getRootDir();

		if ($isCombineOperator) {
			$cd .= ' '.$this->combineOperator.' ';
		}

		return $cd;
	}

}
