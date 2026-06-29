<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();
redirectIfNotEmployer();

$user_id = $_SESSION['user_id'];
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get employer's jobs
$jobs_stmt = $pdo->prepare("SELECT id, title FROM jobs WHERE employer_id = ? ORDER BY posted_date DESC");
$jobs_stmt->execute([$user_id]);
$employer_jobs = $jobs_stmt->fetchAll();

// Build applications query - FIXED: Specify which table's status column
$query = "SELECT a.*, j.title as job_title, j.company_name, j.status as job_status, 
          u.full_name as applicant_name, u.email as applicant_email, u.phone as applicant_phone
          FROM applications a 
          JOIN jobs j ON a.job_id = j.id 
          JOIN users u ON a.jobseeker_id = u.id 
          WHERE j.employer_id = ?";
$params = [$user_id];

if ($job_id > 0) {
    $query .= " AND a.job_id = ?";
    $params[] = $job_id;
}

if ($status_filter != 'all') {
    // Specify that status is from applications table
    $query .= " AND a.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY a.applied_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll();

// Get statistics - FIXED: Specify table names
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN a.status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
    SUM(CASE WHEN a.status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted,
    SUM(CASE WHEN a.status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN a.status = 'hired' THEN 1 ELSE 0 END) as hired
    FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    WHERE j.employer_id = ?";
$stats_stmt = $pdo->prepare($stats_query);
$stats_stmt->execute([$user_id]);
$stats = $stats_stmt->fetch();

include 'includes/header.php';
?>

<style>
.manage-applications-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
    min-height: calc(100vh - 200px);
}

.applications-header {
    text-align: center;
    margin-bottom: 40px;
}

.applications-header h1 {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s;
    cursor: pointer;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
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
    cursor: pointer;
}

.applications-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
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
}

td {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}

tr:hover {
    background: #f8f9fa;
}

.applicant-info h4 {
    margin-bottom: 5px;
    color: var(--primary-color);
}

.applicant-info p {
    font-size: 13px;
    color: #666;
    margin: 3px 0;
}

