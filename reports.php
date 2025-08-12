<?php
include 'dependencies/config.php';
include 'dependencies/auth.php';

require_login(); // user has to be logged in to access this page
require_role(['owner', 'manager']); // only owner and manager has access to this page

$email = $_SESSION['email']; 
$query = "SELECT id, usertype FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
if ($row = $result->fetch_assoc()) {    
    $user_id = $row['id'];
    $usertype = $row['usertype'];
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


// Fetch sales performance report
$sales_sql = "SELECT p.product_name, SUM(o.quantity) AS total_quantity, SUM(o.total_amount) AS total_revenue
              FROM orders o
              JOIN products p ON o.product_id = p.product_id
              WHERE o.status = 'Complete'
              GROUP BY p.product_name
              ORDER BY total_quantity DESC";
$sales_result = $conn->query($sales_sql);

// Fetch stock level report
$stock_sql = "SELECT product_name, quantity FROM products ORDER BY quantity ASC";
$stock_result = $conn->query($stock_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Frontera - Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/reports.css">
</head>
<body>
<div class="header">
    <div class="logo">La Frontera</div>
    <div class="search-nav">
        <a href="MenuPage.php" class="nav-item">Dashboard</a>
        <a href="reports.php" class="nav-item active">Reports</a>
    </div>

    <div class="username-display">
        <?php echo htmlspecialchars($_SESSION['username']); ?>
    </div>

    <div class="user-dropdown">
        <div class="user-icon" id="userDropdownBtn">
            <i class="fas fa-user"></i>
        </div>
        <div class="dropdown-menu" id="userDropdownMenu">
            <a href="profile.php">My Profile</a>
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
        <a href="MenuPage.php" class="sidebar-item"><i class="fas fa-home"></i> Home</a>
        <a href="InventoryPage.php" class="sidebar-item"><i class="fas fa-boxes"></i> Inventory</a>
        <a href="ProductPage.php" class="sidebar-item"><i class="fas fa-box"></i> Products</a>
        <a href="OrderPage.php" class="sidebar-item"><i class="fas fa-shopping-cart"></i> Orders</a>

        <?php if ($_SESSION['user_role'] === 'owner'): ?>
            <a href="accounts_page.php" class="sidebar-item"><i class="fas fa-users-cog"></i> Accounts</a>
        <?php endif; ?>
    </div>

    <div class="content">
    <div class="dashboard">
            <div class="card"><h2>Total Revenue: ₱<?php echo number_format($stats['total_revenue'], 2); ?></h2></div>
            <div class="card"><h2>Total Orders: <?php echo $stats['total_orders']; ?></h2></div>
            <div class="card"><h2>Pending Orders: <?php echo $stats['pending_orders']; ?></h2></div>
        </div><br><br>        
        <div class="content-header">
            <h2>Sales Performance Report</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Units Sold</th>
                    <th>Total Revenue (₱)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($sales_result->num_rows > 0): ?>
                    <?php while ($row = $sales_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['product_name']) ?></td>
                            <td><?= htmlspecialchars($row['total_quantity']) ?></td>
                            <td>₱<?= number_format($row['total_revenue'], 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3">No sales data available.</td></tr>
                <?php endif; ?>
            </tbody>
        </table><br>

        <div class="content-header">
            <h2>Stock Level Report</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Current Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($stock_result->num_rows > 0): ?>
                    <?php while ($row = $stock_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['product_name']) ?></td>
                            <td><?= htmlspecialchars($row['quantity']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="2">No stock data available.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
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
