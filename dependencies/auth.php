<?php
session_start();

// Include logger if not already included
if (!class_exists('SecurityLogger')) {
    require_once __DIR__ . '/logger.php';
}

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
    global $conn;
    if (class_exists('SecurityLogger')) {
        $logger = new SecurityLogger($conn);
        $logger->logSecurityEvent('ACCESS_ATTEMPT', null, $username, "Access attempt: $reason for resource: $resource", false);
    }
}

/**
 * Log access control failure
 */
function logAccessControlFailure($user_id, $username, $resource) {
    global $conn;
    if (class_exists('SecurityLogger')) {
        $logger = new SecurityLogger($conn);
        $logger->logSecurityEvent('ACCESS_DENIED', $user_id, $username, "Access denied to resource: $resource", false);
    }
}


?>