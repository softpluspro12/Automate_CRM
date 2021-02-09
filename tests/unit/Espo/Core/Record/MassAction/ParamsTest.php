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

namespace tests\unit\Espo\Core\Record\MassAction;

use Espo\Core\{
    Record\MassAction\Params,
};

use RuntimeException;

class ParamsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
    }

    public function testFromRawIds()
    {
        $params = Params::fromRaw(
            'Test',
            [
                'ids' => ['1'],
            ]
        );

        $this->assertEquals('Test', $params->getEntityType());

        $this->assertEquals(['1'], $params->getIds());

        $this->assertTrue($params->hasIds());
    }

    public function testFromRawSearchParams1()
    {
        $where = [
            [
                'type' => 'equals',
                'attribute' => 'name',
                'value' => 'test',
            ]
        ];

        $params = Params::fromRaw(
            'Test',
            [
                'where' => $where,
                'selectData' => [
                    'primaryFilter' => 'testFilter',
                ],
            ]
        );

        $searchParams = $params->getSearchParams();

        $this->assertEquals($where, $searchParams->getWhere());

        $this->assertEquals('testFilter', $searchParams->getPrimaryFilter());

        $this->assertFalse($params->hasIds());
    }

    public function testFromRawSearchParams2()
    {
        $where = [
            [
                'type' => 'equals',
                'attribute' => 'name',
                'value' => 'test',
            ]
        ];

        $params = Params::fromRaw(
            'Test',
            [
                'searchParams' => [
                    'primaryFilter' => 'testFilter',
                    'where' => $where,
                ],
            ]
        );

        $searchParams = $params->getSearchParams();

        $this->assertEquals($where, $searchParams->getWhere());

        $this->assertEquals('testFilter', $searchParams->getPrimaryFilter());

        $this->assertFalse($params->hasIds());
    }

    public function testFromRawSearchException1()
    {
        $this->expectException(RuntimeException::class);

        Params::fromRaw(
            'Test',
            [
                'ids' => ['1'],
                'searchParams' => [
                    'primaryFilter' => 'testFilter',
                    'where' => [],
                ],
            ]
        );
    }
}
