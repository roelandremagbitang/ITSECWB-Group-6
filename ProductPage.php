<?php
require_once 'dependencies/config.php';
require_once 'dependencies/auth.php';

require_login(); // user has to be logged in to access this page
require_role(['owner', 'manager', 'customer']); // all account types has access to this page

$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['email'])) {
    die("User not logged in.");
}
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
    die("User not found in database.");
}

$sql = "SELECT * FROM products";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Frontera - Products</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/ProductPage.css">
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
        <a href="InventoryPage.php" class="sidebar-item"><i class="fas fa-boxes"></i> Inventory</a>
        <a href="ProductPage.php" class="sidebar-item active"><i class="fas fa-box"></i> Products</a>
        <a href="OrderPage.php" class="sidebar-item"><i class="fas fa-shopping-cart"></i>Orders</a>

        <?php if ($_SESSION['user_role'] === 'owner'): ?>
            <a href="accounts_page.php" class="sidebar-item"><i class="fas fa-users-cog"></i> Accounts</a>
        <?php endif; ?>
    </div>

    <div class="content">
        <div class="content-header">
            <h2>Products</h2>
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
                    <th>Product</th>
                    <th>Stock</th>
                    <th>Price</th>
                    <th>Supplier</th>
                </tr>
            </thead>
            <tbody id="productTable">
                <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                    <tr>
                        <?php if ($usertype == 'owner' || $usertype == 'manager'): ?>
                        <td>
                            <button class="edit-btn" 
                                product_id="<?= $row['product_id'] ?>"
                                product_name="<?= $row['product_name'] ?>" 
                                price="<?= $row['price'] ?>" 
                                quantity="<?= $row['quantity'] ?>" 
                                supplier="<?= $row['supplier'] ?>"> Edit</button>
                        
                            <button class="delete-btn" product_id="<?= $row['product_id'] ?>">Delete</button>
                        </td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                        <td><?= htmlspecialchars($row['quantity']) ?></td>
                        <td>₱<?= number_format($row['price'], 2) ?></td>
                        <td><?= htmlspecialchars($row['supplier']) ?></td>
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
                <label>Price</label>
                <input type="number" id="productPrice" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" id="productQuantity" required>
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
                <label>Price</label>
                <input type="number" id="editPrice" name="price" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" id="editQuantity" name="quantity" required>
            </div>
            <div class="form-group">
                <label>Supplier</label>
                <input type="text" id="editSupplier" name="supplier" required>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Update Item</button>
            </div>
        </form>
    </div>
</div>

<script>
<?php if ($usertype == 'owner' || $usertype == 'manager'): ?>
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
    formData.append("price", document.getElementById("productPrice").value);
    formData.append("quantity", document.getElementById("productQuantity").value);
    formData.append("supplier", document.getElementById("productSupplier").value);

    fetch("add_product.php", {
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
        document.getElementById("editPrice").value = this.getAttribute("price");
        document.getElementById("editQuantity").value = this.getAttribute("quantity");
        document.getElementById("editSupplier").value = this.getAttribute("supplier");
    });
});

// Submit Edit Form via AJAX
document.getElementById("editItemForm").addEventListener("submit", function(e) {
    e.preventDefault();

    let formData = new FormData(this);

    fetch("edit_product.php", {
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
            fetch("delete_product.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                alert(data);
                location.reload();
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