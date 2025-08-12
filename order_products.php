<?php
include 'dependencies/config.php';
include 'dependencies/auth.php';
include 'dependencies/logger.php';

require_login(); // user has to be logged in to access this page

// Initialize logger
$logger = new SecurityLogger($conn);

// Log page access
        $logger->logSecurityEvent('PAGE_ACCESS', $_SESSION['user_id'], $_SESSION['username'], 
            "Accessed order_products.php page", true);

// Fetch products from inventory
$products = [];
$sql = "SELECT product_id, product_name, quantity, price FROM products WHERE quantity > 0 ORDER BY product_name";
$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    } else {
        // Log no products available
        $logger->logSecurityEvent('NO_PRODUCTS_AVAILABLE', $_SESSION['user_id'], $_SESSION['username'], 
            "No products available for ordering", false);
    }
} else {
    // Log database error
            $logger->logSecurityEvent('DATABASE_ERROR', $_SESSION['user_id'], $_SESSION['username'], 
            "Failed to fetch products: " . $conn->error, false);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create New Order</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/order_products.css">
</head>
<body>

<div class="container">
    <div class="order-card">
        <h2 class="title">Create New Order</h2>
        <form action="order_process.php" method="POST">
            <div class="form-group">
                <label class="form-label">Customer Name:</label>
                <input type="text" name="customer_name" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Select Product:</label>
                <select name="product_id" id="product_id" class="form-control" onchange="updateProductDetails()" required>
                    <option value="">-- Select Product --</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= $product['product_id'] ?>"
                            data-price="<?= $product['price'] ?>"
                            data-stock="<?= $product['quantity'] ?>">
                            <?= $product['product_name'] ?> (Available: <?= $product['quantity'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

            </div>

            <div class="form-group">
                <label class="form-label">Quantity:</label>
                <input type="number" name="quantity" id="quantity" class="form-control" min="1" value="1" oninput="updateTotal()" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Payment Method:</label>
                <select name="payment_method" class="form-control" required>
                    <option value="">-- Select Payment Method --</option>
                    <option value="Cash">Cash</option>
                    <option value="Card">Card</option>
                    <option value="Gcash">Gcash</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Available Stock:</label>
                <input type="text" id="available_stock" class="form-control" readonly>
            </div>

            <div class="form-group">
                <label class="form-label">Price Per Unit:</label>
                <input type="text" id="price_per_unit" class="form-control" readonly>
            </div>

            <div class="form-group">
                <label class="form-label">Total Amount:</label>
                <input type="text" name="total_amount" id="total_amount" class="form-control" readonly>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Place Order</button>
                <button type="button" class="btn-goback" onclick="history.back()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function updateProductDetails() {
        const selectedOption = document.querySelector('#product_id option:checked');
        const price = selectedOption.getAttribute('data-price') || 0;
        const stock = selectedOption.getAttribute('data-stock') || 0;

        document.getElementById('available_stock').value = stock;
        document.getElementById('price_per_unit').value = price;
        updateTotal();
    }

    function updateTotal() {
        const price = parseFloat(document.getElementById('price_per_unit').value || 0);
        const quantity = parseInt(document.getElementById('quantity').value || 0);
        const stock = parseInt(document.getElementById('available_stock').value || 0);

        if (quantity > stock) {
            alert("Quantity exceeds available stock!");
            document.getElementById('quantity').value = stock;
        }

        const total = price * quantity;
        document.getElementById('total_amount').value = total.toFixed(2);
    }
</script>

</body>
</html>
