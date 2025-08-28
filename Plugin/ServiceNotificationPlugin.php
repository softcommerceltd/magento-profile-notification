<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Plugin;

use Magento\Framework\Exception\LocalizedException;
use SoftCommerce\Profile\Model\ServiceAbstract\ServiceInterface;
use SoftCommerce\ProfileNotification\Api\NotificationManagementInterface;

/**
 * Plugin for all profile services to capture execution and log notifications
 */
class ServiceNotificationPlugin
{
    /**
     * @param NotificationManagementInterface $notificationManager
     */
    public function __construct(
        private NotificationManagementInterface $notificationManager
    ) {
    }

    /**
     * Wrap service execution with notification logging
     *
     * @param ServiceInterface $subject
     * @param \Closure $proceed
     * @param mixed ...$args
     * @return void
     */
    public function aroundExecute(
        ServiceInterface $subject,
        \Closure $proceed,
        ...$args
    ): void {
        try {
            $profileId = $subject->getProfileId();
        } catch (LocalizedException $e) {
            // If we can't get profile ID, just proceed without notification
            $proceed(...$args);
            return;
        }

        $processId = $this->notificationManager->startProcess(
            $profileId,
            $this->getServiceTypeId($subject)
        );

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $status = 'success';

        try {
            $proceed(...$args);

            // Log messages from message storage
            $this->logMessagesFromStorage($subject);

            // Update summary with execution metrics
            $this->notificationManager->setSummary($processId, [
                'total_processed' => $this->countTotalProcessed($subject),
                'peak_memory' => memory_get_peak_usage(true) - $startMemory,
                'execution_time' => microtime(true) - $startTime
            ]);

        } catch (\Throwable $e) {
            $status = 'error';
            $this->notificationManager->logException($e, [
                'profile_id' => $profileId,
                'service' => get_class($subject),
                'type_id' => $this->getServiceTypeId($subject)
            ]);
            throw $e;
        } finally {
            $this->notificationManager->endProcess($processId, $status);
        }
    }

    /**
     * Log messages from service message storage
     *
     * @param ServiceInterface $service
     * @return void
     */
    private function logMessagesFromStorage(ServiceInterface $service): void
    {
        $messageStorage = $service->getMessageStorage();
        $data = $messageStorage->getData();

        // MessageStorage data is grouped by entity type (e.g., 'plenty_stock_import')
        foreach ($data as $entityType => $messages) {
            if (!is_array($messages)) {
                continue;
            }

            // Process each message within the entity type group
            foreach ($messages as $message) {
                if (!is_array($message) || !isset($message['message']) || !isset($message['status'])) {
                    continue;
                }

                $severity = $this->mapStatusToSeverity($message['status']);
                
                // Convert entity type to string if it's numeric (entity ID)
                $entityTypeStr = is_numeric($entityType) ? 'entity_' . $entityType : (string)$entityType;
                
                // Build context from message data
                $context = [
                    'entity_id' => $message['entity'] ?? $message['entity_id'] ?? $entityType,
                    'entity_type' => $entityTypeStr,
                    'status' => $message['status']
                ];

                // Add any additional data from the message
                if (isset($message['data']) && is_array($message['data'])) {
                    $context = array_merge($context, $message['data']);
                }

                // Skip if this is just a summary message that duplicates processor logs
                if ($this->shouldSkipMessage($message, $entityType)) {
                    continue;
                }

                $method = $severity;
                // Convert message to string if it's a Phrase object
                $messageText = (string)$message['message'];
                $this->notificationManager->$method($messageText, $context);
            }
        }
    }

    /**
     * Determine if a message should be skipped to avoid duplication
     *
     * @param array $message
     * @param string|int $entityType
     * @return bool
     */
    private function shouldSkipMessage(array $message, string|int $entityType): bool
    {
        // Convert message to string if it's a Phrase object
        $messageText = isset($message['message']) ? (string)$message['message'] : '';
        
        // Skip summary messages that duplicate what we already logged
        if (str_contains($messageText, 'completed successfully') &&
            str_contains($messageText, 'Total Items:')) {
            return true;
        }

        // Add more skip conditions as needed
        return false;
    }

    /**
     * Count total processed items
     *
     * @param ServiceInterface $service
     * @return int
     */
    private function countTotalProcessed(ServiceInterface $service): int
    {
        // Try to get count from request storage
        $requestStorage = $service->getRequestStorage();
        if ($requestStorage && is_array($requestStorage)) {
            return count($requestStorage);
        }

        // Try to get count from data storage
        $dataStorage = $service->getDataStorage();
        $data = $dataStorage->getData();
        if (is_array($data)) {
            return count($data);
        }

        return 0;
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

    /**
     * Get service type ID from class name
     *
     * @param ServiceInterface $service
     * @return string
     */
    private function getServiceTypeId(ServiceInterface $service): string
    {
        $className = get_class($service);

        // Extract type from class name (e.g., OrderExportService -> order_export)
        if (preg_match('/([A-Z][a-z]+)*Service$/', $className, $matches)) {
            $type = str_replace('Service', '', $matches[0]);
            // Convert CamelCase to snake_case
            $type = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $type));
            return $type;
        }

        // Fallback to simple class name
        $parts = explode('\\', $className);
        return strtolower(end($parts));
    }
}
