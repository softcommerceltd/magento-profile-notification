<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface NotificationSearchResultsInterface
 * @api
 */
interface NotificationSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get notifications list
     *
     * @return NotificationInterface[]
     */
    public function getItems();
    
    /**
     * Set notifications list
     *
     * @param NotificationInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}