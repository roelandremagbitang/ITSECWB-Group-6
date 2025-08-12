<?php
include 'dependencies/config.php';
include 'dependencies/auth.php';

redirect_if_logged_in(); // go to MenuPage.php if logged in
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
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $retype_password = $_POST['retype_password'];
        $security_question = $_POST['security_question'];
        $security_answer = $_POST['security_answer'];

        if ($password !== $retype_password) {
            echo "<script>alert('Passwords do not match.');</script>";
        } else {
            // Enforce server-side password policy
            if (strlen($password) < 12) {
                echo "<script>alert('Password must be at least 12 characters long.');</script>";
                exit;
            }
            if (!preg_match('/[A-Z]/', $password)) {
                echo "<script>alert('Password must contain at least one uppercase letter.');</script>";
                exit;
            }
            if (!preg_match('/[a-z]/', $password)) {
                echo "<script>alert('Password must contain at least one lowercase letter.');</script>";
                exit;
            }
            if (!preg_match('/[0-9]/', $password)) {
                echo "<script>alert('Password must contain at least one number.');</script>";
                exit;
            }
            if (!preg_match('/[\W_]/', $password)) {
                echo "<script>alert('Password must contain at least one special character.');</script>";
                exit;
            }

            // Secure password hashing
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Default usertype
            $usertype = "Customer";

            $sql = "INSERT INTO users (username, email, password, security_question, security_answer, usertype) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                die("Error preparing statement: " . $conn->error);
            }

            $stmt->bind_param("ssssss", $username, $email, $hashed_password, $security_question, $security_answer, $usertype);

            if ($stmt->execute()) {
                echo "<script>alert('Signup successful!'); window.location='login.php';</script>";
            } else {
                echo "<script>alert('Error: " . $stmt->error . "');</script>";
            }

            $stmt->close();
        }
    }
    $conn->close();
    ?>
</body>
</html>