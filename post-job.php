<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();
redirectIfNotEmployer();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get employer's companies
$stmt = $pdo->prepare("SELECT * FROM company_profiles WHERE user_id = ? ORDER BY company_name ASC");
$stmt->execute([$user_id]);
$companies = $stmt->fetchAll();

// If no company, redirect to company registration
if (empty($companies)) {
    $_SESSION['info_message'] = "Please register your company first before posting jobs.";
    header('Location: company-register.php');
    exit();
}

// Get categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_id = isset($_POST['company_id']) ? (int)$_POST['company_id'] : 0;
    $title = trim($_POST['title']);
    $category = $_POST['category'];
    $type = $_POST['type'];
    $location = trim($_POST['location']);
    $salary_range = trim($_POST['salary_range']);
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements']);
    $deadline = $_POST['deadline'];
    
    // Get company name for the job
    $stmt = $pdo->prepare("SELECT company_name FROM company_profiles WHERE id = ? AND user_id = ?");
    $stmt->execute([$company_id, $user_id]);
    $company = $stmt->fetch();
    
    if (!$company) {
        $error = "Please select a valid company.";
    } else {
        $company_name = $company['company_name'];
        
        // Validation
        $errors = [];
        if (empty($title)) $errors[] = "Job title is required";
        if (empty($category)) $errors[] = "Category is required";
        if (empty($location)) $errors[] = "Location is required";
        if (empty($description)) $errors[] = "Job description is required";
        if (empty($requirements)) $errors[] = "Requirements are required";
        if (empty($deadline)) $errors[] = "Deadline is required";
        if (strtotime($deadline) <= time()) $errors[] = "Deadline must be in the future";
        if (strtotime($deadline) < strtotime('+7 days')) $errors[] = "Deadline must be at least 7 days from today";
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO jobs (employer_id, company_id, title, company_name, category, type, location, salary_range, description, requirements, deadline, status, posted_date) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
            
            if ($stmt->execute([$user_id, $company_id, $title, $company_name, $category, $type, $location, $salary_range, $description, $requirements, $deadline])) {
                $job_id = $pdo->lastInsertId();
                $current_time = date('H:i:s');
                
                // Log activity
                logUserActivity($pdo, $user_id, 'post_job', "Posted new job: {$title} at {$company_name}");
                
                // Create notification for employer
                createNotification($pdo, $user_id, 'new_job', 'Job Posted Successfully', 
                                  "Your job '{$title}' has been posted successfully at {$current_time}.", 
                                  "job-details.php?id={$job_id}");
                
                // Notify job seekers who have alerts matching this job
                $alert_stmt = $pdo->prepare("SELECT DISTINCT ja.user_id FROM job_alerts ja 
                                             WHERE (ja.category = ? OR ja.category IS NULL) 
                                             AND (ja.location = ? OR ja.location IS NULL)
                                             AND ja.is_active = 1");
                $alert_stmt->execute([$category, $location]);
                $interested_users = $alert_stmt->fetchAll();
                
                foreach ($interested_users as $user) {
                    createNotification($pdo, $user['user_id'], 'new_job', 'New Job Match', 
                                      "New job '{$title}' at {$company_name} matches your preferences.", 
                                      "job-details.php?id={$job_id}");
                }
                
                setSuccess("Job posted successfully! Your job is now live.");
                header('Location: dashboard.php');
                exit();
            } else {
                setError("Failed to post job. Please try again.");
            }
        } else {
            setError(implode("<br>", $errors));
        }
    }
}

include 'includes/header.php';
?>

<style>
.post-job-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
    min-height: calc(100vh - 200px);
}

.post-job-container {
    max-width: 900px;
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

.post-job-form {
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

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 15px;
    font-family: 'Inter', sans-serif;
    transition: all 0.3s ease;
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
    min-height: 120px;
}

small {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #888;
}

.help-text {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #888;
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
    padding-top: 25px;
    border-top: 2px solid #f0f0f0;
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

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border-left: 4px solid #17a2b8;
}

.company-select-wrapper {
    position: relative;
}

.company-select-wrapper select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 16px;
    cursor: pointer;
}

