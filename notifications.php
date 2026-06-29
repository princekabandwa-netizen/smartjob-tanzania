<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Set timezone to Tanzania
date_default_timezone_set('Africa/Dar_es_Salaam');

// Get notifications with pagination
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll();

// Get total count for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$stmt->execute([$user_id]);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $limit);

function formatNotificationDate($timestamp) {
    date_default_timezone_set('Africa/Dar_es_Salaam');
    $now = new DateTime();
    $created = new DateTime($timestamp);
    $diff = $now->getTimestamp() - $created->getTimestamp();
    
    if ($diff < 60) {
        return "Just now";
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . " minute" . ($minutes > 1 ? "s" : "") . " ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago at " . $created->format('H:i');
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        if ($days == 1) {
            return "Yesterday at " . $created->format('H:i');
        }
        return $days . " days ago";
    } else {
        return $created->format('F d, Y \a\t H:i');
    }
}

include 'includes/header.php';
?>

<style>
.notifications-page {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
    min-height: calc(100vh - 200px);
}

.notifications-container {
    max-width: 800px;
    margin: 0 auto;
}

.page-title {
    text-align: center;
    margin-bottom: 40px;
}

.page-title h1 {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.page-title p {
    color: #666;
}

.notifications-list {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.notification-item-full {
    padding: 20px 25px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    gap: 15px;
    transition: background 0.3s;
    position: relative;
}

.notification-item-full:hover {
    background: #f8f9fa;
}

.notification-item-full.unread {
    background: #fff9f0;
}

.notification-item-full.unread::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: var(--secondary-color);
}

.notification-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.notification-icon.job_application { background: #3498db; color: white; }
.notification-icon.application_status { background: #27ae60; color: white; }
.notification-icon.new_job { background: #e74c3c; color: white; }
.notification-icon.account { background: #1abc9c; color: white; }
.notification-icon.system { background: #95a5a6; color: white; }
.notification-icon.message { background: #9b59b6; color: white; }

.notification-icon i {
    font-size: 20px;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    margin-bottom: 5px;
    color: var(--primary-color);
    font-size: 16px;
}

.notification-message {
    color: #666;
    margin-bottom: 8px;
    line-height: 1.5;
    font-size: 14px;
}

.notification-time {
    font-size: 12px;
    color: #999;
}

.notification-time i {
    margin-right: 5px;
}

.notification-link {
    display: flex;
    align-items: center;
    color: var(--secondary-color);
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: color 0.3s;
}

.notification-link:hover {
    text-decoration: underline;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 30px;
}

.pagination a, .pagination span {
    padding: 8px 15px;
    background: white;
    border-radius: 8px;
    text-decoration: none;
    color: var(--primary-color);
    transition: all 0.3s;
}

.pagination a:hover {
    background: var(--secondary-color);
    color: white;
}

.pagination .active {
    background: var(--secondary-color);
    color: white;
}

.empty-notifications {
    text-align: center;
    padding: 60px 20px;
}

.empty-notifications i {
    font-size: 60px;
    color: #ccc;
    margin-bottom: 20px;
}

.empty-notifications h3 {
    color: var(--primary-color);
    margin-bottom: 10px;
}

/* Mark all read button */
.mark-all-container {
    text-align: right;
    margin-bottom: 20px;
}

.btn-mark-all {
    background: var(--primary-color);
    color: white;
    padding: 8px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
}

.btn-mark-all:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .notification-item-full {
        padding: 15px;
        flex-direction: column;
    }
    
    .notification-icon {
        width: 40px;
        height: 40px;
    }
    
    .notification-link {
        margin-top: 10px;
    }
}
</style>

<section class="notifications-page">
    <div class="container">
        <div class="notifications-container">
            <div class="page-title">
                <h1><i class="fas fa-bell"></i> Notifications</h1>
                <p>Stay updated with your latest activities and alerts</p>
            </div>
            
            <?php if($total > 0): ?>
                <div class="mark-all-container">
                    <button class="btn-mark-all" onclick="markAllNotifications()">
                        <i class="fas fa-check-double"></i> Mark all as read
                    </button>
                </div>
            <?php endif; ?>
            
            <div class="notifications-list">
                <?php if(empty($notifications)): ?>
                    <div class="empty-notifications">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No notifications yet</h3>
                        <p>When you receive notifications, they'll appear here</p>
                        <a href="jobs.php" class="btn-submit" style="display: inline-block; width: auto; margin-top: 20px;">
                            Browse Jobs
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach($notifications as $notif): ?>
                        <div class="notification-item-full <?php echo $notif['is_read'] ? '' : 'unread'; ?>" 
                             id="notification-<?php echo $notif['id']; ?>">
                            
                            <div class="notification-icon <?php echo $notif['type']; ?>">
                                <i class="fas <?php 
                                    echo $notif['type'] == 'job_application' ? 'fa-paper-plane' :
                                        ($notif['type'] == 'application_status' ? 'fa-check-circle' :
                                        ($notif['type'] == 'new_job' ? 'fa-briefcase' :
                                        ($notif['type'] == 'account' ? 'fa-user-check' : 'fa-bell')));
                                ?>"></i>
                            </div>
                            
                            <div class="notification-content">
                                <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                                <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                                <div class="notification-time">
                                    <i class="fas fa-clock"></i> 
                                    <?php echo formatNotificationDate($notif['created_at']); ?>
                                </div>
                            </div>
                            
                            <?php if($notif['link'] && $notif['link'] != '#'): ?>
                                <a href="<?php echo $notif['link']; ?>" class="notification-link" onclick="markAsRead(<?php echo $notif['id']; ?>)">
                                    View Details <i class="fas fa-arrow-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php if($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>"><i class="fas fa-chevron-left"></i> Previous</a>
                    <?php endif; ?>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>">Next <i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
function markAsRead(notificationId) {
    $.ajax({
        url: 'ajax/mark-read-direct.php',
        method: 'POST',
        data: { id: notificationId },
        success: function(response) {
            if (response.trim() === 'success') {
                $('#notification-' + notificationId).removeClass('unread');
                updateNotificationBadge();
            }
        }
    });
}

function markAllNotifications() {
    if (confirm('Mark all notifications as read?')) {
        $.ajax({
            url: 'ajax/mark-all-read-direct.php',
            method: 'POST',
            success: function(response) {
                if (response.trim() === 'success') {
                    $('.notification-item-full').removeClass('unread');
                    $('.notification-badge').hide();
                    showNotification('success', 'All notifications marked as read');
                }
            }
        });
    }
}

function updateNotificationBadge() {
    var unreadCount = $('.notification-item-full.unread').length;
    if (unreadCount > 0) {
        $('.notification-badge').text(unreadCount > 99 ? '99+' : unreadCount);
        $('.notification-badge').show();
    } else {
        $('.notification-badge').hide();
    }
}

function showNotification(type, message) {
    if (typeof toastr !== 'undefined') {
        if (type === 'success') toastr.success(message);
        else if (type === 'error') toastr.error(message);
        else toastr.info(message);
    } else {
        alert(message);
    }
}
</script>

<?php include 'includes/footer.php'; ?>