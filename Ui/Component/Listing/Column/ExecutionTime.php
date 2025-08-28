<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Execution Time Column
 */
class ExecutionTime extends Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[$this->getData('name')]) && $item[$this->getData('name')] !== null) {
                    $seconds = (float) $item[$this->getData('name')];
                    $item[$this->getData('name')] = $this->formatTime($seconds);
                }
            }
        }

        return $dataSource;
    }

    /**
     * Format time in seconds to human readable format
     *
     * @param float $seconds
     * @return string
     */
    private function formatTime(float $seconds): string
    {
        if ($seconds < 60) {
            return sprintf('%.2f seconds', $seconds);
        }
        
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        
        return sprintf('%d min %.2f sec', $minutes, $seconds);
    }
}