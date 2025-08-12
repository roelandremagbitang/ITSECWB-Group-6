<?php
session_start();
include 'dependencies/config.php';
include 'dependencies/auth.php';

require_login(); // user has to be logged in to access this page

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);

    // Get order info before updating
    $get_order_sql = "SELECT id FROM orders WHERE id = ? AND status != 'Complete'";
    $check_stmt = $conn->prepare($get_order_sql);
    $check_stmt->bind_param("i", $order_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $sql = "UPDATE orders SET status = 'Complete' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $order_id);

        if ($stmt->execute()) {
            $message = "Order #{$order_id} completed successfully!";
            $message_type = 'success';
        } else {
            $message = "Failed to complete order.";
            $message_type = 'error';
        }
        $stmt->close();
    } else {
        $message = "Order not found or already completed.";
        $message_type = 'error';
    }
    $check_stmt->close();
} else {
    $message = "Invalid request. Please use the orders page to complete orders.";
    $message_type = 'error';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Order - La Frontera</title>
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
        <a href="OrderPage.php" class="nav-item">Orders</a>
        <a href="reports.php" class="nav-item">Reports</a>
    </div>

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
</div>

<div class="message-container">
    <div class="logo" style="font-size: 24px; margin-bottom: 20px;">
        <i class="fas fa-check-circle"></i> Order Completion
    </div>
    
    <div class="message <?php echo $message_type; ?>">
        <?php if ($message_type === 'success'): ?>
            <i class="fas fa-check-circle"></i>
        <?php else: ?>
            <i class="fas fa-exclamation-triangle"></i>
        <?php endif; ?>
        <?php echo htmlspecialchars($message); ?>
    </div>
    
    <a href="OrderPage.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Back to Orders
    </a>
</div>

<script>
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

// Auto redirect after 3 seconds for success
<?php if ($message_type === 'success'): ?>
setTimeout(function() {
    window.location.href = 'OrderPage.php';
}, 3000);
<?php endif; ?>
</script>
</body>
</html>