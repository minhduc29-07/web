<?php
require_once 'db.php';
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']); // Plain text password
    $email = $conn->real_escape_string($_POST['email']);
    $dob = $conn->real_escape_string($_POST['dob']);
    $location = $conn->real_escape_string($_POST['location']);

    // Default role is 'staff'
    $role = 'staff';

    if (empty($username) || empty($password) || empty($email) || empty($dob) || empty($location)) {
        $message = "Please fill in all fields."; // Translated
        $message_type = 'error';
    } else {
        // Check if username or email already exists
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $message = "Username or Email already exists."; // Translated
            $message_type = 'error';
        } else {
            // Insert new user (storing plain password)
            $stmt_insert = $conn->prepare("INSERT INTO users (username, password, role, email, dob, location) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("ssssss", $username, $password, $role, $email, $dob, $location);

            if ($stmt_insert->execute()) {
                header("Location: login.php?registered=true");
                exit;
            } else {
                $message = "Error during registration: " . $conn->error; // Translated
                $message_type = 'error';
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en"> <!-- Changed lang to en -->
<head>
    <meta charset="UTF-8">
    <title>Register</title> <!-- Translated -->
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- THÊM CLASS 'auth-background' VÀO DÒNG DƯỚI ĐÂY -->
    <div id="register-view" class="view active auth-background">
        <div class="form-container">
            <img src="logo.png" alt="Logo" class="logo">
            <h2>Create Account</h2> <!-- Translated -->
            
            <?php if(!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo html_safe($message); ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                
                <div class="form-group">
                    <label for="username">Username</label> <!-- Translated -->
                    <input type="text" id="username" name="username" placeholder="Your Username" required> <!-- Translated -->
                </div>
                
                
                <div class="form-group">
                    <label for="password">Password</label> <!-- Translated -->
                    <input type="password" id="password" name="password" placeholder="Password (at least 6 characters)" required> <!-- Translated -->
                </div>

                
                <div class="form-group">
                    <label for="email">Email</label> <!-- Translated -->
                    <input type="email" id="email" name="email" placeholder="email@example.com" required>
                </div>
                
                
                <div class="form-group">
                    <label for="dob">Date of Birth</label> <!-- Translated -->
                    <input type="date" id="dob" name="dob" required>
                </div>
                
                
                <div class="form-group">
                    <label for="location">Location</label> <!-- Translated -->
                    <input type="text" id="location" name="location" placeholder="e.g., Hanoi" required> <!-- Translated -->
                </div>

                <button type="submit" style="margin-top: 15px;">Register</button> <!-- Translated -->
            </form>
            <p>Already have an account? <a href="login.php">Login now</a></p> <!-- Translated -->
        </div>
    </div>
</body>
</html>