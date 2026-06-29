<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();
redirectIfNotEmployer();

$application_id = isset($_GET['application_id']) ? (int)$_GET['application_id'] : 0;
$error = '';
$success = '';

// Get application details
$stmt = $pdo->prepare("SELECT a.*, j.title, j.company_name, j.location as job_location, j.id as job_id,
                       u.full_name as applicant_name, u.email as applicant_email, u.phone as applicant_phone
                       FROM applications a 
                       JOIN jobs j ON a.job_id = j.id 
                       JOIN users u ON a.jobseeker_id = u.id 
                       WHERE a.id = ? AND j.employer_id = ?");
$stmt->execute([$application_id, $_SESSION['user_id']]);
$application = $stmt->fetch();

if (!$application) {
    setError("Application not found or you don't have permission.");
    header('Location: manage-applications.php');
    exit();
}

// Check if interview already scheduled
$stmt = $pdo->prepare("SELECT * FROM interview_schedules WHERE application_id = ? AND status IN ('scheduled', 'confirmed')");
$stmt->execute([$application_id]);
$existing_interview = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $interview_date = $_POST['interview_date'];
    $interview_time = $_POST['interview_time'];
    $duration = (int)$_POST['duration'];
    $interview_type = $_POST['interview_type'];
    $meeting_link = trim($_POST['meeting_link']);
    $location = trim($_POST['location']);
    $notes = trim($_POST['notes']);
    
    $errors = [];
    
    if (empty($interview_date)) $errors[] = "Interview date is required";
    if (empty($interview_time)) $errors[] = "Interview time is required";
    if (strtotime($interview_date) < strtotime(date('Y-m-d'))) $errors[] = "Interview date cannot be in the past";
    
    if ($interview_type == 'video' && empty($meeting_link)) {
        $errors[] = "Meeting link is required for video interview";
    }
    
    if ($interview_type == 'in_person' && empty($location)) {
        $errors[] = "Location is required for in-person interview";
    }
    
    if (empty($errors)) {
        if ($existing_interview) {
            // Update existing interview
            $stmt = $pdo->prepare("UPDATE interview_schedules SET 
                                   interview_date = ?, interview_time = ?, duration = ?, 
                                   interview_type = ?, meeting_link = ?, location = ?, notes = ?,
                                   status = 'scheduled', updated_at = NOW()
                                   WHERE id = ?");
            $stmt->execute([$interview_date, $interview_time, $duration, $interview_type, 
                           $meeting_link, $location, $notes, $existing_interview['id']]);
            $interview_id = $existing_interview['id'];
        } else {
            // Create new interview
            $stmt = $pdo->prepare("INSERT INTO interview_schedules 
                                   (application_id, employer_id, jobseeker_id, job_id, 
                                    interview_date, interview_time, duration, interview_type, 
                                    meeting_link, location, notes, status, created_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW())");
            $stmt->execute([$application_id, $_SESSION['user_id'], $application['jobseeker_id'], 
                           $application['job_id'], $interview_date, $interview_time, $duration, 
                           $interview_type, $meeting_link, $location, $notes]);
            $interview_id = $pdo->lastInsertId();
        }
        
        // Create notification for job seeker
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link, created_at) 
                               VALUES (?, 'interview', 'Interview Scheduled', 
                                       ?, 
                                       'my-interviews.php', NOW())");
        $message = "An interview has been scheduled for {$application['title']} position on " . 
                   date('F d, Y', strtotime($interview_date)) . " at " . date('h:i A', strtotime($interview_time));
        $stmt->execute([$application['jobseeker_id'], $message]);
        
        // Log activity
        logUserActivity($pdo, $_SESSION['user_id'], 'interview_scheduled', 
                       "Scheduled interview for {$application['applicant_name']} for {$application['title']}");
        
        setSuccess("Interview scheduled successfully! The candidate has been notified.");
        header('Location: manage-applications.php');
        exit();
    } else {
        $error = implode("<br>", $errors);
    }
}

// Get available time slots
$time_slots = [
    '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
    '12:00', '12:30', '13:00', '13:30', '14:00', '14:30',
    '15:00', '15:30', '16:00', '16:30'
];

include 'includes/header.php';
?>

<style>
.schedule-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
    min-height: calc(100vh - 200px);
}

