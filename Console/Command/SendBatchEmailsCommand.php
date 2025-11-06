<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\ProfileNotification\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use SoftCommerce\ProfileNotification\Api\NotificationRepositoryInterface;
use SoftCommerce\ProfileNotification\Api\NotificationManagementInterface;
use SoftCommerce\ProfileNotification\Model\Email\SenderInterface;

/**
 * CLI command to manually send batch notification emails
 */
class SendBatchEmailsCommand extends Command
{
    private const XML_PATH_ENABLED = 'softcommerce_profile_notification/email/enabled';
    private const XML_PATH_BATCH_ENABLED = 'softcommerce_profile_notification/email/batch_enabled';
    private const XML_PATH_BATCH_INTERVAL = 'softcommerce_profile_notification/email/batch_interval';
    private const XML_PATH_THRESHOLD = 'softcommerce_profile_notification/email/threshold';

    /**
     * @param NotificationRepositoryInterface $notificationRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param SenderInterface $emailSender
     * @param State $appState
     * @param StoreManagerInterface $storeManager
     * @param string|null $name
     */
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly SenderInterface $emailSender,
        private readonly State $appState,
        private readonly StoreManagerInterface $storeManager,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName('softcommerce:notification:send-batch-emails')
            ->setDescription('Manually send batch notification emails')
            ->addOption(
                'preview',
                'p',
                InputOption::VALUE_NONE,
                'Preview notifications that would be sent without actually sending'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force sending even if batch emails are disabled in configuration'
            )
            ->addOption(
                'severity',
                's',
                InputOption::VALUE_REQUIRED,
                'Override severity threshold (debug, notice, warning, error, critical)'
            )
            ->addOption(
                'interval',
                'i',
                InputOption::VALUE_REQUIRED,
                'Override interval in minutes (how old notifications must be to be included)'
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'Limit number of notifications to send',
                0
            );

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isPreview = $input->getOption('preview');
        $force = $input->getOption('force');
        $severityOverride = $input->getOption('severity');
        $intervalOverride = $input->getOption('interval');
        $limit = (int) $input->getOption('limit');

        // Check if enabled
        if (!$force && !$this->isActive()) {
            $output->writeln('<error>Batch emails are disabled in configuration. Use --force to override.</error>');
            return Command::FAILURE;
        }

        // Set area code for CLI execution
        try {
            $this->appState->setAreaCode(Area::AREA_ADMINHTML);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // Area code already set
        }

