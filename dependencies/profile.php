<?php
session_start();
require "dependencies/config.php";

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];

$query = "SELECT id, username, email FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $new_email = $_POST['email'];
    $password = $_POST['password'];
    $retype_password = $_POST['retype_password'];

    if (!empty($password)) {
        if ($password !== $retype_password) {
            $message = "Passwords do not match!";
        } else {
            $update_query = "UPDATE users SET username = ?, password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssi", $username, $password, $user['id']);


            if ($stmt->execute()) {
                $message = "Profile updated successfully!";
                $user['username'] = $username;
            } else {
                $message = "Error updating profile.";
            }
        }
    } else {
        $update_query = "UPDATE users SET username = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $username, $user['id']);

        if ($stmt->execute()) {
            $message = "Profile updated successfully!";
            $user['username'] = $username;
        } else {
            $message = "Error updating profile.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - La Frontera</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/profile.css">
</head>
<body>

    <div class="navbar">
        La Frontera
    </div>

    <div class="main-content">
        <div class="profile-card">
            <h2> My Profile</h2>

            <?php if ($message): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>New Password <small>(leave blank to keep current)</small></label>
                    <input type="password" name="password" placeholder="Enter new password">
                </div>
                <div class="form-group">
                    <label>Retype New Password</label>
                    <input type="password" name="retype_password" placeholder="Retype new password">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Update Profile</button>
                    <button type="button" class="btn-goback" onclick="history.back()">Cancel</button>
                </div>

                <button type="button" class="delete-account">Delete Account</button>
            </form>
        </div>
    </div>

<script>
    document.querySelector(".delete-account").addEventListener("click", function () {
        if (confirm("Are you sure you want to delete your account? This action cannot be undone.")) {
            fetch("delete_account.php", {
                method: "POST"
            })
            .then(response => response.text())
            .then(data => {
                alert(data);
                window.location.href = "login.php";
            })
            .catch(error => {
                console.error("Error deleting account:", error);
                alert("An error occurred while deleting the account.");
            });
        }
    });
    </script>
</body>
</html>
