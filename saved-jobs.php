<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();
redirectIfNotJobSeeker();

$user_id = $_SESSION['user_id'];

// Get saved jobs
$stmt = $pdo->prepare("SELECT s.*, j.title, j.company_name, j.location, j.type, j.salary_range, j.deadline, j.status 
                       FROM saved_jobs s 
                       JOIN jobs j ON s.job_id = j.id 
                       WHERE s.user_id = ? 
                       ORDER BY s.saved_date DESC");
$stmt->execute([$user_id]);
$savedJobs = $stmt->fetchAll();

include 'includes/header.php';
?>

<style>
.saved-jobs-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
    min-height: calc(100vh - 200px);
}

.saved-jobs-header {
    text-align: center;
    margin-bottom: 40px;
}

.saved-jobs-header h1 {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.saved-jobs-header p {
    color: #666;
}

.saved-jobs-grid {
    display: grid;
    gap: 20px;
}

.saved-job-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.saved-job-card:hover {
    transform: translateX(5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.job-info h3 {
    margin-bottom: 8px;
    color: var(--primary-color);
}

.job-info h3 a {
    text-decoration: none;
    color: inherit;
}

.job-info h3 a:hover {
    color: var(--secondary-color);
}

.job-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin: 10px 0;
    font-size: 14px;
    color: #666;
}

.job-meta i {
    margin-right: 5px;
    color: var(--secondary-color);
}

.saved-date {
    font-size: 12px;
    color: #999;
    margin-top: 8px;
}

.job-actions {
    display: flex;
    gap: 10px;
}

.btn-apply-saved {
    padding: 10px 20px;
    background: var(--secondary-color);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s;
}

.btn-unsave {
    padding: 10px 20px;
    background: #f0f0f0;
    color: #666;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-unsave:hover {
    background: #e74c3c;
    color: white;
}

.empty-saved {
    text-align: center;
    padding: 60px;
    background: white;
    border-radius: 12px;
}

.empty-saved i {
    font-size: 60px;
    color: #ccc;
    margin-bottom: 20px;
}

.empty-saved h3 {
    margin-bottom: 10px;
    color: var(--primary-color);
}

.empty-saved p {
    color: #666;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .saved-job-card {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .job-actions {
        justify-content: center;
    }
}
</style>

<section class="saved-jobs-section">
    <div class="container">
        <div class="saved-jobs-header">
            <h1><i class="fas fa-bookmark"></i> Saved Jobs</h1>
            <p>Jobs you've saved for later consideration</p>
        </div>

        <?php if(empty($savedJobs)): ?>
            <div class="empty-saved">
                <i class="fas fa-bookmark"></i>
                <h3>No saved jobs yet</h3>
                <p>Start saving jobs you're interested in by clicking the bookmark icon on job listings.</p>
                <a href="jobs.php" class="btn-submit" style="display: inline-block; width: auto; padding: 12px 30px;">Browse Jobs</a>
            </div>
        <?php else: ?>
            <div class="saved-jobs-grid">
                <?php foreach($savedJobs as $job): ?>
                    <div class="saved-job-card" id="job-<?php echo $job['job_id']; ?>">
                        <div class="job-info">
                            <h3>
                                <a href="job-details.php?id=<?php echo $job['job_id']; ?>">
                                    <?php echo htmlspecialchars($job['title']); ?>
                                </a>
                            </h3>
                            <div class="company-name">
                                <i class="fas fa-building"></i> <?php echo htmlspecialchars($job['company_name']); ?>
                            </div>
                            <div class="job-meta">
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo ucfirst($job['type']); ?></span>
                                <?php if($job['salary_range']): ?>
                                    <span><i class="fas fa-money-bill-wave"></i> <?php echo htmlspecialchars($job['salary_range']); ?></span>
                                <?php endif; ?>
                                <span><i class="fas fa-calendar-alt"></i> Deadline: <?php echo date('M d, Y', strtotime($job['deadline'])); ?></span>
                            </div>
                            <div class="saved-date">
                                <i class="fas fa-bookmark"></i> Saved on <?php echo date('M d, Y', strtotime($job['saved_date'])); ?>
                            </div>
                        </div>
                        <div class="job-actions">
                            <?php if($job['status'] == 'active' && strtotime($job['deadline']) > time()): ?>
                                <a href="apply-job.php?id=<?php echo $job['job_id']; ?>" class="btn-apply-saved">Apply Now</a>
                            <?php endif; ?>
                            <button onclick="unsaveJob(<?php echo $job['job_id']; ?>)" class="btn-unsave">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
function unsaveJob(jobId) {
    if (confirm('Remove this job from your saved list?')) {
        $.ajax({
            url: 'ajax/save-job.php',
            method: 'POST',
            data: { job_id: jobId, action: 'unsave' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#job-' + jobId).fadeOut(300, function() {
                        $(this).remove();
                        if ($('.saved-job-card').length === 0) {
                            location.reload();
                        }
                    });
                    showNotification('success', response.message);
                } else {
                    showNotification('error', response.message);
                }
            }
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>