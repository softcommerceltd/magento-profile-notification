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
use SoftCommerce\Profile\Api\ProfileRepositoryInterface;
use SoftCommerce\Profile\Model\TypeInstanceOptionsInterface;

/**
 * Profile Name Column
 */
class ProfileName extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ProfileRepositoryInterface $profileRepository
     * @param TypeInstanceOptionsInterface $typeInstanceOptions
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly ProfileRepositoryInterface $profileRepository,
        private readonly TypeInstanceOptionsInterface $typeInstanceOptions,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

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
                if (isset($item['profile_id'])) {
                    $profileId = (int) $item['profile_id'];
                    try {
                        $profile = $this->profileRepository->getById($profileId);
                        $item['profile_name'] = $profile->getName() ?: 'Profile #' . $profileId;

                        // Generate proper profile URL using the profile type router
                        $router = $this->typeInstanceOptions->getRouter($profile);
                        if ($router) {
                            $item['profile_link'] = $this->context->getUrl(
                                "$router/edit",
                                [
                                    'id' => $profileId,
                                    'type_id' => $profile->getTypeId()
                                ]
                            );
                        } else {
                            // Fallback to standard profile edit
                            $item['profile_link'] = $this->context->getUrl(
                                'softcommerce/profile/edit',
                                ['id' => $profileId]
                            );
                        }
                    } catch (\Exception $e) {
                        $item['profile_name'] = 'Profile #' . $profileId;
                        $item['profile_link'] = '#';
                    }
                }
            }
        }

        return $dataSource;
    }
}
