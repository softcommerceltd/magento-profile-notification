<?php
/**
 * Copyright © Byte8 Ltd (formerly Soft Commerce). All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Model;

use Magento\Framework\Api\SearchResults;
use SoftCommerce\ProfileNotification\Api\Data\NotificationSearchResultsInterface;

/**
 * Notification search results implementation
 */
class NotificationSearchResults extends SearchResults implements NotificationSearchResultsInterface
{
}