<?php
/**
 * Copyright © Byte8 Ltd (formerly Soft Commerce). All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Model\ResourceModel\NotificationSummary;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use SoftCommerce\ProfileNotification\Model\NotificationSummary;
use SoftCommerce\ProfileNotification\Model\ResourceModel\NotificationSummary as NotificationSummaryResource;

/**
 * Notification Summary Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'summary_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'softcommerce_profile_notification_summary_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'notification_summary_collection';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(NotificationSummary::class, NotificationSummaryResource::class);
    }
}