<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();
redirectIfNotJobSeeker();

$user_id = $_SESSION['user_id'];

// Handle alert creation/update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $alert_id = isset($_POST['alert_id']) ? (int)$_POST['alert_id'] : 0;
    $alert_name = trim($_POST['alert_name']);
    $keywords = trim($_POST['keywords']);
    $category = $_POST['category'];
    $location = trim($_POST['location']);
    $job_type = $_POST['job_type'];
    $frequency = $_POST['frequency'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($alert_id > 0) {
        // Update existing alert
        $stmt = $pdo->prepare("UPDATE job_alerts SET alert_name = ?, keywords = ?, category = ?, location = ?, job_type = ?, frequency = ?, is_active = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$alert_name, $keywords, $category, $location, $job_type, $frequency, $is_active, $alert_id, $user_id]);
        $message = "Alert updated successfully!";
    } else {
        // Create new alert
        $stmt = $pdo->prepare("INSERT INTO job_alerts (user_id, alert_name, keywords, category, location, job_type, frequency, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $alert_name, $keywords, $category, $location, $job_type, $frequency, $is_active]);
        $message = "Alert created successfully!";
    }
    
    $_SESSION['success_message'] = $message;
    header('Location: job-alerts.php');
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $alert_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM job_alerts WHERE id = ? AND user_id = ?");
    $stmt->execute([$alert_id, $user_id]);
    $_SESSION['success_message'] = "Alert deleted successfully!";
    header('Location: job-alerts.php');
    exit();
}

// Get user's alerts
$stmt = $pdo->prepare("SELECT * FROM job_alerts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$alerts = $stmt->fetchAll();

// Get categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

include 'includes/header.php';
?>

<style>
.job-alerts-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
    min-height: calc(100vh - 200px);
}

.alerts-header {
    text-align: center;
    margin-bottom: 40px;
}

.alerts-header h1 {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.alerts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.create-alert-card,
.alerts-list-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.card-title {
    font-size: 1.3rem;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
    color: var(--primary-color);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--primary-color);
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.checkbox-group input {
    width: auto;
}

.alert-item {
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s;
}

.alert-item:hover {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.alert-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.alert-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--primary-color);
}

.alert-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-active {
    background: #d4edda;
    color: #155724;
}

.badge-inactive {
    background: #f8d7da;
    color: #721c24;
}

.alert-details {
    font-size: 13px;
    color: #666;
    margin: 10px 0;
}

.alert-details span {
    margin-right: 15px;
}

.alert-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.btn-edit-alert,
.btn-delete-alert,
.btn-test-alert {
    padding: 6px 15px;
    font-size: 12px;
    border-radius: 5px;
    text-decoration: none;
    transition: all 0.3s;
}

.btn-edit-alert {
    background: var(--primary-color);
    color: white;
}

.btn-delete-alert {
    background: #dc3545;
    color: white;
}

.btn-test-alert {
    background: #6c757d;
    color: white;
}

.empty-alerts {
    text-align: center;
    padding: 40px;
    color: #999;
}

@media (max-width: 992px) {
    .alerts-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<section class="job-alerts-section">
    <div class="container">
        <div class="alerts-header">
            <h1><i class="fas fa-bell"></i> Job Alerts</h1>
            <p>Get notified when new jobs matching your criteria are posted</p>
        </div>

        <div class="alerts-grid">
            <!-- Create Alert Form -->
            <div class="create-alert-card">
                <h3 class="card-title"><i class="fas fa-plus-circle"></i> Create New Alert</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Alert Name *</label>
                        <input type="text" name="alert_name" required placeholder="e.g., Web Developer Jobs">
                    </div>
                    
                    <div class="form-group">
                        <label>Keywords (comma separated)</label>
                        <input type="text" name="keywords" placeholder="e.g., PHP, Laravel, JavaScript">
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['name']); ?>">
                                    <?php echo $cat['icon'] . ' ' . htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" placeholder="e.g., Dar es Salaam">
                    </div>
                    
                    <div class="form-group">
                        <label>Job Type</label>
                        <select name="job_type">
                            <option value="">All Types</option>
                            <option value="full-time">Full Time</option>
                            <option value="part-time">Part Time</option>
                            <option value="contract">Contract</option>
                            <option value="internship">Internship</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Frequency</label>
                        <select name="frequency">
                            <option value="instant">Instant (as soon as jobs are posted)</option>
                            <option value="daily">Daily Digest</option>
                            <option value="weekly">Weekly Digest</option>
                        </select>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="is_active" checked>
                        <label>Activate this alert immediately</label>
                    </div>
                    
                    <button type="submit" class="btn-submit">Create Alert</button>
                </form>
            </div>

            <!-- My Alerts List -->
            <div class="alerts-list-card">
                <h3 class="card-title"><i class="fas fa-list"></i> My Alerts</h3>
                
                <?php if(empty($alerts)): ?>
                    <div class="empty-alerts">
                        <i class="fas fa-bell-slash"></i>
                        <p>No alerts created yet</p>
                        <small>Create your first job alert to get notified</small>
                    </div>
                <?php else: ?>
                    <?php foreach($alerts as $alert): ?>
                        <div class="alert-item">
                            <div class="alert-header">
                                <span class="alert-name"><?php echo htmlspecialchars($alert['alert_name']); ?></span>
                                <span class="alert-badge <?php echo $alert['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo $alert['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                            <div class="alert-details">
                                <?php if($alert['keywords']): ?>
                                    <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($alert['keywords']); ?></span>
                                <?php endif; ?>
                                <?php if($alert['category']): ?>
                                    <span><i class="fas fa-folder"></i> <?php echo htmlspecialchars($alert['category']); ?></span>
                                <?php endif; ?>
                                <?php if($alert['location']): ?>
                                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($alert['location']); ?></span>
                                <?php endif; ?>
                                <span><i class="fas fa-clock"></i> <?php echo ucfirst($alert['frequency']); ?></span>
                            </div>
                            <div class="alert-actions">
                                <button onclick="editAlert(<?php echo $alert['id']; ?>)" class="btn-edit-alert">Edit</button>
                                <a href="?delete=<?php echo $alert['id']; ?>" class="btn-delete-alert" onclick="return confirm('Delete this alert?')">Delete</a>
                                <button onclick="testAlert(<?php echo $alert['id']; ?>)" class="btn-test-alert">Test</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
function editAlert(alertId) {
    // Fetch alert data and populate form
    $.ajax({
        url: 'ajax/get-alert.php',
        method: 'GET',
        data: { id: alertId },
        dataType: 'json',
        success: function(data) {
            // Populate form for editing (you can open a modal or redirect)
            if (data.success) {
                // Redirect to edit page or populate modal
                window.location.href = 'edit-alert.php?id=' + alertId;
            }
        }
    });
}

function testAlert(alertId) {
    $.ajax({
        url: 'ajax/test-alert.php',
        method: 'POST',
        data: { alert_id: alertId },
        success: function(response) {
            showNotification('success', 'Test alert sent to your email!');
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>