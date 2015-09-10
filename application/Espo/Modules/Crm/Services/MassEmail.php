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

namespace Espo\Modules\Crm\Services;

use \Espo\Core\Exceptions\Forbidden;

use \Espo\ORM\Entity;

class MassEmail extends \Espo\Services\Record
{
    const MAX_ATTEMPT_COUNT = 3;

    protected function init()
    {
        $this->dependencies[] = 'container';
    }

    protected function getMailSender()
    {
        return $this->getInjection('container')->get('mailSender');
    }

    protected function beforeCreate(Entity $entity, array $data = array())
    {
        parent::beforeCreate($entity, $data);
        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }
    }

    public function createQueue(Entity $massEmail)
    {
        $existingQueueItemList = $this->getEntityManager()->getRepository('EmailQueueItem')->where(array(
            'status' => ['Pending', 'Failed'],
            'massEmailId' => $massEmail->id
        ))->find();
        foreach ($existingQueueItemList as $existingQueueItem) {
            $this->getEntityManager()->getMapper('RDB')->deleteFromDb('EmailQueueItem', $existingQueueItem->id);
        }

        $targetHash = array();
        $entityList = [];

        $targetListCollection = $massEmail->get('targetLists');
        foreach ($targetListCollection as $targetList) {
            $accountList = $targetList->get('accounts');
            foreach ($accountList as $account) {
                $hashId = $account->getEntityType() . '-'. $account->id;
                if (!empty($targetHash[$hashId])) {
                    continue;
                }
                $entityList[] = $account;
                $targetHash[$hashId] = true;
            }
            $contactList = $targetList->get('contacts');
            foreach ($contactList as $contact) {
                $hashId = $contact->getEntityType() . '-'. $contact->id;
                if (!empty($targetHash[$hashId])) {
                    continue;
                }
                $entityList[] = $contact;
                $targetHash[$hashId] = true;
            }
            $leadList = $targetList->get('leads');
            foreach ($leadList as $lead) {
                $hashId = $lead->getEntityType() . '-'. $lead->id;
                if (!empty($targetHash[$hashId])) {
                    continue;
                }
                $entityList[] = $lead;
                $targetHash[$hashId] = true;
            }
            $userList = $targetList->get('users');
            foreach ($userList as $user) {
                $hashId = $user->getEntityType() . '-'. $user->id;
                if (!empty($targetHash[$hashId])) {
                    continue;
                }
                $entityList[] = $user;
                $targetHash[$hashId] = true;
            }
        }

        foreach ($entityList as $target) {
            $queueItem = $this->getEntityManager()->getEntity('EmailQueueItem');
            $queueItem->set(array(
                'massEmailId' => $massEmail->id,
                'status' => 'Pending',
                'targetId' => $target->id,
                'targetType' => $target->getEntityType()
            ));
            $this->getEntityManager()->saveEntity($queueItem);
        }

        $massEmail->set('status', 'In Process');

        if (empty($entityList)) {
            $massEmail->set('status', 'Complete');
        }

        $this->getEntityManager()->saveEntity($massEmail);
    }

    protected function setFailed(Entity $massEmail)
    {
        $massEmail->set('status', 'Failed');
        $this->getEntityManager()->saveEntity($massEmail);

        $queueItemList = $this->getEntityManager()->getRepository('EmailQueueItem')->where(array(
            'status' => 'Pending',
            'massEmailId' => $massEmail->id
        ))->find();
        foreach ($queueItemList as $queueItem) {
            $queueItem->set('status', 'Failed');
            $this->getEntityManager()->saveEntity($queueItem);
        }
    }

    public function processSending(Entity $massEmail)
    {
        $queueItemList = $this->getEntityManager()->getRepository('EmailQueueItem')->where(array(
            'status' => 'Pending',
            'massEmailId' => $massEmail->id
        ))->find();

        $templateId = $massEmail->get('emailTemplateId');
        if (!$templateId) {
            $this->setFailed($massEmail);
            return;
        }

        $emailTemplate = $this->getEntityManager()->getEntity('EmailTemplate', $templateId);
        if (!$emailTemplate) {
            $this->setFailed($massEmail);
            return;
        }
        $attachmetList = $emailTemplate->get('attachmets');

        foreach ($queueItemList as $queueItem) {
            $this->sendQueueItem($queueItem, $massEmail, $emailTemplate, $attachmetList);
        }

        $countLeft = $this->getEntityManager()->getRepository('EmailQueueItem')->where(array(
            'status' => 'Pending',
            'massEmailId' => $massEmail->id
        ))->count();
        if ($countLeft == 0) {
            $massEmail->set('status', 'Complete');
            $this->getEntityManager()->saveEntity($massEmail);
        }
    }

    protected function sendQueueItem(Entity $queueItem, Entity $massEmail, Entity $emailTemplate, $attachmetList = [])
    {
        $target = $this->getEntityManager()->getEntity($queueItem->get('targetType'), $queueItem->get('targetId'));
        if (!$target || !$target->id || !$target->get('emailAddress')) {
            $queueItem->set('status', 'Failed');
            $this->getEntityManager()->saveEntity($queueItem);
            return;
        }

        $templateParams = array(
            'parent' => $target
        );

        $emailData = $this->getEmailTemplateService()->parseTemplate($emailTemplate, $templateParams);

        $email = $this->getEntityManager()->getEntity('Email');
        $email->set($emailData);
        $emailAddress = $target->get('emailAddress');

        if (empty($emailAddress)) {
            return false;
        }

        $email->set('to', $emailAddress);

        $params = array();
        if ($massEmail->get('fromAddress')) {
            $email->set('from', $massEmail->get('fromAddress'));
        }
        if ($massEmail->get('replyToAddress')) {
            $email->set('replyToAddress', $massEmail->get('replyToAddress'));
        }
        if ($massEmail->get('fromName')) {
            $params['fromName'] = $massEmail->get('fromName');
        }
        if ($massEmail->get('replyToName')) {
            $params['replyToName'] = $massEmail->get('replyToName');
        }

        try {
            $attemptCount = $queueItem->get('attemptCount');
            $attemptCount++;
            $queueItem->set('attemptCount', $attemptCount);

            $message = false;
            $this->getMailSender()->useGlobal()->send($email, $params, $message, $attachmetList);

            $queueItem->set('status', 'Sent');
            $queueItem->set('sentAt', date('Y-m-d H:i:s'));
            $this->getEntityManager()->saveEntity($queueItem);
        } catch (\Exception $e) {
            if ($queueItem->get('attemptCount') >= self::MAX_ATTEMPT_COUNT) {
                $queueItem->set('status', 'Failed');
            }
            $this->getEntityManager()->saveEntity($queueItem);
            $GLOBALS['log']->error('MassEmail#sendQueueItem: [' . $e->getCode() . '] ' .$e->getMessage());
            return false;
        }

        return true;
    }

    protected function getEmailTemplateService()
    {
        if (!$this->emailTemplateService) {
            $this->emailTemplateService = $this->getServiceFactory()->create('EmailTemplate');
        }
        return $this->emailTemplateService;
    }
}

