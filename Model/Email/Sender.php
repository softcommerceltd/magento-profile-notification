<?php
/**
 * Copyright © Byte8 Ltd (formerly Soft Commerce). All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Model\Email;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\FlagManager;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use SoftCommerce\Profile\Api\ProfileRepositoryInterface;
use SoftCommerce\ProfileNotification\Api\Data\NotificationInterface;
use SoftCommerce\ProfileNotification\Api\Data\NotificationSummaryInterface;

/**
 * Email Sender
 */
class Sender implements SenderInterface
{
    private const XML_PATH_EMAIL_ENABLED = 'softcommerce_profile_notification/email/enabled';
    private const XML_PATH_EMAIL_RECIPIENT = 'softcommerce_profile_notification/email/recipient';
    private const XML_PATH_EMAIL_SENDER = 'softcommerce_profile_notification/email/sender';
    private const XML_PATH_EMAIL_THRESHOLD = 'softcommerce_profile_notification/email/threshold';
    private const XML_PATH_EMAIL_BATCH_ENABLED = 'softcommerce_profile_notification/email/batch_enabled';
    private const XML_PATH_EMAIL_REAL_TIME_CRITICAL = 'softcommerce_profile_notification/email/real_time_critical';
    private const XML_PATH_EMAIL_SUPPRESS_DUPLICATES = 'softcommerce_profile_notification/email/suppress_duplicate_emails';

    private const EMAIL_TEMPLATE_CRITICAL_ALERT = 'profile_notification_critical_alert';
    private const EMAIL_TEMPLATE_PROCESS_SUMMARY = 'profile_notification_process_summary';
    private const EMAIL_TEMPLATE_BATCH_SUMMARY = 'profile_notification_batch_summary';

    private const FLAG_LAST_SUMMARY_DIGEST_PREFIX = 'softcommerce_notification_last_summary_digest_';
    private const MAX_DETAIL_ITEMS = 20;

