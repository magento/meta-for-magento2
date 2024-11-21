<?php

declare(strict_types=1);

/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Meta\BusinessExtension\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Meta\BusinessExtension\Api\Data\MetaIssueNotificationInterface;

class MetaIssueNotification extends AbstractDb
{
    private const TABLE_NAME = 'meta_issue_notifications';
    public const VERSION_NOTIFICATION_ID = 'version_notification';

    /**
     * Construct
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(self::TABLE_NAME, 'entity_id');
    }

    /**
     * Delete all notifications matching notification id
     *
     * @param string $notificationId
     * @return int
     */
    public function deleteByNotificationId(string $notificationId): int
    {
        $connection = $this->_resources->getConnection();

        $metaIssueNotificationTable = $this->_resources->getTableName(self::TABLE_NAME);
        return $connection->delete($metaIssueNotificationTable, ['notification_id = ?' => $notificationId]);
    }

    /**
     * Save version notification
     *
     * @param MetaIssueNotificationInterface $notification
     */
    public function saveVersionNotification(MetaIssueNotificationInterface $notification)
    {
        $connection = $this->_resources->getConnection();
        $version_notification = [
            'notification_id' => MetaIssueNotification::VERSION_NOTIFICATION_ID,
            'message' => $notification->getMessage(),
            'severity' => $notification->getSeverity(),
        ];

        $metaIssueNotificationTable = $this->_resources->getTableName(self::TABLE_NAME);
        $connection->insertOnDuplicate($metaIssueNotificationTable, $version_notification);
    }

    /**
     * Load version notification
     */
    public function loadVersionNotification()
    {
        $connection = $this->_resources->getConnection();

        $metaIssueNotificationTable = $this->_resources->getTableName(self::TABLE_NAME);
        $select = $connection->select()
            ->from($metaIssueNotificationTable)
            ->where('notification_id = ?', MetaIssueNotification::VERSION_NOTIFICATION_ID);
        return $connection->fetchRow($select);
    }
}