.status-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-reviewed { background: #d1ecf1; color: #0c5460; }
.status-shortlisted { background: #d4edda; color: #155724; }
.status-rejected { background: #f8d7da; color: #721c24; }
.status-hired { background: #d4edda; color: #155724; }

.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-action {
    padding: 5px 12px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.3s;
}

.btn-review { background: #3498db; color: white; }
.btn-shortlist { background: #27ae60; color: white; }
.btn-reject { background: #e74c3c; color: white; }
.btn-hire { background: #9c27b0; color: white; }
.btn-view { background: #95a5a6; color: white; }

.btn-action:hover {
    transform: translateY(-1px);
    opacity: 0.9;
}

.no-applications {
    text-align: center;
    padding: 60px;
    color: #999;
}

.no-applications i {
    font-size: 60px;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .applications-table {
        overflow-x: auto;
    }
    
    table {
        min-width: 800px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<section class="manage-applications-section">
    <div class="container">
        <div class="applications-header">
            <h1><i class="fas fa-users"></i> Manage Applications</h1>
            <p>Review and manage candidate applications</p>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card" onclick="filterByStatus('all')">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Applications</div>
            </div>
            <div class="stat-card" onclick="filterByStatus('pending')">
                <div class="stat-number"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-card" onclick="filterByStatus('shortlisted')">
                <div class="stat-number"><?php echo $stats['shortlisted']; ?></div>
                <div class="stat-label">Shortlisted</div>
            </div>
            <div class="stat-card" onclick="filterByStatus('hired')">
                <div class="stat-number"><?php echo $stats['hired']; ?></div>
                <div class="stat-label">Hired</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters-bar">
            <div class="filter-group">
                <label><i class="fas fa-briefcase"></i> Job:</label>
                <select id="jobFilter" onchange="filterApplications()">
                    <option value="0">All Jobs</option>
                    <?php foreach($employer_jobs as $job): ?>
                        <option value="<?php echo $job['id']; ?>" <?php echo $job_id == $job['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($job['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label><i class="fas fa-filter"></i> Status:</label>
                <select id="statusFilter" onchange="filterApplications()">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="reviewed" <?php echo $status_filter == 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                    <option value="shortlisted" <?php echo $status_filter == 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                    <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="hired" <?php echo $status_filter == 'hired' ? 'selected' : ''; ?>>Hired</option>
                </select>
            </div>
        </div>
        
        <!-- Applications Table -->
        <div class="applications-table">
            <?php if(empty($applications)): ?>
                <div class="no-applications">
                    <i class="fas fa-inbox"></i>
                    <h3>No applications found</h3>
                    <p>When candidates apply for your jobs, they'll appear here.</p>
                    <a href="post-job.php" class="btn-submit" style="display: inline-block; width: auto; margin-top: 20px;">
                        <i class="fas fa-plus-circle"></i> Post a Job
                    </a>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Job Position</th>
                            <th>Applied Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($applications as $app): ?>
                            <tr id="app-row-<?php echo $app['id']; ?>">
                                <td>
                                    <div class="applicant-info">
                                        <h4><?php echo htmlspecialchars($app['applicant_name']); ?></h4>
                                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($app['applicant_email']); ?></p>
                                        <?php if($app['applicant_phone']): ?>
                                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($app['applicant_phone']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($app['job_title']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($app['company_name']); ?></small>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($app['applied_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $app['status']; ?>">
                                        <?php echo ucfirst($app['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-view" onclick="viewApplication(<?php echo $app['id']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        
                                        <?php if($app['status'] == 'pending'): ?>
                                            <button class="btn-action btn-review" onclick="updateStatus(<?php echo $app['id']; ?>, 'reviewed')">
                                                <i class="fas fa-check"></i> Review
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if($app['status'] == 'reviewed'): ?>
                                            <button class="btn-action btn-shortlist" onclick="updateStatus(<?php echo $app['id']; ?>, 'shortlisted')">
                                                <i class="fas fa-star"></i> Shortlist
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if($app['status'] == 'shortlisted'): ?>
                                            <button class="btn-action btn-hire" onclick="updateStatus(<?php echo $app['id']; ?>, 'hired')">
                                                <i class="fas fa-trophy"></i> Hire
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if(in_array($app['status'], ['pending', 'reviewed'])): ?>
                                            <button class="btn-action btn-reject" onclick="updateStatus(<?php echo $app['id']; ?>, 'rejected')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- View Application Modal -->
<div id="viewApplicationModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px; margin: 5% auto; background: white; border-radius: 15px;">
        <div class="modal-header" style="padding: 20px; background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); color: white; border-radius: 15px 15px 0 0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;"><i class="fas fa-file-alt"></i> Application Details</h3>
            <span class="close" onclick="closeModal()" style="cursor: pointer; font-size: 24px;">&times;</span>
        </div>
        <div class="modal-body" id="applicationDetails" style="padding: 25px;">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<script>
function filterApplications() {
    var jobId = $('#jobFilter').val();
    var status = $('#statusFilter').val();
    window.location.href = 'manage-applications.php?job_id=' + jobId + '&status=' + status;
}

function filterByStatus(status) {
    window.location.href = 'manage-applications.php?status=' + status;
}

function updateStatus(applicationId, status) {
    var statusText = '';
    switch(status) {
        case 'reviewed': statusText = 'mark as reviewed'; break;
        case 'shortlisted': statusText = 'shortlist this candidate'; break;
        case 'rejected': statusText = 'reject this candidate'; break;
        case 'hired': statusText = 'hire this candidate'; break;
        default: statusText = 'update status';
    }
    
    if (confirm('Are you sure you want to ' + statusText + '?')) {
        $.ajax({
            url: 'ajax/update-application-status.php',
            method: 'POST',
            data: { application_id: applicationId, status: status },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('success', 'Application ' + response.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
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

function viewApplication(applicationId) {
    $('#viewApplicationModal').fadeIn(300);
    $('#applicationDetails').html('<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
    
    $.ajax({
        url: 'ajax/get-application-details.php',
        method: 'GET',
        data: { id: applicationId },
        success: function(response) {
            $('#applicationDetails').html(response);
        },
        error: function() {
            $('#applicationDetails').html('<div style="text-align: center; padding: 40px; color: #e74c3c;"><i class="fas fa-exclamation-circle"></i> Failed to load application details</div>');
        }
    });
}

function closeModal() {
    $('#viewApplicationModal').fadeOut(300);
}

// Close modal when clicking outside
$(window).on('click', function(event) {
    if ($(event.target).is('#viewApplicationModal')) {
        closeModal();
    }
});

// Toast notification function
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