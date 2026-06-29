<?php
require_once 'includes/init.php';

echo "<h1>Notification System Test</h1>";

// Check if user is logged in
if (!isLoggedIn()) {
    echo "<p style='color: red'>Please login first!</p>";
    echo "<a href='login.php'>Login here</a>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Create a test notification directly
$stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link, created_at) 
                       VALUES (?, 'system', 'Test Notification', 'This is a test notification to verify the system works!', '#', NOW())");
$result = $stmt->execute([$user_id]);

if ($result) {
    echo "<p style='color: green'>✅ Test notification created successfully!</p>";
} else {
    echo "<p style='color: red'>❌ Failed to create test notification</p>";
}

// Check how many notifications exist for this user
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$stmt->execute([$user_id]);
$count = $stmt->fetchColumn();

echo "<p>📊 Total notifications for you: <strong>" . $count . "</strong></p>";

// Show recent notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

if (!empty($notifications)) {
    echo "<h3>Recent Notifications:</h3>";
    echo "<ul>";
    foreach($notifications as $notif) {
        echo "<li><strong>" . htmlspecialchars($notif['title']) . "</strong>: " . htmlspecialchars($notif['message']) . " - " . $notif['created_at'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No notifications found in database</p>";
}

echo "<hr>";
echo "<a href='dashboard.php'>Go to Dashboard</a> | ";
echo "<a href='check-notifications.php'>Refresh</a>";
?>