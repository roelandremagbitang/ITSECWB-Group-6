<?php
include 'dependencies/config.php';
include 'dependencies/auth.php';

require_login(); // user has to be logged in to access this page
require_role(['owner', 'admin', 'customer']); // all account types has access to this page

$sql = "SELECT o.id, p.product_name, o.quantity, o.total_amount, o.status, o.username, o.payment_method
        FROM orders o
        JOIN products p ON o.product_id = p.product_id
        ORDER BY            
            CASE WHEN o.status = 'Pending' THEN 0 ELSE 1 END, 
            o.id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Frontera - Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/OrderPage.css">
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
        <a href="MenuPage.php" class="sidebar-item"><i class="fas fa-home"></i> Home</a>
        <a href="InventoryPage.php" class="sidebar-item"><i class="fas fa-boxes"></i> Inventory</a>
        <a href="ProductPage.php" class="sidebar-item"><i class="fas fa-box"></i> Products</a>
        <a href="OrderPage.php" class="sidebar-item active"><i class="fas fa-shopping-cart"></i> Orders</a>

        <?php if ($_SESSION['user_role'] === 'owner'): ?>
            <a href="accounts_page.php" class="sidebar-item"><i class="fas fa-users-cog"></i> Accounts</a>
        <?php endif; ?>
    </div>

    <div class="content">
        <div class="content-header">
            <h2>Orders</h2>
            <a href="order_products.php" class="btn btn-success" style="text-decoration: none;">
                <i class="fas fa-plus" style="margin-right: 5px;"></i> New Order
            </a>
        </div>

        <?php if (isset($_SESSION['role'])): ?>
            <form method="POST" action="order_process.php">
                <label for="product_id">Product:</label>
                <select id="product_id" name="product_id" onchange="updateTotal()" required>
                    <option value="">Select product</option>
                    <option value="1">Battery</option>
                    <option value="2">Charger</option>
                </select>

                <label for="quantity">Quantity:</label>
                <input type="number" id="quantity" name="quantity" min="1" value="1" onchange="updateTotal()" required>

                <label for="total_amount">Total Amount:</label>
                <input type="number" step="0.01" id="total_amount" name="total_amount" required readonly>

                <button type="submit">Add Order</button>
            </form>
        <?php endif; ?>

        <table id="orders-table">
            <thead>
                <tr>
                <th>Order ID</th>
                <th>Product</th>
                <th>Name</th> <!-- NEW -->
                <th>Payment Method</th>
                <th>Quantity</th>
                <th>Total</th>
                <th>Status</th>
                <th>Cancel</th>
                <th>Complete</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($row['id']) ?></td>                  
                            <td><?= htmlspecialchars($row['product_name']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['payment_method']) ?></td>
                            <td><?= htmlspecialchars($row['quantity']) ?></td>
                            <td>₱<?= htmlspecialchars($row['total_amount'], 2) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td>    
                                <?php if ($row['status'] !== 'Cancelled' && $row['status'] !== 'Complete'): ?>
                                    <form method="POST" action="cancel_order.php" style="display:inline;">
                                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($row['id']) ?>">
                                        <button type="submit" class="btn btn-danger cancel-btn">Cancel</button>
                                    </form>
                                <?php elseif ($row['status'] === 'Cancelled'): ?>
                                    <span style="color: red;">Cancelled</span>
                                <?php else: ?>
                                    <span style="color: gray;"></span>
                                <?php endif; ?>                                
                            </td>

                            <td>
                                <?php if ($row['status'] !== 'Complete' && $row['status'] !== 'Cancelled'): ?>
                                    <form method="POST" action="complete_order.php" style="display:inline;">
                                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($row['id']) ?>">
                                        <button type="submit" class="btn btn-primary">Complete</button>
                                    </form>
                                <?php elseif ($row['status'] === 'Complete'): ?>
                                    <span style="color: green;">Complete</span>
                                <?php else: ?>
                                    <span style="color: gray;"></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">No orders yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const productPrices = {
        1: 100.00, // Battery
        2: 150.00  // Charger
    };

    function updateTotal() {
        const productId = document.getElementById("product_id").value;
        const quantity = parseInt(document.getElementById("quantity").value) || 0;
        const price = productPrices[productId] || 0;
        document.getElementById("total_amount").value = (price * quantity).toFixed(2);
    }

    document.addEventListener("DOMContentLoaded", function () {
    const userRole = "<?php echo $_SESSION['role'] ?? 'staff'; ?>";
    const cancelButtons = document.querySelectorAll(".cancel-btn");

    cancelButtons.forEach(button => {
        button.addEventListener("click", function () {
            const orderId = this.getAttribute("data-order-id");

            if (confirm("Are you sure you want to cancel this order?")) {
                fetch("cancel_order.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `order_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) location.reload(); // Refresh the page to update status
                })
                .catch(error => console.error("Error:", error));
            }
        });
    });

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
    });
</script>

</body>
</html>