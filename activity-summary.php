<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['role'] == 'admin');

// Get action statistics
$stmt = $pdo->prepare("SELECT action_type, COUNT(*) as count 
                       FROM user_action_logs 
                       WHERE user_id = ? 
                       GROUP BY action_type 
                       ORDER BY count DESC");
$stmt->execute([$user_id]);
$action_stats = $stmt->fetchAll();

// Get recent actions
$stmt = $pdo->prepare("SELECT * FROM user_action_logs 
                       WHERE user_id = ? 
                       ORDER BY created_at DESC 
                       LIMIT 20");
$stmt->execute([$user_id]);
$recent_actions = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container" style="padding: 60px 20px;">
    <h1><i class="fas fa-chart-line"></i> Your Activity Summary</h1>
    
    <div class="stats-grid" style="margin: 30px 0;">
        <?php foreach($action_stats as $stat): ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stat['count']; ?></div>
                <div class="stat-label"><?php echo ucfirst(str_replace('_', ' ', $stat['action_type'])); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="activities-table">
        <h3>Recent Activities</h3>
        <table>
            <thead>
                <tr><th>Action</th><th>Details</th><th>Time</th></tr>
            </thead>
            <tbody>
                <?php foreach($recent_actions as $action): ?>
                    <tr>
                        <td><?php echo ucfirst(str_replace('_', ' ', $action['action_type'])); ?></td>
                        <td><?php echo htmlspecialchars($action['action_details']); ?></td>
                        <td><?php echo date('M d, H:i:s', strtotime($action['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>