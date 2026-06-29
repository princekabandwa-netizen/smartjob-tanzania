<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();
redirectIfNotEmployer();

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get job details
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND employer_id = ?");
$stmt->execute([$job_id, $_SESSION['user_id']]);
$job = $stmt->fetch();

if (!$job) {
    setError("Job not found or you don't have permission to edit it.");
    header('Location: dashboard.php');
    exit();
}

// Store old data for logging
$old_data = $job;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $company_name = trim($_POST['company_name']);
    $category = $_POST['category'];
    $type = $_POST['type'];
    $location = trim($_POST['location']);
    $salary_range = trim($_POST['salary_range']);
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements']);
    $deadline = $_POST['deadline'];
    $status = $_POST['status'];
    
    // Validation
    $errors = [];
    if (empty($title)) $errors[] = "Job title is required";
    if (empty($company_name)) $errors[] = "Company name is required";
    if (empty($category)) $errors[] = "Category is required";
    if (empty($location)) $errors[] = "Location is required";
    if (empty($description)) $errors[] = "Job description is required";
    if (empty($requirements)) $errors[] = "Requirements are required";
    if (empty($deadline)) $errors[] = "Deadline is required";
    if (strtotime($deadline) <= time()) $errors[] = "Deadline must be in the future";
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE jobs SET title = ?, company_name = ?, category = ?, type = ?, 
                               location = ?, salary_range = ?, description = ?, requirements = ?, 
                               deadline = ?, status = ? WHERE id = ? AND employer_id = ?");
        
        if ($stmt->execute([$title, $company_name, $category, $type, $location, $salary_range, 
                           $description, $requirements, $deadline, $status, $job_id, $_SESSION['user_id']])) {
            
            // Track job update
            $current_time = date('H:i:s');
            $changes = [];
            if ($old_data['title'] != $title) $changes[] = "Title: {$old_data['title']} → {$title}";
            if ($old_data['company_name'] != $company_name) $changes[] = "Company: {$old_data['company_name']} → {$company_name}";
            if ($old_data['location'] != $location) $changes[] = "Location: {$old_data['location']} → {$location}";
            if ($old_data['status'] != $status) $changes[] = "Status: {$old_data['status']} → {$status}";
            if ($old_data['salary_range'] != $salary_range) $changes[] = "Salary: {$old_data['salary_range']} → {$salary_range}";
            
            $changes_text = empty($changes) ? "No changes made" : implode(', ', $changes);
            
            // Log activity
            logUserActivity($pdo, $_SESSION['user_id'], 'job_updated', "Updated job #{$job_id}: {$changes_text}");
            
            // Create notification
            createNotification($pdo, $_SESSION['user_id'], 'system', 'Job Updated', 
                              "Your job '{$title}' has been updated successfully at {$current_time}.", 
                              "job-details.php?id={$job_id}");
            
            setSuccess("Job updated successfully!");
            header('Location: dashboard.php');
            exit();
        } else {
            setError("Failed to update job. Please try again.");
        }
    } else {
        setError(implode("<br>", $errors));
    }
}

// Get categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

include 'includes/header.php';
?>

<style>
.edit-job-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
    min-height: calc(100vh - 200px);
}

