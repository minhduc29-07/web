<?php
// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><title>Reset Admin</title>";
echo "<style>body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; } .success { color: green; font-weight: bold; } .error { color: red; font-weight: bold; }</style>";
echo "</head><body>";
echo "<h1>Resetting Admin Password...</h1>";

require_once 'db.php'; // Database connection

// New password
$new_password = '123456';

// SECURITY: Hash the new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

if (!$hashed_password) {
    echo "<p class='error'>Critical error: password_hash function failed.</p>";
    exit;
}

echo "<p>Generated new hash: $hashed_password</p>";

// Prepare UPDATE statement
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
if (!$stmt) {
    echo "<p class='error'>Error preparing statement: " . $conn->error . "</p>";
    exit;
}

$stmt->bind_param("s", $hashed_password);

// Execute
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "<p class='success'>SUCCESS! The password for account 'admin' has been reset to '123456'.</p>";
        echo "<p>You can now return to the login page.</p>";
    } else {
        echo "<p class='error'>ERROR: Could not find an account with username 'admin' to update. Have you run the SQL file?</p>";
    }
} else {
    echo "<p class='error'>Execution error: " . $stmt->error . "</p>";
}

$stmt->close();
$conn->close();

echo "</body></html>";
?>
