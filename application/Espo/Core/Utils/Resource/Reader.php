<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
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

namespace Espo\Core\Utils\Resource;

use Espo\Core\Utils\File\Unifier;
use Espo\Core\Utils\File\UnifierObj;

use stdClass;

/**
 * Reads resource data.
 */
class Reader
{
    private $paths = [
        'corePath' => 'application/Espo/Resources/',
        'modulePath' => 'application/Espo/Modules/{*}/Resources/',
        'customPath' => 'custom/Espo/Custom/Resources/',
    ];

    private $unifier;

    private $unifierObj;

    public function __construct(Unifier $unifier, UnifierObj $unifierObj)
    {
        $this->unifier = $unifier;
        $this->unifierObj = $unifierObj;
    }

    /**
     * Read resource data.
     *
     * @return stdClass|array
     */
    public function read(string $path, ReaderParams $params)
    {
        $paths = $this->buildPaths($path, $params);

        if ($params->asArray()) {
            return $this->unifier->unify($paths);
        }

        return $this->unifierObj->unify($paths);
    }

    private function buildPaths(string $path, ReaderParams $params): array
    {
        $paths = [];

        foreach ($this->paths as $key => $dir) {
            $paths[$key] = $dir . $path;
        }

        if ($params->noCustom()) {
            unset($paths['customPath']);
        }

        return $paths;
    }
}
