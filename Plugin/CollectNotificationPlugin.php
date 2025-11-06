<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Plugin;

use Psr\Log\LoggerInterface;
use SoftCommerce\PlentyProfile\Model\CollectManagementInterface;
use SoftCommerce\ProfileNotification\Api\NotificationManagementInterface;

/**
 * Plugin for collect management services to capture errors and log notifications
 */
class CollectNotificationPlugin
{
    /**
     * @param NotificationManagementInterface $notificationManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly NotificationManagementInterface $notificationManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Intercept execute method to track the entire collect process
     *
     * @param CollectManagementInterface $subject
     * @param \Closure $proceed
     * @param mixed ...$args
     * @return void
     */
    public function aroundExecute(
        CollectManagementInterface $subject,
        \Closure $proceed,
        ...$args
    ): void {
        $entityType = $this->extractEntityType($subject);
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            // Get profile ID from the collect service
            $profileId = $subject->getProfileId();

            // Profile ID is required for notification tracking
            if (!$profileId) {
                // Log to file but skip notification database tracking
                $this->logToFile(
                    sprintf('WARNING: %s collect service running without profile context. Notifications will not be tracked in database.', ucfirst($entityType))
                );

                // Execute without notification tracking
                $proceed(...$args);

                // Log completion to file
                $executionTime = microtime(true) - $startTime;
                $this->logToFile(
                    sprintf('%s collect completed in %.2f seconds (no profile context)', ucfirst($entityType), $executionTime)
                );

                return;
            }

            // Start process tracking with valid profile ID
            $processId = $this->notificationManager->startProcess(
                $profileId,
                $entityType . '_collect'
            );

            // Execute the collect process
            $proceed(...$args);

            // Log completion
            $executionTime = microtime(true) - $startTime;
            $peakMemory = memory_get_peak_usage(true) - $startMemory;

            $context = [
                'entity_type' => $entityType,
                'execution_time' => round($executionTime, 2),
                'peak_memory' => $this->formatBytes($peakMemory),
                'collect_stage' => 'complete'
            ];

            // Get counts from storage
            $responseStorage = $subject->getResponseStorage();
            if ($responseStorage && method_exists($responseStorage, 'getData')) {
                $responseData = $responseStorage->getData();
                $context['total_collected'] = is_array($responseData) ? count($responseData) : 0;
            }

            $this->notificationManager->notice(
                sprintf('%s collect completed in %.2f seconds', ucfirst($entityType), $executionTime),
                $context
            );

            // Update process summary if we have a process ID
            if ($processId) {
                $this->notificationManager->setSummary($processId, [
                    'total_processed' => $context['total_collected'] ?? 0,
                    'peak_memory' => memory_get_peak_usage(true) - $startMemory,
                    'execution_time' => $executionTime
                ]);

                $this->notificationManager->endProcess($processId, 'success');
            }

            // Log any messages from the collect process
            $this->logMessagesFromStorage($subject);

        } catch (\Throwable $e) {
            $context = [
                'service' => get_class($subject),
                'entity_type' => $entityType,
                'method' => 'execute',
                'arguments' => $this->sanitizeArguments($args)
            ];

            $this->notificationManager->logException($e, $context);

            // End process with error status if we have a process ID
            if (isset($processId) && $processId) {
                $this->notificationManager->endProcess($processId, 'error');
            }

            // Log any messages that were added before the error
            $this->logMessagesFromStorage($subject);

            throw $e;
        }
    }

    /**
     * Intercept saveData method to log any errors
     *
     * @param CollectManagementInterface $subject
     * @param \Closure $proceed
     * @param array $response
     * @param array $fields
     * @return void
     */
    /*public function aroundSaveData(
        CollectManagementInterface $subject,
        \Closure $proceed,
        array $response,
        array $fields = []
    ): void {
        try {
            $proceed($response, $fields);

            // Log messages from message storage after save
            $this->logMessagesFromStorage($subject);
        } catch (\Throwable $e) {
            // Log the exception
            $context = [
                'service' => get_class($subject),
                'method' => 'saveData',
                'response_count' => count($response),
                'fields' => $fields
            ];

            $this->notificationManager->logException($e, $context);

            // Log any messages that were added before the error
            $this->logMessagesFromStorage($subject);

            throw $e;
        }
    }*/

    /**
     * Intercept buildDataForSave method to catch data preparation errors
     *
     * @param CollectManagementInterface $subject
     * @param \Closure $proceed
     * @param array $response
     * @return array
     */
    /*public function aroundBuildDataForSave(
        CollectManagementInterface $subject,
        \Closure $proceed,
        array $response
    ): array {
        try {
            $result = $proceed($response);

            // Log any messages that were added during data building
            $this->logMessagesFromStorage($subject);

            return $result;
        } catch (\Throwable $e) {
            $context = [
                'service' => get_class($subject),
                'method' => 'buildDataForSave',
                'response_count' => count($response)
            ];

            $this->notificationManager->logException($e, $context);

            // Log any messages that were added before the error
            $this->logMessagesFromStorage($subject);

            throw $e;
        }
    }*/

    /**
     * Intercept cleanup method to log cleanup operations
     *
     * @param CollectManagementInterface $subject
     * @param \Closure $proceed
     * @param array $response
     * @return void
     */
    /*public function aroundCleanup(
        CollectManagementInterface $subject,
        \Closure $proceed,
        array $response
    ): void {
        try {
            $proceed($response);

            // Log cleanup completion
            $this->notificationManager->debug('Collect cleanup completed', [
                'service' => get_class($subject),
                'response_count' => count($response)
            ]);

            // Log any messages from the cleanup process
            $this->logMessagesFromStorage($subject);
        } catch (\Throwable $e) {
            $context = [
                'service' => get_class($subject),
                'method' => 'cleanup',
                'response_count' => count($response)
            ];

            $this->notificationManager->logException($e, $context);
            throw $e;
        }
    }*/

    /**
     * Log messages from collect service message collector
     *
     * @param CollectManagementInterface $collectService
     * @return void
     */
    private function logMessagesFromStorage(CollectManagementInterface $collectService): void
    {
        $messageCollector = $collectService->getMessageCollector();
        $data = $messageCollector->getMessages();

        // Extract entity type from class name
        $entityType = $this->extractEntityType($collectService);

        // Process messages similar to ServiceNotificationPlugin
        foreach ($data as $entityId => $messages) {
            if (!is_array($messages)) {
                continue;
            }

            foreach ($messages as $message) {
                if (!is_array($message) || !isset($message['message']) || !isset($message['status'])) {
                    continue;
                }

                $severity = $this->mapStatusToSeverity($message['status']);

                // Build context
                $context = [
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'status' => $message['status'],
                    'collect_stage' => 'collect'
                ];

                // Add any additional metadata from the message (messageCollector uses 'metadata' key)
                if (isset($message['metadata']) && is_array($message['metadata'])) {
                    $context = array_merge($context, $message['metadata']);
                }

                // Convert message to string if it's a Phrase object
                $messageText = (string)$message['message'];
                $method = $severity;
                $this->notificationManager->$method($messageText, $context);
            }
        }
    }

    /**
     * Extract entity type from collect service class
     *
     * @param CollectManagementInterface $collectService
     * @return string
     */
    private function extractEntityType(CollectManagementInterface $collectService): string
    {
        $className = get_class($collectService);

        // Handle specific known patterns
        if (str_contains($className, 'OrderCollect')) {
            return 'order';
        } elseif (str_contains($className, 'CustomerCollect') || str_contains($className, 'AccountCollect')) {
            return 'customer';
        } elseif (str_contains($className, 'ItemCollect') || str_contains($className, 'VariationCollect')) {
            return 'product';
        } elseif (str_contains($className, 'StockCollect')) {
            return 'stock';
        } elseif (str_contains($className, 'CategoryCollect')) {
            return 'category';
        } elseif (str_contains($className, 'PropertyCollect')) {
            return 'property';
        } elseif (str_contains($className, 'AttributeCollect')) {
            return 'attribute';
        }

        // Try to extract from class name pattern
        if (preg_match('/([A-Z][a-z]+)Collect/', $className, $matches)) {
            return strtolower($matches[1]);
        }

        // Fallback
        return 'entity';
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
     * Format bytes to human readable string
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
     * Sanitize arguments for logging
     *
     * @param array $args
     * @return array
     */
    private function sanitizeArguments(array $args): array
    {
        $sanitized = [];

        foreach ($args as $key => $arg) {
            if (is_object($arg)) {
                $sanitized[$key] = get_class($arg);
            } elseif (is_array($arg)) {
                $sanitized[$key] = 'array(' . count($arg) . ')';
            } elseif (is_string($arg) && strlen($arg) > 100) {
                $sanitized[$key] = substr($arg, 0, 100) . '...';
            } else {
                $sanitized[$key] = $arg;
            }
        }

        return $sanitized;
    }

    /**
     * Log message to file when profile context is not available
     *
     * @param string $message
     * @return void
     */
    private function logToFile(string $message): void
    {
        $this->logger->warning($message);
    }
}
