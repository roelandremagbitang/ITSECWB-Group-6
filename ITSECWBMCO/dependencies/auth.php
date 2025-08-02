<?php
session_start();

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function require_role($roles) {
    if (!isset($_SESSION['user_role'])) {
        header("Location: login.php");
        exit();
    }

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    if (!in_array($_SESSION['user_role'], $roles)) {
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Access Denied</title>
            <script>
                window.onload = function() {
                    if (confirm("An admin account is required to access this page. Sign in to an admin account?")) {
                        window.location.href = "login.php";
                    } else {
                        window.location.href = "InventoryPage.php";
                    }
                };
            </script>
        </head>
        <body>
        </body>
        </html>';
        exit();
    }
}

function redirect_if_logged_in() {
    if (isset($_SESSION['user_id'])) {
        header("Location: MenuPage.php");
        exit();
    }
}
?>