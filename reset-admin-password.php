<?php
require_once 'includes/init.php';

// This script will reset admin password
// DELETE THIS FILE AFTER RUNNING!

echo "<h1>Admin Password Reset Tool</h1>";

// Check if admin exists
$stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE username = 'admin'");
$stmt->execute();
$admin = $stmt->fetch();

if ($admin) {
    // Update password to 'admin123'
    $new_password = 'admin123';
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    if ($stmt->execute([$hashed_password, $admin['id']])) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724;'>";
        echo "✅ Password reset successful!<br>";
        echo "Username: <strong>admin</strong><br>";
        echo "Password: <strong>admin123</strong><br>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;'>";
        echo "❌ Failed to update password!";
        echo "</div>";
    }
} else {
    // Create new admin account
    $username = 'admin';
    $email = 'admin@smartjob.co.tz';
    $password = 'admin123';
    $full_name = 'System Administrator';
    $role = 'admin';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$username, $email, $hashed_password, $full_name, $role])) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724;'>";
        echo "✅ Admin account created successfully!<br>";
        echo "Username: <strong>admin</strong><br>";
        echo "Password: <strong>admin123</strong><br>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;'>";
        echo "❌ Failed to create admin account!";
        echo "</div>";
    }
}

echo "<hr>";
echo "<a href='login.php' style='display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a>";
echo "<br><br>";
echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px;'>";
echo "⚠️ <strong>Security Note:</strong> Please delete this file (reset-admin-password.php) after successful login!";
echo "</div>";
?>