<?php
session_start();
include 'dependencies/config.php';
include 'dependencies/auth.php';
include 'dependencies/logger.php';
include 'dependencies/validator.php';

require_login(); // user has to be logged in to access this page
require_role(['owner', 'manager']); // only owner and manager has access to this page

// Initialize logger and validator
$logger = new SecurityLogger($conn);
$validator = new SecurityValidator($logger);

$message = '';
$message_type = '';

if (!isset($_SESSION['email'])) {
    $message = "User not logged in.";
    $message_type = 'error';
    
    // Log unauthorized access attempt
            $logger->logSecurityEvent('UNAUTHORIZED_ACCESS', null, 'Unknown', 'Attempted to access delete_inventory.php without login', false);
} else {
    if (isset($_POST['product_id'])) {
        $product_id = $_POST['product_id'];

        // Validate product ID
        if (!$validator->validateUserId($product_id, $_SESSION['user_id'], $_SESSION['username'])) {
            $message = "Invalid product ID.";
            $message_type = 'error';
            
            // Log validation failure
            $logger->logSecurityEvent('VALIDATION_FAILURE', $_SESSION['user_id'], $_SESSION['username'], 
                "Invalid product ID for deletion: $product_id", false);
        } else {
            // Get product name before deletion for confirmation message
            $product_name = '';
            $get_name_query = "SELECT product_name, current_stock, supplier FROM inventory WHERE id = ?";
            $get_stmt = $conn->prepare($get_name_query);
            $get_stmt->bind_param("i", $product_id);
            $get_stmt->execute();
            $result = $get_stmt->get_result();
            
            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $product_name = $row['product_name'];
                $current_stock = $row['current_stock'];
                $supplier = $row['supplier'];
                
                // Check if product has active orders
                $check_orders = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE product_id = ? AND status IN ('Pending', 'Processing')");
                $check_orders->bind_param("i", $product_id);
                $check_orders->execute();
                $order_result = $check_orders->get_result();
                $order_count = $order_result->fetch_assoc()['order_count'];
                $check_orders->close();
                
                if ($order_count > 0) {
                    $message = "Cannot delete product '{$product_name}' - it has {$order_count} active order(s).";
                    $message_type = 'error';
                    
                    // Log attempt to delete product with active orders
                                    $logger->logSecurityEvent('DELETE_BLOCKED', $_SESSION['user_id'], $_SESSION['username'], 
                    "Attempted to delete product with active orders: Product: $product_name, Active Orders: $order_count", false);
                } else {
                    // Prepare SQL query to delete the product from the database
                    $query = "DELETE FROM inventory WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $product_id);

                    if ($stmt->execute()) {
                        $message = $product_name ? "Product '{$product_name}' deleted successfully from inventory!" : "Product deleted successfully from inventory!";
                        $message_type = 'success';
                        
                        // Log successful inventory deletion
                                        $logger->logSecurityEvent('INVENTORY_DELETED', $_SESSION['user_id'], $_SESSION['username'], 
                    "Inventory item deleted: Product: $product_name, Stock: $current_stock, Supplier: $supplier", true);
                    } else {
                        $message = "Error: Unable to delete the product.";
                        $message_type = 'error';
                        
                        // Log deletion failure
                        $logger->logSecurityEvent('INVENTORY_DELETION_FAILED', $_SESSION['user_id'], $_SESSION['username'], 
                            "Failed to delete inventory item: Product: $product_name, Error: " . $stmt->error, false);
                    }
                    $stmt->close();
                }
            } else {
                $message = "Product not found.";
                $message_type = 'error';
                
                // Log product not found
                $logger->logSecurityEvent('PRODUCT_NOT_FOUND', $_SESSION['user_id'], $_SESSION['username'], 
                    "Attempted to delete non-existent inventory item: ID $product_id", false);
            }
            $get_stmt->close();
        }
    } else {
        $message = "Product ID not provided.";
        $message_type = 'error';
        
        // Log missing product ID
        $logger->logSecurityEvent('VALIDATION_FAILURE', $_SESSION['user_id'], $_SESSION['username'], 
            "Product ID not provided for deletion", false);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Inventory - La Frontera</title>
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
        <a href="InventoryPage.php" class="nav-item">Inventory</a>
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
        <i class="fas fa-trash-alt"></i> Inventory Management
    </div>
    
    <div class="message <?php echo $message_type; ?>">
        <?php if ($message_type === 'success'): ?>
            <i class="fas fa-check-circle"></i>
        <?php else: ?>
            <i class="fas fa-exclamation-triangle"></i>
        <?php endif; ?>
        <?php echo htmlspecialchars($message); ?>
    </div>
    
    <a href="InventoryPage.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Back to Inventory
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
    window.location.href = 'InventoryPage.php';
}, 3000);
<?php endif; ?>
</script>
</body>
</html>
