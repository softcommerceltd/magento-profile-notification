<?php
/**
 * Copyright © Byte8 Ltd (formerly Soft Commerce). All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Email Threshold Source Model
 */
class EmailThreshold implements OptionSourceInterface
{
    /**
     * Get options array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'warning', 'label' => __('Warning and above')],
            ['value' => 'error', 'label' => __('Error and above')],
            ['value' => 'critical', 'label' => __('Critical only')],
            ['value' => 'all', 'label' => __('All notifications')],
            ['value' => 'summary', 'label' => __('Process summary only')]
        ];
    }
}