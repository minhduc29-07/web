<?php
require_once 'db.php';
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password']; // Plain text password
    $email = $conn->real_escape_string($_POST['email']);
    $dob = $conn->real_escape_string($_POST['dob']);
    $location = $conn->real_escape_string($_POST['location']);

    // Default role is 'staff'
    $role = 'staff';

    // Validate required fields and password length
    if (empty($username) || empty($password) || empty($email) || empty($dob) || empty($location)) {
        $message = "Please fill in all required fields.";
        $message_type = 'error';
    } else if (strlen($password) < 6) { 
        $message = "Password must be at least 6 characters long.";
        $message_type = 'error';
    } else {
        // Check if username or email already exists
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $message = "Username or Email already exists.";
            $message_type = 'error';
        } else {
            // SECURITY: Hash password before saving
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            if (!$hashed_password) {
                $message = "Critical error: password_hash failed.";
                $message_type = 'error';
            } else {
                // Insert new user (store hashed password)
                $stmt_insert = $conn->prepare("INSERT INTO users (username, password, role, email, dob, location) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_insert->bind_param("ssssss", $username, $hashed_password, $role, $email, $dob, $location);

                if ($stmt_insert->execute()) {
                    // Redirect after successful registration
                    header("Location: login.php?registered=1");
                    exit;
                } else {
                    $message = "An error occurred during registration: " . $stmt_insert->error;
                    $message_type = 'error';
                }
                $stmt_insert->close();
            }
        }
        $stmt_check->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="register-view" class="view active auth-background">
        <div class="form-container">
            <img src="logo.png" alt="Logo" class="logo">
            <h2>Register</h2>

            <?php if(!empty($message)): ?>
                <p class="message <?php echo $message_type; ?>"><?php echo html_safe($message); ?></p>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <input type="hidden" name="role" value="staff">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Your username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Password (minimum 6 characters)" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="email@example.com" required>
                </div>
                
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" required>
                </div>
                
                <div class="form-group">
                    <label for="location">Address</label>
                    <input type="text" id="location" name="location" placeholder="Example: Hanoi" required>
                </div>

                <button type="submit" style="margin-top: 15px;">Register</button>
            </form>

            <p>Already have an account? <a href="login.php">Login now</a></p>
        </div>
    </div>
</body>
</html>
