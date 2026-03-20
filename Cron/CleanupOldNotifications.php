<?php
/**
 * Copyright © Byte8 Ltd (formerly Soft Commerce). All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use SoftCommerce\ProfileNotification\Model\ResourceModel\Notification as NotificationResource;

/**
 * Cron job to cleanup old notifications
 */
class CleanupOldNotifications
{
    private const XML_PATH_ENABLED = 'softcommerce_profile_notification/general/enabled';
    private const XML_PATH_RETENTION_DAYS = 'softcommerce_profile_notification/general/retention_days';
    private const XML_PATH_MAX_NOTIFICATIONS = 'softcommerce_profile_notification/general/max_notifications';
    private const BATCH_SIZE = 50000;

    /**
     * @param NotificationResource $resource
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly NotificationResource $resource,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Execute cleanup
     *
     * @return void
     */
    public function execute(): void
    {
        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE)) {
            return;
        }

        try {
            $this->cleanupByAge();
            $this->cleanupByCount();
        } catch (\Exception $e) {
            $this->logger->error('Profile notification cleanup failed: ' . $e->getMessage());
        }
    }

    /**
     * Cleanup notifications by age using direct SQL in batches
     *
     * @return void
     */
    private function cleanupByAge(): void
    {
        $retentionDays = (int) $this->scopeConfig->getValue(
            self::XML_PATH_RETENTION_DAYS,
            ScopeInterface::SCOPE_STORE
        );

        if ($retentionDays <= 0) {
            return;
        }

        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getMainTable();
        $totalDeleted = 0;

        do {
            $ids = $connection->fetchCol(
                $connection->select()
                    ->from($tableName, ['notification_id'])
                    ->where('created_at < ?', $cutoffDate)
                    ->limit(self::BATCH_SIZE)
            );

            if (empty($ids)) {
                break;
            }

            $deleted = $connection->delete(
                $tableName,
                ['notification_id IN (?)' => $ids]
            );
            $totalDeleted += $deleted;
        } while (count($ids) >= self::BATCH_SIZE);

        if ($totalDeleted > 0) {
            $this->logger->info(sprintf(
                'Cleaned up %d notifications older than %d days',
                $totalDeleted,
                $retentionDays
            ));
        }
    }

    /**
     * Cleanup notifications by count using direct SQL in batches
     *
     * @return void
     */
    private function cleanupByCount(): void
    {
        $maxNotifications = (int) $this->scopeConfig->getValue(
            self::XML_PATH_MAX_NOTIFICATIONS,
            ScopeInterface::SCOPE_STORE
        );

        if ($maxNotifications <= 0) {
            return;
        }

        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getMainTable();
        $totalCount = (int) $connection->fetchOne(
            $connection->select()->from($tableName, ['COUNT(*)'])
        );

        if ($totalCount <= $maxNotifications) {
            return;
        }

        $totalDeleted = 0;

        do {
            $ids = $connection->fetchCol(
                $connection->select()
                    ->from($tableName, ['notification_id'])
                    ->order('created_at ASC')
                    ->limit(self::BATCH_SIZE)
            );

            if (empty($ids)) {
                break;
            }

            $deleted = $connection->delete(
                $tableName,
                ['notification_id IN (?)' => $ids]
            );
            $totalDeleted += $deleted;
            $remaining = $totalCount - $totalDeleted;
        } while (count($ids) >= self::BATCH_SIZE && $remaining > $maxNotifications);

        if ($totalDeleted > 0) {
            $this->logger->info(sprintf(
                'Cleaned up %d notifications to maintain maximum of %d',
                $totalDeleted,
                $maxNotifications
            ));
        }
    }
}
