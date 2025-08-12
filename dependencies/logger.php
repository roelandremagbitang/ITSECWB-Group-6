<?php
class SecurityLogger {
    private $log_file;
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->log_file = __DIR__ . '/../logs/security.log';
        
        // Create logs directory if it doesn't exist
        $logs_dir = dirname($this->log_file);
        if (!is_dir($logs_dir)) {
            mkdir($logs_dir, 0755, true);
        }
    }
    
    /**
     * Log security events to database and file
     */
    public function logSecurityEvent($event_type, $user_id, $username, $details, $ip_address, $success = true) {
        $timestamp = date('Y-m-d H:i:s');
        $success_flag = $success ? 1 : 0;
        
        // Log to database
        $this->logToDatabase($event_type, $user_id, $username, $details, $ip_address, $success_flag, $timestamp);
        
        // Log to file
        $this->logToFile($event_type, $user_id, $username, $details, $ip_address, $success_flag, $timestamp);
    }
    
    /**
     * Log authentication attempts
     */
    public function logAuthAttempt($email, $success, $ip_address, $details = '') {
        $timestamp = date('Y-m-d H:i:s');
        $user_id = null;
        $username = 'Unknown';
        
        if ($success) {
            // Get user info for successful login
            $stmt = $this->conn->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $user_id = $row['id'];
                $username = $row['username'];
            }
            $stmt->close();
        }
        
        $event_type = $success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILURE';
        $this->logSecurityEvent($event_type, $user_id, $username, $details, $ip_address, $success);
    }
    
    /**
     * Log access control failures
     */
    public function logAccessControlFailure($user_id, $username, $requested_resource, $ip_address) {
        $details = "Access denied to resource: $requested_resource";
        $this->logSecurityEvent('ACCESS_DENIED', $user_id, $username, $details, $ip_address, false);
    }
    
    /**
     * Log input validation failures
     */
    public function logValidationFailure($user_id, $username, $input_type, $input_value, $validation_rule, $ip_address) {
        $details = "Validation failed for $input_type: '$input_value' (Rule: $validation_rule)";
        $this->logSecurityEvent('VALIDATION_FAILURE', $user_id, $username, $details, $ip_address, false);
    }
    
    /**
     * Log password change attempts
     */
    public function logPasswordChange($user_id, $username, $success, $ip_address, $details = '') {
        $event_type = $success ? 'PASSWORD_CHANGE_SUCCESS' : 'PASSWORD_CHANGE_FAILURE';
        $this->logSecurityEvent($event_type, $user_id, $username, $details, $ip_address, $success);
    }
    
    /**
     * Log account operations
     */
    public function logAccountOperation($operation_type, $user_id, $username, $target_user_id, $target_username, $ip_address, $success = true) {
        $details = "$operation_type operation on account: $target_username (ID: $target_user_id)";
        $this->logSecurityEvent($operation_type, $user_id, $username, $details, $ip_address, $success);
    }
    
    /**
     * Log to database
     */
    private function logToDatabase($event_type, $user_id, $username, $details, $ip_address, $success, $timestamp) {
        $stmt = $this->conn->prepare("INSERT INTO security_logs (event_type, user_id, username, details, ip_address, success, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssss", $event_type, $user_id, $username, $details, $ip_address, $success, $timestamp);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Log to file
     */
    private function logToFile($event_type, $user_id, $username, $details, $ip_address, $success, $timestamp) {
        $log_entry = sprintf(
            "[%s] %s | User: %s (ID: %s) | IP: %s | Success: %s | Details: %s\n",
            $timestamp,
            $event_type,
            $username,
            $user_id ?: 'Unknown',
            $ip_address,
            $success ? 'Yes' : 'No',
            $details
        );
        
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get user's IP address
     */
    public static function getClientIP() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
}
?>
