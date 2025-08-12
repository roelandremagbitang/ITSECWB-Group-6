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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and validate form data
    $product_name = trim($_POST['product_name']);
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $supplier = trim($_POST['supplier']);

    // Validate inputs
    $validation_errors = [];
    
    if (!$validator->validateProductName($product_name, $_SESSION['user_id'], $_SESSION['username'])) {
        $validation_errors = array_merge($validation_errors, $validator->getErrors());
    }
    
    if (!$validator->validatePrice($price, $_SESSION['user_id'], $_SESSION['username'])) {
        $validation_errors = array_merge($validation_errors, $validator->getErrors());
    }
    
    if (!$validator->validateQuantity($quantity, $_SESSION['user_id'], $_SESSION['username'])) {
        $validation_errors = array_merge($validation_errors, $validator->getErrors());
    }
    
    if (!$validator->validateSupplier($supplier, $_SESSION['user_id'], $_SESSION['username'])) {
        $validation_errors = array_merge($validation_errors, $validator->getErrors());
    }

    if (empty($validation_errors)) {
        // Check if product already exists
        $check_sql = "SELECT id FROM products WHERE product_name = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $product_name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            // Insert into database
            $sql = "INSERT INTO products (product_name, price, quantity, supplier) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdss", $product_name, $price, $quantity, $supplier);

            if ($stmt->execute()) {
                $message = "Product '{$product_name}' added successfully!";
                $message_type = 'success';
                
                // Log successful product creation
                $logger->logSecurityEvent('PRODUCT_CREATED', $_SESSION['user_id'], $_SESSION['username'], 
                    "Product created: Name: $product_name, Price: $price, Quantity: $quantity, Supplier: $supplier", 
                    true);
            } else {
                $message = "Error: " . $stmt->error;
                $message_type = 'error';
                
                // Log database error
                $logger->logSecurityEvent('DATABASE_ERROR', $_SESSION['user_id'], $_SESSION['username'], 
                    "Failed to create product: " . $stmt->error, false);
            }
            $stmt->close();
        } else {
            $message = "Product '{$product_name}' already exists.";
            $message_type = 'error';
            
            // Log duplicate product attempt
                            $logger->logSecurityEvent('DUPLICATE_PRODUCT', $_SESSION['user_id'], $_SESSION['username'], 
                    "Attempted to create duplicate product: $product_name", false);
        }
        $check_stmt->close();
    } else {
        $message = "Validation errors: " . implode(", ", $validation_errors);
        $message_type = 'error';
        
        // Log validation failures
        $logger->logSecurityEvent('VALIDATION_FAILURE', $_SESSION['user_id'], $_SESSION['username'], 
            "Product validation failed: " . implode(", ", $validation_errors), false);
    }
} else {
    $message = "Invalid request method. Please use the products page to add items.";
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
    <title>Add Product - La Frontera</title>
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
        <a href="ProductPage.php" class="nav-item">Products</a>
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
        <i class="fas fa-box"></i> Product Management
    </div>
    
    <div class="message <?php echo $message_type; ?>">
        <?php if ($message_type === 'success'): ?>
            <i class="fas fa-check-circle"></i>
        <?php else: ?>
            <i class="fas fa-exclamation-triangle"></i>
        <?php endif; ?>
        <?php echo htmlspecialchars($message); ?>
    </div>
    
    <a href="ProductPage.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Back to Products
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
    window.location.href = 'ProductPage.php';
}, 3000);
<?php endif; ?>
</script>
</body>
</html>