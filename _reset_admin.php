<?php
// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><title>Reset Admin</title>"; // Translated
echo "<style>body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; } .success { color: green; font-weight: bold; } .error { color: red; font-weight: bold; }</style>";
echo "</head><body>";
echo "<h1>Resetting Admin Password...</h1>"; // Translated

require_once 'db.php'; // Connect to DB

// New password
$new_password = '123456';

// Hash the password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

if (!$hashed_password) {
    echo "<p class='error'>Fatal Error: password_hash function is not working.</p>"; // Translated
    exit;
}

echo "<p>New hash created: $hashed_password</p>"; // Translated

// Prepare UPDATE statement
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
if (!$stmt) {
    echo "<p class='error'>Error preparing statement: " . $conn->error . "</p>"; // Translated
    exit;
}

$stmt->bind_param("s", $hashed_password);

// Execute
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "<p class='success'>SUCCESS! Password for 'admin' has been reset to '123456'.</p>"; // Translated
        echo "<p>You can now return to the login page.</p>"; // Translated
    } else {
        echo "<p class='error'>ERROR: No account found with username 'admin' to update. Did you run the shoe_store.sql file?</p>"; // Translated
    }
} else {
    echo "<p class='error'>Error executing: " . $stmt->error . "</p>"; // Translated
}

$stmt->close();
$conn->close();

echo "<hr>";
echo "<a href='login.php'>Click here to return to the Login page</a>"; // Translated
echo "</body></html>";
?>