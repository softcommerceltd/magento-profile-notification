<?php
/**
 * Copyright © Byte8 Ltd (formerly Soft Commerce). All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Model;

use Magento\Framework\Model\AbstractModel;
use SoftCommerce\ProfileNotification\Api\Data\NotificationInterface;

/**
 * Notification Model
 */
class Notification extends AbstractModel implements NotificationInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'softcommerce_profile_notification';

    /**
     * @var string
     */
    protected $_eventObject = 'notification';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Notification::class);
    }

    /**
     * @inheritdoc
     */
    public function getNotificationId(): ?int
    {
        $id = $this->getData(self::NOTIFICATION_ID);
        return $id ? (int) $id : null;
    }

    /**
     * @inheritdoc
     */
    public function setNotificationId(int $notificationId): NotificationInterface
    {
        return $this->setData(self::NOTIFICATION_ID, $notificationId);
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
    public function setProfileId(int $profileId): NotificationInterface
    {
        return $this->setData(self::PROFILE_ID, $profileId);
    }

    /**
     * @inheritdoc
     */
    public function getProcessId(): ?string
    {
        return $this->getData(self::PROCESS_ID);
    }

    /**
     * @inheritdoc
     */
    public function setProcessId(?string $processId): NotificationInterface
    {
        return $this->setData(self::PROCESS_ID, $processId);
    }

    /**
     * @inheritdoc
     */
    public function getEntityId(): ?string
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setEntityId($entityId): NotificationInterface
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritdoc
     */
    public function getEntityType(): ?string
    {
        return $this->getData(self::ENTITY_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setEntityType(?string $entityType): NotificationInterface
    {
        return $this->setData(self::ENTITY_TYPE, $entityType);
    }

    /**
     * @inheritdoc
     */
    public function getSeverity(): string
    {
        return (string) $this->getData(self::SEVERITY);
    }

    /**
     * @inheritdoc
     */
    public function setSeverity(string $severity): NotificationInterface
    {
        return $this->setData(self::SEVERITY, $severity);
    }

    /**
     * @inheritdoc
     */
    public function getType(): ?string
    {
        return $this->getData(self::TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setType(?string $type): NotificationInterface
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return (string) $this->getData(self::TITLE);
    }

    /**
     * @inheritdoc
     */
    public function setTitle(string $title): NotificationInterface
    {
        return $this->setData(self::TITLE, $title);
    }

    /**
     * @inheritdoc
     */
    public function getMessage(): ?string
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * @inheritdoc
     */
    public function setMessage(?string $message): NotificationInterface
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * @inheritdoc
     */
    public function getContext(): ?string
    {
        return $this->getData(self::CONTEXT);
    }

    /**
     * @inheritdoc
     */
    public function setContext(?string $context): NotificationInterface
    {
        return $this->setData(self::CONTEXT, $context);
    }

    /**
     * @inheritdoc
     */
    public function getSource(): ?string
    {
        return $this->getData(self::SOURCE);
    }

    /**
     * @inheritdoc
     */
    public function setSource(?string $source): NotificationInterface
    {
        return $this->setData(self::SOURCE, $source);
    }

    /**
     * @inheritdoc
     */
    public function getExceptionClass(): ?string
    {
        return $this->getData(self::EXCEPTION_CLASS);
    }

    /**
     * @inheritdoc
     */
    public function setExceptionClass(?string $exceptionClass): NotificationInterface
    {
        return $this->setData(self::EXCEPTION_CLASS, $exceptionClass);
    }

    /**
     * @inheritdoc
     */
    public function getStackTrace(): ?string
    {
        return $this->getData(self::STACK_TRACE);
    }

    /**
     * @inheritdoc
     */
    public function setStackTrace(?string $stackTrace): NotificationInterface
    {
        return $this->setData(self::STACK_TRACE, $stackTrace);
    }

    /**
     * @inheritdoc
     */
    public function getIsRead(): bool
    {
        return (bool) $this->getData(self::IS_READ);
    }

    /**
     * @inheritdoc
     */
    public function setIsRead(bool $isRead): NotificationInterface
    {
        return $this->setData(self::IS_READ, $isRead);
    }

    /**
     * @inheritdoc
     */
    public function getIsEmailed(): bool
    {
        return (bool) $this->getData(self::IS_EMAILED);
    }

    /**
     * @inheritdoc
     */
    public function setIsEmailed(bool $isEmailed): NotificationInterface
    {
        return $this->setData(self::IS_EMAILED, $isEmailed);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(string $createdAt): NotificationInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(string $updatedAt): NotificationInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
