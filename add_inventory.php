<?php
require_once 'dependencies/config.php';
require_once 'dependencies/auth.php';
require_once 'dependencies/logger.php';
require_once 'dependencies/validator.php';

require_login(); // user has to be logged in to access this page
require_role(['owner', 'manager']); // only owner and manager has access to this page

// Initialize logger and validator
$logger = new SecurityLogger($conn);
$validator = new SecurityValidator($logger);

$message = '';
$message_type = '';

// Check if the user is logged in and has the correct permissions
if (!isset($_SESSION['email'])) {
    $message = "User not logged in.";
    $message_type = 'error';
            $logger->logSecurityEvent('UNAUTHORIZED_ACCESS', null, 'Unknown', 'Attempted to access add_inventory.php without login', false);
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
        $logger->logSecurityEvent('USER_NOT_FOUND', null, 'Unknown', "User not found for email: $email", false);
    }

    // Ensure the data is coming via POST
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $product_name = $_POST['product_name'];
        $current_stock = $_POST['current_stock'];
        $supplier = $_POST['supplier'];

        // Validate inputs
        $validation_errors = [];
        
        if (!$validator->validateProductName($product_name, $user_id, $_SESSION['username'])) {
            $validation_errors = array_merge($validation_errors, $validator->getErrors());
        }
        
        if (!$validator->validateQuantity($current_stock, $user_id, $_SESSION['username'])) {
            $validation_errors = array_merge($validation_errors, $validator->getErrors());
        }
        
        if (!$validator->validateSupplier($supplier, $user_id, $_SESSION['username'])) {
            $validation_errors = array_merge($validation_errors, $validator->getErrors());
        }

        if (empty($validation_errors)) {
            // Default values
            $inbound_qty = 0;  // Default value
            $outbound_qty = 0;  // Default value

            // Set the current date and time as last restocked
            $last_restocked = date("Y-m-d H:i:s"); // Current date and time in MySQL format

            // Insert the new item into the inventory table
            $query = "INSERT INTO inventory (product_name, inbound_qty, outbound_qty, current_stock, supplier, last_restocked) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("siiiss", $product_name, $inbound_qty, $outbound_qty, $current_stock, $supplier, $last_restocked);
            
            if ($stmt->execute()) {
                $message = "Item '{$product_name}' added successfully to inventory!";
                $message_type = 'success';
                
                // Log successful inventory addition
                $logger->logSecurityEvent('INVENTORY_ADDED', $user_id, $_SESSION['username'], "Added inventory item: $product_name (Stock: $current_stock, Supplier: $supplier)", true);
            } else {
                $message = "Error adding item: " . $conn->error;
                $message_type = 'error';
                
                // Log database error
                $logger->logSecurityEvent('DATABASE_ERROR', $user_id, $_SESSION['username'], "Failed to add inventory item: $product_name - " . $conn->error, false);
            }
        } else {
            $message = "Validation errors: " . implode(", ", $validation_errors);
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Inventory - La Frontera</title>
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
        <i class="fas fa-boxes"></i> Inventory Management
    </div>
    
    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php if ($message_type === 'success'): ?>
                <i class="fas fa-check-circle"></i>
            <?php else: ?>
                <i class="fas fa-exclamation-triangle"></i>
            <?php endif; ?>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php else: ?>
        <div class="message error">
            <i class="fas fa-exclamation-triangle"></i>
            Invalid request method. Please use the inventory page to add items.
        </div>
    <?php endif; ?>
    
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
