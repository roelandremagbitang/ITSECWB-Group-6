# Error Handling and Logging System Implementation

This document outlines the comprehensive error handling and logging system implemented for the La Frontera Inventory Management System to fulfill the security requirements checklist.

## Overview

The system implements a multi-layered approach to error handling and logging that ensures:
- Secure error handling without exposing debugging information
- Comprehensive logging of all security events
- Input validation that rejects invalid data instead of sanitizing
- Access control logging and monitoring
- Administrator-only access to security logs

## Files Created/Modified

### New Files Created

1. **`dependencies/logger.php`** - Security logging system
2. **`dependencies/error_handler.php`** - Error handling system
3. **`dependencies/validator.php`** - Input validation system
4. **`error.php`** - Generic error page
5. **`access_denied.php`** - Access denied page
6. **`logs_viewer.php`** - Security logs viewer for administrators
7. **`export_logs.php`** - Log export functionality
8. **`database/security_logs.sql`** - Database schema for security logs

### Modified Files

1. **`dependencies/config.php`** - Added debug mode and secure error handling
2. **`dependencies/auth.php`** - Integrated logging for access control
3. **`login.php`** - Added authentication logging
4. **`forget_password.php`** - Added validation and logging
5. **`accounts_page.php`** - Added account operation logging
6. **`add_inventory.php`** - Added validation and logging

## Checklist Requirements Fulfilled

### ✅ Error Handling and Logging

#### Use error handlers that do not display debugging or stack trace information
- **Implementation**: `SecurityErrorHandler` class in `dependencies/error_handler.php`
- **Features**: 
  - Disables error display in production mode
  - Catches PHP errors, exceptions, and fatal errors
  - Logs all errors securely without exposing system details

#### Implement generic error messages and use custom error pages
- **Implementation**: 
  - `error.php` - Generic error page for system errors
  - `access_denied.php` - Access denied page for authorization failures
- **Features**:
  - No debugging information displayed
  - User-friendly error messages
  - Consistent error page design

#### Logging controls should support both success and failure of specified security events
- **Implementation**: `SecurityLogger` class in `dependencies/logger.php`
- **Features**:
  - Logs both successful and failed security events
  - Tracks event types, user information, IP addresses, and timestamps
  - Stores logs in both database and file system

#### Restrict access to logs to only website administrators
- **Implementation**: `logs_viewer.php` and `export_logs.php`
- **Features**:
  - Requires `owner` or `admin` role access
  - Uses existing authentication system
  - Provides filtered log viewing and CSV export

#### Log all input validation failures
- **Implementation**: `SecurityValidator` class in `dependencies/validator.php`
- **Features**:
  - Logs every validation failure with detailed information
  - Tracks field name, invalid value, and validation rule
  - Integrates with the main logging system

#### Log all authentication attempts, especially failures
- **Implementation**: Enhanced `login.php` and `forget_password.php`
- **Features**:
  - Logs successful and failed login attempts
  - Tracks account lockouts and password reset attempts
  - Records IP addresses and timestamps for security analysis

#### Log all access control failures
- **Implementation**: Enhanced `dependencies/auth.php`
- **Features**:
  - Logs unauthorized access attempts
  - Records resource access failures
  - Tracks role-based access control violations

## Security Features Implemented

### 1. Input Validation System

The `SecurityValidator` class implements strict input validation that:
- **Rejects invalid input** instead of sanitizing (as required by checklist)
- **Validates data range** and **data length** for all inputs
- **Prevents HTML injection** by rejecting dangerous characters
- **Logs all validation failures** for security monitoring

#### Validation Rules Implemented:
- **Email**: Valid format, maximum 255 characters
- **Password**: Minimum 8 characters, maximum 128 characters, complexity requirements
- **Username**: 3-50 characters, alphanumeric and underscores only
- **Product Names**: 1-100 characters, no HTML tags
- **Quantities**: Positive integers, maximum 999,999
- **Prices**: Positive decimals, maximum 999,999.99, 2 decimal places max
- **Supplier Names**: 1-100 characters, no HTML tags

