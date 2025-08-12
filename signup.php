<?php
require_once 'dependencies/config.php';
require_once 'dependencies/auth.php';
require_once 'dependencies/logger.php';
require_once 'dependencies/validator.php';

redirect_if_logged_in(); // go to MenuPage.php if logged in

// Initialize logger and validator
$logger = new SecurityLogger($conn);
$validator = new SecurityValidator($logger);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup - La Frontera</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
    <link rel="stylesheet" href="css/signup.css">
    <script>
        function validatePasswords() {
            const password = document.getElementById("password").value;
            const retypePassword = document.getElementById("retype_password").value;

            if (password !== retypePassword) {
                alert("Passwords do not match. Please try again.");
                return false;
            }
            if (password.length < 12) {
                alert("Password must be at least 12 characters long.");
                return false;
            }
            if (!/[A-Z]/.test(password)) {
                alert("Password must contain at least one uppercase letter.");
                return false;
            }
            if (!/[a-z]/.test(password)) {
                alert("Password must contain at least one lowercase letter.");
                return false;
            }
            if (!/[0-9]/.test(password)) {
                alert("Password must contain at least one number.");
                return false;
            }
            if (!/[\W_]/.test(password)) {
                alert("Password must contain at least one special character.");
                return false;
            }
            return true;
        }
    </script>
</head>

<body>
    <div class="wrapper">
        <div class="title">Signup</div>
        <form action="" method="POST" onsubmit="return validatePasswords()">
            <div class="row">
                <i class="fas fa-user"></i>
                <input type="text" name="username" placeholder="Username" required />
            </div>
            <div class="row">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email ID" required />
            </div>
            <div class="row">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Password" required />
            </div>
            <div class="row">
                <i class="fas fa-lock"></i>
                <input type="password" id="retype_password" name="retype_password" placeholder="Retype Password" required />
            </div>
            <div class="row">
                <i class="fas fa-question-circle"></i>
                <select name="security_question" required>
                    <option value="" disabled selected>Select your security question</option>
                    <option value="What is your favorite color?">What is your favorite color?</option>
                    <option value="What is your favorite movie?">What is your favorite movie?</option>
                    <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                    <option value="What was the name of your first pet?">What was the name of your first pet?</option>
                    <option value="What was the name of your elementary school?">What was the name of your elementary school?</option>
                </select>
            </div>
            <div class="row">
                <i class="fas fa-question-circle"></i>
                <input type="text" name="security_answer" placeholder="Answer" required />
            </div>
            <div class="row button">
                <input type="submit" value="Signup" name="signup" />
            </div>
            <div class="login-link">Already have an account? <a href="login.php">Login now</a></div>
        </form>
    </div>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $retype_password = $_POST['retype_password'];
        $security_question = trim($_POST['security_question']);
        $security_answer = trim($_POST['security_answer']);

        // Validate inputs
        $validation_errors = [];
        
        if (!$validator->validateUsername($username, null, 'System')) {
            $validation_errors = array_merge($validation_errors, $validator->getErrors());
        }
        
        if (!$validator->validateEmail($email, null, 'System')) {
            $validation_errors = array_merge($validation_errors, $validator->getErrors());
        }
        
        if (!$validator->validatePassword($password, null, 'System')) {
            $validation_errors = array_merge($validation_errors, $validator->getErrors());
        }
        
        if (!$validator->validateSecurityAnswer($security_answer, null, 'System')) {
            $validation_errors = array_merge($validation_errors, $validator->getErrors());
        }

        if ($password !== $retype_password) {
            $validation_errors[] = "Passwords do not match";
            $logger->logSecurityEvent('VALIDATION_FAILURE', null, 'System', 'Password confirmation mismatch during signup', false);
        }

        if (empty($validation_errors)) {
            // Check if username or email already exists
            $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ss", $username, $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $logger->logSecurityEvent('DUPLICATE_USER', null, 'System', "Signup attempt with existing username or email: $username / $email", false);
                echo "<script>alert('Username or email already exists.');</script>";
            } else {
                // Secure password hashing
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Default usertype
                $usertype = "customer";

                $sql = "INSERT INTO users (username, email, password, security_question, security_answer, usertype) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                if ($stmt === false) {
                    $logger->logSecurityEvent('DATABASE_ERROR', null, 'System', "Failed to prepare signup statement: " . $conn->error, false);
                    die("Error preparing statement: " . $conn->error);
                }

                $stmt->bind_param("ssssss", $username, $email, $hashed_password, $security_question, $security_answer, $usertype);

                if ($stmt->execute()) {
                    $logger->logSecurityEvent('USER_REGISTERED', null, 'System', "New user registered: $username ($email)", true);
                    echo "<script>alert('Signup successful!'); window.location='login.php';</script>";
                } else {
                    $logger->logSecurityEvent('USER_REGISTRATION_FAILED', null, 'System', "Failed to register user: $username, Error: " . $stmt->error, false);
                    echo "<script>alert('Error: " . $stmt->error . "');</script>";
                }

                $stmt->close();
            }
            $check_stmt->close();
        } else {
            // Log validation failures
            $logger->logSecurityEvent('VALIDATION_FAILURE', null, 'System', "Signup validation failed: " . implode(", ", $validation_errors), false);
            echo "<script>alert('Validation errors: " . implode("\\n", $validation_errors) . "');</script>";
        }
    }
    $conn->close();
    ?>
</body>
</html>