<?php
/**
 * Copyright © Byte8 Ltd (formerly Soft Commerce). All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Controller\Adminhtml\Notification;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use SoftCommerce\ProfileNotification\Api\NotificationRepositoryInterface;

/**
 * View notification details controller
 */
class View extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'SoftCommerce_ProfileNotification::notification_view';

    /**
     * @var PageFactory
     */
    private PageFactory $resultPageFactory;

    /**
     * @var NotificationRepositoryInterface
     */
    private NotificationRepositoryInterface $notificationRepository;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param NotificationRepositoryInterface $notificationRepository
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        NotificationRepositoryInterface $notificationRepository
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $notificationId = (int) $this->getRequest()->getParam('notification_id');

        try {
            $notification = $this->notificationRepository->get($notificationId);

            // Mark as read
            if (!$notification->getIsRead()) {
                $notification->setIsRead(true);
                $this->notificationRepository->save($notification);
            }

            $resultPage = $this->resultPageFactory->create();
            $resultPage->setActiveMenu('SoftCommerce_Profile::profile_core');
            $resultPage->getConfig()->getTitle()->prepend(__('Notification Details'));

            // Add back button
            /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
            $resultPage->getConfig()->getTitle()->prepend(__('Notification #%1', $notificationId));

            return $resultPage;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Notification #%1 not found', $notificationId));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }
    }
}
