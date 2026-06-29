<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();

// Only admins can see all activities
$is_admin = ($_SESSION['role'] == 'admin');

$user_id = $_SESSION['user_id'];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$action_filter = isset($_GET['action']) ? $_GET['action'] : '';

// Validate limit
if ($limit < 1) $limit = 20;
if ($limit > 500) $limit = 500;

if ($is_admin) {
    // Admin can see all users' activities
    $query = "SELECT l.*, u.full_name, u.email, u.role 
              FROM user_action_logs l 
              LEFT JOIN users u ON l.user_id = u.id 
              WHERE 1=1";
    $params = [];
} else {
    // Regular users see only their own activities
    $query = "SELECT l.*, u.full_name, u.email, u.role 
              FROM user_action_logs l 
              LEFT JOIN users u ON l.user_id = u.id 
              WHERE l.user_id = ?";
    $params = [$user_id];
}

if ($action_filter && $action_filter != 'all') {
    $query .= " AND l.action_type = ?";
    $params[] = $action_filter;
}

$query .= " ORDER BY l.created_at DESC LIMIT " . (int)$limit;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$activities = $stmt->fetchAll();

// Get all action types for filter
$action_types = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT action_type FROM user_action_logs ORDER BY action_type");
    $action_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $action_types = ['login_success', 'login_failed', 'logout', 'profile_update', 'job_posted', 'job_updated', 'job_deleted', 'job_applied'];
}

include 'includes/header.php';
?>

<style>
.activities-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
    min-height: calc(100vh - 200px);
}

.activities-header {
    text-align: center;
    margin-bottom: 40px;
}

.activities-header h1 {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.filters-bar {
    background: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 30px;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-group label {
    font-weight: 500;
    color: var(--primary-color);
}

.filter-group select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
}

.btn-refresh {
    padding: 8px 20px;
    background: var(--secondary-color);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-refresh:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

.activities-table {
    background: white;
    border-radius: 12px;
    overflow-x: auto;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: var(--primary-color);
    color: white;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    white-space: nowrap;
}

td {
    padding: 12px 15px;
    border-bottom: 1px solid #e0e0e0;
    vertical-align: top;
}

tr:hover {
    background: #f8f9fa;
}

.action-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

.action-login_success { background: #d4edda; color: #155724; }
.action-login_failed { background: #f8d7da; color: #721c24; }
.action-logout { background: #e2e3e5; color: #383d41; }
.action-profile_update { background: #d1ecf1; color: #0c5460; }
.action-job_posted { background: #d4edda; color: #155724; }
.action-job_updated { background: #fff3cd; color: #856404; }
.action-job_deleted { background: #f8d7da; color: #721c24; }
.action-job_applied { background: #cce5ff; color: #004085; }

.role-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.role-jobseeker { background: #d1ecf1; color: #0c5460; }
.role-employer { background: #d4edda; color: #155724; }
.role-admin { background: #f8d7da; color: #721c24; }

.details-cell {
    max-width: 300px;
    word-wrap: break-word;
    font-size: 13px;
}

.ip-cell code {
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
}

.empty-state {
    text-align: center;
    padding: 60px;
    color: #999;
}

.empty-state i {
    font-size: 60px;
    margin-bottom: 20px;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .activities-table {
        font-size: 12px;
    }
    th, td {
        padding: 8px;
    }
    .details-cell {
        max-width: 150px;
    }
    .filter-group select {
        font-size: 12px;
    }
}
</style>

<section class="activities-section">
    <div class="container">
        <div class="activities-header">
            <h1><i class="fas fa-list-alt"></i> <?php echo $is_admin ? 'All User Activities' : 'My Activity Log'; ?></h1>
            <p>Complete history of all actions performed on the platform</p>
        </div>
        
        <div class="filters-bar">
            <div class="filter-group">
                <label><i class="fas fa-filter"></i> Action Type:</label>
                <select id="actionFilter" onchange="filterActivities()">
                    <option value="all">All Actions</option>
                    <?php foreach($action_types as $at): ?>
                        <option value="<?php echo htmlspecialchars($at); ?>" <?php echo $action_filter == $at ? 'selected' : ''; ?>>
                            <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($at))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-eye"></i> Show:</label>
                <select id="limitFilter" onchange="filterActivities()">
                    <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>Last 20</option>
                    <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>Last 50</option>
                    <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>Last 100</option>
                    <option value="200" <?php echo $limit == 200 ? 'selected' : ''; ?>>Last 200</option>
                    <option value="500" <?php echo $limit == 500 ? 'selected' : ''; ?>>Last 500</option>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-calendar"></i> Total Records:</label>
                <span style="background: #f0f0f0; padding: 8px 12px; border-radius: 8px;">
                    <?php echo count($activities); ?> activities
                </span>
            </div>
            <button class="btn-refresh" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        
        <div class="activities-table">
            <?php if(empty($activities)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No activities found</h3>
                    <p>Activities will appear here as users interact with the platform.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <?php if($is_admin): ?>
                                <th>User</th>
                                <th>Role</th>
                            <?php endif; ?>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($activities as $activity): ?>
                            <tr>
                                <?php if($is_admin): ?>
                                    <td>
                                        <strong><?php echo htmlspecialchars($activity['full_name'] ?? 'Unknown User'); ?></strong><br>
                                        <small><?php echo htmlspecialchars($activity['email'] ?? 'No email'); ?></small>
                                    </td>
                                    <td>
                                        <span class="role-badge role-<?php echo strtolower($activity['role'] ?? 'unknown'); ?>">
                                            <?php echo ucfirst($activity['role'] ?? 'Unknown'); ?>
                                        </span>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <span class="action-badge action-<?php echo $activity['action_type']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $activity['action_type'])); ?>
                                    </span>
                                </td>
                                <td class="details-cell"><?php echo htmlspecialchars($activity['action_details']); ?></td>
                                <td class="ip-cell">
                                    <code><?php echo htmlspecialchars($activity['ip_address'] ?? 'Unknown'); ?></code>
                                </td>
                                <td style="white-space: nowrap;">
                                    <?php echo date('Y-m-d H:i:s', strtotime($activity['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <?php if(count($activities) >= $limit && !empty($activities)): ?>
            <div style="text-align: center; margin-top: 20px;">
                <small class="text-muted">Showing last <?php echo count($activities); ?> activities. 
                <a href="?limit=<?php echo $limit + 50; ?>&action=<?php echo urlencode($action_filter); ?>">Load more</a></small>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
function filterActivities() {
    var action = document.getElementById('actionFilter').value;
    var limit = document.getElementById('limitFilter').value;
    window.location.href = 'all-activities.php?action=' + encodeURIComponent(action) + '&limit=' + limit;
}
</script>

<?php include 'includes/footer.php'; ?>