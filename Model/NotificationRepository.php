<?php
/**
 * Copyright © Byte8 Ltd (formerly Soft Commerce). All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use SoftCommerce\ProfileNotification\Api\Data\NotificationInterface;
use SoftCommerce\ProfileNotification\Api\Data\NotificationInterfaceFactory;
use SoftCommerce\ProfileNotification\Api\Data\NotificationSearchResultsInterface;
use SoftCommerce\ProfileNotification\Api\Data\NotificationSearchResultsInterfaceFactory;
use SoftCommerce\ProfileNotification\Api\NotificationRepositoryInterface;
use SoftCommerce\ProfileNotification\Model\ResourceModel\Notification as ResourceNotification;
use SoftCommerce\ProfileNotification\Model\ResourceModel\Notification\CollectionFactory as NotificationCollectionFactory;

/**
 * Notification Repository
 */
class NotificationRepository implements NotificationRepositoryInterface
{
    /**
     * @param ResourceNotification $resource
     * @param NotificationInterfaceFactory $notificationFactory
     * @param NotificationCollectionFactory $notificationCollectionFactory
     * @param NotificationSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        private readonly ResourceNotification $resource,
        private readonly NotificationInterfaceFactory $notificationFactory,
        private readonly NotificationCollectionFactory $notificationCollectionFactory,
        private readonly NotificationSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(NotificationInterface $notification): NotificationInterface
    {
        try {
            $this->resource->save($notification);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the notification: %1', $exception->getMessage()),
                $exception
            );
        }
        return $notification;
    }

    /**
     * @inheritdoc
     */
    public function get(int $notificationId): NotificationInterface
    {
        $notification = $this->notificationFactory->create();
        $this->resource->load($notification, $notificationId);

        if (!$notification->getNotificationId()) {
            throw new NoSuchEntityException(__('The notification with ID "%1" doesn\'t exist.', $notificationId));
        }

        return $notification;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): NotificationSearchResultsInterface
    {
        $collection = $this->notificationCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function delete(NotificationInterface $notification): bool
    {
        try {
            $this->resource->delete($notification);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the notification: %1', $exception->getMessage())
            );
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $notificationId): bool
    {
        return $this->delete($this->get($notificationId));
    }
}
