# Developer Guide - Profile Notification System

## Architecture Overview

The Profile Notification System uses a plugin-based architecture to intercept profile service and processor executions, capturing all events, errors, and performance metrics.

### Key Components

1. **NotificationManager**: Core service for logging notifications
2. **Plugins**: Intercept service and processor executions
3. **Observers**: Capture message storage events
4. **Repository**: Data persistence layer
5. **Email Sender**: Handle email notifications

## Core Concepts

### Notification Flow

```
Profile Service Execute
    ↓
ServiceNotificationPlugin::aroundExecute()
    ↓
NotificationManager::startProcess()
    ↓
[Service Execution]
    ├── ProcessorNotificationPlugin::aroundExecute()
    ├── MessageStorageObserver::execute()
    └── Exception Handling
    ↓
NotificationManager::endProcess()
    ↓
Email Notifications (if configured)
```

### Context Management

The notification system maintains context throughout execution:

```php
// Global context set at service level
$notificationManager->setContext([
    'profile_id' => 123,
    'type_id' => 'order_export'
]);

// Additional context at processor level
$notificationManager->setContext([
    'entity_id' => 'ORDER-001',
    'entity_type' => 'order'
]);
```

## Using the Notification System

### Basic Usage

```php
use SoftCommerce\ProfileNotification\Api\NotificationManagementInterface;

class MyService
{
    public function __construct(
        private NotificationManagementInterface $notificationManager
    ) {}

    public function execute()
    {
        // Log different severity levels
        $this->notificationManager->debug('Starting process');
        $this->notificationManager->notice('Item processed successfully');
        $this->notificationManager->warning('Skipping invalid item');
        $this->notificationManager->error('Failed to process item');
        $this->notificationManager->critical('System failure');
        
        // Log with context
        $this->notificationManager->error('Order export failed', [
            'order_id' => '100000123',
            'error_code' => 'API_TIMEOUT',
            'api_endpoint' => '/orders'
        ]);
        
        // Log exceptions
        try {
            $this->riskyOperation();
        } catch (\Exception $e) {
            $this->notificationManager->logException($e, [
                'operation' => 'risky_operation'
            ]);
        }
    }
}
```

### Process Tracking

```php
class MyProfileService implements ServiceInterface
{
    public function execute()
    {
        // Process tracking is automatic via plugins
        // but you can add custom tracking:
        
        $processId = $this->notificationManager->startProcess(
            $this->getProfileId(),
            'custom_process'
        );
        
        try {
            // Your processing logic
            $this->doWork();
            
            // Update summary
            $this->notificationManager->setSummary($processId, [
                'total_processed' => 100,
                'custom_metric' => 'value'
            ]);
            
        } finally {
            $this->notificationManager->endProcess($processId, 'completed');
        }
    }
}
```

## Extending the System

### Custom Severity Mapper

```php
class CustomSeverityMapper
{
    public function map(string $status): string
    {
        return match($status) {
            'custom_status' => NotificationManagementInterface::SEVERITY_WARNING,
            default => NotificationManagementInterface::SEVERITY_NOTICE
        };
    }
}
```

### Custom Notification Plugin

```php
namespace Vendor\Module\Plugin;

class CustomNotificationPlugin
{
    public function beforeExecute($subject)
    {
        $this->notificationManager->setContext([
            'custom_data' => $this->getCustomData($subject)
        ]);
    }
    
    public function afterExecute($subject, $result)
    {
        $this->notificationManager->notice('Custom operation completed', [
            'result' => $result
        ]);
        return $result;
    }
}
```

### Event Observers

```php
<!-- etc/events.xml -->
<event name="custom_profile_event">
    <observer name="custom_notification_observer" 
              instance="Vendor\Module\Observer\CustomNotificationObserver"/>
</event>

// Observer Implementation
class CustomNotificationObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $data = $observer->getData('custom_data');
        $this->notificationManager->notice('Custom event triggered', [
            'data' => $data
        ]);
    }
}
```

## Repository Usage

### Retrieving Notifications

```php
use SoftCommerce\ProfileNotification\Api\NotificationRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class NotificationReader
{
    public function getRecentErrors(int $profileId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('profile_id', $profileId)
            ->addFilter('severity', ['error', 'critical'], 'in')
            ->addFilter('created_at', date('Y-m-d', strtotime('-1 day')), 'gteq')
            ->create();
            
        $searchResults = $this->notificationRepository->getList($searchCriteria);
        return $searchResults->getItems();
    }
}
```

### Creating Notifications Directly

```php
use SoftCommerce\ProfileNotification\Api\Data\NotificationInterfaceFactory;

class DirectNotificationCreator
{
    public function createNotification(array $data): void
    {
        $notification = $this->notificationFactory->create();
        $notification->setProfileId($data['profile_id'])
            ->setSeverity(NotificationInterface::SEVERITY_ERROR)
            ->setTitle($data['title'])
            ->setMessage($data['message'])
            ->setContext($this->serializer->serialize($data['context']));
            
        $this->notificationRepository->save($notification);
    }
}
```

