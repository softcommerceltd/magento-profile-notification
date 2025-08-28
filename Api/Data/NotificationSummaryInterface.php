<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Api\Data;

/**
 * Interface NotificationSummaryInterface
 * @api
 */
interface NotificationSummaryInterface
{
    const SUMMARY_ID = 'summary_id';
    const PROFILE_ID = 'profile_id';
    const PROCESS_ID = 'process_id';
    const TOTAL_PROCESSED = 'total_processed';
    const TOTAL_SUCCESS = 'total_success';
    const TOTAL_WARNINGS = 'total_warnings';
    const TOTAL_ERRORS = 'total_errors';
    const TOTAL_CRITICAL = 'total_critical';
    const STATUS = 'status';
    const STARTED_AT = 'started_at';
    const FINISHED_AT = 'finished_at';
    const PEAK_MEMORY = 'peak_memory';
    const EXECUTION_TIME = 'execution_time';
    
    /**
     * Get summary ID
     *
     * @return int|null
     */
    public function getSummaryId(): ?int;
    
    /**
     * Set summary ID
     *
     * @param int $summaryId
     * @return $this
     */
    public function setSummaryId(int $summaryId): self;
    
    /**
     * Get profile ID
     *
     * @return int
     */
    public function getProfileId(): int;
    
    /**
     * Set profile ID
     *
     * @param int $profileId
     * @return $this
     */
    public function setProfileId(int $profileId): self;
    
    /**
     * Get process ID
     *
     * @return string
     */
    public function getProcessId(): string;
    
    /**
     * Set process ID
     *
     * @param string $processId
     * @return $this
     */
    public function setProcessId(string $processId): self;
    
    /**
     * Get total processed
     *
     * @return int
     */
    public function getTotalProcessed(): int;
    
    /**
     * Set total processed
     *
     * @param int $totalProcessed
     * @return $this
     */
    public function setTotalProcessed(int $totalProcessed): self;
    
    /**
     * Get total success
     *
     * @return int
     */
    public function getTotalSuccess(): int;
    
    /**
     * Set total success
     *
     * @param int $totalSuccess
     * @return $this
     */
    public function setTotalSuccess(int $totalSuccess): self;
    
    /**
     * Get total warnings
     *
     * @return int
     */
    public function getTotalWarnings(): int;
    
    /**
     * Set total warnings
     *
     * @param int $totalWarnings
     * @return $this
     */
    public function setTotalWarnings(int $totalWarnings): self;
    
    /**
     * Get total errors
     *
     * @return int
     */
    public function getTotalErrors(): int;
    
    /**
     * Set total errors
     *
     * @param int $totalErrors
     * @return $this
     */
    public function setTotalErrors(int $totalErrors): self;
    
    /**
     * Get total critical
     *
     * @return int
     */
    public function getTotalCritical(): int;
    
    /**
     * Set total critical
     *
     * @param int $totalCritical
     * @return $this
     */
    public function setTotalCritical(int $totalCritical): self;
    
    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus(): ?string;
    
    /**
     * Set status
     *
     * @param string|null $status
     * @return $this
     */
    public function setStatus(?string $status): self;
    
    /**
     * Get started at
     *
     * @return string|null
     */
    public function getStartedAt(): ?string;
    
    /**
     * Set started at
     *
     * @param string|null $startedAt
     * @return $this
     */
    public function setStartedAt(?string $startedAt): self;
    
    /**
     * Get finished at
     *
     * @return string|null
     */
    public function getFinishedAt(): ?string;
    
    /**
     * Set finished at
     *
     * @param string|null $finishedAt
     * @return $this
     */
    public function setFinishedAt(?string $finishedAt): self;
    
    /**
     * Get peak memory
     *
     * @return int|null
     */
    public function getPeakMemory(): ?int;
    
    /**
     * Set peak memory
     *
     * @param int|null $peakMemory
     * @return $this
     */
    public function setPeakMemory(?int $peakMemory): self;
    
    /**
     * Get execution time
     *
     * @return float|null
     */
    public function getExecutionTime(): ?float;
    
    /**
     * Set execution time
     *
     * @param float|null $executionTime
     * @return $this
     */
    public function setExecutionTime(?float $executionTime): self;
}