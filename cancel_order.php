<?php
require_once 'dependencies/config.php';
require_once 'dependencies/auth.php';
require_once 'dependencies/logger.php';
require_once 'dependencies/validator.php';

require_login(); // user has to be logged in to access this page

// Initialize logger and validator
$logger = new SecurityLogger($conn);
$validator = new SecurityValidator($logger);

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);

    // Validate order ID
    if (!$validator->validateUserId($order_id, $_SESSION['user_id'], $_SESSION['username'])) {
        $message = "Invalid order ID.";
        $message_type = 'error';
        
        // Log validation failure
        $logger->logSecurityEvent('VALIDATION_FAILURE', $_SESSION['user_id'], $_SESSION['username'], 
            "Invalid order ID for cancellation: $order_id", false);
    } else {
        // Step 1: Fetch the order to get product and quantity
        $order_sql = "SELECT o.product_id, o.quantity, o.status, o.username, p.product_name 
                      FROM orders o 
                      JOIN products p ON o.product_id = p.product_id 
                      WHERE o.id = ?";
        $stmt = $conn->prepare($order_sql);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();

        if (!$order) {
            $message = "Order not found.";
            $message_type = 'error';
            
            // Log order not found
            $logger->logSecurityEvent('ORDER_NOT_FOUND', $_SESSION['user_id'], $_SESSION['username'], 
                "Attempted to cancel non-existent order: $order_id", false);
        } else {
            // Only cancel if order is not already completed or cancelled
            if ($order['status'] === 'Complete' || $order['status'] === 'Cancelled') {
                $message = "Cannot cancel order. Order is already " . $order['status'] . ".";
                $message_type = 'error';
                
                // Log attempt to cancel completed/cancelled order
                $logger->logSecurityEvent('INVALID_ORDER_STATUS', $_SESSION['user_id'], $_SESSION['username'], 
                    "Attempted to cancel order with status: {$order['status']}, Order ID: $order_id", false);
            } else {
                $product_id = $order['product_id'];
                $quantity = $order['quantity'];

                // Step 2: Update order status to 'Cancelled'
                $update_sql = "UPDATE orders SET status = 'Cancelled' WHERE id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("i", $order_id);
                
                if ($stmt->execute()) {
                    // Step 3: Restock the product
                    $restock_sql = "UPDATE products SET quantity = quantity + ? WHERE product_id = ?";
                    $stmt = $conn->prepare($restock_sql);
                    $stmt->bind_param("ii", $quantity, $product_id);
                    
                    if ($stmt->execute()) {
                        $message = "Order #{$order_id} cancelled successfully. Stock has been restored.";
                        $message_type = 'success';
                        
                        // Log successful order cancellation
                        $logger->logSecurityEvent('ORDER_CANCELLED', $_SESSION['user_id'], $_SESSION['username'], 
                                                    "Order cancelled successfully: Order ID: $order_id, Customer: {$order['username']}, Product: {$order['product_name']}, Quantity: $quantity", 
                        true);
                    } else {
                        $message = "Order cancelled but failed to restore stock.";
                        $message_type = 'error';
                        
                        // Log stock restoration failure
                        $logger->logSecurityEvent('STOCK_RESTORATION_FAILED', $_SESSION['user_id'], $_SESSION['username'], 
                                                    "Order cancelled but stock restoration failed: Order ID: $order_id, Product ID: $product_id, Quantity: $quantity", 
                        false);
                    }
                } else {
                    $message = "Failed to cancel the order.";
                    $message_type = 'error';
                    
                    // Log order cancellation failure
                    $logger->logSecurityEvent('ORDER_CANCELLATION_FAILED', $_SESSION['user_id'], $_SESSION['username'], 
                        "Failed to cancel order: Order ID: $order_id, Error: " . $stmt->error, false);
                }
            }
        }
        $stmt->close();
    }
} else {
    $message = "Invalid request. Please use the orders page to cancel orders.";
    $message_type = 'error';
    
    // Log invalid request
    $logger->logSecurityEvent('INVALID_REQUEST', $_SESSION['user_id'], $_SESSION['username'], 
        "Invalid request method for order cancellation: " . $_SERVER['REQUEST_METHOD'], false);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Order - La Frontera</title>
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
        <i class="fas fa-times-circle"></i> Order Cancellation
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
