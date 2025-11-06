# SoftCommerce Profile Notification Module

## Overview

The Profile Notification module provides a comprehensive notification and logging system for the Mage2Plenty connector. It captures all errors, warnings, and critical events during profile import/export operations, providing complete visibility into system operations.

## Features

### 🔔 Real-time Notification System
- **Service-level tracking**: Monitors start and end of profile executions
- **Entity-level tracking**: Logs individual order, product, and customer processing
- **Exception handling**: Captures and logs all exceptions with full context
- **Severity levels**: Debug, Notice, Warning, Error, and Critical

### 📊 Admin Dashboard
- **Notification Grid**: View all notifications with advanced filtering
- **Summary Grid**: View process execution summaries with performance metrics
- **Unread notifications**: Bold highlighting for new notifications
- **Bulk actions**: Mark as read, delete multiple notifications

### 📧 Email Alerts
- **Critical alerts**: Immediate email for critical errors
- **Batch summaries**: Periodic email summaries of errors
- **Process reports**: Email summary after profile execution
- **Configurable thresholds**: Set minimum severity for email alerts

### 🎯 Performance Tracking
- **Execution time**: Track how long each process takes
- **Memory usage**: Monitor peak memory consumption
- **Success rates**: Count successful vs failed operations
- **Entity counts**: Track total processed, warnings, errors

### 🔧 Developer Features
- **Plugin architecture**: Easily extendable notification system
- **Context preservation**: Full context for every logged event
- **File logging**: Redundant file logging for reliability
- **API access**: Repository pattern for programmatic access

## Module Structure

```
module-profile-notification/
├── Api/                          # Service contracts and interfaces
├── Block/                        # Admin blocks
├── Controller/                   # Admin controllers
├── Cron/                        # Scheduled tasks
├── Model/                       # Business logic
├── Observer/                    # Event observers
├── Plugin/                      # Interceptors
├── Ui/                          # UI components
├── etc/                         # Configuration
├── view/                        # Templates and layouts
└── README.md                    # This file
```

## Requirements

- Magento 2.4.x
- PHP 8.1+
- SoftCommerce Profile module

## Quick Start

1. Enable the module: `php bin/magento module:enable SoftCommerce_ProfileNotification`
2. Run setup: `php bin/magento setup:upgrade`
3. Configure email settings in Admin > Stores > Configuration > SoftCommerce > Profile Notifications
4. View notifications in Admin > System > Profile Notifications

## CLI Commands

### Send Batch Notification Emails

Manually trigger the sending of batch notification emails:

```bash
# Send batch emails (respects configuration settings)
bin/magento softcommerce:notification:send-batch-emails

# Preview what would be sent without actually sending
bin/magento softcommerce:notification:send-batch-emails --preview

# Force sending even if disabled in configuration
bin/magento softcommerce:notification:send-batch-emails --force

# Override severity threshold (send only errors and critical)
bin/magento softcommerce:notification:send-batch-emails --severity=error

# Override time interval (send notifications older than 30 minutes)
bin/magento softcommerce:notification:send-batch-emails --interval=30

# Limit number of notifications to send
bin/magento softcommerce:notification:send-batch-emails --limit=50

# Combine options
bin/magento softcommerce:notification:send-batch-emails --preview --severity=critical --interval=60
```

## Support

For issues or questions, please contact Soft Commerce Ltd support.

## License

Copyright © Soft Commerce Ltd. All rights reserved.
See LICENSE.txt for license details.