<?php
session_start();

// Log logout event if user was logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    include 'dependencies/config.php';
    include 'dependencies/logger.php';
    
    $logger = new SecurityLogger($conn);
    $logger->logSecurityEvent('USER_LOGOUT', $_SESSION['user_id'], $_SESSION['username'], 
        "User logged out successfully", true);
    
    $conn->close();
}

session_destroy();
header("Location: login.php");
exit;
