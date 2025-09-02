# Collect Services and Profile Notification Integration

## Overview

All collect services that extend `AbstractCollectManagement` are now integrated with the profile notification system to track errors, warnings, and execution metrics.

## Profile ID Requirement

**Important**: Collect services should always be executed with a profile context. The notification system requires a valid profile ID to:

1. Track notifications in the `softcommerce_profile_notification` table
2. Create process summaries with execution metrics
3. Associate errors and warnings with specific profiles

## How to Provide Profile ID

### 1. Via Factory Pattern (Recommended)
```php
$collectService = $collectServiceFactory->create([
    'data' => [ProfileInterface::PROFILE_ID => $profileId]
]);
```

### 2. Via Setter Method
```php
$collectService = $collectServiceFactory->create();
$collectService->setProfileId($profileId);
```

## Behavior Without Profile ID

If a collect service is executed without a profile ID:
- The service will execute normally
- A warning will be logged to `/var/log/profile_notification.log`
- No notifications will be saved to the database
- No process tracking will occur

## Console Commands

Console commands that execute collect services should:
1. Attempt to find an appropriate profile
2. Warn the user if no profile is found
3. Pass the profile ID to the collect service

Example:
```php
$profileId = $this->getProfileId($input, ItemImportServiceInterface::TYPE_ID);

if (!$profileId) {
    $output->writeln('<comment>Warning: No profile found. Notifications will not be tracked.</comment>');
}

$service = $collectServiceFactory->create(['data' => [ProfileInterface::PROFILE_ID => $profileId]]);
```

## Architecture Note

The database schema enforces profile ID as a required foreign key. This ensures:
- Data integrity
- Proper association of notifications with profiles
- Consistent tracking across all profile operations