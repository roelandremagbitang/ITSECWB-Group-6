<?php
require "dependencies/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = $_POST["product_id"];
    $product_name = $_POST["product_name"];
    $price = $_POST["price"];
    $quantity = $_POST["quantity"];
    $supplier = $_POST["supplier"];

    // Prepare the SQL statement
    $sql = "UPDATE products SET product_name=?, price=?, quantity=?, supplier=? WHERE product_id=?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sdisi", $product_name, $price, $quantity, $supplier, $product_id);

        if (mysqli_stmt_execute($stmt)) {
            echo "Product updated successfully!";
        } else {
            echo "Error: " . mysqli_error($conn);
        }

        mysqli_stmt_close($stmt);
    } else {
        echo "SQL Error: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>