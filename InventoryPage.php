<?php
session_start();
include 'dependencies/config.php';

if (!isset($_SESSION['email'])) {
    die("User not logged in.");
    exit();
}
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

$sql = "SELECT * FROM inventory";
$result = mysqli_query($conn, $sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Frontera - Inventory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/inventory_page.css">
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
        <?php if ($_SESSION['user_role'] === 'owner' || $_SESSION['user_role'] === 'manager'): ?>
            <a href="accounts_page.php" class="sidebar-item"><i class="fas fa-home"></i>Home</a>
        <?php endif; ?>
        <a href="InventoryPage.php" class="sidebar-item active"><i class="fas fa-boxes"></i> Inventory</a>
        <a href="ProductPage.php" class="sidebar-item"><i class="fas fa-box"></i> Products</a>
        <a href="OrderPage.php" class="sidebar-item"><i class="fas fa-shopping-cart"></i> Orders</a>
        
        <?php if ($_SESSION['user_role'] === 'owner'): ?>
            <a href="accounts_page.php" class="sidebar-item"><i class="fas fa-users-cog"></i> Accounts</a>
        <?php endif; ?>
    </div>

    <div class="content">
        <div class="content-header">
            <h2>Inventory</h2>
            <?php if ($usertype == 'owner' || $usertype == 'manager'): ?>
            <button class="btn btn-success" id="openModal">
                <i class="fas fa-plus"></i> Add Item
            </button>
            <?php endif; ?>
        </div>

        <table>
            <thead>
                <tr>
                    <?php if ($usertype == 'owner' || $usertype == 'manager'): ?>
                    <th>Edit</th>
                    <?php endif; ?>
                    <th>Product Name</th>
                    <th>Inbound Qty (Previous Transaction)</th>
                    <th>Outbound Qty (Previous Transaction)</th>
                    <th>Current Stock</th>
                    <th>Supplier</th>
                    <th>Last Restocked Date</th>
                </tr>
            </thead>
            <tbody id="productTable">
                <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                    <tr>
                        <?php if ($usertype == 'owner' || $usertype == 'manager'): ?>
                        <td>
                            <button class="edit-btn" 
                                product_id="<?= $row['id'] ?>"
                                product_name="<?= $row['product_name'] ?>" 
                                inbound_qty="<?= $row['inbound_qty'] ?>" 
                                outbound_qty="<?= $row['outbound_qty'] ?>" 
                                current_stock="<?= $row['current_stock'] ?>" 
                                supplier="<?= $row['supplier'] ?>"
                                last_restocked="<?= $row['last_restocked'] ?>"> Edit</button>
                        
                            <button class="delete-btn" product_id="<?= $row['id'] ?>">Delete</button>
                        </td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                        <td><?= htmlspecialchars($row['inbound_qty']) ?></td>
                        <td><?= htmlspecialchars($row['outbound_qty']) ?></td>
                        <td><?= htmlspecialchars($row['current_stock']) ?></td>
                        <td><?= htmlspecialchars($row['supplier']) ?></td>
                        <td><?= htmlspecialchars($row['last_restocked']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="itemModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Item</h3>
            <span class="close" id="closeModal">&times;</span>
        </div>
        <form id="addItemForm">
            <div class="form-group">
                <label>Product Name</label>
                <input type="text" id="productName" required>
            </div>
            <div class="form-group">
                <label>Current Stock</label>
                <input type="number" id="currentStock" required>
            </div>
            <div class="form-group">
                <label>Supplier</label>
                <input type="text" id="productSupplier" required>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Add Item</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Product</h3>
            <span class="close" id="closeEditModal">&times;</span>
        </div>
        <form id="editItemForm">
            <input type="hidden" id="editId" name="product_id">
            <div class="form-group">
                <label>Product Name</label>
                <input type="text" id="editName" name="product_name" required>
            </div>
            <div class="form-group">
                <label>Supplier</label>
                <input type="text" id="editSupplier" name="supplier" required>
            </div>
            <div class="form-group">
                <label>Inbound Qty</label>
                <input type="number" id="editInboundQty" name="inbound_qty" required>
            </div>
            <div class="form-group">
                <label>Outbound Qty</label>
                <input type="number" id="editOutboundQty" name="outbound_qty" required>
            </div>
            <div class="form-group">
                <label>Current Stock</label>
                <input type="number" id="editCurrentStock" name="current_stock" readonly>
            </div>
            <div class="form-group">
                <label>Last Restocked Date (Auto-calculated)</label>
                <input type="text" id="editLastRestocked" name="last_restocked" readonly>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Update Item</button>
            </div>
        </form>
    </div>
</div>

<script>
<?php if ($usertype == 'owner' || $usertype == 'manager'): ?>

    let initialStock = 0;

// Open modal
document.getElementById("openModal").addEventListener("click", function() {
    document.getElementById("itemModal").style.display = "flex";
});

// Close modal
document.getElementById("closeModal").addEventListener("click", function() {
    document.getElementById("itemModal").style.display = "none";
});

// AJAX form submission
document.getElementById("addItemForm").addEventListener("submit", function(e) {
    e.preventDefault();

    let formData = new FormData();
    formData.append("product_name", document.getElementById("productName").value);
    formData.append("current_stock", document.getElementById("currentStock").value);
    formData.append("supplier", document.getElementById("productSupplier").value);
    formData.append("inbound_qty", 0);  // Default value
    formData.append("outbound_qty", 0);  // Default value
    formData.append("last_restocked", new Date().toISOString().slice(0, 19).replace("T", " "));  // Current date and time

    fetch("add_inventory.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        alert(data);
        location.reload();
    })
    .catch(error => console.error("Error:", error));
});

// Open Edit Modal with existing product data
document.querySelectorAll(".edit-btn").forEach(button => {
    button.addEventListener("click", function() {
        document.getElementById("editModal").style.display = "flex";
        document.getElementById("editId").value = this.getAttribute("product_id");
        document.getElementById("editName").value = this.getAttribute("product_name");
        document.getElementById("editSupplier").value = this.getAttribute("supplier");
        document.getElementById("editInboundQty").value = this.getAttribute("inbound_qty");
        document.getElementById("editOutboundQty").value = this.getAttribute("outbound_qty");
        
        // Get the current stock from the database
        let currentStock = parseInt(this.getAttribute("current_stock"));
        document.getElementById("editCurrentStock").value = currentStock;

        initialStock = currentStock;  // Store the original current stock for calculation

        // Auto-calculate Current Stock and Last Restocked Date
        let inboundQty = parseInt(this.getAttribute("inbound_qty"));
        let outboundQty = parseInt(this.getAttribute("outbound_qty"));
        
        // Set initial stock correctly before any changes
        document.getElementById("editCurrentStock").value = initialStock + inboundQty - outboundQty;
        document.getElementById("editLastRestocked").value = new Date().toISOString().slice(0, 19).replace("T", " ");
    });
});

// Submit Edit Form via AJAX
document.getElementById("editInboundQty").addEventListener("input", updateCurrentStock);
document.getElementById("editOutboundQty").addEventListener("input", updateCurrentStock);



function updateCurrentStock() {
    // Get the inbound and outbound quantities from the inputs
    let inboundQty = parseInt(document.getElementById("editInboundQty").value) || 0;
    let outboundQty = parseInt(document.getElementById("editOutboundQty").value) || 0;
    
    // Calculate the new current stock by adjusting the original stock with the inbound and outbound quantities
    let newCurrentStock = initialStock + inboundQty - outboundQty;
    
    // Update the current stock field with the computed value
    document.getElementById("editCurrentStock").value = newCurrentStock;
}

// Submit Edit Form via AJAX
document.getElementById("editItemForm").addEventListener("submit", function(e) {
    // Check if the computed current stock is negative
    let currentStock = parseInt(document.getElementById("editCurrentStock").value) || 0;
    if (currentStock < 0) {
        e.preventDefault();  // Prevent form submission
        alert("Current stock cannot be negative.");
        return;  // Stop further execution
    }

    // Proceed with form submission if stock is valid
    let formData = new FormData(this);

    fetch("edit_inventory.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        alert(data);
        location.reload();
    })
    .catch(error => console.error("Error:", error));
});

// Delete Product via AJAX
document.querySelectorAll(".delete-btn").forEach(button => {
    button.addEventListener("click", function() {
        let product_id = this.getAttribute("product_id");
        if (confirm("Are you sure you want to delete this product?")) {
            let formData = new FormData();
            formData.append("product_id", product_id);
            fetch("delete_inventory.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                alert(data); // Show success/failure message
                location.reload(); // Reload the page to reflect the changes
            })
            .catch(error => console.error("Error:", error));
        }
    });
});

// Close Edit Modal
document.getElementById("closeEditModal").addEventListener("click", function() {
    document.getElementById("editModal").style.display = "none";
});
<?php endif; ?>

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
</script>
</body>
</html>