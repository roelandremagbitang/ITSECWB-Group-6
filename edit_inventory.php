<?php
session_start();
include 'dependencies/config.php';
include 'dependencies/auth.php';
include 'dependencies/logger.php';
include 'dependencies/validator.php';

require_login(); // user has to be logged in to access this page
require_role(['owner', 'admin', 'manager']); // only owner, admin, and manager has access to this page

// Initialize logger and validator
$logger = new SecurityLogger($conn);
$validator = new SecurityValidator($logger);

$message = '';
$message_type = '';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    $message = "User not logged in.";
    $message_type = 'error';
    
    // Log unauthorized access attempt
    $logger->logSecurityEvent('UNAUTHORIZED_ACCESS', null, 'Unknown', 'Attempted to access edit_inventory.php without login', SecurityLogger::getClientIP(), false);
} else {
    $email = $_SESSION['email'];
    $query = "SELECT id, usertype FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $user_id = $row['id'];
        $usertype = $row['usertype'];
    } else {
        $message = "User not found in database.";
        $message_type = 'error';
        
        // Log user not found
        $logger->logSecurityEvent('USER_NOT_FOUND', null, 'Unknown', "User not found for email: $email", SecurityLogger::getClientIP(), false);
    }

    // Check if the required POST data is available
    if (isset($_POST['product_id'], $_POST['product_name'], $_POST['inbound_qty'], $_POST['outbound_qty'], $_POST['supplier'])) {
        $product_id = $_POST['product_id'];
        $product_name = trim($_POST['product_name']);
        $inbound_qty = $_POST['inbound_qty'];
        $outbound_qty = $_POST['outbound_qty'];
        $supplier = trim($_POST['supplier']);

        // Validate inputs
        $validation_errors = [];
        
        if (!$validator->validateUserId($product_id, $user_id, $_SESSION['username'])) {
            $validation_errors = array_merge($validation_errors, $validator->getErrors());
        }
        
        if (!$validator->validateProductName($product_name, $user_id, $_SESSION['username'])) {
            $validation_errors = array_merge($validation_errors, $validator->getErrors());
        }
        
        if (!$validator->validateQuantity($inbound_qty, $user_id, $_SESSION['username'])) {
            $validation_errors = array_merge($validation_errors, $validator->getErrors());
        }
        
        if (!$validator->validateQuantity($outbound_qty, $user_id, $_SESSION['username'])) {
            $validation_errors = array_merge($validation_errors, $validator->getErrors());
        }
        
        if (!$validator->validateSupplier($supplier, $user_id, $_SESSION['username'])) {
            $validation_errors = array_merge($validation_errors, $validator->getErrors());
        }

        if (empty($validation_errors)) {
            // Fetch the current stock from the database
            $sql = "SELECT current_stock, product_name as old_name FROM inventory WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $stmt->bind_result($current_stock, $old_name);
            
            // Check if the current stock was fetched correctly
            if ($stmt->fetch()) {
                // Calculate the new current stock
                $new_current_stock = $current_stock + $inbound_qty - $outbound_qty;
                
                // Validate that new stock is not negative
                if ($new_current_stock < 0) {
                    $message = "Cannot reduce stock below zero. Current stock: $current_stock, Outbound: $outbound_qty";
                    $message_type = 'error';
                    
                    // Log invalid stock calculation
                    $logger->logSecurityEvent('INVALID_STOCK_CALCULATION', $user_id, $_SESSION['username'], 
                        "Attempted to reduce stock below zero: Current: $current_stock, Inbound: $inbound_qty, Outbound: $outbound_qty", SecurityLogger::getClientIP(), false);
                } else {
                    // Get the current date and time for last restocked
                    $last_restocked = date("Y-m-d H:i:s");

                    // Prepare the SQL query to update the inventory
                    $sql = "UPDATE inventory SET product_name = ?, inbound_qty = ?, outbound_qty = ?, current_stock = ?, supplier = ?, last_restocked = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("siiissi", $product_name, $inbound_qty, $outbound_qty, $new_current_stock, $supplier, $last_restocked, $product_id);

                    if ($stmt->execute()) {
                        $message = "Product '{$product_name}' updated successfully!";
                        $message_type = 'success';
                        
                        // Log successful inventory update
                        $logger->logSecurityEvent('INVENTORY_UPDATED', $user_id, $_SESSION['username'], 
                            "Inventory updated: Product: $product_name (was: $old_name), Stock: $current_stock -> $new_current_stock, Inbound: $inbound_qty, Outbound: $outbound_qty, Supplier: $supplier", 
                            SecurityLogger::getClientIP(), true);
                    } else {
                        $message = "Error updating product: " . $conn->error;
                        $message_type = 'error';
                        
                        // Log update failure
                        $logger->logSecurityEvent('INVENTORY_UPDATE_FAILED', $user_id, $_SESSION['username'], 
                            "Failed to update inventory: Product: $product_name, Error: " . $conn->error, SecurityLogger::getClientIP(), false);
                    }
                }
            } else {
                // If the product was not found, display an error
                $message = "Product not found in database.";
                $message_type = 'error';
                
                // Log product not found
                $logger->logSecurityEvent('PRODUCT_NOT_FOUND', $user_id, $_SESSION['username'], 
                    "Attempted to edit non-existent inventory item: ID $product_id", SecurityLogger::getClientIP(), false);
            }
            $stmt->close();
        } else {
            $message = "Validation errors: " . implode(", ", $validation_errors);
            $message_type = 'error';
            
            // Log validation failures
            $logger->logSecurityEvent('VALIDATION_FAILURE', $user_id, $_SESSION['username'], 
                "Inventory edit validation failed: " . implode(", ", $validation_errors), SecurityLogger::getClientIP(), false);
        }
    } else {
        $message = "Missing required data. Please use the inventory page to edit items.";
        $message_type = 'error';
        
        // Log missing data
        $logger->logSecurityEvent('MISSING_DATA', $user_id, $_SESSION['username'], 
            "Missing required data for inventory edit", SecurityLogger::getClientIP(), false);
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Inventory - La Frontera</title>
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
        <i class="fas fa-edit"></i> Inventory Management
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
