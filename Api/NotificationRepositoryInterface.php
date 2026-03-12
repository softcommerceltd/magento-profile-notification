<?php
/**
 * Copyright © Byte8 Ltd (formerly Soft Commerce). All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use SoftCommerce\ProfileNotification\Api\Data\NotificationInterface;
use SoftCommerce\ProfileNotification\Api\Data\NotificationSearchResultsInterface;

/**
 * Interface NotificationRepositoryInterface
 * @api
 */
interface NotificationRepositoryInterface
{
    /**
     * Save notification
     *
     * @param NotificationInterface $notification
     * @return NotificationInterface
     * @throws LocalizedException
     */
    public function save(NotificationInterface $notification): NotificationInterface;
    
    /**
     * Get notification by ID
     *
     * @param int $notificationId
     * @return NotificationInterface
     * @throws NoSuchEntityException
     */
    public function get(int $notificationId): NotificationInterface;
    
    /**
     * Delete notification
     *
     * @param NotificationInterface $notification
     * @return bool
     * @throws LocalizedException
     */
    public function delete(NotificationInterface $notification): bool;
    
    /**
     * Delete notification by ID
     *
     * @param int $notificationId
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $notificationId): bool;
    
    /**
     * Get list of notifications
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return NotificationSearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria): NotificationSearchResultsInterface;
}