.schedule-container {
    max-width: 800px;
    margin: 0 auto;
}

.page-header {
    text-align: center;
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.page-header p {
    color: #666;
}

.schedule-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.applicant-info {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
    padding: 25px;
}

.applicant-info h3 {
    margin-bottom: 10px;
    font-size: 1.3rem;
}

.applicant-info p {
    margin: 5px 0;
    opacity: 0.9;
}

.schedule-form {
    padding: 30px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: var(--primary-color);
}

.form-group label i {
    margin-right: 8px;
    color: var(--secondary-color);
}

.form-group label .required {
    color: var(--secondary-color);
    margin-left: 5px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 0;
}

textarea {
    resize: vertical;
    min-height: 100px;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.btn-submit {
    flex: 1;
    padding: 14px;
    background: blue;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-submit:hover {
    background: #1bbc02;
    transform: translateY(-2px);
}

.btn-cancel {
    flex: 1;
    padding: 14px;
    background: #f50000;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
}

.btn-cancel:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.alert {
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.help-text {
    font-size: 12px;
    color: #888;
    margin-top: 5px;
    display: block;
}

.location-group,
.meeting-group {
    transition: all 0.3s ease;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .schedule-form {
        padding: 20px;
    }
}
</style>

<section class="schedule-section">
    <div class="container">
        <div class="schedule-container">
            <div class="page-header">
                <h1><i class="fas fa-calendar-plus"></i> Schedule Interview</h1>
                <p>Schedule an interview with the candidate</p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="schedule-card">
                <div class="applicant-info">
                    <h3><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($application['applicant_name']); ?></h3>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($application['applicant_email']); ?></p>
                    <?php if($application['applicant_phone']): ?>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($application['applicant_phone']); ?></p>
                    <?php endif; ?>
                    <p><i class="fas fa-briefcase"></i> Applied for: <?php echo htmlspecialchars($application['title']); ?></p>
                </div>
                
                <form method="POST" action="" class="schedule-form" id="scheduleForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Interview Date <span class="required">*</span></label>
                            <input type="date" name="interview_date" id="interview_date" required 
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                   value="<?php echo $existing_interview ? $existing_interview['interview_date'] : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Interview Time <span class="required">*</span></label>
                            <select name="interview_time" id="interview_time" required>
                                <option value="">Select Time</option>
                                <?php foreach($time_slots as $slot): ?>
                                    <option value="<?php echo $slot; ?>" 
                                        <?php echo ($existing_interview && $existing_interview['interview_time'] == $slot) ? 'selected' : ''; ?>>
                                        <?php echo date('h:i A', strtotime($slot)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-hourglass-half"></i> Duration (minutes)</label>
                            <select name="duration">
                                <option value="15" <?php echo ($existing_interview && $existing_interview['duration'] == 15) ? 'selected' : ''; ?>>15 minutes</option>
                                <option value="30" <?php echo ($existing_interview && $existing_interview['duration'] == 30) ? 'selected' : ''; ?>>30 minutes</option>
                                <option value="45" <?php echo ($existing_interview && $existing_interview['duration'] == 45) ? 'selected' : ''; ?>>45 minutes</option>
                                <option value="60" <?php echo ($existing_interview && $existing_interview['duration'] == 60) ? 'selected' : ''; ?>>60 minutes</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-video"></i> Interview Type <span class="required">*</span></label>
                            <select name="interview_type" id="interview_type" required>
                                <option value="video" <?php echo ($existing_interview && $existing_interview['interview_type'] == 'video') ? 'selected' : ''; ?>>Video Call</option>
                                <option value="phone" <?php echo ($existing_interview && $existing_interview['interview_type'] == 'phone') ? 'selected' : ''; ?>>Phone Call</option>
                                <option value="in_person" <?php echo ($existing_interview && $existing_interview['interview_type'] == 'in_person') ? 'selected' : ''; ?>>In Person</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Meeting Link Field (for Video Interview) -->
                    <div class="form-group meeting-group" id="meeting_link_group" style="display: <?php echo ($existing_interview && $existing_interview['interview_type'] == 'video') ? 'block' : 'none'; ?>">
                        <label><i class="fas fa-link"></i> Meeting Link <span class="required" id="meeting_required">*</span></label>
                        <input type="url" name="meeting_link" id="meeting_link" 
                               placeholder="https://meet.google.com/... or https://zoom.us/j/..." 
                               value="<?php echo $existing_interview ? htmlspecialchars($existing_interview['meeting_link']) : ''; ?>">
                        <small class="help-text">Google Meet, Zoom, or Microsoft Teams link</small>
                    </div>
                    
                    <!-- Location Field (for In-Person Interview) -->
                    <div class="form-group location-group" id="location_group" style="display: <?php echo ($existing_interview && $existing_interview['interview_type'] == 'in_person') ? 'block' : 'none'; ?>">
                        <label><i class="fas fa-map-marker-alt"></i> Location <span class="required" id="location_required">*</span></label>
                        <input type="text" name="location" id="location" 
                               placeholder="Office address, building name, room number"
                               value="<?php echo $existing_interview ? htmlspecialchars($existing_interview['location']) : ''; ?>">
                        <small class="help-text">Full address where the interview will take place</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-sticky-note"></i> Additional Notes</label>
                        <textarea name="notes" placeholder="Any special instructions for the candidate..."><?php echo $existing_interview ? htmlspecialchars($existing_interview['notes']) : ''; ?></textarea>
                        <small class="help-text">E.g., What to prepare, dress code, documents to bring, parking instructions</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="fas fa-calendar-check"></i> Schedule Interview
                        </button>
                        <a href="manage-applications.php" class="btn-cancel">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
// Show/hide fields based on interview type
document.getElementById('interview_type').addEventListener('change', function() {
    var type = this.value;
    var meetingGroup = document.getElementById('meeting_link_group');
    var locationGroup = document.getElementById('location_group');
    var meetingLink = document.getElementById('meeting_link');
    var location = document.getElementById('location');
    var meetingRequired = document.getElementById('meeting_required');
    var locationRequired = document.getElementById('location_required');
    
    if (type === 'video') {
        meetingGroup.style.display = 'block';
        locationGroup.style.display = 'none';
        meetingLink.required = true;
        location.required = false;
        meetingRequired.style.display = 'inline';
        locationRequired.style.display = 'none';
    } else if (type === 'in_person') {
        meetingGroup.style.display = 'none';
        locationGroup.style.display = 'block';
        meetingLink.required = false;
        location.required = true;
        meetingRequired.style.display = 'none';
        locationRequired.style.display = 'inline';
    } else {
        meetingGroup.style.display = 'none';
        locationGroup.style.display = 'none';
        meetingLink.required = false;
        location.required = false;
        meetingRequired.style.display = 'none';
        locationRequired.style.display = 'none';
    }
});

// Set minimum date for interview
var today = new Date();
var tomorrow = new Date(today);
tomorrow.setDate(tomorrow.getDate() + 1);
document.getElementById('interview_date').min = tomorrow.toISOString().split('T')[0];

// Form validation
document.getElementById('scheduleForm').addEventListener('submit', function(e) {
    var interviewType = document.getElementById('interview_type').value;
    var meetingLink = document.getElementById('meeting_link').value.trim();
    var location = document.getElementById('location').value.trim();
    var interviewDate = document.getElementById('interview_date').value;
    var interviewTime = document.getElementById('interview_time').value;
    var errors = [];
    
    if (!interviewDate) {
        errors.push('Please select an interview date');
    }
    
    if (!interviewTime) {
        errors.push('Please select an interview time');
    }
    
    if (interviewType === 'video' && !meetingLink) {
        errors.push('Meeting link is required for video interview');
        e.preventDefault();
        showNotification('error', 'Meeting link is required for video interview');
        return false;
    }
    
    if (interviewType === 'in_person' && !location) {
        errors.push('Location is required for in-person interview');
        e.preventDefault();
        showNotification('error', 'Location is required for in-person interview');
        return false;
    }
    
    if (errors.length > 0) {
        e.preventDefault();
        showNotification('error', errors.join('\n'));
        return false;
    }
    
    // Show loading state
    var submitBtn = document.getElementById('submitBtn');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scheduling...';
    submitBtn.disabled = true;
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