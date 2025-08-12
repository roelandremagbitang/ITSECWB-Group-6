<?php
class SecurityErrorHandler {
    private $logger;
    private $conn;
    
    public function __construct($conn, $logger = null) {
        $this->conn = $conn;
        $this->logger = $logger;
        
        // Set error handling
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleFatalError']);
        
        // Disable error display in production
        if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
            error_reporting(0);
            ini_set('display_errors', 0);
        }
    }
    
    /**
     * Handle PHP errors
     */
    public function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $error_message = "PHP Error [$errno]: $errstr in $errfile on line $errline";
        
        // Log the error
        if ($this->logger) {
            $this->logger->logSecurityEvent(
                'PHP_ERROR',
                $_SESSION['user_id'] ?? null,
                $_SESSION['username'] ?? 'System',
                $error_message,
                false
            );
        }
        
        // In production, don't show error details
        if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
            $this->showGenericError();
            return true;
        }
        
        return false;
    }
    
    /**
     * Handle exceptions
     */
    public function handleException($exception) {
        $error_message = "Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
        
        // Log the exception
        if ($this->logger) {
            $this->logger->logSecurityEvent(
                'EXCEPTION',
                $_SESSION['user_id'] ?? null,
                $_SESSION['username'] ?? 'System',
                $error_message,
                false
            );
        }
        
        // In production, show generic error
        if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
            $this->showGenericError();
        } else {
            echo "<h1>Exception</h1>";
            echo "<p>" . htmlspecialchars($exception->getMessage()) . "</p>";
        }
        
        exit(1);
    }
    
    /**
     * Handle fatal errors
     */
    public function handleFatalError() {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $error_message = "Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'];
            
            // Log the fatal error
            if ($this->logger) {
                $this->logger->logSecurityEvent(
                    'FATAL_ERROR',
                    $_SESSION['user_id'] ?? null,
                    $_SESSION['username'] ?? 'System',
                    $error_message,
                    false
                );
            }
            
            // Show generic error page
            $this->showGenericError();
        }
    }
    
    /**
     * Show generic error page without debugging information
     */
    private function showGenericError() {
        if (!headers_sent()) {
            http_response_code(500);
        }
        
        include __DIR__ . '/../error.php';
        exit(1);
    }
    
    /**
     * Handle database connection errors
     */
    public function handleDatabaseError($error_message) {
        // Log database error
        if ($this->logger) {
            $this->logger->logSecurityEvent(
                'DATABASE_ERROR',
                $_SESSION['user_id'] ?? null,
                $_SESSION['username'] ?? 'System',
                $error_message,
                false
            );
        }
        
        // Show generic error
        $this->showGenericError();
    }
    
    /**
     * Handle validation errors
     */
    public function handleValidationError($field, $value, $rule, $user_id = null, $username = null) {
        $error_message = "Validation failed for field '$field' with value '$value' (Rule: $rule)";
        
        // Log validation failure
        if ($this->logger) {
            $this->logger->logValidationFailure(
                $user_id,
                $username ?? 'Unknown',
                $field,
                $value,
                $rule
            );
        }
        
        return false;
    }
    
    /**
     * Handle access control failures
     */
    public function handleAccessControlFailure($resource, $user_id = null, $username = null) {
        // Log access control failure
        if ($this->logger) {
            $this->logger->logAccessControlFailure(
                $user_id,
                $username ?? 'Unknown',
                $resource
            );
        }
        
        // Show access denied page
        $this->showAccessDenied();
    }
    
    /**
     * Show access denied page
     */
    private function showAccessDenied() {
        if (!headers_sent()) {
            http_response_code(403);
        }
        
        include __DIR__ . '/../access_denied.php';
        exit(1);
    }
    
    /**
     * Clean up error handler
     */
    public function __destruct() {
        restore_error_handler();
        restore_exception_handler();
    }
}
?>