### 2. Error Handling System

The `SecurityErrorHandler` class provides:
- **Secure error handling** without exposing system details
- **Custom error pages** for different error types
- **Comprehensive logging** of all system errors
- **Production-safe error display** (no debugging information)

### 3. Security Logging System

The `SecurityLogger` class logs:
- **Authentication events**: Login success/failure, password changes, account lockouts
- **Access control events**: Authorization failures, unauthorized resource access
- **Input validation events**: All validation failures with detailed context
- **System events**: Database errors, PHP errors, exceptions
- **User operations**: Account deletions, inventory changes, product modifications

### 4. Log Management

Administrators can:
- **View security logs** through a web interface
- **Filter logs** by event type, success status, and date
- **Export logs** to CSV format for analysis
- **Monitor security events** in real-time
- **Track user activities** across the system

## Database Schema

### Security Logs Table

```sql
CREATE TABLE security_logs (
  id int(11) NOT NULL AUTO_INCREMENT,
  event_type varchar(100) NOT NULL,
  user_id int(11) DEFAULT NULL,
  username varchar(100) NOT NULL,
  details text,
  ip_address varchar(45) NOT NULL,
  success tinyint(1) NOT NULL DEFAULT 0,
  timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_event_type (event_type),
  KEY idx_user_id (user_id),
  KEY idx_timestamp (timestamp),
  KEY idx_success (success),
  KEY idx_ip_address (ip_address)
);
```

## Usage Examples

### 1. Using the Validator

```php
$validator = new SecurityValidator($logger);

if (!$validator->validateEmail($email, $user_id, $username)) {
    $errors = $validator->getErrors();
    // Handle validation errors
}
```

### 2. Using the Logger

```php
$logger = new SecurityLogger($conn);

// Log authentication attempt
$logger->logAuthAttempt($email, $success, $ip_address, $details);

// Log security event
$logger->logSecurityEvent('CUSTOM_EVENT', $user_id, $username, $details, $ip_address, $success);
```

### 3. Using the Error Handler

```php
$error_handler = new SecurityErrorHandler($conn, $logger);

// Handle database errors
$error_handler->handleDatabaseError("Connection failed");

// Handle validation errors
$error_handler->handleValidationError($field, $value, $rule, $user_id, $username);
```

## Configuration

### Debug Mode

Set `DEBUG_MODE` in `dependencies/config.php`:
- **`true`**: Shows detailed error information (development)
- **`false`**: Shows generic error messages (production)

### Log File Location

Logs are stored in the `logs/` directory relative to the project root. The system automatically creates this directory if it doesn't exist.

## Security Considerations

1. **No Debug Information**: Production mode never exposes system details
2. **Input Rejection**: Invalid input is rejected, not sanitized
3. **Comprehensive Logging**: All security events are logged for audit trails
4. **Access Control**: Logs are only accessible to administrators
5. **IP Tracking**: All events are logged with client IP addresses
6. **User Context**: All events are associated with user accounts

## Monitoring and Maintenance

### Regular Tasks
1. **Review security logs** for suspicious activity
2. **Export logs** for long-term storage and analysis
3. **Monitor failed authentication attempts** for brute force attacks
4. **Check validation failures** for potential attack patterns
5. **Review access control failures** for authorization issues

### Log Rotation
Consider implementing log rotation to prevent log files from growing too large:
- Archive old logs monthly
- Keep logs for at least 1 year for compliance
- Monitor disk space usage

## Compliance

This implementation fulfills all requirements from the error handling and logging checklist:
- ✅ Secure error handling without debugging information
- ✅ Generic error messages and custom error pages
- ✅ Comprehensive logging of security events
- ✅ Administrator-only access to logs
- ✅ Input validation failure logging
- ✅ Authentication attempt logging
- ✅ Access control failure logging

The system provides a robust foundation for security monitoring and compliance while maintaining user-friendly error handling and comprehensive audit trails.