    public function __construct(
        private readonly TransportBuilder $transportBuilder,
        private readonly StateInterface $inlineTranslation,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly ProfileRepositoryInterface $profileRepository,
        private readonly FlagManager $flagManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function isRealTimeCriticalEnabled(): bool
    {
        return $this->isEmailEnabled() &&
            $this->scopeConfig->isSetFlag(self::XML_PATH_EMAIL_REAL_TIME_CRITICAL, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @inheritdoc
     */
    public function shouldSendProcessSummary(): bool
    {
        if (!$this->isEmailEnabled()) {
            return false;
        }

        $threshold = $this->scopeConfig->getValue(self::XML_PATH_EMAIL_THRESHOLD, ScopeInterface::SCOPE_STORE);
        return in_array($threshold, ['all', 'summary']);
    }

    /**
     * @inheritdoc
     */
    public function sendCriticalAlert(string $message, array $context = []): void
    {
        if (!$this->isRealTimeCriticalEnabled()) {
            return;
        }

        try {
            $this->inlineTranslation->suspend();

            $storeId = $this->storeManager->getStore()->getId();
            $recipients = $this->getRecipients();

            if (empty($recipients)) {
                return;
            }

            $profileId = $context['profile_id'] ?? null;
            $profileName = $profileId ? $this->getProfileName($profileId) : null;

            // Prepare email variables — all values must be scalars safe for Magento's template filter.
            // Strings containing "{{" would be parsed as directives and break rendering.
            $templateVars = [
                'message' => $this->sanitizeForTemplate($message),
                'profile_name' => $profileName ?? ($context['alert_source'] ?? 'System'),
                'has_profile' => $profileName !== null,
                'severity' => 'CRITICAL',
                'timestamp' => date('Y-m-d H:i:s'),
                'server_name' => (string) gethostname(),
                'entity_type' => $context['entity_type'] ?? '',
                'entity_id' => (string) ($context['entity_id'] ?? ''),
                'exception_class' => $context['exception_class'] ?? '',
                'stack_trace' => $this->sanitizeForTemplate($context['stack_trace'] ?? '')
            ];

            // Send email
            $this->transportBuilder
                ->setTemplateIdentifier(self::EMAIL_TEMPLATE_CRITICAL_ALERT)
                ->setTemplateOptions([
                    'area' => 'adminhtml',
                    'store' => $storeId
                ])
                ->setTemplateVars($templateVars)
                ->setFromByScope($this->getSender())
                ->addTo($recipients);

            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();

            $this->inlineTranslation->resume();

        } catch (\Exception $e) {
            $this->inlineTranslation->resume();
            $this->logger->error('Failed to send critical alert email: ' . $e->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function sendProcessSummary(NotificationSummaryInterface $summary): void
    {
        if (!$this->shouldSendProcessSummary()) {
            return;
        }

        try {
            // Suppress duplicate process summary emails per profile
            if ($this->isSuppressDuplicatesEnabled()) {
                $currentDigest = $this->computeProcessSummaryDigest($summary);
                $flagKey = self::FLAG_LAST_SUMMARY_DIGEST_PREFIX . $summary->getProfileId();
                $lastDigest = $this->flagManager->getFlagData($flagKey);

                if ($currentDigest === $lastDigest) {
                    return;
                }

                $this->flagManager->saveFlag($flagKey, $currentDigest);
            }

            $this->inlineTranslation->suspend();

            $storeId = $this->storeManager->getStore()->getId();
            $recipients = $this->getRecipients();

            if (empty($recipients)) {
                return;
            }

            // Calculate metrics
            $executionTime = $summary->getExecutionTime() ?: 0;
            $peakMemory = $summary->getPeakMemory() ?: 0;

            // Prepare email variables
            $status = $summary->getStatus();
            $templateVars = [
                'summary' => $summary,
                'profile_name' => $this->getProfileName($summary->getProfileId()),
                'process_id' => $summary->getProcessId(),
                'status' => $status,
                'status_color' => $status === 'success' ? '#009900' : '#ff0000',
                'total_processed' => $summary->getTotalProcessed(),
                'total_success' => $summary->getTotalSuccess(),
                'total_warnings' => $summary->getTotalWarnings(),
                'total_errors' => $summary->getTotalErrors(),
                'total_critical' => $summary->getTotalCritical(),
                'started_at' => $summary->getStartedAt(),
                'finished_at' => $summary->getFinishedAt(),
                'execution_time' => $this->formatExecutionTime($executionTime),
                'peak_memory' => $this->formatBytes($peakMemory),
                'has_errors' => $summary->getTotalErrors() > 0 || $summary->getTotalCritical() > 0,
                'has_warnings' => $summary->getTotalWarnings() > 0
            ];

            // Send email
            $this->transportBuilder
                ->setTemplateIdentifier(self::EMAIL_TEMPLATE_PROCESS_SUMMARY)
                ->setTemplateOptions([
                    'area' => 'adminhtml',
                    'store' => $storeId
                ])
                ->setTemplateVars($templateVars)
                ->setFromByScope($this->getSender())
                ->addTo($recipients);

            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();

            $this->inlineTranslation->resume();

        } catch (\Exception $e) {
            $this->inlineTranslation->resume();
            $this->logger->error('Failed to send process summary email: ' . $e->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function sendBatchNotification(array $notifications): void
    {
        if (!$this->isEmailEnabled() || empty($notifications)) {
            return;
        }

        try {
            $this->inlineTranslation->suspend();

            $storeId = $this->storeManager->getStore()->getId();
            $recipients = $this->getRecipients();

            if (empty($recipients)) {
                return;
            }

            // Group notifications by severity
            $grouped = [
                'critical' => [],
                'error' => [],
                'warning' => [],
                'notice' => []
            ];

            foreach ($notifications as $notification) {
                $severity = $notification->getSeverity();
                if (isset($grouped[$severity])) {
                    $grouped[$severity][] = $notification;
                }
            }

            // Build deduplicated detail items for the email body
            $detailItems = $this->buildNotificationDetails($notifications);

            // Prepare email variables
            $templateVars = [
                'total_notifications' => count($notifications),
                'critical_count' => count($grouped['critical']),
                'error_count' => count($grouped['error']),
                'warning_count' => count($grouped['warning']),
                'notice_count' => count($grouped['notice']),
                'notifications_by_severity' => $grouped,
                'notification_items' => $detailItems,
                'has_details' => !empty($detailItems),
                'details_truncated' => count($detailItems) >= self::MAX_DETAIL_ITEMS,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            // Send email
            $this->transportBuilder
                ->setTemplateIdentifier(self::EMAIL_TEMPLATE_BATCH_SUMMARY)
                ->setTemplateOptions([
                    'area' => 'adminhtml',
                    'store' => $storeId
                ])
                ->setTemplateVars($templateVars)
                ->setFromByScope($this->getSender())
                ->addTo($recipients);

            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();

            $this->inlineTranslation->resume();

        } catch (\Exception $e) {
            $this->inlineTranslation->resume();
            $this->logger->error('Failed to send batch notification email: ' . $e->getMessage());
        }
    }

    /**
     * Check if email notifications are enabled
     *
     * @return bool
     */
    private function isEmailEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_EMAIL_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get email recipients
     *
     * @return array
     */
    private function getRecipients(): array
    {
        $recipients = $this->scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT, ScopeInterface::SCOPE_STORE);
        if (!$recipients) {
            return [];
        }

        return array_map('trim', explode(',', $recipients));
    }

    /**
     * Get email sender
     *
     * @return string
     */
    private function getSender(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_EMAIL_SENDER, ScopeInterface::SCOPE_STORE) ?: 'general';
    }

    /**
     * Get profile name by ID
     *
     * @param int|null $profileId
     * @return string|null
     */
    private function getProfileName(?int $profileId): ?string
    {
        if (!$profileId) {
            return null;
        }

        try {
            $profile = $this->profileRepository->getById($profileId);
            return $profile->getName() ?: 'Profile #' . $profileId;
        } catch (\Exception $e) {
            return 'Profile #' . $profileId;
        }
    }

    /**
     * Sanitize a string value for use in Magento email templates.
     *
     * Magento's template filter processes the entire template output after variable substitution,
     * so any "{{...}}" patterns inside variable values (e.g. from stack traces or error messages)
     * will be interpreted as directives and cause rendering to fail.
     *
     * @param string $value
     * @return string
     */
    private function sanitizeForTemplate(string $value): string
    {
        // Replace {{ with escaped version that won't be parsed as a directive
        return str_replace('{{', '{ {', $value);
    }

    /**
     * Format execution time
     *
     * @param float $seconds
     * @return string
     */
    private function formatExecutionTime(float $seconds): string
    {
        if ($seconds < 60) {
            return sprintf('%.2f seconds', $seconds);
        }

        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;

        return sprintf('%d minutes %.2f seconds', $minutes, $seconds);
    }

    /**
     * Format bytes to human readable
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return sprintf('%.2f %s', $bytes, $units[$i]);
    }

    /**
     * Check if duplicate email suppression is enabled
     *
     * @return bool
     */
    private function isSuppressDuplicatesEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_EMAIL_SUPPRESS_DUPLICATES, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Build deduplicated notification detail items for the email body.
     *
     * Groups notifications by entity ID + title to avoid repeating the same
     * error multiple times, and returns DataObject instances for use with
     * the {{for}} directive in email templates.
     *
     * @param NotificationInterface[] $notifications
     * @return DataObject[]
     */
    private function buildNotificationDetails(array $notifications): array
    {
        $items = [];
        $seen = [];

        foreach ($notifications as $notification) {
            $entityId = $notification->getEntityId() ?: '';
            $title = $notification->getTitle();
            $signature = $entityId . '|' . $title;

            if (isset($seen[$signature])) {
                continue;
            }

            $seen[$signature] = true;

            $profileName = $notification->getProfileId()
                ? $this->getProfileName($notification->getProfileId())
                : null;

            $items[] = new DataObject([
                'severity' => strtoupper($notification->getSeverity()),
                'profile_name' => $profileName ?: 'N/A',
                'entity_type' => $notification->getEntityType() ?: 'N/A',
                'entity_id' => $entityId ?: 'N/A',
                'title' => $this->sanitizeForTemplate($title),
            ]);

            if (count($items) >= self::MAX_DETAIL_ITEMS) {
                break;
            }
        }

        return $items;
    }

    /**
     * Compute a digest hash from a process summary.
     *
     * Uses profile ID, status and error/warning/critical counts so that
     * a new email is only sent when the outcome of a profile run changes.
     *
     * @param NotificationSummaryInterface $summary
     * @return string
     */
    private function computeProcessSummaryDigest(NotificationSummaryInterface $summary): string
    {
        return md5(json_encode([
            'profile_id' => $summary->getProfileId(),
            'status' => $summary->getStatus(),
            'total_warnings' => $summary->getTotalWarnings(),
            'total_errors' => $summary->getTotalErrors(),
            'total_critical' => $summary->getTotalCritical(),
        ]));
    }
}
