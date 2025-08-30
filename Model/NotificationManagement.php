<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use SoftCommerce\ProfileNotification\Api\NotificationManagementInterface;
use SoftCommerce\ProfileNotification\Api\NotificationRepositoryInterface;
use SoftCommerce\ProfileNotification\Api\Data\NotificationInterfaceFactory;
use SoftCommerce\ProfileNotification\Api\Data\NotificationSummaryInterfaceFactory;
use SoftCommerce\ProfileNotification\Model\Email\SenderInterface;
use SoftCommerce\ProfileNotification\Model\ResourceModel\NotificationSummary as SummaryResource;

/**
 * Notification Management Service
 */
class NotificationManagement implements NotificationManagementInterface
{
    private ?int $currentProfileId = null;
    private ?string $currentProcessId = null;
    private ?string $currentTypeId = null;
    private array $currentContext = [];
    
    /**
     * @param NotificationRepositoryInterface $notificationRepository
     * @param NotificationInterfaceFactory $notificationFactory
     * @param NotificationSummaryInterfaceFactory $summaryFactory
     * @param SummaryResource $summaryResource
     * @param SenderInterface $emailSender
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private NotificationInterfaceFactory $notificationFactory,
        private NotificationSummaryInterfaceFactory $summaryFactory,
        private SummaryResource $summaryResource,
        private SenderInterface $emailSender,
        private SerializerInterface $serializer,
        private LoggerInterface $logger
    ) {
    }
    
    /**
     * @inheritdoc
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::SEVERITY_DEBUG, $message, $context);
    }
    
    /**
     * @inheritdoc
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log(self::SEVERITY_NOTICE, $message, $context);
    }
    
    /**
     * @inheritdoc
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::SEVERITY_WARNING, $message, $context);
    }
    
    /**
     * @inheritdoc
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::SEVERITY_ERROR, $message, $context);
    }
    
    /**
     * @inheritdoc
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::SEVERITY_CRITICAL, $message, $context);
        
        // Send immediate email for critical errors
        try {
            if ($this->emailSender->isRealTimeCriticalEnabled()) {
                $this->emailSender->sendCriticalAlert($message, $context);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to send critical alert email: ' . $e->getMessage());
        }
    }
    
    /**
     * @inheritdoc
     */
    public function logException(\Throwable $exception, array $context = []): void
    {
        $context['exception_class'] = get_class($exception);
        $context['stack_trace'] = $exception->getTraceAsString();
        $context['file'] = $exception->getFile();
        $context['line'] = $exception->getLine();
        
        $severity = $exception instanceof \Error ? self::SEVERITY_CRITICAL : self::SEVERITY_ERROR;
        $this->log($severity, $exception->getMessage(), $context);
    }
    
    /**
     * @inheritdoc
     */
    public function startProcess(int $profileId, string $typeId): string
    {
        $processId = uniqid('process_' . $profileId . '_', true);
        
        $summary = $this->summaryFactory->create();
        $summary->setProfileId($profileId);
        $summary->setProcessId($processId);
        $summary->setStatus('running');
        $summary->setStartedAt(date('Y-m-d H:i:s'));
        $summary->setTotalProcessed(0);
        $summary->setTotalSuccess(0);
        $summary->setTotalWarnings(0);
        $summary->setTotalErrors(0);
        $summary->setTotalCritical(0);
        
        try {
            $this->summaryResource->save($summary);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create process summary: ' . $e->getMessage());
        }
        
        $this->setProfileId($profileId);
        $this->setProcessId($processId);
        $this->currentTypeId = $typeId;
        
        $this->notice(sprintf('Started %s process for profile ID %d', $typeId, $profileId), [
            'type' => 'process_start',
            'type_id' => $typeId,
            'entity_type' => $typeId
        ]);
        
        return $processId;
    }
    
