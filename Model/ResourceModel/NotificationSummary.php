<?php
/**
 * Copyright © Byte8 Ltd (formerly Soft Commerce). All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Notification Summary Resource Model
 */
class NotificationSummary extends AbstractDb
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'softcommerce_profile_notification_summary_resource';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('softcommerce_profile_notification_summary', 'summary_id');
    }

    /**
     * Load summary by process ID
     *
     * @param string $processId
     * @return array
     */
    public function loadByProcessId(string $processId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('process_id = ?', $processId);
        
        return $connection->fetchRow($select) ?: [];
    }
}