# User Guide - Profile Notification System

## Overview

The Profile Notification System provides comprehensive logging and alerting for all profile operations in the Mage2Plenty connector. This guide will help you understand how to use and monitor the system effectively.

## Accessing Notifications

### Main Navigation
1. Log in to Magento Admin
2. Navigate to **System > Profile Notifications**

### Alternative Access
- From any Profile page, click the **"Notifications"** tab

## Understanding the Notification Grid

### Grid Columns

| Column | Description |
|--------|-------------|
| **ID** | Unique notification identifier |
| **Severity** | Level of importance (Debug, Notice, Warning, Error, Critical) |
| **Profile** | Which profile generated the notification |
| **Entity Type** | Type of data (order, product, customer, etc.) |
| **Entity ID** | Specific ID of the affected entity |
| **Title** | Brief description of the notification |
| **Read** | Whether the notification has been viewed |
| **Created** | When the notification was generated |

### Severity Levels Explained

- 🟢 **Debug**: Detailed information for developers
- 🔵 **Notice**: Normal operational messages
- 🟡 **Warning**: Issues that don't stop processing
- 🔴 **Error**: Failures that affect individual entities
- ⚫ **Critical**: System-wide failures requiring immediate attention

### Visual Indicators

- **Bold text**: Unread notifications
- **Color coding**: Each severity has a distinct color
- **Background shading**: Unread items have light gray background

## Using Filters

### Quick Filters
- **Severity**: Filter by importance level
- **Profile**: Show notifications from specific profile
- **Date Range**: View notifications from specific period
- **Read/Unread**: Show only new notifications

### Advanced Search
1. Click "Filters" button
2. Enter search criteria:
   - Entity ID: Find notifications for specific order/product
   - Title/Message: Search by keywords
   - Source: Filter by specific module or service

## Bulk Actions

Select multiple notifications using checkboxes, then choose:

- **Mark as Read**: Remove bold formatting
- **Delete**: Permanently remove notifications

## Viewing Notification Details

1. Click on any notification row
2. View detailed information:
   - Full error message
   - Stack trace (for errors)
   - Context data (entity details, profile settings)
   - Timestamp and source

## Process Summary View

Access via **System > Profile Notifications > Summary**

### Summary Metrics
- **Total Processed**: Number of entities handled
- **Success Count**: Successfully processed items
- **Warning Count**: Items with non-critical issues
- **Error Count**: Failed items
- **Execution Time**: How long the process took
- **Memory Usage**: Peak memory consumption

## Email Notifications

### Immediate Alerts
Critical errors trigger immediate emails containing:
- Error description
- Affected profile and entity
- Direct link to admin panel

### Batch Summaries
Periodic emails (configurable) include:
- Count of errors by severity
- Most recent critical issues
- Link to full notification list

### Process Reports
After each profile run:
- Total items processed
- Success/failure rates
- Performance metrics

## Best Practices

### Daily Monitoring
1. Check for critical/error notifications each morning
2. Review warning trends weekly
3. Clear old notifications monthly

### Investigating Issues
1. Start with critical/error severity
2. Look for patterns (same entity type, same time)
3. Check related entities using filters
4. Review full context in detail view

### Performance Monitoring
1. Check Summary view for execution times
2. Monitor memory usage trends
3. Identify slow-running processes

## Common Scenarios

### Order Export Failed
1. Filter by Entity Type = "order"
2. Look for error notifications
3. Click to view specific order ID and error details
4. Address the issue and re-run export

### Memory Limit Errors
1. Check Summary for high memory usage
2. Review critical notifications for memory errors
3. Consider batch size adjustments

### API Connection Issues
1. Look for patterns in error timing
2. Check for "connection" or "timeout" in messages
3. Verify PlentyMarkets API credentials

## Troubleshooting

### No Notifications Appearing
- Verify notifications are enabled in configuration
- Check minimum log level setting
- Ensure profile services are running

### Too Many Notifications
- Adjust minimum log level to Warning or Error
- Use retention settings to auto-delete old entries
- Set up email filters for batch summaries

### Email Alerts Not Received
- Verify email configuration
- Check email threshold settings
- Test with manual critical error

## Keyboard Shortcuts

- **R**: Mark selected as read
- **D**: Delete selected
- **F**: Focus on filters
- **Esc**: Clear selection

## Tips for Efficiency

1. **Save Filter Presets**: Bookmark frequently used filter combinations
2. **Export Data**: Use "Export" button for offline analysis
3. **Quick Actions**: Right-click notifications for context menu
4. **Notification Badge**: Check admin header for unread count

## Retention and Cleanup

- Notifications are automatically deleted after configured retention period
- Manual cleanup available via "Clear All" button
- Exports preserve data before deletion

## Getting Help

- Hover over any field for tooltips
- Click "?" icons for context help
- Contact support for system issues

---

For technical details and API usage, see the [Developer Guide](DEVELOPER_GUIDE.md).