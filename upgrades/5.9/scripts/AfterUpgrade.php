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

class AfterUpgrade
{
    public function run($container)
    {
        $this->container = $container;

        $config = $container->get('config');
        $config->set('busyRangesEntityList', ['Meeting', 'Call']);
        $config->save();

        $this->fixCollation($container);
    }

    protected function fixCollation($container)
    {
        $pdo = $container->get('entityManager')->getPDO();
        $ormMeta = $container->get('ormMetadata')->getData(true);

        $fieldListExceededIndexMaxLength = \Espo\Core\Utils\Database\Schema\Utils::getFieldListExceededIndexMaxLength($ormMeta, 767);

        foreach ($ormMeta as $entityName => $entityParams) {

            if (!isset($fieldListExceededIndexMaxLength[$entityName])) continue;

            $tableName = \Espo\Core\Utils\Util::toUnderScore($entityName);

            //Get table columns params
            $query = "SHOW FULL COLUMNS FROM `". $tableName ."` WHERE `Collation` <> 'utf8mb4_unicode_ci'";

            try {
                $sth = $pdo->prepare($query);
                $sth->execute();
            } catch (\Exception $e) {
                $GLOBALS['log']->debug('Utf8mb4: Table does not exist - ' . $e->getMessage());
                continue;
            }

            $columnParams = array();
            $rowList = $sth->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rowList as $row) {
                $columnParams[ $row['Field'] ] = $row;
            }
            //END: get table columns params

            foreach ($entityParams['fields'] as $fieldName => $fieldParams) {

                $columnName = \Espo\Core\Utils\Util::toUnderScore($fieldName);

                if (!in_array($fieldName, $fieldListExceededIndexMaxLength[$entityName])) continue;
                if (!isset($columnParams[$columnName])) continue;

                if (isset($fieldParams['notStorable']) && $fieldParams['notStorable']) {
                    continue;
                }

                if (!isset($columnParams[$columnName]) || empty($columnParams[$columnName]['Type'])) {
                    continue;
                }

                $query = null;

                switch ($fieldParams['type']) {
                    case 'varchar':
                    case 'text':
                    case 'jsonObject':
                    case 'jsonArray':
                        $query = "ALTER TABLE `".$tableName."` CHANGE COLUMN `". $columnName ."` `". $columnName ."` ". $columnParams[$columnName]['Type'] ." CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
                        break;
                }

                if (!empty($query)) {
                    $GLOBALS['log']->debug('Utf8mb4: execute the query - [' . $query . '].');

                    try {
                        $sth = $pdo->prepare($query);
                        $sth->execute();
                    } catch (\Exception $e) {
                        $GLOBALS['log']->warning('Utf8mb4: FAILED executing the query - [' . $query . '], details: '. $e->getMessage() .'.');
                    }
                }
            }
        }
    }
}
