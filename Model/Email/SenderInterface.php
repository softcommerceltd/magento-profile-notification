<?php
/**
 * Copyright © Byte8 Ltd (formerly Soft Commerce). All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Model\Email;

use SoftCommerce\ProfileNotification\Api\Data\NotificationSummaryInterface;

/**
 * Email Sender Interface
 */
interface SenderInterface
{
    /**
     * Check if real-time critical alerts are enabled
     *
     * @return bool
     */
    public function isRealTimeCriticalEnabled(): bool;
    
    /**
     * Check if process summary emails should be sent
     *
     * @return bool
     */
    public function shouldSendProcessSummary(): bool;
    
    /**
     * Send critical alert email immediately
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function sendCriticalAlert(string $message, array $context = []): void;
    
    /**
     * Send process summary email
     *
     * @param NotificationSummaryInterface $summary
     * @return void
     */
    public function sendProcessSummary(NotificationSummaryInterface $summary): void;
    
    /**
     * Send batch notification email
     *
     * @param array $notifications
     * @return void
     */
    public function sendBatchNotification(array $notifications): void;
}