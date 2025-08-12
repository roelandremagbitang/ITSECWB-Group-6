<?php
include 'dependencies/config.php';
include 'dependencies/auth.php';

require_login(); // user has to be logged in to access this page
require_role(['owner', 'admin', 'manager']); // only owner, admin, and manager has access to this page

$message = '';
$message_type = '';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    $message = "User not logged in.";
    $message_type = 'error';
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
    }

    // Check if the required POST data is available
    if (isset($_POST['product_id'], $_POST['product_name'], $_POST['inbound_qty'], $_POST['outbound_qty'], $_POST['supplier'])) {
        $product_id = $_POST['product_id'];
        $product_name = $_POST['product_name'];
        $inbound_qty = $_POST['inbound_qty'];
        $outbound_qty = $_POST['outbound_qty'];
        $supplier = $_POST['supplier'];

        // Fetch the current stock from the database
        $sql = "SELECT current_stock FROM inventory WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->bind_result($current_stock);
        
        // Check if the current stock was fetched correctly
        if ($stmt->fetch()) {
            // Calculate the new current stock
            $current_stock = $current_stock + $inbound_qty - $outbound_qty; // This ensures current_stock is updated properly
        } else {
            // If the product was not found, display an error
            $message = "Product not found in database.";
            $message_type = 'error';
        }
        $stmt->close();

        if (!$message) {
            // Get the current date and time for last restocked
            $last_restocked = date("Y-m-d H:i:s");

            // Prepare the SQL query to update the inventory
            $sql = "UPDATE inventory SET product_name = ?, inbound_qty = ?, outbound_qty = ?, current_stock = ?, supplier = ?, last_restocked = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siiissi", $product_name, $inbound_qty, $outbound_qty, $current_stock, $supplier, $last_restocked, $product_id);

            if ($stmt->execute()) {
                $message = "Product '{$product_name}' updated successfully!";
                $message_type = 'success';
            } else {
                $message = "Error updating product: " . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    } else {
        $message = "Missing required data. Please use the inventory page to edit items.";
        $message_type = 'error';
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
