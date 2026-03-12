<?php
/**
 * Copyright © Byte8 Ltd (formerly Soft Commerce). All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Process Status Source Model
 */
class ProcessStatus implements OptionSourceInterface
{
    /**
     * Get options array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'running', 'label' => __('Running')],
            ['value' => 'success', 'label' => __('Success')],
            ['value' => 'completed', 'label' => __('Completed')],
            ['value' => 'error', 'label' => __('Error')],
            ['value' => 'failed', 'label' => __('Failed')]
        ];
    }
}