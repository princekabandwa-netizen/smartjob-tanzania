<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();
redirectIfNotJobSeeker();

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get job details
$stmt = $pdo->prepare("SELECT j.*, u.full_name as employer_name, u.email as employer_email 
                       FROM jobs j 
                       JOIN users u ON j.employer_id = u.id 
                       WHERE j.id = ? AND j.status = 'active' AND j.deadline >= CURDATE()");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    setError("Job not found or no longer accepting applications.");
    header('Location: jobs.php');
    exit();
}

// Check if already applied
$stmt = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND jobseeker_id = ?");
$stmt->execute([$job_id, $_SESSION['user_id']]);
if ($stmt->rowCount() > 0) {
    setWarning("You have already applied for this position.");
    header("Location: job-details.php?id={$job_id}");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cover_letter = trim($_POST['cover_letter']);
    $errors = [];
    
    if (empty($cover_letter)) {
        $errors[] = "Please provide a cover letter explaining why you're a good fit for this position.";
    }
    
    if (strlen($cover_letter) < 50) {
        $errors[] = "Cover letter must be at least 50 characters.";
    }
    
    // Handle resume upload
    $resume_path = null;
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx'];
        $filename = $_FILES['resume']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $file_size = $_FILES['resume']['size'];
        
        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid file type. Please upload PDF, DOC, or DOCX files only.";
        }
        
        if ($file_size > 5 * 1024 * 1024) {
            $errors[] = "File is too large. Maximum size is 5MB.";
        }
        
        if (empty($errors)) {
            // Create directory if not exists
            if (!is_dir('uploads/resumes')) {
                mkdir('uploads/resumes', 0777, true);
            }
            $resume_path = 'uploads/resumes/' . time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $filename);
            move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path);
        }
    } else {
        $errors[] = "Please upload your resume/CV.";
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO applications (job_id, jobseeker_id, cover_letter, resume_path, applied_date) 
                               VALUES (?, ?, ?, ?, NOW())");
        
        if ($stmt->execute([$job_id, $_SESSION['user_id'], $cover_letter, $resume_path])) {
            $application_id = $pdo->lastInsertId();
            $current_time = date('H:i:s');
            
            // Log activity
            logUserActivity($pdo, $_SESSION['user_id'], 'job_applied', 
                           "Applied for job: {$job['title']} at {$job['company_name']}");
            
            // Create notification for job seeker
            createNotification($pdo, $_SESSION['user_id'], 'job_application', 'Application Submitted', 
                              "Your application for '{$job['title']}' has been submitted successfully at {$current_time}.", 
                              "my-applications.php");
            
            // Create notification for employer
            createNotification($pdo, $job['employer_id'], 'job_application', 'New Application Received', 
                              "{$_SESSION['full_name']} has applied for '{$job['title']}'.", 
                              "manage-applications.php?job_id={$job_id}");
            
            setSuccess("Your application has been submitted successfully!");
            header("Location: my-applications.php");
            exit();
        } else {
            setError("Failed to submit application. Please try again.");
        }
    } else {
        setError(implode("<br>", $errors));
    }
}

include 'includes/header.php';
?>

<style>
.apply-job-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
    min-height: calc(100vh - 200px);
}

.apply-job-container {
    max-width: 800px;
    margin: 0 auto;
}

.job-summary {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
}

.job-summary h2 {
    margin-bottom: 15px;
    font-size: 1.5rem;
}

.company-info {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-top: 15px;
}

.company-info span {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    opacity: 0.9;
}

