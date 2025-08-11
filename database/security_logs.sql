-- Security Logs Table
-- This table stores all security-related events for auditing and monitoring

CREATE TABLE IF NOT EXISTS `security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(100) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `details` text,
  `ip_address` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_timestamp` (`timestamp`),
  KEY `idx_success` (`success`),
  KEY `idx_ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial system startup log entry
INSERT IGNORE INTO `security_logs` (`event_type`, `username`, `details`, `ip_address`, `success`, `timestamp`) VALUES
('SYSTEM_STARTUP', 'System', 'Security logging system initialized', '127.0.0.1', 1, NOW());

-- Sample event types that will be logged:
-- AUTHENTICATION: login attempts, password changes, account lockouts
-- ACCESS_CONTROL: unauthorized access attempts, role violations
-- VALIDATION: input validation failures, malformed data
-- DATABASE: database errors, connection issues
-- INVENTORY: inventory modifications, stock changes
-- PRODUCTS: product operations, price changes
-- ORDERS: order creation, modification, cancellation
-- ACCOUNT: account operations, user management
-- SYSTEM: system events, errors, warnings
