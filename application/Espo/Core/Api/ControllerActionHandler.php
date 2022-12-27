<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ControllerActionHandler implements RequestHandlerInterface
{
    public function __construct(
        private string $controllerName,
        private string $actionName,
        private ProcessData $processData,
        private ResponseWrapper $responseWrapped,
        private ActionProcessor $actionProcessor,
        private Config $config
    ) {}

    /**
     * @throws NotFound
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestWrapped = new RequestWrapper(
            $request,
            $this->processData->getBasePath(),
            $this->processData->getRouteParams()
        );

        $this->beforeProceed();

        $this->actionProcessor->process(
            $this->controllerName,
            $this->actionName,
            $requestWrapped,
            $this->responseWrapped
        );

        $this->afterProceed();

        return $this->responseWrapped->getResponse();
    }

    private function beforeProceed(): void
    {
        $this->responseWrapped->setHeader('Content-Type', 'application/json');
    }

    private function afterProceed(): void
    {
        $this->responseWrapped
            ->setHeader('X-App-Timestamp', (string) ($this->config->get('appTimestamp') ?? '0'))
            ->setHeader('Expires', '0')
            ->setHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0')
            ->setHeader('Pragma', 'no-cache');
    }
}
