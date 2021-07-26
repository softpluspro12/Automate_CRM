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

namespace Espo\ORM\Query;

use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Query\Part\SelectExpression;
use Espo\ORM\Query\Part\OrderExpression;
use Espo\ORM\Query\Part\Expression;

use RuntimeException;

/**
 * Select parameters.
 *
 * @todo Add validation and normalization.
 */
class Select implements SelectingQuery
{
    use SelectingTrait;
    use BaseTrait;

    public const ORDER_ASC = OrderExpression::ASC;

    public const ORDER_DESC = OrderExpression::DESC;

    /**
     * Get an entity type.
     */
    public function getFrom(): ?string
    {
        return $this->params['from'] ?? null;
    }

    /**
     * Get a from-alias
     */
    public function getFromAlias(): ?string
    {
        return $this->params['fromAlias'] ?? null;
    }

    /**
     * Get a from-query.
     */
    public function getFromQuery(): ?SelectingQuery
    {
        return $this->params['fromQuery'] ?? null;
    }

    /**
     * Get an OFFSET.
     */
    public function getOffset(): ?int
    {
        return $this->params['offset'] ?? null;
    }

    /**
     * Get a LIMIT.
     */
    public function getLimit(): ?int
    {
        return $this->params['limit'] ?? null;
    }

    /**
     * Get USE INDEX (list of indexes).
     *
     * @return string[]
     */
    public function getUseIndex(): array
    {
        return $this->params['useIndex'] ?? [];
    }

    /**
     * Get SELECT items.
     *
     * @return SelectExpression[]
     */
    public function getSelect(): array
    {
        return array_map(
            function ($item) {
                if (is_array($item) && count($item)) {
                    return SelectExpression::fromString($item[0])
                        ->withAlias($item[1] ?? null);
                }

                if (is_string($item)) {
                    return SelectExpression::fromString($item);
                }

                throw new RuntimeException("Bad select item.");
            },
            $this->params['select'] ?? []
        );
    }

    /**
     * Whether DISTINCT is applied.
     */
    public function isDistinct(): bool
    {
        return $this->params['distinct'] ?? false;
    }

    /**
     * Whether a FOR SHARE lock mode is set.
     */
    public function isForShare(): bool
    {
        return $this->params['forShare'] ?? false;
    }

    /**
     * Whether a FOR UPDATE lock mode is set.
     */
    public function isForUpdate(): bool
    {
        return $this->params['forUpdate'] ?? false;
    }

    /**
     * Get GROUP BY items.
     *
     * @return Expression[]
     */
    public function getGroup(): array
    {
        return array_map(
            function (string $item) {
                return Expression::create($item);
            },
            $this->params['groupBy'] ?? []
        );
    }

    /**
     * Get HAVING clause.
     */
    public function getHaving(): ?WhereClause
    {
        $havingClause = $this->params['havingClause'] ?? null;

        if ($havingClause === null || $havingClause === []) {
            return null;
        }

        return WhereClause::fromRaw($havingClause);
    }

    private function validateRawParams(array $params): void
    {
        $this->validateRawParamsSelecting($params);

        if (
            (
                !empty($params['joins']) ||
                !empty($params['leftJoins']) ||
                !empty($params['whereClause']) ||
                !empty($params['orderBy'])
            )
            &&
            empty($params['from']) && empty($params['fromQuery'])
        ) {
            throw new RuntimeException("Select params: Missing 'from'.");
        }
    }
}
