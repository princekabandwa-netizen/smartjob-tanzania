<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'];

date_default_timezone_set('Africa/Dar_es_Salaam');

// Get profile completion from session or calculate
$profileCompletion = isset($_SESSION['profile_completion']) ? $_SESSION['profile_completion'] : calculateProfileCompletion($pdo, $user_id, $role);

if ($role == 'employer') {
    // ============================================
    // EMPLOYER DASHBOARD STATISTICS
    // ============================================
    
    $stmt = $pdo->prepare("SELECT 
        (SELECT COUNT(*) FROM jobs WHERE employer_id = ?) as total_jobs,
        (SELECT COUNT(*) FROM jobs WHERE employer_id = ? AND status = 'active' AND deadline >= CURDATE()) as active_jobs,
        (SELECT COUNT(*) FROM jobs WHERE employer_id = ? AND status = 'closed') as closed_jobs,
        (SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ?) as total_applications,
        (SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ? AND a.status = 'pending') as pending_applications,
        (SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ? AND a.status = 'shortlisted') as shortlisted_applications,
        (SELECT COUNT(*) FROM interview_schedules i JOIN jobs j ON i.job_id = j.id WHERE j.employer_id = ?) as total_interviews
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
    $stats = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE employer_id = ? ORDER BY posted_date DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $recentJobs = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT a.*, j.title, j.company_name, u.full_name as applicant_name, u.email as applicant_email
                           FROM applications a 
                           JOIN jobs j ON a.job_id = j.id 
                           JOIN users u ON a.jobseeker_id = u.id
                           WHERE j.employer_id = ? 
                           ORDER BY a.applied_date DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $recentApplications = $stmt->fetchAll();
    
    // Upcoming interviews for employer
    $stmt = $pdo->prepare("SELECT i.*, j.title, u.full_name as candidate_name 
                           FROM interview_schedules i 
                           JOIN jobs j ON i.job_id = j.id 
                           JOIN users u ON i.jobseeker_id = u.id 
                           WHERE j.employer_id = ? AND i.interview_date >= CURDATE() 
                           ORDER BY i.interview_date ASC LIMIT 3");
    $stmt->execute([$user_id]);
    $upcomingInterviews = $stmt->fetchAll();
    
} else {
    // ============================================
    // JOB SEEKER DASHBOARD STATISTICS
    // ============================================
    
    $stmt = $pdo->prepare("SELECT 
        (SELECT COUNT(*) FROM applications WHERE jobseeker_id = ?) as total_applications,
        (SELECT COUNT(*) FROM applications WHERE jobseeker_id = ? AND status = 'pending') as pending_count,
        (SELECT COUNT(*) FROM applications WHERE jobseeker_id = ? AND status = 'reviewed') as reviewed_count,
        (SELECT COUNT(*) FROM applications WHERE jobseeker_id = ? AND status = 'shortlisted') as shortlisted_count,
        (SELECT COUNT(*) FROM applications WHERE jobseeker_id = ? AND status = 'rejected') as rejected_count,
        (SELECT COUNT(*) FROM applications WHERE jobseeker_id = ? AND status = 'hired') as hired_count,
        (SELECT COUNT(*) FROM saved_jobs WHERE user_id = ?) as saved_jobs_count,
        (SELECT COUNT(*) FROM job_alerts WHERE user_id = ? AND is_active = 1) as active_alerts,
        (SELECT COUNT(*) FROM interview_schedules WHERE jobseeker_id = ?) as total_interviews
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
    $stats = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT a.*, j.title, j.company_name, j.location, j.type
                           FROM applications a 
                           JOIN jobs j ON a.job_id = j.id 
                           WHERE a.jobseeker_id = ? 
                           ORDER BY a.applied_date DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $recentApplications = $stmt->fetchAll();
    
    // Upcoming interviews for job seeker
    $stmt = $pdo->prepare("SELECT i.*, j.title, j.company_name 
                           FROM interview_schedules i 
                           JOIN jobs j ON i.job_id = j.id 
                           WHERE i.jobseeker_id = ? AND i.interview_date >= CURDATE() 
                           ORDER BY i.interview_date ASC LIMIT 3");
    $stmt->execute([$user_id]);
    $upcomingInterviews = $stmt->fetchAll();
    
    // Recommended jobs based on user's activity
    $stmt = $pdo->prepare("SELECT DISTINCT j.category FROM applications a 
                           JOIN jobs j ON a.job_id = j.id 
                           WHERE a.jobseeker_id = ? LIMIT 3");
    $stmt->execute([$user_id]);
    $userCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($userCategories)) {
        $placeholders = implode(',', array_fill(0, count($userCategories), '?'));
        $params = array_merge($userCategories, [$user_id]);
        $sql = "SELECT j.*, u.full_name as employer_name 
                FROM jobs j 
                JOIN users u ON j.employer_id = u.id 
                WHERE j.status = 'active' AND j.deadline >= CURDATE()
                AND j.category IN ($placeholders)
                AND j.id NOT IN (SELECT job_id FROM applications WHERE jobseeker_id = ?)
                ORDER BY j.posted_date DESC LIMIT 4";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        $stmt = $pdo->prepare("SELECT j.*, u.full_name as employer_name 
                               FROM jobs j 
                               JOIN users u ON j.employer_id = u.id 
                               WHERE j.status = 'active' AND j.deadline >= CURDATE()
                               AND j.id NOT IN (SELECT job_id FROM applications WHERE jobseeker_id = ?)
                               ORDER BY j.posted_date DESC LIMIT 4");
        $stmt->execute([$user_id]);
    }
    $recommendedJobs = $stmt->fetchAll();
}

include 'includes/header.php';
?>

<style>
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

/* Welcome Banner */
.welcome-banner {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    color: white;
    position: relative;
    overflow: hidden;
}

.welcome-banner::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    transform: rotate(25deg);
}

.welcome-content h1 {
    font-size: 1.8rem;
    margin-bottom: 10px;
}

.welcome-content p {
    opacity: 0.9;
    margin-bottom: 20px;
}

.welcome-stats {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.welcome-stat {
    background: rgba(255,255,255,0.2);
    padding: 8px 20px;
    border-radius: 12px;
    text-align: center;
}

.welcome-stat .number {
    font-size: 1.3rem;
    font-weight: bold;
}

.welcome-stat .label {
    font-size: 0.8rem;
    opacity: 0.9;
}

/* Profile Completion Card */
.profile-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.profile-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 10px;
}

.profile-header h3 {
    margin: 0;
    color: var(--primary-color);
    font-size: 1.1rem;
}

.profile-header h3 i {
    margin-right: 8px;
    color: var(--secondary-color);
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.progress-container {
    background: #e0e0e0;
    border-radius: 10px;
    height: 10px;
    overflow: hidden;
    margin: 15px 0;
}

.progress-bar {
    background: linear-gradient(90deg, var(--secondary-color), #f39c12);
    height: 100%;
    border-radius: 10px;
    transition: width 0.5s ease;
}

.profile-tips {
    background: #fff3cd;
    padding: 12px;
    border-radius: 10px;
    margin-top: 15px;
    font-size: 13px;
    color: #856404;
}

.profile-tips ul {
    margin: 8px 0 0 20px;
}

.btn-apply-sm {
    display: inline-block;
    margin-top: 10px;
    padding: 8px 20px;
    background: var(--secondary-color);
    color: white;
    border-radius: 8px;
    font-size: 13px;
    text-decoration: none;
    transition: all 0.3s;
}

.btn-apply-sm:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: all 0.3s;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 55px;
    height: 55px;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.stat-info h3 {
    font-size: 28px;
    margin-bottom: 5px;
    color: var(--primary-color);
}

.stat-info p {
    color: #666;
    font-size: 13px;
    margin: 0;
}

.stat-info small {
    font-size: 11px;
    color: #999;
}

/* Dashboard Layout */
.dashboard-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 25px;
}

/* Cards */
.card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.card-header {
    padding: 18px 20px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.card-header h3 {
    margin: 0;
    font-size: 1.1rem;
    color: var(--primary-color);
}

.card-header h3 i {
    margin-right: 8px;
    color: var(--secondary-color);
}

.card-header a {
    color: var(--secondary-color);
    text-decoration: none;
    font-size: 13px;
}

.card-body {
    padding: 20px;
}

/* List Items */
.list-item {
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.2s;
}

.list-item:last-child {
    border-bottom: none;
}

.list-item:hover {
    background: #f8f9fa;
    padding-left: 10px;
}

.item-title {
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--primary-color);
}

.item-meta {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    font-size: 12px;
    color: #666;
}

.item-meta i {
    margin-right: 5px;
    color: var(--secondary-color);
}

/* Status Badges */
.status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-reviewed { background: #d1ecf1; color: #0c5460; }
.status-shortlisted { background: #d4edda; color: #155724; }
.status-rejected { background: #f8d7da; color: #721c24; }
.status-hired { background: #d4edda; color: #155724; }
.status-active { background: #d4edda; color: #155724; }
.status-closed { background: #e2e3e5; color: #383d41; }

/* Quick Actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 25px;
}

.quick-btn {
    background: white;
    padding: 15px;
    text-align: center;
    border-radius: 12px;
    transition: all 0.3s;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.quick-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.quick-btn i {
    font-size: 28px;
    color: var(--secondary-color);
    margin-bottom: 8px;
    display: block;
}

.quick-btn span {
    display: block;
    color: var(--primary-color);
    font-weight: 500;
    font-size: 13px;
}

/* Recommended Jobs */
.recommended-list {
    display: grid;
    gap: 12px;
}

.recommended-item {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 12px;
    transition: all 0.3s;
}

.recommended-item:hover {
    background: white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.recommended-item h4 {
    margin-bottom: 8px;
    color: var(--primary-color);
    font-size: 0.95rem;
}

.recommended-item p {
    margin-bottom: 5px;
    font-size: 12px;
    color: #666;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

/* Responsive */
@media (max-width: 992px) {
    .dashboard-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
    
    .welcome-stats {
        gap: 10px;
    }
    
    .welcome-stat {
        padding: 5px 15px;
    }
    
    .welcome-stat .number {
        font-size: 1rem;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .card-header {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<div class="dashboard-container">
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div class="welcome-content">
            <h1><i class="fas fa-hand-wave"></i> Welcome back, <?php echo htmlspecialchars($full_name); ?>!</h1>
            <p>Track your <?php echo ($role == 'employer') ? 'job postings and candidates' : 'job applications and career progress'; ?>.</p>
            <div class="welcome-stats">
                <div class="welcome-stat">
                    <div class="number"><?php echo date('d'); ?></div>
                    <div class="label">Day</div>
                </div>
                <div class="welcome-stat">
                    <div class="number"><?php echo date('F'); ?></div>
                    <div class="label">Month</div>
                </div>
                <div class="welcome-stat">
                    <div class="number"><?php echo date('Y'); ?></div>
                    <div class="label">Year</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Completion Card -->
    <div class="profile-card">
        <div class="profile-header">
            <h3><i class="fas fa-chart-line"></i> Profile Strength</h3>
            <span class="status-badge" style="background: <?php echo $profileCompletion >= 80 ? '#d4edda' : ($profileCompletion >= 50 ? '#fff3cd' : '#f8d7da'); ?>; color: <?php echo $profileCompletion >= 80 ? '#155724' : ($profileCompletion >= 50 ? '#856404' : '#721c24'); ?>;">
                <?php echo $profileCompletion; ?>% Complete
            </span>
        </div>
        <div class="progress-container">
            <div class="progress-bar" style="width: <?php echo $profileCompletion; ?>%;"></div>
        </div>
        <?php if($profileCompletion < 100): ?>
        <div class="profile-tips">
            <i class="fas fa-info-circle"></i>
            <strong>Complete your profile to get better opportunities!</strong>
            <ul>
                <?php if($profileCompletion < 30): ?>
                    <li>✓ Add your phone number</li>
                <?php endif; ?>
                <?php if($profileCompletion < 50): ?>
                    <li>✓ Add your professional bio and skills</li>
                <?php endif; ?>
                <?php if($profileCompletion < 70): ?>
                    <li>✓ Add your work experience and education</li>
                <?php endif; ?>
                <?php if($profileCompletion < 90): ?>
                    <li>✓ Add certifications</li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
        <div style="margin-top: 15px;">
            <a href="profile.php" class="btn-apply-sm" style="background: var(--primary-color);">Update Profile <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>

    <!-- Statistics Grid -->
    <?php if($role == 'employer'): ?>
    <div class="stats-grid">
        <div class="stat-card" onclick="window.location.href='post-job.php'">
            <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
            <div class="stat-info">
                <h3><?php echo $stats['total_jobs']; ?></h3>
                <p>Total Jobs</p>
                <small><?php echo $stats['active_jobs']; ?> active | <?php echo $stats['closed_jobs']; ?> closed</small>
            </div>
        </div>
        <div class="stat-card" onclick="window.location.href='manage-applications.php'">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <h3><?php echo $stats['total_applications']; ?></h3>
                <p>Total Applications</p>
            </div>
        </div>
        <div class="stat-card" onclick="window.location.href='manage-applications.php?status=pending'">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <h3><?php echo $stats['pending_applications']; ?></h3>
                <p>Pending Review</p>
            </div>
        </div>
        <div class="stat-card" onclick="window.location.href='schedule-interview.php'">
            <div class="stat-icon"><i class="fas fa-calendar"></i></div>
            <div class="stat-info">
                <h3><?php echo $stats['total_interviews']; ?></h3>
                <p>Scheduled Interviews</p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="stats-grid">
        <div class="stat-card" onclick="window.location.href='my-applications.php'">
            <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            <div class="stat-info">
                <h3><?php echo $stats['total_applications']; ?></h3>
                <p>Applications Sent</p>
            </div>
        </div>
        <div class="stat-card" onclick="window.location.href='my-applications.php?status=pending'">
            <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-info">
                <h3><?php echo $stats['pending_count']; ?></h3>
                <p>In Review</p>
            </div>
        </div>
        <div class="stat-card" onclick="window.location.href='my-applications.php?status=shortlisted'">
            <div class="stat-icon"><i class="fas fa-star"></i></div>
            <div class="stat-info">
                <h3><?php echo $stats['shortlisted_count']; ?></h3>
                <p>Shortlisted</p>
            </div>
        </div>
        <div class="stat-card" onclick="window.location.href='saved-jobs.php'">
            <div class="stat-icon"><i class="fas fa-bookmark"></i></div>
            <div class="stat-info">
                <h3><?php echo $stats['saved_jobs_count']; ?></h3>
                <p>Saved Jobs</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Dashboard Layout -->
    <div class="dashboard-layout">
        <!-- Left Column -->
        <div class="left-column">
            <?php if($role == 'employer'): ?>
                <!-- Recent Jobs -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-briefcase"></i> Recent Job Postings</h3>
                        <a href="post-job.php"><i class="fas fa-plus"></i> Post New Job</a>
                    </div>
                    <div class="card-body">
                        <?php if(empty($recentJobs)): ?>
                            <div class="empty-state">
                                <i class="fas fa-briefcase"></i>
                                <p>No jobs posted yet</p>
                                <a href="post-job.php" class="btn-apply-sm">Post Your First Job</a>
                            </div>
                        <?php else: ?>
                            <?php foreach($recentJobs as $job): ?>
                                <div class="list-item">
                                    <div class="item-title"><?php echo htmlspecialchars($job['title']); ?></div>
                                    <div class="item-meta">
                                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                                        <span><i class="fas fa-clock"></i> <?php echo ucfirst($job['type']); ?></span>
                                        <span class="status-badge status-<?php echo $job['status']; ?>"><?php echo ucfirst($job['status']); ?></span>
                                    </div>
                                    <div style="margin-top: 10px;">
                                        <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn-apply-sm" style="background: var(--primary-color); padding: 4px 12px;">View</a>
                                        <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="btn-apply-sm" style="background: #ffc107; color: #333; padding: 4px 12px;">Edit</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Applications -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-users"></i> Recent Applications</h3>
                        <a href="manage-applications.php">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if(empty($recentApplications)): ?>
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <p>No applications yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($recentApplications as $app): ?>
                                <div class="list-item">
                                    <div class="item-title"><?php echo htmlspecialchars($app['applicant_name']); ?></div>
                                    <div class="item-meta">
                                        <span><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($app['title']); ?></span>
                                        <span><i class="fas fa-calendar"></i> <?php echo date('M d', strtotime($app['applied_date'])); ?></span>
                                        <span class="status-badge status-<?php echo $app['status']; ?>"><?php echo ucfirst($app['status']); ?></span>
                                    </div>
                                    <div style="margin-top: 10px;">
                                        <a href="view-application.php?id=<?php echo $app['id']; ?>" class="btn-apply-sm" style="background: var(--primary-color); padding: 4px 12px;">Review</a>
                                        <a href="schedule-interview.php?application_id=<?php echo $app['id']; ?>" class="btn-apply-sm" style="background: #27ae60; padding: 4px 12px;">Schedule Interview</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Recent Applications for Job Seeker -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-alt"></i> My Applications</h3>
                        <a href="my-applications.php">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if(empty($recentApplications)): ?>
                            <div class="empty-state">
                                <i class="fas fa-file-alt"></i>
                                <p>No applications yet</p>
                                <a href="jobs.php" class="btn-apply-sm">Browse Jobs</a>
                            </div>
                        <?php else: ?>
                            <?php foreach($recentApplications as $app): ?>
                                <div class="list-item">
                                    <div class="item-title"><?php echo htmlspecialchars($app['title']); ?></div>
                                    <div class="item-meta">
                                        <span><i class="fas fa-building"></i> <?php echo htmlspecialchars($app['company_name']); ?></span>
                                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($app['location']); ?></span>
                                        <span class="status-badge status-<?php echo $app['status']; ?>"><?php echo ucfirst($app['status']); ?></span>
                                    </div>
                                    <div style="margin-top: 10px;">
                                        <a href="job-details.php?id=<?php echo $app['job_id']; ?>" class="btn-apply-sm" style="background: var(--primary-color); padding: 4px 12px;">View Job</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recommended Jobs -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-star"></i> Recommended For You</h3>
                        <a href="jobs.php">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if(empty($recommendedJobs)): ?>
                            <div class="empty-state">
                                <i class="fas fa-star"></i>
                                <p>Apply to jobs to get recommendations</p>
                                <a href="jobs.php" class="btn-apply-sm">Browse Jobs</a>
                            </div>
                        <?php else: ?>
                            <div class="recommended-list">
                                <?php foreach($recommendedJobs as $job): ?>
                                    <div class="recommended-item">
                                        <h4><?php echo htmlspecialchars($job['title']); ?></h4>
                                        <p><i class="fas fa-building"></i> <?php echo htmlspecialchars($job['company_name']); ?></p>
                                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?></p>
                                        <a href="apply-job.php?id=<?php echo $job['id']; ?>" class="btn-apply-sm">Apply Now</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column -->
        <div class="right-column">
            <!-- Quick Actions -->
            <div class="quick-actions">
                <?php if($role == 'employer'): ?>
                    <a href="post-job.php" class="quick-btn">
                        <i class="fas fa-plus-circle"></i>
                        <span>Post a Job</span>
                    </a>
                    <a href="find-candidates.php" class="quick-btn">
                        <i class="fas fa-search"></i>
                        <span>Find Candidates</span>
                    </a>
                    <a href="manage-applications.php" class="quick-btn">
                        <i class="fas fa-users"></i>
                        <span>Applications</span>
                    </a>
                    <a href="company-profile.php" class="quick-btn">
                        <i class="fas fa-building"></i>
                        <span>Company Profile</span>
                    </a>
                <?php else: ?>
                    <a href="jobs.php" class="quick-btn">
                        <i class="fas fa-search"></i>
                        <span>Browse Jobs</span>
                    </a>
                    <a href="my-applications.php" class="quick-btn">
                        <i class="fas fa-file-alt"></i>
                        <span>My Applications</span>
                    </a>
                    <a href="saved-jobs.php" class="quick-btn">
                        <i class="fas fa-bookmark"></i>
                        <span>Saved Jobs</span>
                    </a>
                    <a href="job-alerts.php" class="quick-btn">
                        <i class="fas fa-bell"></i>
                        <span>Job Alerts</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Upcoming Interviews -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-alt"></i> Upcoming Interviews</h3>
                    <a href="<?php echo ($role == 'employer') ? 'manage-interviews.php' : 'my-interviews.php'; ?>">View All</a>
                </div>
                <div class="card-body">
                    <?php if(empty($upcomingInterviews)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar"></i>
                            <p>No upcoming interviews</p>
                            <?php if($role == 'employer'): ?>
                                <a href="manage-applications.php" class="btn-apply-sm">Review Applications</a>
                            <?php else: ?>
                                <a href="jobs.php" class="btn-apply-sm">Browse Jobs</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach($upcomingInterviews as $interview): ?>
                            <div class="list-item">
                                <div class="item-title">
                                    <?php if($role == 'employer'): ?>
                                        <?php echo htmlspecialchars($interview['candidate_name']); ?> - <?php echo htmlspecialchars($interview['title']); ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($interview['title']); ?> at <?php echo htmlspecialchars($interview['company_name']); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="item-meta">
                                    <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($interview['interview_date'])); ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($interview['interview_time'])); ?></span>
                                    <span><i class="fas fa-video"></i> <?php echo ucfirst($interview['interview_type']); ?></span>
                                </div>
                                <div style="margin-top: 10px;">
                                    <a href="interview-details.php?id=<?php echo $interview['id']; ?>" class="btn-apply-sm" style="background: var(--primary-color); padding: 4px 12px;">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Active Job Alerts (Job Seekers Only) -->
            <?php if($role == 'jobseeker'): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-bell"></i> Job Alerts</h3>
                    <a href="job-alerts.php">Manage</a>
                </div>
                <div class="card-body">
                    <div class="list-item" style="text-align: center;">
                        <div class="item-title">You have <?php echo $stats['active_alerts']; ?> active job alerts</div>
                        <div class="item-meta" style="justify-content: center;">
                            <span><i class="fas fa-envelope"></i> Get notified when new jobs match your preferences</span>
                        </div>
                        <div style="margin-top: 10px;">
                            <a href="job-alerts.php" class="btn-apply-sm">Manage Alerts</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>