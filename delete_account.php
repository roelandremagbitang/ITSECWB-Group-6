<?php
session_start();
include 'dependencies/config.php';
include 'dependencies/auth.php';
include 'dependencies/logger.php';
include 'dependencies/validator.php';

require_login(); // user has to be logged in to access this page
require_role(['owner', 'admin', 'customer']); // owner, admin, and customer has access to this page

// Initialize logger and validator
$logger = new SecurityLogger($conn);
$validator = new SecurityValidator($logger);

$message = '';
$message_type = '';

if (!isset($_SESSION['email'])) {
    $message = "Not authorized.";
    $message_type = 'error';
    
    // Log unauthorized access attempt
            $logger->logSecurityEvent('UNAUTHORIZED_ACCESS', null, 'Unknown', 'Attempted to access delete_account.php without login', false);
} else {
    $email = $_SESSION['email'];
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $user_role = $_SESSION['user_role'];

    // Prevent deletion of owner accounts
    if ($user_role === 'owner') {
        $message = "Owner accounts cannot be deleted for security reasons.";
        $message_type = 'error';
        
        // Log attempt to delete owner account
        $logger->logSecurityEvent('UNAUTHORIZED_ACTION', $user_id, $username, 
            "Attempted to delete owner account: $email", false);
    } else {
        // Get user details before deletion for logging
        $get_user = $conn->prepare("SELECT usertype, created_at FROM users WHERE email = ?");
        $get_user->bind_param("s", $email);
        $get_user->execute();
        $user_result = $get_user->get_result();
        
        if ($user_result->num_rows === 1) {
            $user_details = $user_result->fetch_assoc();
            
            // Check if user has active orders
            $check_orders = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE username = ? AND status IN ('Pending', 'Processing')");
            $check_orders->bind_param("s", $username);
            $check_orders->execute();
            $order_result = $check_orders->get_result();
            $order_count = $order_result->fetch_assoc()['order_count'];
            $check_orders->close();
            
            if ($order_count > 0) {
                $message = "Cannot delete account - you have {$order_count} active order(s). Please complete or cancel them first.";
                $message_type = 'error';
                
                // Log attempt to delete account with active orders
                $logger->logSecurityEvent('DELETE_BLOCKED', $user_id, $username, 
                    "Attempted to delete account with active orders: Count: $order_count", false);
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);

                if ($stmt->execute()) {
                    $message = "Account deleted successfully. You will be logged out.";
                    $message_type = 'success';
                    
                    // Log successful account deletion
                    $logger->logAccountOperation('ACCOUNT_DELETED', $user_id, $username, $user_id, $username, true);
                    
                    session_unset();
                    session_destroy();
                } else {
                    $message = "Failed to delete account.";
                    $message_type = 'error';
                    
                    // Log account deletion failure
                    $logger->logAccountOperation('ACCOUNT_DELETION_FAILED', $user_id, $username, $user_id, $username, false);
                }
                $stmt->close();
            }
        } else {
            $message = "User not found in database.";
            $message_type = 'error';
            
            // Log user not found
            $logger->logSecurityEvent('USER_NOT_FOUND', $user_id, $username, 
                "User not found during account deletion: $email", false);
        }
        $get_user->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account - La Frontera</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <style>
        .message-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .message {
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn-back {
            background-color: #FFC800;
            color: black;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        .btn-back:hover {
            background-color: #e6b300;
            color: black;
        }
    </style>
</head>
<body>
<div class="header">
    <div class="logo">La Frontera</div>
    <div class="search-nav">
        <a href="MenuPage.php" class="nav-item">Dashboard</a>
        <?php if ($message_type !== 'success'): ?>
        <a href="profile.php" class="nav-item">Profile</a>
        <?php endif; ?>
    </div>

    <?php if ($message_type !== 'success'): ?>
    <div class="username-display">
        <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?>
    </div>

    <div class="user-dropdown">
        <div class="user-icon" id="userDropdownBtn">
            <i class="fas fa-user"></i>
        </div>
        <div class="dropdown-menu" id="userDropdownMenu">
            <a href="profile.php">My Account</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="message-container">
    <div class="logo" style="font-size: 24px; margin-bottom: 20px;">
        <i class="fas fa-user-times"></i> Account Management
    </div>
    
    <div class="message <?php echo $message_type; ?>">
        <?php if ($message_type === 'success'): ?>
            <i class="fas fa-check-circle"></i>
        <?php else: ?>
            <i class="fas fa-exclamation-triangle"></i>
        <?php endif; ?>
        <?php echo htmlspecialchars($message); ?>
    </div>
    
    <?php if ($message_type === 'success'): ?>
        <a href="login.php" class="btn-back">
            <i class="fas fa-sign-in-alt"></i> Go to Login
        </a>
    <?php else: ?>
        <a href="profile.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Profile
        </a>
    <?php endif; ?>
</div>

<script>
<?php if ($message_type !== 'success'): ?>
// Toggle profile dropdown
document.getElementById("userDropdownBtn").addEventListener("click", function() {
    const dropdown = document.getElementById("userDropdownMenu");
    dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
});

// Close dropdown if clicked outside
window.addEventListener("click", function(event) {
    if (!event.target.closest(".user-dropdown")) {
        document.getElementById("userDropdownMenu").style.display = "none";
    }
});
<?php endif; ?>

// Auto redirect after 5 seconds for success
<?php if ($message_type === 'success'): ?>
setTimeout(function() {
    window.location.href = 'login.php';
}, 5000);
<?php endif; ?>
</script>
</body>
</html>
