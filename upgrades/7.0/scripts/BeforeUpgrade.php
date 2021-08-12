<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Exceptions\Error;

use Espo\Core\Container;

class BeforeUpgrade
{
    public function run(Container $container)
    {
        $this->container = $container;

        $this->processCheckExtensions();
    }

    private function processCheckExtensions(): void
    {
        $errorMessageList = [];

        $this->processCheckExtension('Advanced Pack', '2.8.0', $errorMessageList);
        $this->processCheckExtension('Sales Pack', '1.1.0', $errorMessageList);
        $this->processCheckExtension('Outlook Integration', '1.2.5', $errorMessageList);
        $this->processCheckExtension('Real Estate', '1.5.0', $errorMessageList);

        if (!count($errorMessageList)) {
            return;
        }

        $message = implode("\n\n", $errorMessageList);

        throw new Error($message);
    }

    private function processCheckExtension(string $name, string $minVersion, array &$errorMessageList): void
    {
        $em = $this->container->get('entityManager');

        $extension = $em->getRepository('Extension')
            ->where([
                'name' => $name,
                'isInstalled' => true,
            ])
            ->findOne();

        if (!$extension) {
            return;
        }

        $version = $extension->get('version');

        if (version_compare($version, $minVersion, '>=')) {
            return;
        }

        $message =
            "EspoCRM 7.0 is not compatible with '{$name}' extension of a version lower than {$minVersion}. " .
            "Please upgrade the extension or uninstall it. Then run the upgrade command again.";

        $errorMessageList[] = $message;
    }
}
