<?php
require_once 'includes/init.php';

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get job details
$stmt = $pdo->prepare("SELECT j.*, u.full_name as employer_name, u.email as employer_email, u.phone as employer_phone 
                       FROM jobs j 
                       JOIN users u ON j.employer_id = u.id 
                       WHERE j.id = ?");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    header('Location: jobs.php');
    exit();
}

// Check if job is still active
$is_active = ($job['status'] == 'active' && strtotime($job['deadline']) > time());

// Get related jobs
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE category = ? AND id != ? AND status = 'active' AND deadline >= CURDATE() LIMIT 3");
$stmt->execute([$job['category'], $job_id]);
$related_jobs = $stmt->fetchAll();

// Check if user has saved this job
$is_saved = false;
if (isLoggedIn() && isJobSeeker()) {
    $stmt = $pdo->prepare("SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ?");
    $stmt->execute([$_SESSION['user_id'], $job_id]);
    $is_saved = $stmt->rowCount() > 0;
}

// Check if user has already applied
$has_applied = false;
if (isLoggedIn() && isJobSeeker()) {
    $stmt = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND jobseeker_id = ?");
    $stmt->execute([$job_id, $_SESSION['user_id']]);
    $has_applied = $stmt->rowCount() > 0;
}

include 'includes/header.php';
?>

<style>
.job-details-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
    min-height: calc(100vh - 200px);
}

.job-details-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

.main-content {
    background: white;
    border-radius: 15px;
    padding: 35px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.job-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.job-header h1 {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 15px;
}

.job-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 15px;
}

.job-meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-size: 14px;
}

.job-meta-item i {
    color: var(--secondary-color);
    width: 16px;
}

