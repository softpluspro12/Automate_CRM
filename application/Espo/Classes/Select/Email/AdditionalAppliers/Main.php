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

namespace Espo\Classes\Select\Email\AdditionalAppliers;

use Espo\Core\Select\Applier\AdditionalApplier;
use Espo\Entities\Email;
use Espo\ORM\Query\SelectBuilder;
use Espo\Core\Select\SearchParams;

use Espo\Classes\Select\Email\Helpers\JoinHelper;

use Espo\Entities\User;
use Espo\Tools\Email\Folder;

class Main implements AdditionalApplier
{
    public function __construct(
        private User $user,
        private JoinHelper $joinHelper
    ) {}

    public function apply(SelectBuilder $queryBuilder, SearchParams $searchParams): void
    {
        $folder = $this->retrieveFolder($searchParams);

        if ($folder === Folder::DRAFTS) {
            $queryBuilder->useIndex('createdById');
        }
        else if ($folder === Folder::IMPORTANT) {
            // skip
        }
        else if ($this->checkApplyDateSentIndex($queryBuilder, $searchParams)) {
            $queryBuilder->useIndex('dateSent');
        }

        if ($folder !== Folder::DRAFTS) {
            $this->joinEmailUser($queryBuilder);
        }
    }

    protected function joinEmailUser(SelectBuilder $queryBuilder): void
    {
        $this->joinHelper->joinEmailUser($queryBuilder, $this->user->getId());

        if ($queryBuilder->build()->getSelect() === []) {
            $queryBuilder->select('*');
        }

        $itemList = [
            Email::USERS_COLUMN_IS_READ,
            Email::USERS_COLUMN_IS_IMPORTANT,
            Email::USERS_COLUMN_IN_TRASH,
            Email::USERS_COLUMN_FOLDER_ID,
        ];

        foreach ($itemList as $item) {
            $queryBuilder->select('emailUser.' . $item, $item);
        }
    }

    protected function retrieveFolder(SearchParams $searchParams): ?string
    {
        if (!$searchParams->getWhere()) {
            return null;
        }

        foreach ($searchParams->getWhere()->getItemList() as $item) {
            if ($item->getType() === 'inFolder') {
                return $item->getValue();
            }
        }

        return null;
    }

    protected function checkApplyDateSentIndex(SelectBuilder $queryBuilder, SearchParams $searchParams): bool
    {
        if ($searchParams->getTextFilter()) {
            return false;
        }

        if ($searchParams->getOrderBy() && $searchParams->getOrderBy() !== 'dateSent') {
            return false;
        }

        $whereItemList = [];

        if ($searchParams->getWhere()) {
            $whereItemList = $searchParams->getWhere()->getItemList();
        }

        foreach ($whereItemList as $item) {
            $itemAttribute = $item->getAttribute();

            if (
                $itemAttribute &&
                $itemAttribute !== 'folderId' &&
                !in_array($itemAttribute, ['teams', 'users', 'status'])
            ) {
                return false;
            }
        }

        if ($queryBuilder->hasLeftJoinAlias('teamsAccess')) {
            return false;
        }

        return true;
    }
}
