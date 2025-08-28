# API Reference - Profile Notification System

## Table of Contents
- [Core Interfaces](#core-interfaces)
- [Data Interfaces](#data-interfaces)
- [Models](#models)
- [Plugins](#plugins)
- [Events](#events)
- [Configuration](#configuration)

## Core Interfaces

### NotificationManagementInterface

Main service for managing notifications.

```php
namespace SoftCommerce\ProfileNotification\Api;

interface NotificationManagementInterface
{
    const SEVERITY_DEBUG = 'debug';
    const SEVERITY_NOTICE = 'notice';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_ERROR = 'error';
    const SEVERITY_CRITICAL = 'critical';
```

#### Methods

##### debug()
```php
public function debug(string $message, array $context = []): void;
```
Log a debug-level message.

**Parameters:**
- `$message` - The log message
- `$context` - Additional context data

##### notice()
```php
public function notice(string $message, array $context = []): void;
```
Log a notice-level message.

##### warning()
```php
public function warning(string $message, array $context = []): void;
```
Log a warning-level message.

##### error()
```php
public function error(string $message, array $context = []): void;
```
Log an error-level message.

##### critical()
```php
public function critical(string $message, array $context = []): void;
```
Log a critical-level message and trigger immediate email.

##### logException()
```php
public function logException(\Throwable $exception, array $context = []): void;
```
Log an exception with full stack trace.

**Parameters:**
- `$exception` - The exception to log
- `$context` - Additional context data

##### startProcess()
```php
public function startProcess(int $profileId, string $typeId): string;
```
Start tracking a new process.

**Returns:** Process ID (unique identifier)

##### endProcess()
```php
public function endProcess(string $processId, string $status): void;
```
End process tracking and finalize summary.

**Parameters:**
- `$processId` - The process ID from startProcess()
- `$status` - Final status (success, error, etc.)

##### setSummary()
```php
public function setSummary(string $processId, array $summary): void;
```
Update process summary metrics.

**Parameters:**
- `$processId` - The process ID
- `$summary` - Array of metrics (total_processed, execution_time, etc.)

##### setProfileId()
```php
public function setProfileId(int $profileId): void;
```
Set the current profile context.

##### setProcessId()
```php
public function setProcessId(string $processId): void;
```
Set the current process context.

##### setContext()
```php
public function setContext(array $context): void;
```
Set additional context that will be included in all subsequent logs.

### NotificationRepositoryInterface

Repository for notification persistence.

```php
namespace SoftCommerce\ProfileNotification\Api;

interface NotificationRepositoryInterface
{
```

#### Methods

##### save()
```php
public function save(NotificationInterface $notification): NotificationInterface;
```
Save a notification.

**Throws:** `LocalizedException`

##### get()
```php
public function get(int $notificationId): NotificationInterface;
```
Get notification by ID.

**Throws:** `NoSuchEntityException`

##### delete()
```php
public function delete(NotificationInterface $notification): bool;
```
Delete a notification.

**Throws:** `LocalizedException`

##### deleteById()
```php
public function deleteById(int $notificationId): bool;
```
Delete notification by ID.

**Throws:** `LocalizedException`, `NoSuchEntityException`

##### getList()
```php
public function getList(SearchCriteriaInterface $searchCriteria): NotificationSearchResultsInterface;
```
Get list of notifications matching search criteria.

**Example:**
```php
$searchCriteria = $this->searchCriteriaBuilder
    ->addFilter('severity', 'error')
    ->addFilter('profile_id', 123)
    ->setPageSize(20)
    ->create();
    
$results = $repository->getList($searchCriteria);
```

## Data Interfaces

### NotificationInterface

Represents a single notification.

```php
namespace SoftCommerce\ProfileNotification\Api\Data;

interface NotificationInterface
{
    const NOTIFICATION_ID = 'notification_id';
    const PROFILE_ID = 'profile_id';
    const PROCESS_ID = 'process_id';
    const ENTITY_ID = 'entity_id';
    const ENTITY_TYPE = 'entity_type';
    const SEVERITY = 'severity';
    const TYPE = 'type';
    const TITLE = 'title';
    const MESSAGE = 'message';
    const CONTEXT = 'context';
    const SOURCE = 'source';
    const EXCEPTION_CLASS = 'exception_class';
    const STACK_TRACE = 'stack_trace';
    const IS_READ = 'is_read';
    const IS_EMAILED = 'is_emailed';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
```

#### Key Methods

| Method | Return Type | Description |
|--------|------------|-------------|
| `getNotificationId()` | `?int` | Get notification ID |
| `getProfileId()` | `int` | Get associated profile ID |
| `getSeverity()` | `string` | Get severity level |
| `getMessage()` | `?string` | Get notification message |
| `getContext()` | `?array` | Get context data (deserialized) |
| `getIsRead()` | `bool` | Check if notification is read |
| `getCreatedAt()` | `string` | Get creation timestamp |

### NotificationSummaryInterface

Represents a process execution summary.

```php
namespace SoftCommerce\ProfileNotification\Api\Data;

interface NotificationSummaryInterface
{
    const SUMMARY_ID = 'summary_id';
    const PROFILE_ID = 'profile_id';
    const PROCESS_ID = 'process_id';
    const TOTAL_PROCESSED = 'total_processed';
    const TOTAL_SUCCESS = 'total_success';
    const TOTAL_WARNINGS = 'total_warnings';
    const TOTAL_ERRORS = 'total_errors';
    const TOTAL_CRITICAL = 'total_critical';
    const STATUS = 'status';
    const STARTED_AT = 'started_at';
    const FINISHED_AT = 'finished_at';
    const PEAK_MEMORY = 'peak_memory';
    const EXECUTION_TIME = 'execution_time';
```

#### Key Methods

| Method | Return Type | Description |
|--------|------------|-------------|
| `getProcessId()` | `string` | Get unique process identifier |
| `getTotalProcessed()` | `?int` | Get total items processed |
| `getTotalErrors()` | `?int` | Get error count |
| `getExecutionTime()` | `?float` | Get execution time in seconds |
| `getPeakMemory()` | `?int` | Get peak memory usage in bytes |

### NotificationSearchResultsInterface

Search results container.

```php
namespace SoftCommerce\ProfileNotification\Api\Data;

interface NotificationSearchResultsInterface extends SearchResultsInterface
{
    public function getItems(): array;
    public function setItems(array $items): self;
}
```

## Models

### Email\SenderInterface

Interface for sending email notifications.

```php
namespace SoftCommerce\ProfileNotification\Model\Email;

interface SenderInterface
{
    public function sendCriticalAlert(string $message, array $context = []): void;
    public function sendProcessSummary(string $processId): void;
    public function sendBatchSummary(array $notifications): void;
}
```

## Plugins

### ServiceNotificationPlugin

Intercepts all profile service executions.

**Target:** `SoftCommerce\Profile\Model\ServiceAbstract\ServiceInterface`

**Methods intercepted:**
- `execute()` - Wrapped with process tracking

### ProcessorNotificationPlugin

Intercepts individual processor executions.

**Target:** `SoftCommerce\Profile\Model\ServiceAbstract\ProcessorInterface`

**Methods intercepted:**
- `execute()` - Wrapped with entity-level tracking

## Events

### softcommerce_profile_message_storage_add_message

Dispatched when a message is added to message storage.

**Event Data:**
- `message` - Array containing message data

### softcommerce_profile_message_storage_add_messages

Dispatched when multiple messages are added to message storage.

**Event Data:**
- `messages` - Array of message arrays

## Configuration

### System Configuration Paths

```php
// General Settings
'softcommerce_profile_notification/general/enabled'
'softcommerce_profile_notification/general/log_level'
'softcommerce_profile_notification/general/retention_days'

// Email Settings
'softcommerce_profile_notification/email/enabled'
'softcommerce_profile_notification/email/recipient'
'softcommerce_profile_notification/email/sender'
'softcommerce_profile_notification/email/threshold'
'softcommerce_profile_notification/email/batch_enabled'
'softcommerce_profile_notification/email/batch_interval'
'softcommerce_profile_notification/email/real_time_critical'
```

### Configuration Helper Example

```php
class ConfigHelper
{
    public function __construct(
        private ScopeConfigInterface $scopeConfig
    ) {}
    
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'softcommerce_profile_notification/general/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }
    
    public function getRetentionDays(): int
    {
        return (int) $this->scopeConfig->getValue(
            'softcommerce_profile_notification/general/retention_days',
            ScopeInterface::SCOPE_STORE
        ) ?: 30;
    }
}
```

## Database Schema

### softcommerce_profile_notification

| Column | Type | Description |
|--------|------|-------------|
| notification_id | int(10) unsigned | Primary key |
| profile_id | int(10) unsigned | Foreign key to profile |
| process_id | varchar(64) | Process identifier |
| entity_id | varchar(255) | Entity identifier |
| entity_type | varchar(64) | Entity type |
| severity | varchar(16) | Severity level |
| type | varchar(128) | Notification type |
| title | varchar(255) | Short title |
| message | text | Full message |
| context | json | Additional data |
| source | varchar(255) | Source class/method |
| exception_class | varchar(255) | Exception class name |
| stack_trace | text | Exception stack trace |
| is_read | boolean | Read status |
| is_emailed | boolean | Email sent status |
| created_at | timestamp | Creation time |
| updated_at | timestamp | Last update time |

### softcommerce_profile_notification_summary

| Column | Type | Description |
|--------|------|-------------|
| summary_id | int(10) unsigned | Primary key |
| profile_id | int(10) unsigned | Profile ID |
| process_id | varchar(64) | Process identifier |
| total_processed | int(10) unsigned | Items processed |
| total_success | int(10) unsigned | Successful items |
| total_warnings | int(10) unsigned | Warning count |
| total_errors | int(10) unsigned | Error count |
| total_critical | int(10) unsigned | Critical error count |
| status | varchar(16) | Process status |
| started_at | timestamp | Start time |
| finished_at | timestamp | End time |
| peak_memory | bigint(20) unsigned | Peak memory (bytes) |
| execution_time | decimal(12,4) | Execution time (seconds) |

## Error Codes

Common error contexts used by the system:

| Code | Description |
|------|-------------|
| `PROCESS_START_FAILED` | Failed to initialize process |
| `ENTITY_NOT_FOUND` | Entity ID not found |
| `API_CONNECTION_ERROR` | External API connection failed |
| `MEMORY_LIMIT_EXCEEDED` | PHP memory limit reached |
| `TIMEOUT_ERROR` | Process execution timeout |
| `VALIDATION_ERROR` | Data validation failed |
| `PERMISSION_DENIED` | Insufficient permissions |
| `CONFIGURATION_ERROR` | Invalid configuration |