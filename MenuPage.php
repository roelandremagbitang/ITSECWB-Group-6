<?php
include 'dependencies/config.php';
include 'dependencies/auth.php';

require_login(); // user has to be logged in to access this page
require_role(['owner', 'customer', 'manager']); // all has access to this page

$email = $_SESSION['email']; 
$query = "SELECT id, usertype, last_failed_login FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
if ($row = $result->fetch_assoc()) {    
    $user_id = $row['id'];
    $usertype = $row['usertype'];

    // Fetch last unsuccessful login attempt
    $last_failed_login = $row['last_failed_login'];

    // If there is a recorded failed login, prepare a notification
    if ($last_failed_login !== null) {
        $notification_message = "⚠️ Notice: Your last unsuccessful login attempt was on " . 
            date("F j, Y, g:i a", strtotime($last_failed_login)) . ".";

        // Clear last_failed_login so it only shows once
        $clear_failed_query = "UPDATE users SET last_failed_login = NULL WHERE id = ?";
        $clear_stmt = $conn->prepare($clear_failed_query);
        $clear_stmt->bind_param("i", $user_id);
        $clear_stmt->execute();
        $clear_stmt->close();
    }
} else {
    die("User not found in database.");
}

// Fetch Total Revenue, Orders, Pending Orders
$stats_query = "SELECT 
    SUM(CASE WHEN status = 'Complete' THEN total_amount ELSE 0 END) AS total_revenue,
    COUNT(id) AS total_orders,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_orders
FROM orders";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Fetch Top-Selling Products
$top_products_query = "SELECT p.product_name, SUM(o.quantity) AS total_sold
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    GROUP BY p.product_name
    ORDER BY total_sold DESC
    LIMIT 5";
$top_products_result = $conn->query($top_products_query);

// Fetch Low Stock Products
$low_stock_query = "SELECT product_name, quantity FROM products ORDER BY quantity ASC LIMIT 5";
$low_stock_result = $conn->query($low_stock_query);

// Fetch Latest Orders
$latest_orders_query = "SELECT o.id, p.product_name, o.quantity, o.total_amount, o.status FROM orders o 
    JOIN products p ON o.product_id = p.product_id ORDER BY o.id DESC LIMIT 5";
$latest_orders_result = $conn->query($latest_orders_query);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Frontera - Inventory System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/MenuPage.css">
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<body>
<div class="header">
    <div class="logo">La Frontera</div>
    <div class="search-nav">
        <a href="MenuPage.php" class="nav-item">Dashboard</a>
        <a href="reports.php" class="nav-item">Reports</a>
    </div>

    <div class="username-display">
        <?php echo htmlspecialchars($_SESSION['username']); ?>
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
    
<div class="container">
    <div class="sidebar">
        <div>
        <p>Email: <strong><?php echo htmlspecialchars($email); ?></strong></p><br>
        <p>User Type: <strong><?php echo htmlspecialchars($usertype); ?></strong></p><br>
        </div>
        <a href="MenuPage.php" class="sidebar-item active"><i class="fas fa-home"></i> Home</a>
        <a href="InventoryPage.php" class="sidebar-item"><i class="fas fa-boxes"></i> Inventory</a>
        <a href="ProductPage.php" class="sidebar-item"><i class="fas fa-box"></i> Products</a>
        <a href="OrderPage.php" class="sidebar-item"><i class="fas fa-shopping-cart"></i> Orders</a>

        <?php if ($_SESSION['user_role'] === 'owner' || 'manager'): ?>
            <a href="accounts_page.php" class="sidebar-item"><i class="fas fa-users-cog"></i>Accounts</a>
        <?php endif; ?>

        <?php if ($_SESSION['user_role'] === 'owner'): ?>
            <a href="logs_page.php" class="sidebar-item"><i class="fas fa-clipboard-list"></i>Logs</a>
        <?php endif; ?>

    </div>
    
    <div class="content">
        <?php if (isset($notification_message)): ?>
    <div style="
        background-color: #fff3cd;
        color: #856404;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #ffeeba;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 500;
    ">
        <?php echo $notification_message; ?>
    </div>
<?php endif; ?>

        <div class="dashboard">
            <div class="card"><h2>Total Revenue: ₱<?php echo number_format($stats['total_revenue'], 2); ?></h2></div>
            <div class="card"><h2>Total Orders: <?php echo $stats['total_orders']; ?></h2></div>
            <div class="card"><h2>Pending Orders: <?php echo $stats['pending_orders']; ?></h2></div>
        </div>
        
        <div class="data-section">
            <h2>Top-Selling Products</h2>
            <table>
                <thead><tr><th>Product</th><th>Sold Quantity</th></tr></thead>
                <tbody>
                    <?php while ($row = $top_products_result->fetch_assoc()): ?>
                        <tr><td><?php echo htmlspecialchars($row['product_name']); ?></td><td><?php echo $row['total_sold']; ?></td></tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div class="data-section">
            <h2>Low Stock Products</h2>
            <table>
                <thead><tr><th>Product</th><th>Stock</th></tr></thead>
                <tbody>
                    <?php while ($row = $low_stock_result->fetch_assoc()): ?>
                        <tr><td><?php echo htmlspecialchars($row['product_name']); ?></td><td><?php echo $row['quantity']; ?></td></tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div class="data-section">
            <h2>Latest Orders</h2>
            <table>
                <thead><tr><th>Order ID</th><th>Product</th><th>Quantity</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                    <?php while ($row = $latest_orders_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    document.getElementById("userDropdownBtn").addEventListener("click", function() {
        const dropdown = document.getElementById("userDropdownMenu");
        dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    });

    window.addEventListener("click", function(event) {
        if (!event.target.closest(".user-dropdown")) {
            document.getElementById("userDropdownMenu").style.display = "none";
        }
    });
</script>
</body>
</html>
