<?php
/**
 * Copyright © Byte8 Ltd (formerly Soft Commerce). All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Api;

/**
 * Interface NotificationManagementInterface
 * @api
 */
interface NotificationManagementInterface
{
    const SEVERITY_DEBUG = 'debug';
    const SEVERITY_NOTICE = 'notice';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_ERROR = 'error';
    const SEVERITY_CRITICAL = 'critical';
    
    /**
     * Log debug message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug(string $message, array $context = []): void;
    
    /**
     * Log notice message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice(string $message, array $context = []): void;
    
    /**
     * Log warning message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning(string $message, array $context = []): void;
    
    /**
     * Log error message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error(string $message, array $context = []): void;
    
    /**
     * Log critical message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical(string $message, array $context = []): void;
    
    /**
     * Log exception
     *
     * @param \Throwable $exception
     * @param array $context
     * @return void
     */
    public function logException(\Throwable $exception, array $context = []): void;
    
    /**
     * Start new process
     *
     * @param int $profileId
     * @param string $typeId
     * @return string Process ID
     */
    public function startProcess(int $profileId, string $typeId): string;
    
    /**
     * End process
     *
     * @param string $processId
     * @param string $status
     * @return void
     */
    public function endProcess(string $processId, string $status): void;
    
    /**
     * Set process summary
     *
     * @param string $processId
     * @param array $summary
     * @return void
     */
    public function setSummary(string $processId, array $summary): void;
    
    /**
     * Set current profile context
     *
     * @param int $profileId
     * @return void
     */
    public function setProfileId(int $profileId): void;
    
    /**
     * Set current process context
     *
     * @param string $processId
     * @return void
     */
    public function setProcessId(string $processId): void;
    
    /**
     * Set additional context data
     *
     * @param array $context
     * @return void
     */
    public function setContext(array $context): void;
}