<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use SoftCommerce\ProfileNotification\Model\ResourceModel\Notification\CollectionFactory;

/**
 * Cron job to cleanup old notifications
 */
class CleanupOldNotifications
{
    private const XML_PATH_ENABLED = 'softcommerce_profile_notification/general/enabled';
    private const XML_PATH_RETENTION_DAYS = 'softcommerce_profile_notification/general/retention_days';
    private const XML_PATH_MAX_NOTIFICATIONS = 'softcommerce_profile_notification/general/max_notifications';

    /**
     * @param CollectionFactory $collectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
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
     * Cleanup notifications by age
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

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('created_at', ['lt' => $cutoffDate]);
        $collection->walk('delete');

        $count = $collection->getSize();
        if ($count > 0) {
            $this->logger->info(sprintf(
                'Cleaned up %d notifications older than %d days',
                $count,
                $retentionDays
            ));
        }
    }

    /**
     * Cleanup notifications by count
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

        $collection = $this->collectionFactory->create();
        $totalCount = $collection->getSize();

        if ($totalCount <= $maxNotifications) {
            return;
        }

        $deleteCount = $totalCount - $maxNotifications;

        $collection = $this->collectionFactory->create();
        $collection->setOrder('created_at', 'ASC');
        $collection->setPageSize($deleteCount);
        $collection->walk('delete');

        $this->logger->info(sprintf(
            'Cleaned up %d notifications to maintain maximum of %d',
            $deleteCount,
            $maxNotifications
        ));
    }
}
