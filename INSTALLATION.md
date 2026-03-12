# Installation Guide

## Prerequisites

Before installing the Profile Notification module, ensure you have:

- Magento 2.4.x installed and running
- PHP 8.1 or higher
- Composer installed
- SoftCommerce Profile module installed
- Database backup (recommended)

## Installation Steps

### 1. Module Installation

Since the module is part of the packages/modules directory, it should be automatically discovered by Composer.

### 2. Enable the Module

```bash
php bin/magento module:enable SoftCommerce_ProfileNotification
```

### 3. Run Setup Scripts

```bash
php bin/magento setup:upgrade
```

This will create the required database tables:
- `softcommerce_profile_notification` - Stores individual notifications
- `softcommerce_profile_notification_summary` - Stores process summaries

### 4. Compile Dependency Injection

```bash
php bin/magento setup:di:compile
```

### 5. Deploy Static Content

```bash
php bin/magento setup:static-content:deploy -f
```

### 6. Clear Cache

```bash
php bin/magento cache:clean
php bin/magento cache:flush
```

## Configuration

### Email Settings

1. Navigate to **Admin > Stores > Configuration > SoftCommerce > Profile Notifications**
2. Configure the following settings:

#### General Settings
- **Enable Notifications**: Yes/No
- **Minimum Log Level**: Debug/Notice/Warning/Error/Critical
- **Retention Period (Days)**: Number of days to keep notifications

#### Email Settings
- **Enable Email Notifications**: Yes/No
- **Recipient Email**: Email address for notifications
- **Email Sender**: Select sender identity
- **Email Threshold**: Minimum severity to trigger emails
- **Enable Batch Email Summary**: Yes/No
- **Batch Interval (Minutes)**: Time between batch emails
- **Send Critical Errors Immediately**: Yes/No

### Cron Jobs

The module includes three cron jobs that run automatically:

1. **Cleanup Old Notifications** (`softcommerce_notification_cleanup`)
   - Runs daily at 2:00 AM
   - Removes notifications older than retention period

2. **Send Batch Emails** (`softcommerce_notification_send_batch_emails`)
   - Runs every 15 minutes
   - Sends batch email summaries

3. **Mark Emailed Notifications** (`softcommerce_notification_mark_emailed`)
   - Runs every 5 minutes
   - Updates email status flags

## Verification

To verify the installation:

1. Check module status:
   ```bash
   php bin/magento module:status | grep SoftCommerce_ProfileNotification
   ```

2. Verify database tables:
   ```sql
   SHOW TABLES LIKE 'softcommerce_profile_notification%';
   ```

3. Access the admin panel:
   - Navigate to **System > Profile Notifications**
   - You should see the notification grid

## Troubleshooting

### Module Not Found
If the module is not recognized:
1. Check that the module exists in `packages/modules/module-profile-notification`
2. Verify `registration.php` is present
3. Clear Composer cache: `composer clear-cache`

### Database Errors
If you encounter database errors:
1. Check error logs in `var/log/system.log`
2. Verify foreign key constraints with profile tables
3. Run `php bin/magento setup:db-schema:upgrade`

### Permission Issues
Ensure proper file permissions:
```bash
find packages/modules/module-profile-notification -type d -exec chmod 755 {} \;
find packages/modules/module-profile-notification -type f -exec chmod 644 {} \;
```

## Uninstallation

To remove the module:

1. Disable the module:
   ```bash
   php bin/magento module:disable SoftCommerce_ProfileNotification
   ```

2. Remove database tables (optional):
   ```sql
   DROP TABLE IF EXISTS softcommerce_profile_notification_summary;
   DROP TABLE IF EXISTS softcommerce_profile_notification;
   ```

3. Run setup:
   ```bash
   php bin/magento setup:upgrade
   ```

## Support

For installation support, please contact Byte8 Ltd.
