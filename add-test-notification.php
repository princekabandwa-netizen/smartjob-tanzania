<?php
require_once 'includes/init.php';

if (!isLoggedIn()) {
    die("Please login first. <a href='login.php'>Login here</a>");
}

$user_id = $_SESSION['user_id'];

// Create multiple test notifications
$notifications = [
    ['Welcome!', 'Welcome to SmartJob Tanzania! Start your job search today.', 'success'],
    ['Profile Complete', 'Complete your profile to get better job matches.', 'info'],
    ['New Jobs Available', '5 new jobs matching your skills have been posted.', 'job_application'],
    ['Application Update', 'Your application for Software Engineer has been reviewed.', 'application_status']
];

foreach ($notifications as $notif) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link, created_at) 
                           VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, $notif[2], $notif[0], $notif[1], '#']);
}

echo "Test notifications added! <br>";
echo "<a href='dashboard.php'>Go to Dashboard</a><br>";
echo "<a href='check-notifications.php'>Check Notifications</a>";
?>