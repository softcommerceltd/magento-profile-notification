<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Model;

use Magento\Framework\Model\AbstractModel;
use SoftCommerce\ProfileNotification\Api\Data\NotificationSummaryInterface;

/**
 * Notification Summary Model
 */
class NotificationSummary extends AbstractModel implements NotificationSummaryInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'softcommerce_profile_notification_summary';

    /**
     * @var string
     */
    protected $_eventObject = 'notification_summary';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\NotificationSummary::class);
    }

    /**
     * @inheritdoc
     */
    public function getSummaryId(): ?int
    {
        $id = $this->getData(self::SUMMARY_ID);
        return $id ? (int) $id : null;
    }

    /**
     * @inheritdoc
     */
    public function setSummaryId(int $summaryId): NotificationSummaryInterface
    {
        return $this->setData(self::SUMMARY_ID, $summaryId);
    }

    /**
     * @inheritdoc
     */
    public function getProfileId(): int
    {
        return (int) $this->getData(self::PROFILE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setProfileId(int $profileId): NotificationSummaryInterface
    {
        return $this->setData(self::PROFILE_ID, $profileId);
    }

    /**
     * @inheritdoc
     */
    public function getProcessId(): string
    {
        return (string) $this->getData(self::PROCESS_ID);
    }

    /**
     * @inheritdoc
     */
    public function setProcessId(string $processId): NotificationSummaryInterface
    {
        return $this->setData(self::PROCESS_ID, $processId);
    }

    /**
     * @inheritdoc
     */
    public function getTotalProcessed(): int
    {
        return (int) $this->getData(self::TOTAL_PROCESSED);
    }

    /**
     * @inheritdoc
     */
    public function setTotalProcessed(int $totalProcessed): NotificationSummaryInterface
    {
        return $this->setData(self::TOTAL_PROCESSED, $totalProcessed);
    }

    /**
     * @inheritdoc
     */
    public function getTotalSuccess(): int
    {
        return (int) $this->getData(self::TOTAL_SUCCESS);
    }

    /**
     * @inheritdoc
     */
    public function setTotalSuccess(int $totalSuccess): NotificationSummaryInterface
    {
        return $this->setData(self::TOTAL_SUCCESS, $totalSuccess);
    }

    /**
     * @inheritdoc
     */
    public function getTotalWarnings(): int
    {
        return (int) $this->getData(self::TOTAL_WARNINGS);
    }

    /**
     * @inheritdoc
     */
    public function setTotalWarnings(int $totalWarnings): NotificationSummaryInterface
    {
        return $this->setData(self::TOTAL_WARNINGS, $totalWarnings);
    }

    /**
     * @inheritdoc
     */
    public function getTotalErrors(): int
    {
        return (int) $this->getData(self::TOTAL_ERRORS);
    }

    /**
     * @inheritdoc
     */
    public function setTotalErrors(int $totalErrors): NotificationSummaryInterface
    {
        return $this->setData(self::TOTAL_ERRORS, $totalErrors);
    }

    /**
     * @inheritdoc
     */
    public function getTotalCritical(): int
    {
        return (int) $this->getData(self::TOTAL_CRITICAL);
    }

    /**
     * @inheritdoc
     */
    public function setTotalCritical(int $totalCritical): NotificationSummaryInterface
    {
        return $this->setData(self::TOTAL_CRITICAL, $totalCritical);
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus(?string $status): NotificationSummaryInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getStartedAt(): ?string
    {
        return $this->getData(self::STARTED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setStartedAt(?string $startedAt): NotificationSummaryInterface
    {
        return $this->setData(self::STARTED_AT, $startedAt);
    }

    /**
     * @inheritdoc
     */
    public function getFinishedAt(): ?string
    {
        return $this->getData(self::FINISHED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setFinishedAt(?string $finishedAt): NotificationSummaryInterface
    {
        return $this->setData(self::FINISHED_AT, $finishedAt);
    }

    /**
     * @inheritdoc
     */
    public function getPeakMemory(): ?int
    {
        $memory = $this->getData(self::PEAK_MEMORY);
        return $memory !== null ? (int) $memory : null;
    }

    /**
     * @inheritdoc
     */
    public function setPeakMemory(?int $peakMemory): NotificationSummaryInterface
    {
        return $this->setData(self::PEAK_MEMORY, $peakMemory);
    }

    /**
     * @inheritdoc
     */
    public function getExecutionTime(): ?float
    {
        $time = $this->getData(self::EXECUTION_TIME);
        return $time !== null ? (float) $time : null;
    }

    /**
     * @inheritdoc
     */
    public function setExecutionTime(?float $executionTime): NotificationSummaryInterface
    {
        return $this->setData(self::EXECUTION_TIME, $executionTime);
    }
}