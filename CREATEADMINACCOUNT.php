<?php
session_start();

require_once 'db_connect.php';

// Initialize messages
$success = "";
$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);

    // Validate inputs
    if (empty($username) || empty($password) || empty($confirmPassword)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT admin_ID FROM admin_account WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username already taken.";
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert new admin account
            $insertStmt = $conn->prepare("INSERT INTO admin_account (username, password) VALUES (?, ?)");
            $insertStmt->bind_param("ss", $username, $hashedPassword);

            if ($insertStmt->execute()) {
                $success = "Admin account created successfully.";
            } else {
                $error = "Error creating account.";
            }
            $insertStmt->close();
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account</title>
</head>
<body>
    <div class="createAdminContainer">
        <h2>Create Admin Account</h2>

        <!-- Show success or error messages -->
        <?php if (!empty($success)) { echo "<div style='color: green;'>$success</div>"; } ?>
        <?php if (!empty($error)) { echo "<div style='color: red;'>$error</div>"; } ?>

        <form method="POST" action="">
            <label for="username">Username:</label><br>
            <input type="text" name="username" id="username" placeholder="Enter username" required><br><br>

            <label for="password">Password:</label><br>
            <input type="password" name="password" id="password" placeholder="Enter password" required><br><br>

            <label for="confirm_password">Confirm Password:</label><br>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm password" required><br><br>

            <button type="submit">Create Admin</button>
        </form>

        <br>
        <a href="DASHBOARD.php">Back to Dashboard</a>
    </div>
</body>
</html>
