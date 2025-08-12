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
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f8fa;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background-color: #ffc107;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            font-weight: bold;
            font-size: 20px;
        }

        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .profile-card {
            background-color: #fff;
            border-radius: 12px;
            padding: 30px 40px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
        }

        .profile-card h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #444;
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 15px;
        }

        .btn-primary {
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }

        .btn-primary:hover {
            background-color: #218838;
        }

        .message {
            margin-bottom: 20px;
            background-color: #eaf7ec;
            border: 1px solid #28a745;
            color: #155724;
            padding: 10px 15px;
            border-radius: 8px;
            text-align: center;
        }

        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #007bff;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .profile-avatar {
            display: flex;
            justify-content: center;
            margin-bottom: 25px;
        }

        .profile-avatar .circle {
            width: 100px;
            height: 100px;
            background-color: #ddd;
            border-radius: 50%;
        }
    </style>
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

            <div class="profile-avatar">
                <div class="circle"></div>
            </div>

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

                <button type="submit" class="btn-primary">Update Profile</button>
            </form>

            <a class="back-link" href="MenuPage.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>

</body>
</html>