.job-actions {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.btn-apply-now {
    padding: 12px 30px;
    background: var(--secondary-color);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.btn-apply-now:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

.btn-save-job-detail {
    padding: 12px 30px;
    background: #f0f0f0;
    color: #666;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-save-job-detail.saved {
    background: var(--secondary-color);
    color: white;
}

.btn-save-job-detail.saved i {
    color: white;
}

.section-title {
    font-size: 1.3rem;
    margin: 30px 0 20px;
    color: var(--primary-color);
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
}

.job-description-content,
.job-requirements-content {
    line-height: 1.8;
    color: #444;
}

.employer-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-top: 30px;
}

.employer-info h3 {
    margin-bottom: 15px;
    color: var(--primary-color);
}

.employer-info p {
    margin: 8px 0;
    color: #666;
}

/* Sidebar */
.sidebar {
    position: sticky;
    top: 100px;
    height: fit-content;
}

.info-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.info-card h3 {
    margin-bottom: 20px;
    color: var(--primary-color);
    font-size: 1.2rem;
}

.info-list {
    list-style: none;
}

.info-list li {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-list li:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 500;
    color: #666;
}

.info-value {
    color: var(--primary-color);
    font-weight: 500;
}

.share-buttons {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.share-btn {
    flex: 1;
    padding: 8px;
    text-align: center;
    border-radius: 8px;
    text-decoration: none;
    color: white;
    transition: all 0.3s;
}

.share-btn.facebook { background: #3b5998; }
.share-btn.twitter { background: #1da1f2; }
.share-btn.whatsapp { background: #25d366; }
.share-btn.linkedin { background: #0077b5; }

.related-job-card {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.3s;
}

.related-job-card:last-child {
    border-bottom: none;
}

.related-job-card:hover {
    background: #f8f9fa;
    transform: translateX(5px);
}

.related-job-card h4 {
    margin-bottom: 8px;
}

.related-job-card h4 a {
    color: var(--primary-color);
    text-decoration: none;
}

.related-job-card h4 a:hover {
    color: var(--secondary-color);
}

.related-job-card p {
    font-size: 13px;
    color: #666;
    margin: 5px 0;
}

@media (max-width: 992px) {
    .job-details-container {
        grid-template-columns: 1fr;
    }
    
    .sidebar {
        position: static;
    }
}

@media (max-width: 768px) {
    .main-content {
        padding: 20px;
    }
    
    .job-header h1 {
        font-size: 1.5rem;
    }
    
    .job-actions {
        flex-direction: column;
    }
    
    .job-meta {
        gap: 10px;
    }
}
</style>

<section class="job-details-section">
    <div class="container">
        <div class="job-details-container">
            <!-- Main Content -->
            <div class="main-content">
                <div class="job-header">
                    <h1><?php echo htmlspecialchars($job['title']); ?></h1>
                    <div class="job-meta">
                        <div class="job-meta-item">
                            <i class="fas fa-building"></i>
                            <span><?php echo htmlspecialchars($job['company_name']); ?></span>
                        </div>
                        <div class="job-meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($job['location']); ?></span>
                        </div>
                        <div class="job-meta-item">
                            <i class="fas fa-clock"></i>
                            <span><?php echo ucfirst($job['type']); ?></span>
                        </div>
                        <?php if($job['salary_range']): ?>
                            <div class="job-meta-item">
                                <i class="fas fa-money-bill-wave"></i>
                                <span><?php echo htmlspecialchars($job['salary_range']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="job-meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Posted: <?php echo date('M d, Y', strtotime($job['posted_date'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="job-actions">
                        <?php if(isLoggedIn() && isJobSeeker()): ?>
                            <?php if($is_active && !$has_applied): ?>
                                <a href="apply-job.php?id=<?php echo $job_id; ?>" class="btn-apply-now">
                                    <i class="fas fa-paper-plane"></i> Apply Now
                                </a>
                            <?php elseif($has_applied): ?>
                                <button class="btn-apply-now" disabled style="background: #6c757d; cursor: not-allowed;">
                                    <i class="fas fa-check-circle"></i> Already Applied
                                </button>
                            <?php elseif(!$is_active): ?>
                                <button class="btn-apply-now" disabled style="background: #6c757d; cursor: not-allowed;">
                                    <i class="fas fa-clock"></i> Application Closed
                                </button>
                            <?php endif; ?>
                            
                            <button onclick="toggleSaveJob(<?php echo $job_id; ?>)" 
                                    class="btn-save-job-detail <?php echo $is_saved ? 'saved' : ''; ?>"
                                    id="save-btn-<?php echo $job_id; ?>">
                                <i class="fas <?php echo $is_saved ? 'fa-bookmark' : 'fa-bookmark'; ?>"></i>
                                <?php echo $is_saved ? 'Saved' : 'Save Job'; ?>
                            </button>
                        <?php elseif(!isLoggedIn()): ?>
                            <a href="login.php" class="btn-apply-now">
                                <i class="fas fa-sign-in-alt"></i> Login to Apply
                            </a>
                        <?php elseif(isEmployer()): ?>
                            <a href="edit-job.php?id=<?php echo $job_id; ?>" class="btn-apply-now" style="background: var(--primary-color);">
                                <i class="fas fa-edit"></i> Edit Job
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="job-description">
                    <h3 class="section-title"><i class="fas fa-align-left"></i> Job Description</h3>
                    <div class="job-description-content">
                        <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                    </div>
                </div>
                
                <div class="job-requirements">
                    <h3 class="section-title"><i class="fas fa-check-circle"></i> Requirements</h3>
                    <div class="job-requirements-content">
                        <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                    </div>
                </div>
                
                <div class="employer-info">
                    <h3><i class="fas fa-building"></i> About the Employer</h3>
                    <p><strong>Company:</strong> <?php echo htmlspecialchars($job['company_name']); ?></p>
                    <p><strong>Contact Person:</strong> <?php echo htmlspecialchars($job['employer_name']); ?></p>
                    <?php if($job['employer_email']): ?>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($job['employer_email']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="info-card">
                    <h3><i class="fas fa-info-circle"></i> Job Summary</h3>
                    <ul class="info-list">
                        <li>
                            <span class="info-label">Published:</span>
                            <span class="info-value"><?php echo date('M d, Y', strtotime($job['posted_date'])); ?></span>
                        </li>
                        <li>
                            <span class="info-label">Deadline:</span>
                            <span class="info-value"><?php echo date('M d, Y', strtotime($job['deadline'])); ?></span>
                        </li>
                        <li>
                            <span class="info-label">Job Type:</span>
                            <span class="info-value"><?php echo ucfirst($job['type']); ?></span>
                        </li>
                        <li>
                            <span class="info-label">Category:</span>
                            <span class="info-value"><?php echo htmlspecialchars($job['category']); ?></span>
                        </li>
                        <li>
                            <span class="info-label">Location:</span>
                            <span class="info-value"><?php echo htmlspecialchars($job['location']); ?></span>
                        </li>
                    </ul>
                </div>
                
                <div class="info-card">
                    <h3><i class="fas fa-share-alt"></i> Share This Job</h3>
                    <div class="share-buttons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://localhost/smartjob-tanzania/job-details.php?id=' . $job_id); ?>" target="_blank" class="share-btn facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://localhost/smartjob-tanzania/job-details.php?id=' . $job_id); ?>&text=<?php echo urlencode($job['title'] . ' at ' . $job['company_name']); ?>" target="_blank" class="share-btn twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://wa.me/?text=<?php echo urlencode($job['title'] . ' at ' . $job['company_name'] . ' - http://localhost/smartjob-tanzania/job-details.php?id=' . $job_id); ?>" target="_blank" class="share-btn whatsapp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode('http://localhost/smartjob-tanzania/job-details.php?id=' . $job_id); ?>" target="_blank" class="share-btn linkedin">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                
                <?php if(!empty($related_jobs)): ?>
                    <div class="info-card">
                        <h3><i class="fas fa-briefcase"></i> Related Jobs</h3>
                        <?php foreach($related_jobs as $related): ?>
                            <div class="related-job-card">
                                <h4>
                                    <a href="job-details.php?id=<?php echo $related['id']; ?>">
                                        <?php echo htmlspecialchars($related['title']); ?>
                                    </a>
                                </h4>
                                <p><i class="fas fa-building"></i> <?php echo htmlspecialchars($related['company_name']); ?></p>
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($related['location']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
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
                    btn.html('<i class="fas fa-bookmark"></i> Save Job');
                    btn.removeClass('saved');
                    showNotification('info', 'Job removed from saved');
                }
            } else {
                showNotification('error', response.message);
            }
        }
    });
}
</script>

<!-- Report Job Modal -->
<div id="reportModal" class="report-modal">
    <div class="report-modal-content">
        <div class="report-modal-header">
            <h3><i class="fas fa-flag"></i> Report Job</h3>
            <span class="close" onclick="closeReportModal()">&times;</span>
        </div>
        <form id="reportForm" method="POST">
            <div class="report-modal-body">
                <input type="hidden" name="job_id" id="report_job_id">
                <div class="report-reason">
                    <label><i class="fas fa-exclamation-triangle"></i> Reason for reporting *</label>
                    <select name="reason" id="report_reason" required>
                        <option value="">Select a reason</option>
                        <option value="Spam">Spam or misleading content</option>
                        <option value="Scam">Scam or fraudulent job</option>
                        <option value="Duplicate">Duplicate job posting</option>
                        <option value="Inappropriate">Inappropriate content</option>
                        <option value="Expired">Job is expired but still active</option>
                        <option value="Wrong Category">Wrong job category</option>
                        <option value="Other">Other reason</option>
                    </select>
                </div>
                <div class="report-description">
                    <label><i class="fas fa-comment"></i> Additional details</label>
                    <textarea name="description" id="report_description" placeholder="Please provide more details about why you're reporting this job..."></textarea>
                </div>
            </div>
            <div class="report-modal-footer">
                <button type="button" class="btn-cancel-report" onclick="closeReportModal()">Cancel</button>
                <button type="submit" class="btn-submit-report">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReportModal(jobId) {
    document.getElementById('report_job_id').value = jobId;
    document.getElementById('reportModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeReportModal() {
    document.getElementById('reportModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('reportForm').reset();
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('reportModal');
    if (event.target == modal) {
        closeReportModal();
    }
}

// Handle report form submission
$('#reportForm').on('submit', function(e) {
    e.preventDefault();
    
    var jobId = $('#report_job_id').val();
    var reason = $('#report_reason').val();
    var description = $('#report_description').val();
    
    if (!reason) {
        showNotification('error', 'Please select a reason for reporting');
        return;
    }
    
    $.ajax({
        url: 'ajax/report-job.php',
        method: 'POST',
        data: {
            job_id: jobId,
            reason: reason,
            description: description
        },
        dataType: 'json',
        beforeSend: function() {
            $('.btn-submit-report').html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
            $('.btn-submit-report').prop('disabled', true);
        },
        success: function(response) {
            if (response.success) {
                showNotification('success', 'Job reported successfully. Our team will review it.');
                closeReportModal();
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Something went wrong. Please try again.');
        },
        complete: function() {
            $('.btn-submit-report').html('Submit Report');
            $('.btn-submit-report').prop('disabled', false);
        }
    });
});

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