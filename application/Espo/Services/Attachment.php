<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2017 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Services;

use \Espo\ORM\Entity;

class Attachment extends Record
{
    protected $notFilteringAttributeList = ['contents'];

    public function upload($fileData)
    {
        if (!$this->getAcl()->checkScope('Attachment', 'create')) {
            throw new Forbidden();
        }

        $arr = explode(',', $fileData);
        if (count($arr) > 1) {
            list($prefix, $contents) = $arr;
            $contents = base64_decode($contents);
        } else {
            $contents = '';
        }

        $attachment = $this->getEntityManager()->getEntity('Attachment');
        $attachment->set('contents', $contents);
        $this->getEntityManager()->saveEntity($attachment);

        return $attachment;
    }

    public function createEntity($data)
    {
        if (!empty($data['file'])) {
            list($prefix, $contents) = explode(',', $data['file']);
            $contents = base64_decode($contents);
            $data['contents'] = $contents;
        }

        $entity = parent::createEntity($data);

        if (!empty($data['file'])) {
            $entity->clear('contents');
        }

        return $entity;
    }

    protected function beforeCreate(Entity $entity, array $data = array())
    {
        $storage = $entity->get('storage');
        if ($storage && !$this->getMetadata()->get(['app', 'fileStorage', 'implementationClassNameMap', $storage])) {
            $entity->clear('storage');
        }
    }

    protected function beforeUpdate(Entity $entity, array $data = array())
    {
        $storage = $entity->get('storage');
        if ($storage && !$this->getMetadata()->get(['app', 'fileStorage', 'implementationClassNameMap', $storage])) {
            $entity->clear('storage');
        }
    }
}

