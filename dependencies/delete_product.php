<?php
require "dependencies/config.php";

// Validate input
if (isset($_POST['product_id']) && is_numeric($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']); // Convert to integer for security

    // Prepare and execute delete query
    $sql = "DELETE FROM products WHERE product_id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $product_id);

    if (mysqli_stmt_execute($stmt)) {
        echo "Product deleted successfully!";
    } else {
        echo "Error deleting product: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
} else {
    echo "Invalid product ID!";
}

mysqli_close($conn);
?>