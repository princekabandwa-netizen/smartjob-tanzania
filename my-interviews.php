<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();
redirectIfNotJobSeeker();

$user_id = $_SESSION['user_id'];

// Get all interviews for the job seeker
$stmt = $pdo->prepare("SELECT i.*, j.title, j.company_name, j.location as job_location,
                       u.full_name as employer_name, u.email as employer_email
                       FROM interview_schedules i
                       JOIN jobs j ON i.job_id = j.id
                       JOIN users u ON i.employer_id = u.id
                       WHERE i.jobseeker_id = ?
                       ORDER BY i.interview_date ASC, i.interview_time ASC");
$stmt->execute([$user_id]);
$interviews = $stmt->fetchAll();

include 'includes/header.php';
?>

<style>
.interviews-section {
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

.interviews-grid {
    display: grid;
    gap: 25px;
}

.interview-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    transition: all 0.3s;
}

.interview-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.12);
}

.interview-header {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.interview-header h3 {
    margin: 0;
    font-size: 1.2rem;
}

.status-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-scheduled { background: #3498db; color: white; }
.status-confirmed { background: #27ae60; color: white; }
.status-completed { background: #95a5a6; color: white; }
.status-cancelled { background: #e74c3c; color: white; }

.interview-body {
    padding: 20px;
}

.interview-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #555;
    font-size: 14px;
}

.detail-item i {
    width: 20px;
    color: var(--secondary-color);
}

.interview-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
}

.btn-confirm {
    padding: 8px 20px;
    background: #27ae60;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-confirm:hover {
    background: #219a52;
    transform: translateY(-2px);
}

.btn-join {
    padding: 8px 20px;
    background: #3498db;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    display: inline-block;
    transition: all 0.3s;
}

.btn-join:hover {
    background: #2980b9;
    transform: translateY(-2px);
}

.btn-cancel-interview {
    padding: 8px 20px;
    background: #e74c3c;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-cancel-interview:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

.empty-state {
    text-align: center;
    padding: 60px;
    background: white;
    border-radius: 15px;
}

.empty-state i {
    font-size: 60px;
    color: #ccc;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .interview-header {
        flex-direction: column;
        text-align: center;
    }
    
    .interview-details {
        grid-template-columns: 1fr;
    }
    
    .interview-actions {
        flex-direction: column;
    }
}
</style>

<section class="interviews-section">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-calendar-alt"></i> My Interviews</h1>
            <p>Track and manage your scheduled interviews</p>
        </div>
        
        <?php if(empty($interviews)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No interviews scheduled</h3>
                <p>When employers schedule interviews with you, they'll appear here.</p>
                <a href="jobs.php" class="btn-submit" style="display: inline-block; width: auto; margin-top: 20px;">
                    Browse Jobs
                </a>
            </div>
        <?php else: ?>
            <div class="interviews-grid">
                <?php foreach($interviews as $interview): ?>
                    <div class="interview-card" id="interview-<?php echo $interview['id']; ?>">
                        <div class="interview-header">
                            <h3><?php echo htmlspecialchars($interview['title']); ?> at <?php echo htmlspecialchars($interview['company_name']); ?></h3>
                            <span class="status-badge status-<?php echo $interview['status']; ?>">
                                <?php echo ucfirst($interview['status']); ?>
                            </span>
                        </div>
                        
                        <div class="interview-body">
                            <div class="interview-details">
                                <div class="detail-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>Date: <?php echo date('F d, Y', strtotime($interview['interview_date'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-clock"></i>
                                    <span>Time: <?php echo date('h:i A', strtotime($interview['interview_time'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-hourglass-half"></i>
                                    <span>Duration: <?php echo $interview['duration']; ?> minutes</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-video"></i>
                                    <span>Type: <?php echo ucfirst(str_replace('_', ' ', $interview['interview_type'])); ?></span>
                                </div>
                                <?php if($interview['interview_type'] == 'video' && $interview['meeting_link']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-link"></i>
                                        <span>Meeting Link: <a href="<?php echo $interview['meeting_link']; ?>" target="_blank">Click to join</a></span>
                                    </div>
                                <?php endif; ?>
                                <?php if($interview['location']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>Location: <?php echo htmlspecialchars($interview['location']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if($interview['notes']): ?>
                                <div class="detail-item" style="margin-top: 10px;">
                                    <i class="fas fa-sticky-note"></i>
                                    <span>Notes: <?php echo htmlspecialchars($interview['notes']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="interview-actions">
                                <?php if($interview['status'] == 'scheduled'): ?>
                                    <button onclick="confirmInterview(<?php echo $interview['id']; ?>)" class="btn-confirm">
                                        <i class="fas fa-check"></i> Confirm Interview
                                    </button>
                                    <button onclick="cancelInterview(<?php echo $interview['id']; ?>)" class="btn-cancel-interview">
                                        <i class="fas fa-times"></i> Request Cancellation
                                    </button>
                                <?php endif; ?>
                                
                                <?php if($interview['status'] == 'confirmed' && $interview['interview_type'] == 'video' && $interview['meeting_link']): ?>
                                    <a href="<?php echo $interview['meeting_link']; ?>" target="_blank" class="btn-join">
                                        <i class="fas fa-video"></i> Join Meeting
                                    </a>
                                <?php endif; ?>
                                
                                <?php if($interview['status'] == 'completed'): ?>
                                    <button class="btn-view" onclick="viewFeedback(<?php echo $interview['id']; ?>)">
                                        <i class="fas fa-star"></i> View Feedback
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
function confirmInterview(interviewId) {
    if (confirm('Confirm your attendance for this interview?')) {
        $.ajax({
            url: 'ajax/confirm-interview.php',
            method: 'POST',
            data: { interview_id: interviewId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('success', 'Interview confirmed!');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    showNotification('error', response.message);
                }
            }
        });
    }
}

function cancelInterview(interviewId) {
    if (confirm('Are you sure you want to request cancellation? This may affect your application.')) {
        $.ajax({
            url: 'ajax/cancel-interview.php',
            method: 'POST',
            data: { interview_id: interviewId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('info', 'Cancellation request sent');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    showNotification('error', response.message);
                }
            }
        });
    }
}

function viewFeedback(interviewId) {
    $.ajax({
        url: 'ajax/get-interview-feedback.php',
        method: 'GET',
        data: { interview_id: interviewId },
        success: function(response) {
            showNotification('info', 'Feedback: ' + response);
        }
    });
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