.edit-job-container {
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

.edit-job-form {
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

.form-group input[readonly] {
    background: #f5f5f5;
    cursor: not-allowed;
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

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 35px;
    padding-top: 25px;
    border-top: 2px solid #f0f0f0;
}

.btn-update {
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

.btn-update:hover {
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

.btn-delete {
    flex: 1;
    padding: 14px;
    background: #dc3545;
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

.btn-delete:hover {
    background: #c82333;
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

.char-counter {
    font-size: 12px;
    margin-top: 5px;
    text-align: right;
    color: #666;
}

@media (max-width: 768px) {
    .edit-job-form {
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

<section class="edit-job-section">
    <div class="container">
        <div class="edit-job-container">
            <div class="page-header">
                <h1><i class="fas fa-edit"></i> Edit Job</h1>
                <p>Update your job posting information</p>
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
            
            <form method="POST" action="" class="edit-job-form" id="editJobForm">
                <div class="form-group">
                    <label><i class="fas fa-heading"></i> Job Title <span class="required">*</span></label>
                    <input type="text" name="title" required value="<?php echo htmlspecialchars($job['title']); ?>" placeholder="e.g., Senior Web Developer">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-building"></i> Company Name <span class="required">*</span></label>
                    <input type="text" name="company_name" required value="<?php echo htmlspecialchars($job['company_name']); ?>" placeholder="Your company name">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Category <span class="required">*</span></label>
                        <select name="category" required>
                            <option value="">Select Category</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['name']); ?>" <?php echo $job['category'] == $cat['name'] ? 'selected' : ''; ?>>
                                    <?php echo $cat['icon'] . ' ' . htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Job Type <span class="required">*</span></label>
                        <select name="type" required>
                            <option value="full-time" <?php echo $job['type'] == 'full-time' ? 'selected' : ''; ?>>Full Time</option>
                            <option value="part-time" <?php echo $job['type'] == 'part-time' ? 'selected' : ''; ?>>Part Time</option>
                            <option value="contract" <?php echo $job['type'] == 'contract' ? 'selected' : ''; ?>>Contract</option>
                            <option value="internship" <?php echo $job['type'] == 'internship' ? 'selected' : ''; ?>>Internship</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Location <span class="required">*</span></label>
                        <input type="text" name="location" required value="<?php echo htmlspecialchars($job['location']); ?>" placeholder="e.g., Dar es Salaam">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-money-bill-wave"></i> Salary Range</label>
                        <input type="text" name="salary_range" value="<?php echo htmlspecialchars($job['salary_range']); ?>" placeholder="e.g., TSh 500,000 - 1,000,000">
                        <small>Optional - leave blank if negotiable</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Application Deadline <span class="required">*</span></label>
                        <input type="date" name="deadline" required value="<?php echo $job['deadline']; ?>" min="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                        <small>Minimum 7 days from today</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-toggle-on"></i> Job Status</label>
                        <select name="status">
                            <option value="active" <?php echo $job['status'] == 'active' ? 'selected' : ''; ?>>Active - Accepting Applications</option>
                            <option value="closed" <?php echo $job['status'] == 'closed' ? 'selected' : ''; ?>>Closed - No Longer Accepting</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Job Description <span class="required">*</span></label>
                    <textarea name="description" rows="6" required placeholder="Describe the role, responsibilities, and what the job entails..."><?php echo htmlspecialchars($job['description']); ?></textarea>
                    <div class="char-counter" id="descCounter">0 characters</div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-check-circle"></i> Requirements <span class="required">*</span></label>
                    <textarea name="requirements" rows="6" required placeholder="List the skills, qualifications, and experience required..."><?php echo htmlspecialchars($job['requirements']); ?></textarea>
                    <div class="char-counter" id="reqCounter">0 characters</div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-update" id="submitBtn">
                        <i class="fas fa-save"></i> Update Job
                    </button>
                    <a href="dashboard.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <a href="delete-job.php?id=<?php echo $job_id; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this job? This action cannot be undone.')">
                        <i class="fas fa-trash"></i> Delete Job
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
// Character counters
$(document).ready(function() {
    function updateCharCount(textarea, counterId, maxLength) {
        var length = $(textarea).val().length;
        $(counterId).text(length + ' characters');
        if (length > maxLength) {
            $(counterId).css('color', '#e74c3c');
        } else {
            $(counterId).css('color', '#666');
        }
    }
    
    $('#editJobForm textarea[name="description"]').on('keyup', function() {
        updateCharCount(this, '#descCounter', 5000);
    });
    updateCharCount('#editJobForm textarea[name="description"]', '#descCounter', 5000);
    
    $('#editJobForm textarea[name="requirements"]').on('keyup', function() {
        updateCharCount(this, '#reqCounter', 5000);
    });
    updateCharCount('#editJobForm textarea[name="requirements"]', '#reqCounter', 5000);
    
    // Form validation
    $('#editJobForm').on('submit', function(e) {
        var deadline = new Date($('[name="deadline"]').val());
        var minDate = new Date();
        minDate.setDate(minDate.getDate() + 7);
        
        if (deadline < minDate) {
            e.preventDefault();
            showNotification('error', 'Deadline must be at least 7 days from today!', 'Validation Error');
            return false;
        }
        
        var submitBtn = $('#submitBtn');
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...');
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