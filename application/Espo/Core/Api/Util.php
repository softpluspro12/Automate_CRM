<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Api;

use stdClass;

class Util
{
    private const IP_PARAM_LIST = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
    ];

    public static function cloneObject(stdClass $source): stdClass
    {
        $cloned = (object) [];

        foreach (get_object_vars($source) as $k => $v) {
            $cloned->$k = self::cloneObjectItem($v);
        }

        return $cloned;
    }

    /**
     * @param mixed $item
     * @return mixed
     */
    private static function cloneObjectItem($item)
    {
        if (is_array($item)) {
            $cloned = [];

            foreach ($item as $v) {
                $cloned[] = self::cloneObjectItem($v);
            }

            return $cloned;
        }

        if ($item instanceof stdClass) {
            return self::cloneObject($item);
        }

        if (is_object($item)) {
            return clone $item;
        }

        return $item;
    }

    public static function obtainIpFromRequest(Request $request): ?string
    {
        foreach (self::IP_PARAM_LIST as $var){
            $value = $request->getServerParam($var);

            if (!is_string($value)) {
                continue;
            }

            foreach (explode(',', $value) as $item) {
                $item = trim($item);

                if (filter_var($item, FILTER_VALIDATE_IP) !== false) {
                    return $item;
                }
            }
        }

        return null;
    }
}