## Email Integration

### Custom Email Templates

```xml
<!-- etc/email_templates.xml -->
<template id="custom_notification_template"
          label="Custom Notification"
          file="custom-notification.html"
          type="html"
          module="Vendor_Module"
          area="adminhtml"/>
```

### Programmatic Email Sending

```php
use SoftCommerce\ProfileNotification\Model\Email\SenderInterface;

class CustomEmailSender
{
    public function sendCustomAlert(string $message): void
    {
        $this->emailSender->send(
            'custom_notification_template',
            ['message' => $message, 'timestamp' => date('Y-m-d H:i:s')],
            'admin@example.com'
        );
    }
}
```

## Performance Considerations

### Batch Processing

```php
// Instead of logging each item
foreach ($items as $item) {
    $this->notificationManager->notice("Processed {$item->getId()}");
}

// Log summary after batch
$this->notificationManager->notice("Processed {$count} items", [
    'item_ids' => $processedIds,
    'duration' => $duration
]);
```

### Async Logging

The notification system uses database transactions. For high-volume operations:

```php
// Disable immediate persistence
$this->notificationManager->setDeferredMode(true);

// Process items
foreach ($items as $item) {
    $this->processItem($item);
}

// Flush all notifications at once
$this->notificationManager->flush();
```

## Testing

### Unit Tests

```php
class ServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testNotificationLogging()
    {
        $notificationManager = $this->createMock(NotificationManagementInterface::class);
        
        $notificationManager->expects($this->once())
            ->method('error')
            ->with('Test error', ['context' => 'test']);
            
        $service = new MyService($notificationManager);
        $service->processWithError();
    }
}
```

### Integration Tests

```php
/**
 * @magentoDbIsolation enabled
 */
class NotificationIntegrationTest extends \PHPUnit\Framework\TestCase
{
    public function testNotificationPersistence()
    {
        $notificationManager = $this->objectManager->get(NotificationManagementInterface::class);
        $repository = $this->objectManager->get(NotificationRepositoryInterface::class);
        
        $notificationManager->error('Test error', ['test' => true]);
        
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('message', 'Test error')
            ->create();
            
        $results = $repository->getList($searchCriteria);
        $this->assertCount(1, $results->getItems());
    }
}
```

## Debugging

### Enable Debug Logging

```php
// In your service
$this->notificationManager->debug('Variable state', [
    'variable' => $variable,
    'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
]);
```

### Log Analysis

```bash
# View recent errors
SELECT * FROM softcommerce_profile_notification 
WHERE severity IN ('error', 'critical') 
AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY created_at DESC;

# Check process performance
SELECT process_id, execution_time, peak_memory, total_errors 
FROM softcommerce_profile_notification_summary 
WHERE profile_id = ? 
ORDER BY started_at DESC 
LIMIT 10;
```

## Configuration

### Programmatic Configuration

```php
use Magento\Framework\App\Config\ScopeConfigInterface;

class ConfigReader
{
    const XML_PATH_ENABLED = 'softcommerce_profile_notification/general/enabled';
    const XML_PATH_LOG_LEVEL = 'softcommerce_profile_notification/general/log_level';
    
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED);
    }
    
    public function getMinimumLogLevel(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_LOG_LEVEL);
    }
}
```

## Best Practices

1. **Use Appropriate Severity Levels**
   - Debug: Development information
   - Notice: Normal operations
   - Warning: Recoverable issues
   - Error: Operation failures
   - Critical: System failures

2. **Include Relevant Context**
   ```php
   $this->notificationManager->error('API call failed', [
       'endpoint' => $endpoint,
       'request_data' => $this->sanitizeData($requestData),
       'response_code' => $responseCode,
       'response_body' => substr($responseBody, 0, 1000)
   ]);
   ```

3. **Avoid Over-Logging**
   - Don't log every iteration in loops
   - Summarize batch operations
   - Use debug level for verbose logging

4. **Security Considerations**
   - Never log passwords or API keys
   - Sanitize sensitive data
   - Be careful with customer information

## Troubleshooting

### Notifications Not Appearing
1. Check if module is enabled
2. Verify minimum log level configuration
3. Check for database errors in `var/log/exception.log`

### Performance Issues
1. Review notification volume
2. Check retention settings
3. Analyze slow queries
4. Consider implementing deferred mode

### Memory Issues
```php
// For large datasets, process in chunks
$collection->setPageSize(100);
$pages = $collection->getLastPageNumber();

for ($page = 1; $page <= $pages; $page++) {
    $collection->setCurPage($page)->load();
    foreach ($collection as $item) {
        // Process item
    }
    $collection->clear();
}
```

## API Reference

For detailed API documentation, see [API_REFERENCE.md](API_REFERENCE.md)