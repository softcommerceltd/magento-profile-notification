<?php
/**
 * Copyright © Byte8 Ltd (formerly Soft Commerce). All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Notification Resource Model
 */
class Notification extends AbstractDb
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'softcommerce_profile_notification_resource';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('softcommerce_profile_notification', 'notification_id');
    }
}