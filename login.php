<?php
require_once "dependencies/config.php";
require_once "dependencies/auth.php";
require_once "dependencies/logger.php";

// Initialize logger
$logger = new SecurityLogger($conn);

// Process login if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']); // Remove sanitization - we'll validate instead
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $sql = "SELECT id, username, email, password, usertype, failed_login_attempts, account_locked_until FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if ($user['account_locked_until'] !== null && strtotime($user['account_locked_until']) > time()) {
                    $remaining = strtotime($user['account_locked_until']) - time();
                    $_SESSION['error'] = "Account is locked. Try again in " . ceil($remaining / 60) . " minute(s).";
                    
                    // Log locked account attempt
                    $logger->logAuthAttempt($email, false, "Account locked");
                } else {
                    if (password_verify($password, $user['password'])) {
                        // Reset failed attempts
                        $reset_sql = "UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL WHERE id = ?";
                        $reset_stmt = $conn->prepare($reset_sql);
                        $reset_stmt->bind_param("i", $user['id']);
                        $reset_stmt->execute();

                        // Log successful login
                        $logger->logAuthAttempt($email, true, "Login successful");

                        // Set session variables
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_role'] = $user['usertype'];

                        // Redirect based on role
                        if ($user['usertype'] === 'owner' || $user['usertype'] === 'admin' || $user['usertype'] === 'manager') {
                            header("Location: MenuPage.php");
                        } elseif ($user['usertype'] === 'customer') {
                            header("Location: InventoryPage.php");
                        } else {
                            $_SESSION['error'] = "Unauthorized user type.";
                            $logger->logAuthAttempt($email, false, "Unauthorized user type: " . $user['usertype']);
                            header("Location: login.php");
                        }
                        exit();
                    } else {
                        $failed_attempts = $user['failed_login_attempts'] + 1;
                        $lockout_time = null;

                        if ($failed_attempts >= 5) {
                            $lockout_time = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                            $_SESSION['error'] = "Too many failed attempts. Account locked for 15 minutes.";
                        } else {
                            $_SESSION['error'] = "Invalid email ID and/or password. Attempt $failed_attempts of 5.";
                        }

                        // Log failed login attempt
                        $logger->logAuthAttempt($email, false, "Failed attempt $failed_attempts of 5");

                        // Update failed attempt count
                        $update_sql = "UPDATE users SET failed_login_attempts = ?, last_failed_login = NOW(), account_locked_until = ? WHERE id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("isi", $failed_attempts, $lockout_time, $user['id']);
                        $update_stmt->execute();
                    }
                }
            } else {
                $_SESSION['error'] = "Invalid email ID and/or password.";
                // Log failed login attempt (user not found)
                                    $logger->logAuthAttempt($email, false, "User not found");
            }

            $stmt->close();
        } else {
            $_SESSION['error'] = "Database error. Try again later.";
            // Log database error
                            $logger->logSecurityEvent('DATABASE_ERROR', null, 'System', 'Database connection failed during login', false);
        }
    } else {
        $_SESSION['error'] = "All fields are required.";
        // Log validation failure
                    $logger->logSecurityEvent('VALIDATION_FAILURE', null, 'System', 'Empty email or password field', false);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Login - La Frontera</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
    <link rel="stylesheet" href="css/login.css"/>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>
<body>
<div class="wrapper">
    <!-- Image Section -->
    <div class="image-container"></div>

    <!-- Form Section -->
    <div class="form-container">
        <div class="title">Login</div>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="error-message"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="row">
                <i class="fas fa-user"></i>
                <input type="email" name="email" placeholder="Email ID" required />
            </div>
            <div class="row">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required />
            </div>
            <div class="pass"><a href="forget_password.php">Forgot Password?</a></div>
            <div class="row button">
                <input type="submit" value="Login" name="login" />
            </div>
            <div class="signup-link">Don't have an account? <a href="signup.php">Signup now</a></div>
        </form>
    </div>
</div>
</body>
</html>