.application-form {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
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

/* File Upload Button - Simple and Clean */
.file-upload-wrapper {
    position: relative;
    margin-top: 5px;
}

.file-upload-btn {
    display: inline-block;
    width: 100%;
    padding: 14px 20px;
    background: #f0f0f0;
    border: 2px dashed #ccc;
    border-radius: 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
    color: #666;
}

.file-upload-btn:hover {
    background: #e8e8e8;
    border-color: var(--secondary-color);
    color: var(--secondary-color);
}

.file-upload-btn i {
    font-size: 20px;
    margin-right: 10px;
}

.file-upload-input {
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.file-info {
    margin-top: 10px;
    padding: 8px 12px;
    background: #f8f9fa;
    border-radius: 8px;
    font-size: 13px;
    display: none;
}

.file-info.show {
    display: block;
}

.file-info.success {
    background: #d4edda;
    color: #155724;
    border-left: 3px solid #28a745;
}

.file-info.error {
    background: #f8d7da;
    color: #721c24;
    border-left: 3px solid #dc3545;
}

.file-info i {
    margin-right: 8px;
}

textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 15px;
    font-family: 'Inter', sans-serif;
    resize: vertical;
    min-height: 150px;
    transition: all 0.3s ease;
}

textarea:focus {
    outline: none;
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
}

.char-counter {
    font-size: 12px;
    margin-top: 5px;
    text-align: right;
    color: #666;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 35px;
}

.btn-submit {
    flex: 1;
    padding: 14px;
    background: linear-gradient(135deg, var(--secondary-color), #c0392b);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(231, 76, 60, 0.3);
}

.btn-cancel {
    flex: 1;
    padding: 14px;
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    transition: all 0.3s ease;
}

.btn-cancel:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.alert {
    padding: 15px 20px;
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

small {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #888;
}

@media (max-width: 768px) {
    .apply-job-container {
        padding: 0 15px;
    }
    
    .application-form {
        padding: 25px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .job-summary {
        padding: 20px;
    }
    
    .company-info {
        gap: 10px;
        flex-direction: column;
    }
}
</style>

<section class="apply-job-section">
    <div class="container">
        <div class="apply-job-container">
            <div class="job-summary">
                <h2><i class="fas fa-briefcase"></i> Apply for: <?php echo htmlspecialchars($job['title']); ?></h2>
                <div class="company-info">
                    <span><i class="fas fa-building"></i> <?php echo htmlspecialchars($job['company_name']); ?></span>
                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                    <span><i class="fas fa-clock"></i> <?php echo ucfirst($job['type']); ?></span>
                    <span><i class="fas fa-calendar-alt"></i> Deadline: <?php echo date('M d, Y', strtotime($job['deadline'])); ?></span>
                </div>
            </div>
            
            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data" class="application-form" id="applicationForm">
                <div class="form-group">
                    <label><i class="fas fa-file-upload"></i> Upload Resume/CV <span class="required">*</span></label>
                    <div class="file-upload-wrapper">
                        <div class="file-upload-btn" id="fileUploadBtn">
                            <i class="fas fa-cloud-upload-alt"></i> Click to Browse Resume/CV
                        </div>
                        <input type="file" name="resume" id="resumeInput" class="file-upload-input" accept=".pdf,.doc,.docx">
                    </div>
                    <div id="fileInfo" class="file-info">
                        <i class="fas fa-info-circle"></i> <span id="fileInfoText">No file selected</span>
                    </div>
                    <small>Supported formats: PDF, DOC, DOCX (Max 5MB)</small>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-envelope-open-text"></i> Cover Letter <span class="required">*</span></label>
                    <textarea name="cover_letter" id="coverLetter" required placeholder="Write your cover letter here... Explain why you're interested in this position and why you're the best candidate..."></textarea>
                    <div class="char-counter" id="coverLetterCounter">0 characters (minimum 50)</div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Submit Application
                    </button>
                    <a href="job-details.php?id=<?php echo $job_id; ?>" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    // Make the upload button open file manager on click
    $('#fileUploadBtn').on('click', function() {
        $('#resumeInput').click();
    });
    
    // Handle file selection
    $('#resumeInput').on('change', function(e) {
        var file = e.target.files[0];
        var fileInfo = $('#fileInfo');
        var fileInfoText = $('#fileInfoText');
        
        if (file) {
            var fileName = file.name;
            var fileExt = fileName.split('.').pop().toLowerCase();
            var allowedExt = ['pdf', 'doc', 'docx'];
            var fileSize = file.size;
            var maxSize = 5 * 1024 * 1024; // 5MB
            
            // Validate file extension
            if (allowedExt.indexOf(fileExt) === -1) {
                fileInfo.removeClass('show success').addClass('show error');
                fileInfoText.html('<i class="fas fa-exclamation-triangle"></i> Invalid file type. Please upload PDF, DOC, or DOCX files only.');
                $('#resumeInput').val('');
                return;
            }
            
            // Validate file size
            if (fileSize > maxSize) {
                fileInfo.removeClass('show success').addClass('show error');
                fileInfoText.html('<i class="fas fa-exclamation-triangle"></i> File is too large. Maximum size is 5MB.');
                $('#resumeInput').val('');
                return;
            }
            
            // Valid file
            fileInfo.removeClass('error').addClass('show success');
            fileInfoText.html('<i class="fas fa-check-circle"></i> Selected: ' + fileName + ' (' + (fileSize / 1024 / 1024).toFixed(2) + ' MB)');
            
            // Show success notification
            showNotification('success', 'Resume selected successfully!', 'File Ready');
        } else {
            fileInfo.removeClass('show success error');
            fileInfoText.html('No file selected');
        }
    });
    
    // Cover letter character counter
    function updateCoverLetterCounter() {
        var length = $('#coverLetter').val().length;
        $('#coverLetterCounter').text(length + ' characters (minimum 50)');
        if (length < 50 && length > 0) {
            $('#coverLetterCounter').css('color', '#e74c3c');
        } else if (length >= 50) {
            $('#coverLetterCounter').css('color', '#27ae60');
        } else {
            $('#coverLetterCounter').css('color', '#666');
        }
    }
    
    $('#coverLetter').on('keyup', updateCoverLetterCounter);
    updateCoverLetterCounter();
    
    // Form submission validation
    $('#applicationForm').on('submit', function(e) {
        var coverLetter = $('#coverLetter').val();
        var resume = $('#resumeInput')[0].files[0];
        var isValid = true;
        
        if (!resume) {
            e.preventDefault();
            $('#fileInfo').removeClass('success').addClass('show error');
            $('#fileInfoText').html('<i class="fas fa-exclamation-triangle"></i> Please select a resume/CV file.');
            showNotification('error', 'Please upload your resume/CV.', 'Missing Information');
            isValid = false;
        }
        
        if (coverLetter.length < 50) {
            e.preventDefault();
            showNotification('error', 'Please write a more detailed cover letter (at least 50 characters).', 'Missing Information');
            $('#coverLetter').focus();
            isValid = false;
        }
        
        if (isValid) {
            var submitBtn = $('#submitBtn');
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Submitting Application...');
            submitBtn.prop('disabled', true);
        }
    });
});

function showNotification(type, message, title) {
    if (typeof toastr !== 'undefined') {
        var options = {
            closeButton: true,
            progressBar: true,
            timeOut: 5000,
            positionClass: "toast-top-right"
        };
        
        if (type === 'success') {
            toastr.success(message, title || 'Success!', options);
        } else if (type === 'error') {
            toastr.error(message, title || 'Error!', options);
        } else {
            toastr.info(message, title || 'Information', options);
        }
    } else {
        alert(message);
    }
}
</script>

<?php include 'includes/footer.php'; ?>