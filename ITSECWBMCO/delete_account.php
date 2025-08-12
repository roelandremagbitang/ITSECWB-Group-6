<?php
session_start();
include 'dependencies/config.php';
include 'dependencies/auth.php';

require_login(); // user has to be logged in to access this page
require_role(['owner', 'admin', 'customer']); // owner, admin, and customer has access to this page

$message = '';
$message_type = '';

if (!isset($_SESSION['email'])) {
    $message = "Not authorized.";
    $message_type = 'error';
} else {
    $email = $_SESSION['email'];

    $stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);

    if ($stmt->execute()) {
        $message = "Account deleted successfully. You will be logged out.";
        $message_type = 'success';
        session_unset();
        session_destroy();
    } else {
        $message = "Failed to delete account.";
        $message_type = 'error';
    }

    $stmt->close();
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
