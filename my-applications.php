<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();
redirectIfNotJobSeeker();

$user_id = $_SESSION['user_id'];
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get all applications with filters
$query = "SELECT a.*, j.title, j.company_name, j.location, j.type, j.deadline, j.status as job_status
          FROM applications a 
          JOIN jobs j ON a.job_id = j.id 
          WHERE a.jobseeker_id = ?";
$params = [$user_id];

if ($status_filter != 'all') {
    $query .= " AND a.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY a.applied_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
    SUM(CASE WHEN status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN status = 'hired' THEN 1 ELSE 0 END) as hired
    FROM applications WHERE jobseeker_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

include 'includes/header.php';
?>

<style>
.my-applications-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
    min-height: calc(100vh - 200px);
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
}

.page-header h1 {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.page-header p {
    color: #666;
}

/* Stats Cards */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-box {
    background: white;
    padding: 20px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: transform 0.3s;
    cursor: pointer;
}

.stat-box:hover {
    transform: translateY(-5px);
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: var(--secondary-color);
}

.stat-label {
    color: #666;
    font-size: 14px;
    margin-top: 5px;
}

/* Filter Tabs */
.filter-tabs {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 30px;
    background: white;
    padding: 15px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.filter-tab {
    padding: 8px 20px;
    background: #f0f0f0;
    color: #666;
    text-decoration: none;
    border-radius: 25px;
    transition: all 0.3s;
    font-size: 14px;
}

.filter-tab:hover {
    background: var(--secondary-color);
    color: white;
}

.filter-tab.active {
    background: var(--secondary-color);
    color: white;
}

/* Applications List */
.applications-list {
    display: grid;
    gap: 20px;
}

.application-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: transform 0.3s;
}

.application-card:hover {
    transform: translateX(5px);
}

.application-header {
    padding: 20px;
    background: var(--primary-color);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.application-header h3 {
    margin: 0;
    font-size: 1.2rem;
}

.application-header h3 a {
    color: white;
    text-decoration: none;
}

.application-header h3 a:hover {
    text-decoration: underline;
}

.status-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 25px;
    font-size: 12px;
    font-weight: 600;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-reviewed {
    background: #d1ecf1;
    color: #0c5460;
}

.status-shortlisted {
    background: #d4edda;
    color: #155724;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.status-hired {
    background: #d4edda;
    color: #155724;
}

.application-body {
    padding: 20px;
}

.company-info {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e0e0e0;
}

.company-info span {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #666;
    font-size: 14px;
}

.company-info i {
    color: var(--secondary-color);
    width: 16px;
}

.application-details {
    margin-bottom: 15px;
}

.application-details p {
    margin: 8px 0;
    color: #555;
    line-height: 1.6;
}

.application-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e0e0e0;
    font-size: 13px;
    color: #999;
}

.application-meta i {
    margin-right: 5px;
}

.application-actions {
    padding: 15px 20px;
    background: #f8f9fa;
    display: flex;
    gap: 10px;
    border-top: 1px solid #e0e0e0;
}

.btn-view-job {
    background: var(--primary-color);
    color: white;
    padding: 8px 20px;
    text-decoration: none;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.btn-view-job:hover {
    background: var(--secondary-color);
}

.btn-withdraw {
    background: #dc3545;
    color: white;
    padding: 8px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
}

.btn-withdraw:hover {
    background: #c82333;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 15px;
}

.empty-state i {
    font-size: 60px;
    color: #ccc;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: var(--primary-color);
    margin-bottom: 10px;
}

.empty-state p {
    color: #666;
    margin-bottom: 20px;
}

/* Responsive */
@media (max-width: 768px) {
    .stats-container {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .application-header {
        flex-direction: column;
        text-align: center;
    }
    
    .company-info {
        flex-direction: column;
        gap: 10px;
    }
    
    .application-actions {
        flex-direction: column;
    }
    
    .btn-view-job, .btn-withdraw {
        text-align: center;
    }
}
</style>

<section class="my-applications-section">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-file-alt"></i> My Applications</h1>
            <p>Track and manage your job applications</p>
        </div>
        
        <!-- Statistics -->
        <div class="stats-container">
            <div class="stat-box" onclick="filterByStatus('all')">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Applications</div>
            </div>
            <div class="stat-box" onclick="filterByStatus('pending')">
                <div class="stat-number"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-box" onclick="filterByStatus('shortlisted')">
                <div class="stat-number"><?php echo $stats['shortlisted']; ?></div>
                <div class="stat-label">Shortlisted</div>
            </div>
            <div class="stat-box" onclick="filterByStatus('hired')">
                <div class="stat-number"><?php echo $stats['hired']; ?></div>
                <div class="stat-label">Hired</div>
            </div>
        </div>
        
        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?status=all" class="filter-tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All</a>
            <a href="?status=pending" class="filter-tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="?status=reviewed" class="filter-tab <?php echo $status_filter == 'reviewed' ? 'active' : ''; ?>">Reviewed</a>
            <a href="?status=shortlisted" class="filter-tab <?php echo $status_filter == 'shortlisted' ? 'active' : ''; ?>">Shortlisted</a>
            <a href="?status=rejected" class="filter-tab <?php echo $status_filter == 'rejected' ? 'active' : ''; ?>">Rejected</a>
            <a href="?status=hired" class="filter-tab <?php echo $status_filter == 'hired' ? 'active' : ''; ?>">Hired</a>
        </div>
        
        <!-- Applications List -->
        <?php if(empty($applications)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No applications found</h3>
                <p>You haven't applied for any jobs yet.</p>
                <a href="jobs.php" class="btn-submit" style="display: inline-block; width: auto; padding: 12px 30px;">
                    <i class="fas fa-search"></i> Browse Jobs
                </a>
            </div>
        <?php else: ?>
            <div class="applications-list">
                <?php foreach($applications as $app): ?>
                    <div class="application-card" id="app-<?php echo $app['id']; ?>">
                        <div class="application-header">
                            <h3>
                                <a href="job-details.php?id=<?php echo $app['job_id']; ?>">
                                    <?php echo htmlspecialchars($app['title']); ?>
                                </a>
                            </h3>
                            <span class="status-badge status-<?php echo $app['status']; ?>">
                                <?php echo ucfirst($app['status']); ?>
                            </span>
                        </div>
                        
                        <div class="application-body">
                            <div class="company-info">
                                <span><i class="fas fa-building"></i> <?php echo htmlspecialchars($app['company_name']); ?></span>
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($app['location']); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo ucfirst($app['type']); ?></span>
                            </div>
                            
                            <div class="application-details">
                                <?php if($app['cover_letter']): ?>
                                    <p><strong>Cover Letter:</strong> <?php echo nl2br(htmlspecialchars(substr($app['cover_letter'], 0, 200))) . (strlen($app['cover_letter']) > 200 ? '...' : ''); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="application-meta">
                                <span><i class="fas fa-calendar-alt"></i> Applied: <?php echo date('F d, Y', strtotime($app['applied_date'])); ?></span>
                                <?php if($app['status'] == 'shortlisted'): ?>
                                    <span><i class="fas fa-star"></i> Congratulations! You've been shortlisted</span>
                                <?php elseif($app['status'] == 'hired'): ?>
                                    <span><i class="fas fa-trophy"></i> You've been hired! Check your email</span>
                                <?php elseif($app['status'] == 'rejected'): ?>
                                    <span><i class="fas fa-sad-tear"></i> Not selected this time. Keep trying!</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="application-actions">
                            <a href="job-details.php?id=<?php echo $app['job_id']; ?>" class="btn-view-job">
                                <i class="fas fa-eye"></i> View Job Details
                            </a>
                            <?php if($app['status'] == 'pending'): ?>
                                <button onclick="withdrawApplication(<?php echo $app['id']; ?>)" class="btn-withdraw">
                                    <i class="fas fa-trash"></i> Withdraw Application
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterByStatus(status) {
    window.location.href = 'my-applications.php?status=' + status;
}

function withdrawApplication(applicationId) {
    if (confirm('Are you sure you want to withdraw this application? This action cannot be undone.')) {
        $.ajax({
            url: 'ajax/withdraw-application.php',
            method: 'POST',
            data: { application_id: applicationId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#app-' + applicationId).fadeOut(300, function() {
                        $(this).remove();
                        showNotification('success', 'Application withdrawn successfully');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    });
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function() {
                showNotification('error', 'Something went wrong. Please try again.');
            }
        });
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