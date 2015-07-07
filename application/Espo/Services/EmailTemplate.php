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
 ************************************************************************/

namespace Espo\Services;

use \Espo\ORM\Entity;
use \Espo\Core\Entities\Person;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\NotFound;


class EmailTemplate extends Record
{

    protected function init()
    {
        $this->dependencies[] = 'fileManager';
        $this->dependencies[] = 'dateTime';
    }

    protected function getFileManager()
    {
        return $this->injections['fileManager'];
    }

    protected function getDateTime()
    {
        return $this->injections['dateTime'];
    }

    public function parse($id, array $params = array(), $copyAttachments = false)
    {
        $emailTemplate = $this->getEntity($id);
        if (empty($emailTemplate)) {
            throw new NotFound();
        }

        $entityList = array();
        if (!empty($params['entityHash']) && is_array($params['entityHash'])) {
            $entityList = $params['entityHash'];
        }

        if (!isset($entityList['User'])) {
            $entityList['User'] = $this->getUser();
        }

        if (!empty($params['emailAddress'])) {
            $emailAddress = $this->getEntityManager()->getRepository('EmailAddress')->where(array(
                'lower' => $params['emailAddress']
            ))->findOne();

            $entity = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddress($params['emailAddress']);

            if ($entity) {
                if ($entity instanceof Person) {
                    $entityList['Person'] = $entity;
                }
                if (empty($entityList[$entity->getEntityType()])) {
                    $entityList[$entity->getEntityType()] = $entity;
                }
            }
        }

        if (!empty($params['parentId']) && !empty($params['parentType'])) {
            $parent = $this->getEntityManager()->getEntity($params['parentType'], $params['parentId']);
            if (!empty($parent)) {
                $entityList[$params['parentType']] = $parent;
                $entityList['Parent'] = $parent;

                if (empty($entityList['Person']) && ($entity instanceof Person)) {
                    $entityList['Person'] = $parent;
                }
            }
        }

        $subject = $emailTemplate->get('subject');
        $body = $emailTemplate->get('body');

        foreach ($entityList as $type => $entity) {
            $subject = $this->parseText($type, $entity, $subject);
        }
        foreach ($entityList as $type => $entity) {
            $body = $this->parseText($type, $entity, $body);
        }

        $attachmentsIds = array();
        $attachmentsNames = new \StdClass();

        if ($copyAttachments) {
            $attachmentList = $emailTemplate->get('attachments');
            if (!empty($attachmentList)) {
                foreach ($attachmentList as $attachment) {
                    $clone = $this->getEntityManager()->getEntity('Attachment');
                    $data = $attachment->toArray();
                    unset($data['parentType']);
                    unset($data['parentId']);
                    unset($data['id']);
                    $clone->set($data);
                    $this->getEntityManager()->saveEntity($clone);

                    $contents = $this->getFileManager()->getContents('data/upload/' . $attachment->id);
                    if (empty($contents)) {
                        continue;
                    }
                    $this->getFileManager()->putContents('data/upload/' . $clone->id, $contents);

                    $attachmentsIds[] = $id = $clone->id;
                    $attachmentsNames->$id = $clone->get('name');
                }
            }
        }

        return array(
            'subject' => $subject,
            'body' => $body,
            'attachmentsIds' => $attachmentsIds,
            'attachmentsNames' => $attachmentsNames,
            'isHtml' => $emailTemplate->get('isHtml')
        );
    }

    protected function parseText($type, Entity $entity, $text)
    {
        $fieldList = array_keys($entity->getFields());
        $fieldList[] = $id;
        foreach ($fieldList as $field) {
            $value = $entity->get($field);
            if (is_object($value)) {
                continue;
            }

            if ($entity->fields[$field]['type'] == 'date') {
                $value = $this->getDateTime()->convertSystemDateToGlobal($value);
            } else if ($entity->fields[$field]['type'] == 'datetime') {
                $value = $this->getDateTime()->convertSystemDateTimeToGlobal($value);
            }
            $text = str_replace('{' . $type . '.' . $field . '}', $value, $text);
        }
        return $text;
    }
}

