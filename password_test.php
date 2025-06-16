<?php
// Check if the password hash in the database matches the default password
$password = 'password123';
$hash = '$2y$10$bUEvfgg.gYr.lSq1.NWJxeG5hB.c8CCYTFiJ0DjSf1JJQNGqrZwIi';

echo "<h1>Password Verification Test</h1>";
echo "<p>Testing if password 'password123' verifies against the hash in the database</p>";

$result = password_verify($password, $hash);

if ($result) {
    echo "<p style='color: green; font-weight: bold;'>✓ Password verification SUCCESSFUL</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ Password verification FAILED</p>";
    
    // Try creating a new hash for "password123" to see what it looks like
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    echo "<p>New hash for 'password123': $newHash</p>";
}

// Display PHP version information
echo "<h2>PHP Environment Info</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Password algorithm used: " . (defined('PASSWORD_DEFAULT') ? PASSWORD_DEFAULT : 'unknown') . "</p>";
echo "<p>Password algorithm name: " . password_algos()[PASSWORD_DEFAULT] . "</p>";

// Check if bcrypt is available
echo "<p>Bcrypt available: " . (function_exists('password_hash') ? 'Yes' : 'No') . "</p>";
?>
