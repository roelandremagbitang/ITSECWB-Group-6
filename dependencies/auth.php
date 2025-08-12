<?php
session_start();

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        // Log access attempt
        if (isset($_SESSION['username'])) {
            logAccessAttempt($_SESSION['username'], 'login_required', $_SERVER['REQUEST_URI']);
        }
        header("Location: login.php");
        exit();
    }
}

function require_role($roles) {
    if (!isset($_SESSION['user_role'])) {
        // Log access attempt
        if (isset($_SESSION['username'])) {
            logAccessAttempt($_SESSION['username'], 'role_required', $_SERVER['REQUEST_URI']);
        }
        header("Location: login.php");
        exit();
    }

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    if (!in_array($_SESSION['user_role'], $roles)) {
        // Log access control failure
        logAccessControlFailure($_SESSION['user_id'], $_SESSION['username'], $_SERVER['REQUEST_URI']);
        
        // Show access denied page instead of JavaScript alert
        header("Location: access_denied.php");
        exit();
    }
}

function redirect_if_logged_in() {
    if (isset($_SESSION['user_id'])) {
        header("Location: MenuPage.php");
        exit();
    }
}

/**
 * Log access attempt
 */
function logAccessAttempt($username, $reason, $resource) {
    if (function_exists('logSecurityEvent')) {
        $ip = getClientIP();
        logSecurityEvent('ACCESS_ATTEMPT', null, $username, "Access attempt: $reason for resource: $resource", $ip, false);
    }
}

/**
 * Log access control failure
 */
function logAccessControlFailure($user_id, $username, $resource) {
    if (function_exists('logSecurityEvent')) {
        $ip = getClientIP();
        logSecurityEvent('ACCESS_DENIED', $user_id, $username, "Access denied to resource: $resource", $ip, false);
    }
}

/**
 * Get client IP address
 */
function getClientIP() {
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
?>