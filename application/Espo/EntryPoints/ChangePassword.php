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

namespace Espo\EntryPoints;

use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\BadRequest;

class ChangePassword extends \Espo\Core\EntryPoints\Base
{
    public static $authRequired = false;

    public function run()
    {
        $requestId = $_GET['id'];
        if (empty($requestId)) {
            throw new BadRequest();
        }

        $config = $this->getConfig();
        $themeManager = $this->getThemeManager();

        $p = $this->getEntityManager()->getRepository('PasswordChangeRequest')->where(array(
            'requestId' => $requestId
        ))->findOne();

        if (!$p) {
            throw new NotFound();
        }

        $runScript = "
                    app.getController('PasswordChangeRequest', function (controller) {
                        controller.doAction('passwordChange', '{$requestId}');
                    });
        ";

        $html = file_get_contents('main.html');
        $html = str_replace('{{cacheTimestamp}}', $config->get('cacheTimestamp', 0), $html);
        $html = str_replace('{{useCache}}', $config->get('useCache') ? 'true' : 'false' , $html);
        $html = str_replace('{{stylesheet}}', $themeManager->getStylesheet(), $html);
        $html = str_replace('{{runScript}}', $runScript , $html);
        echo $html;
        exit;
    }

    protected function getThemeManager()
    {
        return $this->getContainer()->get('themeManager');
    }
}

