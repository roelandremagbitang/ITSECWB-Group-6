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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $customer_name = trim($_POST['customer_name']);
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $total_amount = $_POST['total_amount'];

    // Validate inputs
    $validation_errors = [];
    
    if (!$validator->validateCustomerName($customer_name, $_SESSION['user_id'], $_SESSION['username'])) {
        $validation_errors = array_merge($validation_errors, $validator->getErrors());
    }
    
    if (!$validator->validateUserId($product_id, $_SESSION['user_id'], $_SESSION['username'])) {
        $validation_errors = array_merge($validation_errors, $validator->getErrors());
    }
    
    if (!$validator->validateQuantity($quantity, $_SESSION['user_id'], $_SESSION['username'])) {
        $validation_errors = array_merge($validation_errors, $validator->getErrors());
    }
    
    if (!$validator->validatePrice($total_amount, $_SESSION['user_id'], $_SESSION['username'])) {
        $validation_errors = array_merge($validation_errors, $validator->getErrors());
    }

    if (empty($validation_errors)) {
        // Verify product exists and has sufficient stock
        $check_product = $conn->prepare("SELECT product_name, current_stock FROM inventory WHERE id = ?");
        $check_product->bind_param("i", $product_id);
        $check_product->execute();
        $product_result = $check_product->get_result();
        
        if ($product_result->num_rows === 1) {
            $product = $product_result->fetch_assoc();
            
            if ($product['current_stock'] >= $quantity) {
                        $stmt = $conn->prepare("INSERT INTO orders (username, product_id, quantity, total_amount, status, payment_method) VALUES (?, ?, ?, ?, 'Pending', 'Cash')");
        $stmt->bind_param("siid", $customer_name, $product_id, $quantity, $total_amount);

                if ($stmt->execute()) {
                    // Update inventory stock
                    $new_stock = $product['current_stock'] - $quantity;
                    $update_stock = $conn->prepare("UPDATE inventory SET current_stock = ?, outbound_qty = outbound_qty + ? WHERE id = ?");
                    $update_stock->bind_param("iii", $new_stock, $quantity, $product_id);
                    $update_stock->execute();
                    
                    $message = "Order for {$customer_name} added successfully!";
                    $message_type = 'success';
                    
                    // Log successful order creation
                    $logger->logSecurityEvent('ORDER_CREATED', $_SESSION['user_id'], $_SESSION['username'], 
                        "Order created: Customer: $customer_name, Product: {$product['product_name']}, Quantity: $quantity, Amount: $total_amount", 
                        true);
                } else {
                    $message = "Error adding order: " . $stmt->error;
                    $message_type = 'error';
                    
                    // Log database error
                    $logger->logSecurityEvent('DATABASE_ERROR', $_SESSION['user_id'], $_SESSION['username'], 
                        "Failed to create order: " . $stmt->error, false);
                }
                $stmt->close();
            } else {
                $message = "Insufficient stock. Available: {$product['current_stock']}, Requested: $quantity";
                $message_type = 'error';
                
                // Log insufficient stock attempt
                $logger->logSecurityEvent('INSUFFICIENT_STOCK', $_SESSION['user_id'], $_SESSION['username'], 
                    "Order attempt with insufficient stock: Product: {$product['product_name']}, Available: {$product['current_stock']}, Requested: $quantity", 
                    false);
            }
        } else {
            $message = "Product not found.";
            $message_type = 'error';
            
            // Log invalid product ID
            $logger->logSecurityEvent('VALIDATION_FAILURE', $_SESSION['user_id'], $_SESSION['username'], 
                "Order attempt with invalid product ID: $product_id", false);
        }
        $check_product->close();
    } else {
        $message = "Validation errors: " . implode(", ", $validation_errors);
        $message_type = 'error';
        
        // Log validation failures
        $logger->logSecurityEvent('VALIDATION_FAILURE', $_SESSION['user_id'], $_SESSION['username'], 
            "Order validation failed: " . implode(", ", $validation_errors), false);
    }
} else {
    $message = "Invalid request method. Please use the order form to add orders.";
    $message_type = 'error';
    
    // Log invalid request method
    $logger->logSecurityEvent('INVALID_REQUEST', $_SESSION['user_id'], $_SESSION['username'], 
        "Invalid request method: " . $_SERVER['REQUEST_METHOD'], false);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Order - La Frontera</title>
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
        <i class="fas fa-plus-circle"></i> Order Management
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
