<?php
/**
 * Copyright © Byte8 Ltd (formerly Soft Commerce). All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Cron;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\FlagManager;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use SoftCommerce\ProfileNotification\Api\Data\NotificationInterface;
use SoftCommerce\ProfileNotification\Api\NotificationRepositoryInterface;
use SoftCommerce\ProfileNotification\Model\Email\SenderInterface;

/**
 * Cron job to send batch notification emails
 */
class SendBatchEmails
{
    private const XML_PATH_ENABLED = 'softcommerce_profile_notification/email/enabled';
    private const XML_PATH_BATCH_ENABLED = 'softcommerce_profile_notification/email/batch_enabled';
    private const XML_PATH_BATCH_INTERVAL = 'softcommerce_profile_notification/email/batch_interval';
    private const XML_PATH_THRESHOLD = 'softcommerce_profile_notification/email/threshold';
    private const XML_PATH_SUPPRESS_DUPLICATES = 'softcommerce_profile_notification/email/suppress_duplicate_emails';
    private const FLAG_LAST_BATCH_DIGEST = 'softcommerce_notification_last_batch_digest';

    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SenderInterface $emailSender,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly FlagManager $flagManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Execute batch email sending
     *
     * @return void
     */
    public function execute(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            $notifications = $this->getUnsentNotifications();

            if (empty($notifications)) {
                return;
            }

            $shouldSend = true;

            if ($this->isSuppressDuplicatesEnabled()) {
                $currentDigest = $this->computeDigest($notifications);
                $lastDigest = $this->flagManager->getFlagData(self::FLAG_LAST_BATCH_DIGEST);

                if ($currentDigest === $lastDigest) {
                    $shouldSend = false;
                } else {
                    $this->flagManager->saveFlag(self::FLAG_LAST_BATCH_DIGEST, $currentDigest);
                }
            }

            if ($shouldSend) {
                $this->emailSender->sendBatchNotification($notifications);
            }

            // Always mark notifications as emailed to prevent accumulation
            foreach ($notifications as $notification) {
                $notification->setIsEmailed(true);
                $this->notificationRepository->save($notification);
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to send batch notification email: ' . $e->getMessage());
        }
    }

    /**
     * Check if batch emails are enabled
     *
     * @return bool
     */
    private function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE) &&
            $this->scopeConfig->isSetFlag(self::XML_PATH_BATCH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if duplicate email suppression is enabled
     *
     * @return bool
     */
    private function isSuppressDuplicatesEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_SUPPRESS_DUPLICATES, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Compute a digest hash from notifications to detect duplicate content.
     *
     * The digest is based on severity counts and unique error messages,
     * so an email is only sent when the notification landscape actually changes.
     *
     * @param NotificationInterface[] $notifications
     * @return string
     */
    private function computeDigest(array $notifications): string
    {
        $severityCounts = [];
        $messageSignatures = [];

        foreach ($notifications as $notification) {
            $severity = $notification->getSeverity();
            $severityCounts[$severity] = ($severityCounts[$severity] ?? 0) + 1;
            $messageSignatures[] = $severity . ':' . $notification->getTitle();
        }

        ksort($severityCounts);
        sort($messageSignatures);

        return md5(json_encode([
            'severity_counts' => $severityCounts,
            'messages' => $messageSignatures,
        ]));
    }

    /**
     * Get unsent notifications based on threshold
     *
     * @return NotificationInterface[]
     * @throws LocalizedException
     */
    private function getUnsentNotifications(): array
    {
        $threshold = $this->scopeConfig->getValue(self::XML_PATH_THRESHOLD, ScopeInterface::SCOPE_STORE);
        $interval = (int) $this->scopeConfig->getValue(self::XML_PATH_BATCH_INTERVAL, ScopeInterface::SCOPE_STORE);

        $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$interval} minutes"));

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('is_emailed', false)
            ->addFilter('created_at', $cutoffTime, 'lteq');

        // Apply severity threshold
        switch ($threshold) {
            case 'critical':
                $searchCriteria->addFilter('severity', 'critical');
                break;
            case 'error':
                $searchCriteria->addFilter('severity', ['error', 'critical'], 'in');
                break;
            case 'warning':
                $searchCriteria->addFilter('severity', ['warning', 'error', 'critical'], 'in');
                break;
            case 'summary':
                // Don't send individual notifications, only process summaries
                return [];
        }

        $searchResults = $this->notificationRepository->getList($searchCriteria->create());

        return $searchResults->getItems();
    }
}