        try {
            // Get notifications
            $notifications = $this->getUnsentNotifications($severityOverride, $intervalOverride);

            if (empty($notifications)) {
                $output->writeln('<info>No notifications found to send.</info>');
                return Command::SUCCESS;
            }

            // Apply limit if specified
            if ($limit > 0) {
                $notifications = array_slice($notifications, 0, $limit);
            }

            // Preview mode
            if ($isPreview) {
                $this->displayPreview($output, $notifications);
                return Command::SUCCESS;
            }

            // Send emails
            $output->writeln(sprintf(
                '<info>Sending batch email with %d notifications...</info>',
                count($notifications)
            ));

            // Get default store for CLI context
            $defaultStore = $this->storeManager->getDefaultStoreView();
            if (!$defaultStore) {
                $stores = $this->storeManager->getStores();
                $defaultStore = reset($stores);
            }

            // Set current store for proper email configuration
            $this->storeManager->setCurrentStore($defaultStore->getId());

            // Send the batch email
            $this->emailSender->sendBatchNotification($notifications);

            // Mark notifications as emailed
            foreach ($notifications as $notification) {
                $notification->setIsEmailed(true);
                $this->notificationRepository->save($notification);
            }

            $output->writeln('<info>Batch email sent successfully!</info>');

            // Show summary
            $this->displaySummary($output, $notifications);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    /**
     * Check if batch emails are enabled
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE) &&
               $this->scopeConfig->isSetFlag(self::XML_PATH_BATCH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get unsent notifications based on threshold
     *
     * @param string|null $severityOverride
     * @param string|null $intervalOverride
     * @return array
     * @throws LocalizedException
     */
    private function getUnsentNotifications(?string $severityOverride = null, ?string $intervalOverride = null): array
    {
        $threshold = $severityOverride ?: $this->scopeConfig->getValue(self::XML_PATH_THRESHOLD, ScopeInterface::SCOPE_STORE);
        $interval = $intervalOverride ?: (int) $this->scopeConfig->getValue(self::XML_PATH_BATCH_INTERVAL, ScopeInterface::SCOPE_STORE);

        $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$interval} minutes"));

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('is_emailed', false)
            ->addFilter('created_at', $cutoffTime, 'lteq');

        // Apply severity threshold
        switch ($threshold) {
            case NotificationManagementInterface::SEVERITY_CRITICAL:
                $searchCriteria->addFilter('severity', NotificationManagementInterface::SEVERITY_CRITICAL);
                break;
            case NotificationManagementInterface::SEVERITY_ERROR:
                $searchCriteria->addFilter('severity', [
                    NotificationManagementInterface::SEVERITY_ERROR,
                    NotificationManagementInterface::SEVERITY_CRITICAL
                ], 'in');
                break;
            case NotificationManagementInterface::SEVERITY_WARNING:
                $searchCriteria->addFilter('severity', [
                    NotificationManagementInterface::SEVERITY_WARNING,
                    NotificationManagementInterface::SEVERITY_ERROR,
                    NotificationManagementInterface::SEVERITY_CRITICAL
                ], 'in');
                break;
            case NotificationManagementInterface::SEVERITY_NOTICE:
                $searchCriteria->addFilter('severity', [
                    NotificationManagementInterface::SEVERITY_NOTICE,
                    NotificationManagementInterface::SEVERITY_WARNING,
                    NotificationManagementInterface::SEVERITY_ERROR,
                    NotificationManagementInterface::SEVERITY_CRITICAL
                ], 'in');
                break;
            case NotificationManagementInterface::SEVERITY_DEBUG:
                // Include all severities
                break;
            case 'summary':
                // Don't send individual notifications, only process summaries
                return [];
        }

        $searchCriteria->setPageSize(1000);
        $searchResults = $this->notificationRepository->getList($searchCriteria->create());

        return $searchResults->getItems();
    }

    /**
     * Display preview of notifications
     *
     * @param OutputInterface $output
     * @param array $notifications
     * @return void
     */
    private function displayPreview(OutputInterface $output, array $notifications): void
    {
        $output->writeln(sprintf(
            '<info>Found %d notifications that would be sent:</info>',
            count($notifications)
        ));
        $output->writeln('');

        $table = new Table($output);
        $table->setHeaders(['ID', 'Created', 'Severity', 'Entity Type', 'Entity ID', 'Title']);

        foreach ($notifications as $notification) {
            $table->addRow([
                $notification->getNotificationId(),
                $notification->getCreatedAt(),
                $notification->getSeverity(),
                $notification->getEntityType() ?: '-',
                $notification->getEntityId() ?: '-',
                substr($notification->getTitle(), 0, 50) . (strlen($notification->getTitle()) > 50 ? '...' : '')
            ]);
        }

        $table->render();
    }

    /**
     * Display summary after sending
     *
     * @param OutputInterface $output
     * @param array $notifications
     * @return void
     */
    private function displaySummary(OutputInterface $output, array $notifications): void
    {
        $output->writeln('');
        $output->writeln('<info>Summary:</info>');

        $severityCounts = [];
        foreach ($notifications as $notification) {
            $severity = $notification->getSeverity();
            if (!isset($severityCounts[$severity])) {
                $severityCounts[$severity] = 0;
            }
            $severityCounts[$severity]++;
        }

        foreach ($severityCounts as $severity => $count) {
            $output->writeln(sprintf('  %s: %d', ucfirst($severity), $count));
        }

        $output->writeln(sprintf('  Total: %d notifications', count($notifications)));
    }
}
