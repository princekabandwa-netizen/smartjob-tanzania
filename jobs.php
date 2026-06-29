<?php
require_once 'includes/init.php';

// Get filter parameters
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$job_type = isset($_GET['job_type']) ? trim($_GET['job_type']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT j.*, u.full_name as employer_name FROM jobs j 
          JOIN users u ON j.employer_id = u.id 
          WHERE j.status = 'active' AND j.deadline >= CURDATE()";
$count_query = "SELECT COUNT(*) FROM jobs j WHERE j.status = 'active' AND j.deadline >= CURDATE()";
$params = [];

if (!empty($keyword)) {
    $query .= " AND (j.title LIKE ? OR j.company_name LIKE ? OR j.description LIKE ?)";
    $count_query .= " AND (j.title LIKE ? OR j.company_name LIKE ? OR j.description LIKE ?)";
    $search_param = "%$keyword%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($category)) {
    $query .= " AND j.category = ?";
    $count_query .= " AND j.category = ?";
    $params[] = $category;
}

if (!empty($location)) {
    $query .= " AND j.location LIKE ?";
    $count_query .= " AND j.location LIKE ?";
    $params[] = "%$location%";
}

if (!empty($job_type)) {
    $query .= " AND j.type = ?";
    $count_query .= " AND j.type = ?";
    $params[] = $job_type;
}

$query .= " ORDER BY j.posted_date DESC LIMIT $limit OFFSET $offset";

// Execute queries
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_jobs = $count_stmt->fetchColumn();
$total_pages = ceil($total_jobs / $limit);

// Get all categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Get unique locations for filter
$locations = $pdo->query("SELECT DISTINCT location FROM jobs WHERE status = 'active' ORDER BY location")->fetchAll();

// Get saved jobs for logged in user
$saved_jobs = [];
if (isLoggedIn() && isJobSeeker()) {
    $stmt = $pdo->prepare("SELECT job_id FROM saved_jobs WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $saved_jobs = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

include 'includes/header.php';
?>

<style>
.jobs-browse-section {
    padding: 60px 0;
    background: #f8f9fa;
}

.jobs-header {
    text-align: center;
    margin-bottom: 40px;
}

.jobs-header h1 {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.jobs-header p {
    color: #666;
}

.jobs-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 30px;
}

/* Filters Sidebar */
.filters-sidebar {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    position: sticky;
    top: 100px;
    height: fit-content;
}

.filter-card h3 {
    margin-bottom: 20px;
    color: var(--primary-color);
    font-size: 1.2rem;
}

.filter-group {
    margin-bottom: 20px;
}

.filter-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--dark-color);
    font-size: 14px;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.filter-group input:focus,
.filter-group select:focus {
    outline: none;
    border-color: var(--secondary-color);
}

.btn-apply-filters {
    width: 100%;
    padding: 12px;
    background: var(--secondary-color);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    margin-bottom: 10px;
    transition: all 0.3s;
}

.btn-apply-filters:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

.btn-reset-filters {
    display: block;
    text-align: center;
    padding: 10px;
    background: #f0f0f0;
    color: var(--dark-color);
    text-decoration: none;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.btn-reset-filters:hover {
    background: #e0e0e0;
}

/* Jobs Content */
.jobs-content {
    background: transparent;
}

.jobs-count {
    background: white;
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

/* Job Card */
.job-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.job-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.job-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.company-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.job-type {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.job-type.full-time { background: #e8f5e9; color: #4caf50; }
.job-type.part-time { background: #fff3e0; color: #ff9800; }
.job-type.contract { background: #e3f2fd; color: #2196f3; }
.job-type.internship { background: #f3e5f5; color: #9c27b0; }

.job-title {
    font-size: 1.3rem;
    margin-bottom: 8px;
}

.job-title a {
    color: var(--primary-color);
    text-decoration: none;
}

.job-title a:hover {
    color: var(--secondary-color);
}

.company-name {
    color: #666;
    margin-bottom: 12px;
    font-size: 14px;
}

.job-details {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 15px;
    font-size: 14px;
    color: #666;
}

.job-details i {
    margin-right: 5px;
    color: var(--secondary-color);
}

.job-description {
    color: #666;
    line-height: 1.6;
    margin-bottom: 20px;
    font-size: 14px;
}

.job-card-footer {
    display: flex;
    gap: 12px;
    align-items: center;
}

.btn-view-job {
    padding: 10px 20px;
    background: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-view-job:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
}

.btn-apply-job {
    padding: 10px 20px;
    background: var(--secondary-color);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-apply-job:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

.btn-save-job {
    padding: 10px 20px;
    background: #f0f0f0;
    color: #666;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-save-job i {
    margin-right: 5px;
}

.btn-save-job.saved {
    background: var(--secondary-color);
    color: white;
}

.btn-save-job.saved i {
    color: white;
}

.btn-save-job:hover {
    background: #e0e0e0;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 40px;
}

.page-link {
    padding: 8px 15px;
    background: white;
    color: var(--primary-color);
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s;
}

.page-link.active {
    background: var(--secondary-color);
    color: white;
}

.page-link:hover {
    background: var(--primary-color);
    color: white;
}

.no-jobs-found {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
}

.no-jobs-found i {
    font-size: 60px;
    color: #ccc;
    margin-bottom: 20px;
}

/* Responsive */
@media (max-width: 992px) {
    .jobs-layout {
        grid-template-columns: 1fr;
    }
    
    .filters-sidebar {
        position: static;
    }
}

@media (max-width: 768px) {
    .job-card-footer {
        flex-wrap: wrap;
    }
    
    .job-details {
        gap: 10px;
    }
}
</style>

<section class="jobs-browse-section">
    <div class="container">
        <div class="jobs-header">
            <h1><i class="fas fa-search"></i> Browse Jobs in Tanzania</h1>
            <p>Find your next career opportunity from thousands of jobs</p>
        </div>

        <div class="jobs-layout">
            <!-- Sidebar Filters -->
            <aside class="filters-sidebar">
                <div class="filter-card">
                    <h3><i class="fas fa-filter"></i> Filter Jobs</h3>
                    
                    <form method="GET" action="jobs.php" id="filterForm">
                        <?php if(!empty($keyword)): ?>
                            <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>">
                        <?php endif; ?>
                        
                        <div class="filter-group">
                            <label><i class="fas fa-search"></i> Keywords</label>
                            <input type="text" name="keyword" placeholder="Job title, skills, company" value="<?php echo htmlspecialchars($keyword); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label><i class="fas fa-tag"></i> Category</label>
                            <select name="category">
                                <option value="">All Categories</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['name']); ?>" <?php echo $category == $cat['name'] ? 'selected' : ''; ?>>
                                        <?php echo $cat['icon'] . ' ' . htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label><i class="fas fa-map-marker-alt"></i> Location</label>
                            <select name="location">
                                <option value="">All Locations</option>
                                <?php foreach($locations as $loc): ?>
                                    <option value="<?php echo htmlspecialchars($loc['location']); ?>" <?php echo $location == $loc['location'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($loc['location']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label><i class="fas fa-briefcase"></i> Job Type</label>
                            <select name="job_type">
                                <option value="">All Types</option>
                                <option value="full-time" <?php echo $job_type == 'full-time' ? 'selected' : ''; ?>>Full Time</option>
                                <option value="part-time" <?php echo $job_type == 'part-time' ? 'selected' : ''; ?>>Part Time</option>
                                <option value="contract" <?php echo $job_type == 'contract' ? 'selected' : ''; ?>>Contract</option>
                                <option value="internship" <?php echo $job_type == 'internship' ? 'selected' : ''; ?>>Internship</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn-apply-filters">Apply Filters</button>
                        <a href="jobs.php" class="btn-reset-filters">Reset All</a>
                    </form>
                </div>
            </aside>

            <!-- Job Listings -->
            <div class="jobs-content">
                <div class="jobs-count">
                    <p>Found <strong><?php echo $total_jobs; ?></strong> jobs</p>
                </div>

                <?php if(empty($jobs)): ?>
                    <div class="no-jobs-found">
                        <i class="fas fa-search"></i>
                        <h3>No jobs found</h3>
                        <p>Try adjusting your search filters or browse all jobs</p>
                        <a href="jobs.php" class="btn-submit" style="display: inline-block; width: auto; margin-top: 20px;">View All Jobs</a>
                    </div>
                <?php else: ?>
                    <?php foreach($jobs as $job): ?>
                        <div class="job-card">
                            <div class="job-card-header">
                                <div class="company-icon">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div class="job-type <?php echo $job['type']; ?>">
                                    <?php echo ucfirst($job['type']); ?>
                                </div>
                            </div>
                            
                            <h3 class="job-title">
                                <a href="job-details.php?id=<?php echo $job['id']; ?>">
                                    <?php echo htmlspecialchars($job['title']); ?>
                                </a>
                            </h3>
                            
                            <div class="company-name">
                                <i class="fas fa-building"></i> <?php echo htmlspecialchars($job['company_name']); ?>
                            </div>
                            
                            <div class="job-details">
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                                <span><i class="fas fa-money-bill-wave"></i> <?php echo htmlspecialchars($job['salary_range'] ?: 'Negotiable'); ?></span>
                                <span><i class="fas fa-calendar-alt"></i> Deadline: <?php echo date('M d, Y', strtotime($job['deadline'])); ?></span>
                            </div>
                            
                            <div class="job-description">
                                <?php echo substr(htmlspecialchars($job['description']), 0, 150) . '...'; ?>
                            </div>
                            
                            <div class="job-card-footer">
                                <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn-view-job">
                                    <i class="fas fa-eye"></i> View Details
                                </a>

                                <?php if(isLoggedIn() && isJobSeeker() && !$has_applied): ?>
    <button onclick="openReportModal(<?php echo $job['id']; ?>)" class="btn-report-job">
        <i class="fas fa-flag"></i> Report Job
    </button>
<?php endif; ?>
                                
                                <?php if(isLoggedIn() && isJobSeeker()): ?>
                                    <?php if(strtotime($job['deadline']) > time()): ?>
                                        <a href="apply-job.php?id=<?php echo $job['id']; ?>" class="btn-apply-job">
                                            <i class="fas fa-paper-plane"></i> Apply Now
                                        </a>
                                    <?php endif; ?>
                                    
                                    <button onclick="toggleSaveJob(<?php echo $job['id']; ?>)" 
                                            class="btn-save-job <?php echo in_array($job['id'], $saved_jobs) ? 'saved' : ''; ?>"
                                            id="save-btn-<?php echo $job['id']; ?>">
                                        <i class="fas <?php echo in_array($job['id'], $saved_jobs) ? 'fa-bookmark' : 'fa-bookmark'; ?>"></i>
                                        <?php echo in_array($job['id'], $saved_jobs) ? 'Saved' : 'Save'; ?>
                                    </button>
                                <?php elseif(!isLoggedIn()): ?>
                                    <a href="login.php" class="btn-apply-job">
                                        <i class="fas fa-sign-in-alt"></i> Login to Apply
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if($page > 1): ?>
                                <a href="?page=<?php echo $page-1; ?>&keyword=<?php echo urlencode($keyword); ?>&category=<?php echo urlencode($category); ?>&location=<?php echo urlencode($location); ?>&job_type=<?php echo urlencode($job_type); ?>" class="page-link">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if($i == $page): ?>
                                    <span class="page-link active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>&keyword=<?php echo urlencode($keyword); ?>&category=<?php echo urlencode($category); ?>&location=<?php echo urlencode($location); ?>&job_type=<?php echo urlencode($job_type); ?>" class="page-link">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if($page < $total_pages): ?>
                                <a href="?page=<?php echo $page+1; ?>&keyword=<?php echo urlencode($keyword); ?>&category=<?php echo urlencode($category); ?>&location=<?php echo urlencode($location); ?>&job_type=<?php echo urlencode($job_type); ?>" class="page-link">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
function toggleSaveJob(jobId) {
    var btn = $('#save-btn-' + jobId);
    var isSaved = btn.hasClass('saved');
    
    $.ajax({
        url: 'ajax/save-job.php',
        method: 'POST',
        data: { 
            job_id: jobId, 
            action: isSaved ? 'unsave' : 'save' 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (response.action === 'saved') {
                    btn.html('<i class="fas fa-bookmark"></i> Saved');
                    btn.addClass('saved');
                    showNotification('success', 'Job saved! <a href="saved-jobs.php">View saved jobs</a>');
                } else {
                    btn.html('<i class="fas fa-bookmark"></i> Save');
                    btn.removeClass('saved');
                    showNotification('info', 'Job removed from saved');
                }
            } else {
                if (response.message.includes('login')) {
                    window.location.href = 'login.php';
                } else {
                    showNotification('error', response.message);
                }
            }
        },
        error: function() {
            showNotification('error', 'Something went wrong. Please try again.');
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>