@media (max-width: 768px) {
    .post-job-form {
        padding: 25px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<section class="post-job-section">
    <div class="container">
        <div class="post-job-container">
            <div class="page-header">
                <h1><i class="fas fa-plus-circle"></i> Post a New Job</h1>
                <p>Fill in the details below to reach qualified candidates</p>
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
            
            <?php if(isset($_SESSION['info_message'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <?php 
                        echo $_SESSION['info_message'];
                        unset($_SESSION['info_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="post-job-form" id="postJobForm">
                <!-- Company Selection -->
                <div class="form-group">
                    <label><i class="fas fa-building"></i> Company <span class="required">*</span></label>
                    <div class="company-select-wrapper">
                        <select name="company_id" required>
                            <option value="">Select Your Company</option>
                            <?php foreach($companies as $company): ?>
                                <option value="<?php echo $company['id']; ?>">
                                    <?php echo htmlspecialchars($company['company_name']); ?>
                                    <?php if(!$company['is_verified']): ?>
                                        (Pending Verification)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <small class="help-text">
                        <i class="fas fa-info-circle"></i> 
                        <a href="company-register.php">Register a new company</a> if you don't see your company listed
                    </small>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-heading"></i> Job Title <span class="required">*</span></label>
                    <input type="text" name="title" required placeholder="e.g., Senior Web Developer" autofocus>
                    <small>Be specific and use keywords that candidates would search for</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Category <span class="required">*</span></label>
                        <select name="category" required>
                            <option value="">Select Category</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['name']); ?>">
                                    <?php echo $cat['icon'] . ' ' . htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Job Type <span class="required">*</span></label>
                        <select name="type" required>
                            <option value="full-time">Full Time</option>
                            <option value="part-time">Part Time</option>
                            <option value="contract">Contract</option>
                            <option value="internship">Internship</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Location <span class="required">*</span></label>
                        <input type="text" name="location" required placeholder="e.g., Dar es Salaam">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-money-bill-wave"></i> Salary Range</label>
                        <input type="text" name="salary_range" placeholder="e.g., TSh 500,000 - 1,000,000">
                        <small>Optional - leave blank if negotiable</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Application Deadline <span class="required">*</span></label>
                    <input type="date" name="deadline" required min="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                    <small>Minimum 7 days from today</small>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Job Description <span class="required">*</span></label>
                    <textarea name="description" rows="6" required placeholder="Describe the role, responsibilities, and what the job entails..."></textarea>
                    <div class="char-counter" id="descCounter">0 characters</div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-check-circle"></i> Requirements <span class="required">*</span></label>
                    <textarea name="requirements" rows="6" required placeholder="List the skills, qualifications, and experience required..."></textarea>
                    <div class="char-counter" id="reqCounter">0 characters</div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Post Job
                    </button>
                    <a href="dashboard.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    // Character counters
    function updateDescCounter() {
        var length = $('textarea[name="description"]').val().length;
        $('#descCounter').text(length + ' characters');
        if (length > 5000) {
            $('#descCounter').css('color', '#e74c3c');
        } else {
            $('#descCounter').css('color', '#666');
        }
    }
    
    function updateReqCounter() {
        var length = $('textarea[name="requirements"]').val().length;
        $('#reqCounter').text(length + ' characters');
        if (length > 5000) {
            $('#reqCounter').css('color', '#e74c3c');
        } else {
            $('#reqCounter').css('color', '#666');
        }
    }
    
    $('textarea[name="description"]').on('keyup', updateDescCounter);
    $('textarea[name="requirements"]').on('keyup', updateReqCounter);
    updateDescCounter();
    updateReqCounter();
    
    // Form validation
    $('#postJobForm').on('submit', function(e) {
        var company = $('select[name="company_id"]').val();
        var deadline = new Date($('[name="deadline"]').val());
        var minDate = new Date();
        minDate.setDate(minDate.getDate() + 7);
        
        if (!company) {
            e.preventDefault();
            showNotification('error', 'Please select a company', 'Validation Error');
            return false;
        }
        
        if (deadline < minDate) {
            e.preventDefault();
            showNotification('error', 'Deadline must be at least 7 days from today!', 'Validation Error');
            return false;
        }
        
        var submitBtn = $('#submitBtn');
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Posting Job...');
        submitBtn.prop('disabled', true);
    });
});

function showNotification(type, message, title) {
    if (typeof toastr !== 'undefined') {
        if (type === 'success') toastr.success(message, title || 'Success!');
        else if (type === 'error') toastr.error(message, title || 'Error!');
        else toastr.info(message, title || 'Information');
    } else {
        alert(message);
    }
}
</script>

<?php include 'includes/footer.php'; ?>