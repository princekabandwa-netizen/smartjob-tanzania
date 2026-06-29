<?php
require_once 'includes/init.php';

if (!isLoggedIn()) {
    die("Please login first. <a href='login.php'>Login here</a>");
}

// Add sample notifications
$notifications = [
    ['success', 'Welcome!', 'Welcome to SmartJob Tanzania! Start your job search today.', 'jobs.php'],
    ['info', 'Profile Complete', 'Complete your profile to get better job matches.', 'profile.php'],
    ['application', 'New Jobs', '5 new jobs matching your skills have been posted.', 'jobs.php'],
];

foreach ($notifications as $notif) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], $notif[0], $notif[1], $notif[2], $notif[3]]);
}

echo "Test notifications added! <a href='dashboard.php'>Go to Dashboard</a>";
?>