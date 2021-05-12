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

namespace Espo\Tools\Export;

use Espo\Core\{
    Select\SearchParams,
    Select\Where\Item as WhereItem,
};

use RuntimeException;

class Params
{
    private $entityType;

    private $attributeList = null;

    private $fieldList = null;

    private $fileName = null;

    private $format = null;

    private $name = null;

    private $searchParams = null;

    private $applyAccessControl = true;

    public function __construct(string $entityType)
    {
        $this->entityType = $entityType;
    }

    public static function fromRaw(array $params): self
    {
        $entityType = $params['entityType'] ?? null;

        if (!$entityType) {
            throw new RuntimeException("No entityType.");
        }

        $obj = new self($entityType);

        $obj->name = $params['name'] ?? $params['exportName'] ?? null;

        $obj->fileName = $params['fileName'] ?? null;
        $obj->format = $params['format'] ?? null;
        $obj->attributeList = $params['attributeList'] ?? null;
        $obj->fieldList = $params['fieldList'] ?? null;

        $where = $params['where'] ?? null;
        $ids = $params['ids'] ?? null;

        $searchParams = $params['searchParams'] ?? null;

        if ($where && !is_array($where)) {
            throw new RuntimeException("Bad 'where'.");
        }

        if ($searchParams && !is_array($searchParams)) {
            throw new RuntimeException("Bad 'searchParams'.");
        }

        if ($where && $searchParams) {
            $searchParams['where'] = $where;
        }

        if ($where && !$searchParams) {
            $searchParams = [
                'where' => $where,
            ];
        }

        if ($searchParams) {
            if ($ids) {
                throw new RuntimeException("Can't combine 'ids' and search params.");
            }
        }
        else if ($ids) {
            if (!is_array($ids)) {
                throw new RuntimeException("Bad 'ids'.");
            }

            $obj->searchParams = SearchParams
                ::fromNothing()
                ->withWhere(
                    WhereItem::fromRaw([
                        'type' => 'equals',
                        'attribute' => 'id',
                        'value' => $ids,
                    ])
                );
        }

        if ($searchParams) {
            $actualSearchParams = $searchParams;

            unset($actualSearchParams['select']);

            $obj->searchParams = SearchParams::fromRaw($actualSearchParams);
        }

        return $obj;
    }

    public static function fromEntityType(string $entityType): self
    {
        return new self($entityType);
    }

    public function withFormat(?string $format): self
    {
        $obj = clone $this;

        $obj->format = $format;

        return $obj;
    }

    public function withFileName(?string $fileName): self
    {
        $obj = clone $this;

        $obj->fileName = $fileName;

        return $obj;
    }

    public function withName(?string $name): self
    {
        $obj = clone $this;

        $obj->name = $name;

        return $obj;
    }

    public function withSearchParams(?SearchParams $searchParams): self
    {
        $obj = clone $this;

        $obj->searchParams = $searchParams;

        return $obj;
    }

    public function withFieldList(?array $fieldList): self
    {
        $obj = clone $this;

        $obj->fieldList = $fieldList;

        return $obj;
    }

    public function withAttributeList(?array $attributeList): self
    {
        $obj = clone $this;

        $obj->attributeList = $attributeList;

        return $obj;
    }

    public function withAccessControl(bool $applyAccessControl = true): self
    {
        $obj = clone $this;

        $obj->applyAccessControl = $applyAccessControl;

        return $obj;
    }

    /**
     * Get search params.
     */
    public function getSearchParams(): SearchParams
    {
        if (!$this->searchParams) {
            return SearchParams::fromNothing();
        }

        return $this->searchParams;
    }

    /**
     * Get a target entity type.
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Get a filename for a result export file.
     */
    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    /**
     * Get a name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get a format.
     */
    public function getFormat(): ?string
    {
        return $this->format;
    }

    /**
     * Get attributes to be exported.
     */
    public function getAttributeList(): ?array
    {
        return $this->attributeList;
    }

    /**
     * Get fields to be exported.
     */
    public function getFieldList(): ?array
    {
        return $this->fieldList;
    }

    /**
     * Whether all fields should be exported.
     */
    public function allFields(): bool
    {
        return $this->fieldList === null && $this->attributeList === null;
    }

    /**
     * Whether to apply access control.
     */
    public function applyAccessControl(): bool
    {
        return $this->applyAccessControl;
    }
}
