<?php
include 'dependencies/config.php';
include 'dependencies/auth.php';

require_login(); // user has to be logged in to access this page

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = $_POST['customer_name'];
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $total_amount = $_POST['total_amount'];
    $payment_method = $_POST['payment_method']; 
    $order_status = 'Pending'; // Default status

    // Check if enough stock is available
    $checkStock = $conn->prepare("SELECT quantity FROM products WHERE product_id = ?");
    $checkStock->bind_param("i", $product_id);
    $checkStock->execute();
    $stockResult = $checkStock->get_result();
    $stockRow = $stockResult->fetch_assoc();

    if ($stockRow && $stockRow['quantity'] >= $quantity) {
        // Deduct stock
        $updateStock = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ?");
        $updateStock->bind_param("ii", $quantity, $product_id);
        $updateStock->execute();

        // Insert order
        $sql = "INSERT INTO orders (username, product_id, quantity, total_amount, payment_method, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siidss", $customer_name, $product_id, $quantity, $total_amount, $payment_method, $order_status);
        
        if ($stmt->execute()) {
            $message = "Order placed successfully for {$customer_name}!";
            $message_type = 'success';
        } else {
            $message = "Error placing order: " . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    } else {
        $message = "Error: Not enough stock available.";
        $message_type = 'error';
    }
    $checkStock->close();
} else {
    $message = "Invalid request method. Please use the order form to place orders.";
    $message_type = 'error';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Order - La Frontera</title>
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
            margin: 10px;
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
        <i class="fas fa-shopping-cart"></i> Order Processing
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
        <i class="fas fa-list"></i> View Orders
    </a>
    <a href="order_products.php" class="btn-back">
        <i class="fas fa-plus"></i> New Order
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
