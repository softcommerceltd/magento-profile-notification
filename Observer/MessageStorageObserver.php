<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SoftCommerce\ProfileNotification\Api\NotificationManagementInterface;

/**
 * Observer to capture messages added to message storage
 */
class MessageStorageObserver implements ObserverInterface
{
    /**
     * @param NotificationManagementInterface $notificationManager
     */
    public function __construct(
        private NotificationManagementInterface $notificationManager
    ) {
    }
    
    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $message = $observer->getData('message');
        if (!$message || !is_array($message)) {
            return;
        }
        
        // Extract message details
        $messageText = $message['message'] ?? '';
        $status = $message['status'] ?? 'notice';
        
        // Build context
        $context = [
            'entity_id' => $message['entity_id'] ?? null,
            'entity_type' => $message['type_id'] ?? null,
            'status' => $status
        ];
        
        // Add any additional data
        if (isset($message['data']) && is_array($message['data'])) {
            $context = array_merge($context, $message['data']);
        }
        
        // Map status to severity and log
        $severity = $this->mapStatusToSeverity($status);
        $method = $severity;
        
        try {
            $this->notificationManager->$method($messageText, $context);
        } catch (\Exception $e) {
            // Silently fail to avoid breaking the main process
        }
    }
    
    /**
     * Map message status to notification severity
     *
     * @param string $status
     * @return string
     */
    private function mapStatusToSeverity(string $status): string
    {
        return match(strtolower($status)) {
            'success', 'complete', 'completed' => NotificationManagementInterface::SEVERITY_NOTICE,
            'warning', 'skipped', 'skip' => NotificationManagementInterface::SEVERITY_WARNING,
            'error', 'failed', 'fail' => NotificationManagementInterface::SEVERITY_ERROR,
            'critical' => NotificationManagementInterface::SEVERITY_CRITICAL,
            default => NotificationManagementInterface::SEVERITY_DEBUG
        };
    }
}