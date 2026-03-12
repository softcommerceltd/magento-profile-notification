<?php
/**
 * Copyright © Byte8 Ltd (formerly Soft Commerce). All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use SoftCommerce\ProfileNotification\Api\NotificationManagementInterface;

/**
 * Log Level Source Model
 */
class LogLevel implements OptionSourceInterface
{
    /**
     * Get options array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => NotificationManagementInterface::SEVERITY_DEBUG, 'label' => __('Debug and above')],
            ['value' => NotificationManagementInterface::SEVERITY_NOTICE, 'label' => __('Notice and above')],
            ['value' => NotificationManagementInterface::SEVERITY_WARNING, 'label' => __('Warning and above')],
            ['value' => NotificationManagementInterface::SEVERITY_ERROR, 'label' => __('Error and above')],
            ['value' => NotificationManagementInterface::SEVERITY_CRITICAL, 'label' => __('Critical only')]
        ];
    }
}