    /**
     * @inheritdoc
     */
    public function endProcess(string $processId, string $status): void
    {
        try {
            $summary = $this->summaryFactory->create();
            $summaryData = $this->summaryResource->loadByProcessId($processId);
            
            if (!empty($summaryData)) {
                $summary->setData($summaryData);
                $summary->setStatus($status);
                $summary->setFinishedAt(date('Y-m-d H:i:s'));
                $summary->setPeakMemory(memory_get_peak_usage(true));
                
                if ($summary->getStartedAt()) {
                    $startTime = strtotime($summary->getStartedAt());
                    $endTime = time();
                    $summary->setExecutionTime($endTime - $startTime);
                }
                
                $this->summaryResource->save($summary);
                
                // Send process summary email if configured
                if ($this->emailSender->shouldSendProcessSummary()) {
                    $this->emailSender->sendProcessSummary($summary);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to update process summary: ' . $e->getMessage());
        }
        
        $message = $this->currentTypeId 
            ? sprintf('%s process completed with status: %s', $this->currentTypeId, $status)
            : sprintf('Process completed with status: %s', $status);
            
        $this->notice($message, [
            'type' => 'process_end',
            'type_id' => $this->currentTypeId,
            'entity_type' => $this->currentTypeId,
            'status' => $status
        ]);
        
        $this->currentProcessId = null;
        $this->currentTypeId = null;
    }
    
    /**
     * @inheritdoc
     */
    public function setSummary(string $processId, array $summary): void
    {
        try {
            $summaryModel = $this->summaryFactory->create();
            $summaryData = $this->summaryResource->loadByProcessId($processId);
            
            if (!empty($summaryData)) {
                $summaryModel->setData($summaryData);
                $summaryModel->addData($summary);
                $this->summaryResource->save($summaryModel);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to update process summary: ' . $e->getMessage());
        }
    }
    
    /**
     * @inheritdoc
     */
    public function setProfileId(int $profileId): void
    {
        $this->currentProfileId = $profileId;
    }
    
    /**
     * @inheritdoc
     */
    public function setProcessId(string $processId): void
    {
        $this->currentProcessId = $processId;
    }
    
    /**
     * @inheritdoc
     */
    public function setContext(array $context): void
    {
        $this->currentContext = array_merge($this->currentContext, $context);
    }
    
    /**
     * Log notification
     *
     * @param string $severity
     * @param string $message
     * @param array $context
     * @return void
     */
    private function log(string $severity, string $message, array $context = []): void
    {
        try {
            // Merge current context with passed context (passed context takes precedence)
            $fullContext = array_merge($this->currentContext, $context);
            
            $notification = $this->notificationFactory->create();
            
            $notification->setProfileId($fullContext['profile_id'] ?? $this->currentProfileId ?? 0);
            $notification->setProcessId($fullContext['process_id'] ?? $this->currentProcessId);
            $notification->setEntityId($fullContext['entity_id'] ?? null);
            $notification->setEntityType($fullContext['entity_type'] ?? null);
            $notification->setSeverity($severity);
            $notification->setType($fullContext['type'] ?? null);
            $notification->setTitle($this->extractTitle($message));
            $notification->setMessage($message);
            
            // Remove sensitive data before serializing context
            $safeContext = $this->sanitizeContext($fullContext);
            $notification->setContext($this->serializer->serialize($safeContext));
            
            $notification->setSource($this->extractSource());
            
            if (isset($fullContext['exception_class'])) {
                $notification->setExceptionClass($fullContext['exception_class']);
                $notification->setStackTrace($fullContext['stack_trace'] ?? null);
            }
            
            $this->notificationRepository->save($notification);
            
            // Update process summary counters
            if ($this->currentProcessId) {
                $this->updateSummaryCounters($this->currentProcessId, $severity);
            }
            
        } catch (\Exception $e) {
            // Fallback to file logging if database save fails
            $this->logger->error('Failed to save notification: ' . $e->getMessage());
            $this->logger->log($this->mapSeverityToLogLevel($severity), $message, $context);
        }
    }
    
    /**
     * Extract title from message
     *
     * @param string $message
     * @return string
     */
    private function extractTitle(string $message): string
    {
        $lines = explode("\n", $message);
        $title = trim($lines[0] ?? $message);
        
        // Limit to 255 characters
        if (mb_strlen($title) > 255) {
            $title = mb_substr($title, 0, 252) . '...';
        }
        
        return $title;
    }
    
    /**
     * Extract source from backtrace
     *
     * @return string
     */
    private function extractSource(): string
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        
        foreach ($backtrace as $frame) {
            if (isset($frame['class']) && 
                !str_contains($frame['class'], 'ProfileNotification') &&
                !str_contains($frame['class'], 'Interceptor')) {
                return $frame['class'] . '::' . ($frame['function'] ?? 'unknown');
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Sanitize context data
     *
     * @param array $context
     * @return array
     */
    private function sanitizeContext(array $context): array
    {
        $sensitiveKeys = ['password', 'token', 'secret', 'key', 'authorization'];
        
        foreach ($context as $key => $value) {
            $lowerKey = strtolower($key);
            foreach ($sensitiveKeys as $sensitive) {
                if (str_contains($lowerKey, $sensitive)) {
                    $context[$key] = '***REDACTED***';
                    break;
                }
            }
            
            if (is_array($value)) {
                $context[$key] = $this->sanitizeContext($value);
            }
        }
        
        return $context;
    }
    
    /**
     * Update summary counters
     *
     * @param string $processId
     * @param string $severity
     * @return void
     */
    private function updateSummaryCounters(string $processId, string $severity): void
    {
        try {
            $summaryModel = $this->summaryFactory->create();
            $summaryData = $this->summaryResource->loadByProcessId($processId);
            
            if (empty($summaryData)) {
                return;
            }
            
            $summaryModel->setData($summaryData);
            $summaryModel->setTotalProcessed($summaryModel->getTotalProcessed() + 1);
            
            switch ($severity) {
                case self::SEVERITY_DEBUG:
                case self::SEVERITY_NOTICE:
                    $summaryModel->setTotalSuccess($summaryModel->getTotalSuccess() + 1);
                    break;
                case self::SEVERITY_WARNING:
                    $summaryModel->setTotalWarnings($summaryModel->getTotalWarnings() + 1);
                    break;
                case self::SEVERITY_ERROR:
                    $summaryModel->setTotalErrors($summaryModel->getTotalErrors() + 1);
                    break;
                case self::SEVERITY_CRITICAL:
                    $summaryModel->setTotalCritical($summaryModel->getTotalCritical() + 1);
                    break;
            }
            
            $this->summaryResource->save($summaryModel);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update summary counters: ' . $e->getMessage());
        }
    }
    
    /**
     * Map severity to PSR log level
     *
     * @param string $severity
     * @return string
     */
    private function mapSeverityToLogLevel(string $severity): string
    {
        return match($severity) {
            self::SEVERITY_DEBUG => \Psr\Log\LogLevel::DEBUG,
            self::SEVERITY_NOTICE => \Psr\Log\LogLevel::NOTICE,
            self::SEVERITY_WARNING => \Psr\Log\LogLevel::WARNING,
            self::SEVERITY_ERROR => \Psr\Log\LogLevel::ERROR,
            self::SEVERITY_CRITICAL => \Psr\Log\LogLevel::CRITICAL,
            default => \Psr\Log\LogLevel::INFO
        };
    }
}