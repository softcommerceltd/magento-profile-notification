<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Plugin;

use SoftCommerce\Profile\Model\ServiceAbstract\ProcessorInterface;
use SoftCommerce\ProfileNotification\Api\NotificationManagementInterface;

/**
 * Plugin for all profile processors to capture individual entity processing
 */
class ProcessorNotificationPlugin
{
    /**
     * @param NotificationManagementInterface $notificationManager
     */
    public function __construct(
        private NotificationManagementInterface $notificationManager
    ) {
    }

    /**
     * Wrap processor execution with entity-level notification logging
     *
     * @param ProcessorInterface $subject
     * @param \Closure $proceed
     * @return mixed
     */
    public function aroundExecute(
        ProcessorInterface $subject,
        \Closure $proceed
    ) {
        $entityId = null;
        $entityType = $this->extractEntityType($subject);

        // Try to get entity ID from processor
        if (method_exists($subject, 'getProcessorId')) {
            $entityId = $subject->getProcessorId();
        } elseif (method_exists($subject, 'getEntityId')) {
            $entityId = $subject->getEntityId();
        } elseif (method_exists($subject, 'getId')) {
            $entityId = $subject->getId();
        }

        $context = [
            'processor' => get_class($subject),
            'entity_type' => $entityType,
            'entity_id' => $entityId
        ];

        // Set context for any notifications logged during processing
        $this->notificationManager->setContext($context);

        try {
            $result = $proceed();

            // Log successful processing at debug level
            if ($entityId) {
                $this->notificationManager->debug(
                    sprintf('Successfully processed %s: %s', $entityType, $entityId),
                    $context
                );
            }

            return $result;

        } catch (\Throwable $e) {
            // Log the exception with full entity context
            $this->notificationManager->logException($e, array_merge($context, [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ]));

            // Re-throw to maintain original behavior
            throw $e;
        }
    }

    /**
     * Extract entity type from processor class name
     *
     * @param ProcessorInterface $processor
     * @return string
     */
    private function extractEntityType(ProcessorInterface $processor): string
    {
        if ($processor->getTypeId()) {
            return $processor->getTypeId();
        }

        if ($processor->getContext()?->getTypeId()) {
            return $processor->getContext()->getTypeId();
        }

        $className = get_class($processor);

        // Check for specific patterns in class name
        if (str_contains($className, 'Order')) {
            return 'order';
        } elseif (str_contains($className, 'Product') || str_contains($className, 'Item')) {
            return 'product';
        } elseif (str_contains($className, 'Customer')) {
            return 'customer';
        } elseif (str_contains($className, 'Stock')) {
            return 'stock';
        } elseif (str_contains($className, 'Category')) {
            return 'category';
        } elseif (str_contains($className, 'Invoice')) {
            return 'invoice';
        } elseif (str_contains($className, 'Shipment')) {
            return 'shipment';
        } elseif (str_contains($className, 'Credit')) {
            return 'creditmemo';
        }

        // Try to extract from namespace
        $parts = explode('\\', $className);
        foreach ($parts as $part) {
            if (str_contains($part, 'Profile') && $part !== 'Profile') {
                // Extract type from something like 'OrderProfile' or 'ItemProfile'
                $type = str_replace('Profile', '', $part);
                return strtolower($type);
            }
        }

        // Fallback to generic type
        return 'entity';
    }
}
