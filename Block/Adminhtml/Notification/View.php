<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Block\Adminhtml\Notification;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use SoftCommerce\ProfileNotification\Api\Data\NotificationInterface;
use SoftCommerce\ProfileNotification\Api\NotificationRepositoryInterface;
use SoftCommerce\Profile\Api\ProfileRepositoryInterface;

/**
 * Notification view block
 */
class View extends Template
{
    /**
     * @var NotificationRepositoryInterface
     */
    private NotificationRepositoryInterface $notificationRepository;

    /**
     * @var ProfileRepositoryInterface
     */
    private ProfileRepositoryInterface $profileRepository;

    /**
     * @var NotificationInterface|null
     */
    private ?NotificationInterface $notification = null;

    /**
     * @param Context $context
     * @param NotificationRepositoryInterface $notificationRepository
     * @param ProfileRepositoryInterface $profileRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        NotificationRepositoryInterface $notificationRepository,
        ProfileRepositoryInterface $profileRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->notificationRepository = $notificationRepository;
        $this->profileRepository = $profileRepository;
    }

    /**
     * Get notification
     *
     * @return NotificationInterface|null
     */
    public function getNotification(): ?NotificationInterface
    {
        if ($this->notification === null) {
            $notificationId = (int) $this->getRequest()->getParam('notification_id');
            if ($notificationId) {
                try {
                    $this->notification = $this->notificationRepository->get($notificationId);
                } catch (NoSuchEntityException $e) {
                    $this->notification = null;
                }
            }
        }
        return $this->notification;
    }

    /**
     * Get profile name
     *
     * @return string
     */
    public function getProfileName(): string
    {
        $notification = $this->getNotification();
        if (!$notification || !$notification->getProfileId()) {
            return __('Unknown')->render();
        }

        try {
            $profile = $this->profileRepository->get($notification->getProfileId());
            return $profile->getName() ?: __('Profile #%1', $profile->getEntityId())->render();
        } catch (NoSuchEntityException $e) {
            return __('Profile #%1 (Deleted)', $notification->getProfileId())->render();
        }
    }

    /**
     * Get severity label
     *
     * @return string
     */
    public function getSeverityLabel(): string
    {
        $notification = $this->getNotification();
        if (!$notification) {
            return '';
        }

        $severities = [
            'debug' => __('Debug'),
            'notice' => __('Notice'),
            'warning' => __('Warning'),
            'error' => __('Error'),
            'critical' => __('Critical')
        ];

        $severity = $notification->getSeverity();
        return isset($severities[$severity]) ? (string) $severities[$severity] : $severity;
    }

    /**
     * Get severity class
     *
     * @return string
     */
    public function getSeverityClass(): string
    {
        $notification = $this->getNotification();
        return $notification ? 'severity-' . $notification->getSeverity() : '';
    }

    /**
     * Get back URL
     *
     * @return string
     */
    public function getBackUrl(): string
    {
        return $this->getUrl('*/*/');
    }
}