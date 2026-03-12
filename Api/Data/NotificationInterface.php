<?php
/**
 * Copyright © Byte8 Ltd (formerly Soft Commerce). All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Api\Data;

/**
 * Interface NotificationInterface
 * @api
 */
interface NotificationInterface
{
    const NOTIFICATION_ID = 'notification_id';
    const PROFILE_ID = 'profile_id';
    const PROCESS_ID = 'process_id';
    const ENTITY_ID = 'entity_id';
    const ENTITY_TYPE = 'entity_type';
    const SEVERITY = 'severity';
    const TYPE = 'type';
    const TITLE = 'title';
    const MESSAGE = 'message';
    const CONTEXT = 'context';
    const SOURCE = 'source';
    const EXCEPTION_CLASS = 'exception_class';
    const STACK_TRACE = 'stack_trace';
    const IS_READ = 'is_read';
    const IS_EMAILED = 'is_emailed';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    
    /**
     * Get notification ID
     *
     * @return int|null
     */
    public function getNotificationId(): ?int;
    
    /**
     * Set notification ID
     *
     * @param int $notificationId
     * @return $this
     */
    public function setNotificationId(int $notificationId): self;
    
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
     * @return string|null
     */
    public function getProcessId(): ?string;
    
    /**
     * Set process ID
     *
     * @param string|null $processId
     * @return $this
     */
    public function setProcessId(?string $processId): self;
    
    /**
     * Get entity ID
     *
     * @return string|null
     */
    public function getEntityId(): ?string;
    
    /**
     * Set entity ID
     *
     * @param string|null $entityId
     * @return $this
     */
    public function setEntityId(?string $entityId): self;
    
    /**
     * Get entity type
     *
     * @return string|null
     */
    public function getEntityType(): ?string;
    
    /**
     * Set entity type
     *
     * @param string|null $entityType
     * @return $this
     */
    public function setEntityType(?string $entityType): self;
    
    /**
     * Get severity
     *
     * @return string
     */
    public function getSeverity(): string;
    
    /**
     * Set severity
     *
     * @param string $severity
     * @return $this
     */
    public function setSeverity(string $severity): self;
    
    /**
     * Get type
     *
     * @return string|null
     */
    public function getType(): ?string;
    
    /**
     * Set type
     *
     * @param string|null $type
     * @return $this
     */
    public function setType(?string $type): self;
    
    /**
     * Get title
     *
     * @return string
     */
    public function getTitle(): string;
    
    /**
     * Set title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): self;
    
    /**
     * Get message
     *
     * @return string|null
     */
    public function getMessage(): ?string;
    
    /**
     * Set message
     *
     * @param string|null $message
     * @return $this
     */
    public function setMessage(?string $message): self;
    
    /**
     * Get context
     *
     * @return string|null
     */
    public function getContext(): ?string;
    
    /**
     * Set context
     *
     * @param string|null $context
     * @return $this
     */
    public function setContext(?string $context): self;
    
    /**
     * Get source
     *
     * @return string|null
     */
    public function getSource(): ?string;
    
    /**
     * Set source
     *
     * @param string|null $source
     * @return $this
     */
    public function setSource(?string $source): self;
    
    /**
     * Get exception class
     *
     * @return string|null
     */
    public function getExceptionClass(): ?string;
    
    /**
     * Set exception class
     *
     * @param string|null $exceptionClass
     * @return $this
     */
    public function setExceptionClass(?string $exceptionClass): self;
    
    /**
     * Get stack trace
     *
     * @return string|null
     */
    public function getStackTrace(): ?string;
    
    /**
     * Set stack trace
     *
     * @param string|null $stackTrace
     * @return $this
     */
    public function setStackTrace(?string $stackTrace): self;
    
    /**
     * Get is read
     *
     * @return bool
     */
    public function getIsRead(): bool;
    
    /**
     * Set is read
     *
     * @param bool $isRead
     * @return $this
     */
    public function setIsRead(bool $isRead): self;
    
    /**
     * Get is emailed
     *
     * @return bool
     */
    public function getIsEmailed(): bool;
    
    /**
     * Set is emailed
     *
     * @param bool $isEmailed
     * @return $this
     */
    public function setIsEmailed(bool $isEmailed): self;
    
    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;
    
    /**
     * Set created at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;
    
    /**
     * Get updated at
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;
    
    /**
     * Set updated at
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): self;
}