<?php
/**
 * Copyright © Byte8 Ltd (formerly Soft Commerce). All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Controller\Adminhtml\Notification;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use SoftCommerce\ProfileNotification\Model\ResourceModel\Notification as NotificationResource;

/**
 * Clear all read notifications action
 */
class ClearAll extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'SoftCommerce_ProfileNotification::notification_delete';

    /**
     * @var NotificationResource
     */
    protected NotificationResource $notificationResource;

    /**
     * @param Context $context
     * @param NotificationResource $notificationResource
     */
    public function __construct(
        Context $context,
        NotificationResource $notificationResource
    ) {
        parent::__construct($context);
        $this->notificationResource = $notificationResource;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $connection = $this->notificationResource->getConnection();
            $table = $this->notificationResource->getMainTable();
            
            // Delete all read notifications
            $deletedCount = $connection->delete(
                $table,
                ['is_read = ?' => 1]
            );
            
            $this->messageManager->addSuccessMessage(
                __('Cleared %1 read notification(s).', $deletedCount)
            );
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('An error occurred while clearing read notifications.')
            );
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}