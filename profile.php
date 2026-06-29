<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user profile data
$stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

// Get user certifications
$stmt = $pdo->prepare("SELECT * FROM user_certifications WHERE user_id = ? ORDER BY year_completed DESC");
$stmt->execute([$user_id]);
$certifications = $stmt->fetchAll();

// Get user education
$stmt = $pdo->prepare("SELECT * FROM user_education WHERE user_id = ? ORDER BY end_year DESC");
$stmt->execute([$user_id]);
$education = $stmt->fetchAll();

// Get user work experience
$stmt = $pdo->prepare("SELECT * FROM user_experience WHERE user_id = ? ORDER BY end_date DESC");
$stmt->execute([$user_id]);
$experiences = $stmt->fetchAll();

// Get profile completion from session or calculate
$profileCompletion = isset($_SESSION['profile_completion']) ? $_SESSION['profile_completion'] : calculateProfileCompletion($pdo, $user_id, $user_role);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action == 'update_basic') {
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        
        $errors = [];
        if (empty($full_name)) $errors[] = "Full name is required";
        if (empty($email)) $errors[] = "Email is required";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, email = ? WHERE id = ?");
            $stmt->execute([$full_name, $phone, $email, $user_id]);
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;
            
            updateProfileCompletionSession($pdo, $user_id, $user_role);
            
            $_SESSION['success_message'] = "Basic information updated successfully!";
        } else {
            $_SESSION['error_message'] = implode("<br>", $errors);
        }
        header('Location: profile.php');
        exit();
    }
    
    if ($action == 'update_bio') {
        $bio = trim($_POST['bio']);
        $skills = trim($_POST['skills']);
        $experience = trim($_POST['experience']);
        
        if ($profile) {
            $stmt = $pdo->prepare("UPDATE user_profiles SET bio = ?, skills = ?, experience = ? WHERE user_id = ?");
            $stmt->execute([$bio, $skills, $experience, $user_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, bio, skills, experience) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $bio, $skills, $experience]);
        }
        
        updateProfileCompletionSession($pdo, $user_id, $user_role);
        
        $_SESSION['success_message'] = "Professional information updated successfully!";
        header('Location: profile.php');
        exit();
    }
    
    if ($action == 'add_experience') {
        $job_title = trim($_POST['job_title']);
        $company = trim($_POST['company']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $is_current = isset($_POST['is_current']) ? 1 : 0;
        $description = trim($_POST['description']);
        
        $stmt = $pdo->prepare("INSERT INTO user_experience (user_id, job_title, company, start_date, end_date, is_current, description) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $job_title, $company, $start_date, $is_current ? null : $end_date, $is_current, $description]);
        
        updateProfileCompletionSession($pdo, $user_id, $user_role);
        
        $_SESSION['success_message'] = "Work experience added successfully!";
        header('Location: profile.php');
        exit();
    }
    
    if ($action == 'add_education') {
        $degree = trim($_POST['degree']);
        $institution = trim($_POST['institution']);
        $start_year = $_POST['start_year'];
        $end_year = $_POST['end_year'];
        $description = trim($_POST['description']);
        
        $stmt = $pdo->prepare("INSERT INTO user_education (user_id, degree, institution, start_year, end_year, description) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $degree, $institution, $start_year, $end_year, $description]);
        
        updateProfileCompletionSession($pdo, $user_id, $user_role);
        
        $_SESSION['success_message'] = "Education added successfully!";
        header('Location: profile.php');
        exit();
    }
    
    if ($action == 'add_certification') {
        $cert_name = trim($_POST['cert_name']);
        $issuing_org = trim($_POST['issuing_org']);
        $year_completed = $_POST['year_completed'];
        $cert_link = trim($_POST['cert_link']);
        
        $stmt = $pdo->prepare("INSERT INTO user_certifications (user_id, cert_name, issuing_org, year_completed, cert_link) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $cert_name, $issuing_org, $year_completed, $cert_link]);
        
        updateProfileCompletionSession($pdo, $user_id, $user_role);
        
        $_SESSION['success_message'] = "Certification added successfully!";
        header('Location: profile.php');
        exit();
    }
    
    if ($action == 'delete_item') {
        $item_type = $_POST['item_type'];
        $item_id = (int)$_POST['item_id'];
        
        if ($item_type == 'experience') {
            $stmt = $pdo->prepare("DELETE FROM user_experience WHERE id = ? AND user_id = ?");
            $stmt->execute([$item_id, $user_id]);
        } elseif ($item_type == 'education') {
            $stmt = $pdo->prepare("DELETE FROM user_education WHERE id = ? AND user_id = ?");
            $stmt->execute([$item_id, $user_id]);
        } elseif ($item_type == 'certification') {
            $stmt = $pdo->prepare("DELETE FROM user_certifications WHERE id = ? AND user_id = ?");
            $stmt->execute([$item_id, $user_id]);
        }
        
        updateProfileCompletionSession($pdo, $user_id, $user_role);
        
        $_SESSION['success_message'] = "Item deleted successfully!";
        header('Location: profile.php');
        exit();
    }
}

include 'includes/header.php';
?>

<style>
.profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px;
}

.profile-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    color: white;
}

.profile-avatar {
    display: flex;
    align-items: center;
    gap: 25px;
    flex-wrap: wrap;
}

.avatar-circle {
    width: 100px;
    height: 100px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: bold;
    border: 3px solid white;
}

.avatar-info h1 {
    margin-bottom: 8px;
    font-size: 1.8rem;
}

.avatar-info p {
    opacity: 0.9;
    margin-bottom: 5px;
}

.avatar-info .role-badge {
    background: rgba(255,255,255,0.2);
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    display: inline-block;
    margin-right: 10px;
}

.completion-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.completion-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.completion-header h3 {
    margin: 0;
    color: var(--primary-color);
}

.completion-percent {
    font-size: 20px;
    font-weight: bold;
    color: var(--secondary-color);
}

.progress-bar-container {
    background: #e0e0e0;
    border-radius: 10px;
    height: 10px;
    overflow: hidden;
    margin: 15px 0;
}

.progress-fill {
    background: linear-gradient(90deg, var(--secondary-color), #f39c12);
    height: 100%;
    border-radius: 10px;
    transition: width 0.5s ease;
    width: <?php echo $profileCompletion; ?>%;
}

.completion-tips {
    background: #fff3cd;
    padding: 12px;
    border-radius: 10px;
    margin-top: 15px;
    font-size: 13px;
    color: #856404;
}

.completion-tips ul {
    margin: 8px 0 0 20px;
}

.form-card {
    background: white;
    border-radius: 16px;
    margin-bottom: 25px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.card-title {
    background: #f8f9fa;
    padding: 18px 20px;
    border-bottom: 1px solid #e0e0e0;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--primary-color);
}

.card-title i {
    margin-right: 10px;
    color: var(--secondary-color);
}

.card-body {
    padding: 25px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--primary-color);
    font-size: 14px;
}

.form-group label i {
    margin-right: 8px;
    color: var(--secondary-color);
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--secondary-color);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.btn-submit {
    background: linear-gradient(135deg, var(--secondary-color), #c0392b);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(231,76,60,0.3);
}

.btn-secondary {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
}

.btn-danger {
    background: #dc3545;
    color: white;
    border: none;
    padding: 5px 12px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 12px;
}

.items-list {
    margin-top: 20px;
}

.item-card {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 15px;
    position: relative;
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    flex-wrap: wrap;
    gap: 10px;
}

.item-title {
    font-weight: 600;
    color: var(--primary-color);
}

.item-date {
    font-size: 12px;
    color: #666;
}

.item-org {
    font-size: 13px;
    color: #666;
    margin-bottom: 8px;
}

.item-description {
    font-size: 13px;
    color: #555;
    line-height: 1.5;
}

.item-actions {
    position: absolute;
    top: 15px;
    right: 15px;
}

.alert {
    padding: 12px 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

.modal {
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(5px);
    display: none;
}

.modal-content {
    background: white;
    margin: 5% auto;
    width: 90%;
    max-width: 550px;
    border-radius: 16px;
    animation: modalSlideIn 0.3s ease;
}

.modal-header {
    padding: 20px;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
    border-radius: 16px 16px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header .close {
    font-size: 28px;
    cursor: pointer;
}

.modal-body {
    padding: 25px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    gap: 10px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .profile-avatar {
        flex-direction: column;
        text-align: center;
    }
    
    .avatar-circle {
        margin: 0 auto;
    }
    
    .item-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .item-actions {
        position: static;
        margin-top: 10px;
    }
}
</style>

<div class="profile-container">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar">
            <div class="avatar-circle">
                <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
            </div>
            <div class="avatar-info">
                <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                <?php if($user['phone']): ?>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?></p>
                <?php endif; ?>
                <span class="role-badge"><i class="fas fa-briefcase"></i> <?php echo ucfirst($user['role']); ?></span>
                <span class="role-badge"><i class="fas fa-calendar"></i> Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
            </div>
        </div>
    </div>

    <!-- Profile Completion Card -->
    <div class="completion-card">
        <div class="completion-header">
            <h3><i class="fas fa-chart-line"></i> Profile Strength</h3>
            <span class="completion-percent"><?php echo $profileCompletion; ?>%</span>
        </div>
        <div class="progress-bar-container">
            <div class="progress-fill" style="width: <?php echo $profileCompletion; ?>%;"></div>
        </div>
        <?php if($profileCompletion < 100): ?>
        <div class="completion-tips">
            <i class="fas fa-info-circle"></i> <strong>Complete your profile to get better opportunities!</strong>
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
    </div>

    <!-- Alert Messages -->
    <?php if(isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <!-- Basic Information Form -->
    <div class="form-card">
        <div class="card-title">
            <i class="fas fa-user"></i> Basic Information
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_basic">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Full Name *</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email Address *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="+255 XXX XXX XXX">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user-circle"></i> Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled readonly>
                        <small>Username cannot be changed</small>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Save Changes</button>
                <a href="forgot-password.php" class="btn-secondary" style="margin-left: 10px; display: inline-block; text-decoration: none;">
                    <i class="fas fa-key"></i> Change Password
                </a>
            </form>
        </div>
    </div>

    <!-- Professional Information Form -->
    <div class="form-card">
        <div class="card-title">
            <i class="fas fa-briefcase"></i> Professional Information
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_bio">
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Bio / About Me</label>
                    <textarea name="bio" rows="4" placeholder="Tell us about yourself, your career goals, and what you're looking for..."><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-code"></i> Skills (comma separated)</label>
                    <textarea name="skills" rows="3" placeholder="e.g., PHP, JavaScript, Project Management, Communication, Leadership..."><?php echo htmlspecialchars($profile['skills'] ?? ''); ?></textarea>
                    <small>List your key skills separated by commas</small>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-briefcase"></i> Professional Summary / Experience</label>
                    <textarea name="experience" rows="4" placeholder="Summarize your professional experience and key achievements..."><?php echo htmlspecialchars($profile['experience'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn-submit">Save Professional Info</button>
            </form>
        </div>
    </div>

    <!-- Work Experience Section -->
    <div class="form-card">
        <div class="card-title">
            <i class="fas fa-briefcase"></i> Work Experience
            <button onclick="openModal('experienceModal')" style="float: right; background: var(--secondary-color); color: white; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer;">
                <i class="fas fa-plus"></i> Add Experience
            </button>
        </div>
        <div class="card-body">
            <?php if(empty($experiences)): ?>
                <p style="color: #999; text-align: center; padding: 20px;">No work experience added yet</p>
            <?php else: ?>
                <div class="items-list">
                    <?php foreach($experiences as $exp): ?>
                        <div class="item-card">
                            <div class="item-header">
                                <span class="item-title"><?php echo htmlspecialchars($exp['job_title']); ?> at <?php echo htmlspecialchars($exp['company']); ?></span>
                                <span class="item-date"><?php echo date('M Y', strtotime($exp['start_date'])); ?> - <?php echo $exp['is_current'] ? 'Present' : date('M Y', strtotime($exp['end_date'])); ?></span>
                            </div>
                            <?php if($exp['description']): ?>
                                <div class="item-description"><?php echo nl2br(htmlspecialchars($exp['description'])); ?></div>
                            <?php endif; ?>
                            <div class="item-actions">
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Delete this experience?')">
                                    <input type="hidden" name="action" value="delete_item">
                                    <input type="hidden" name="item_type" value="experience">
                                    <input type="hidden" name="item_id" value="<?php echo $exp['id']; ?>">
                                    <button type="submit" class="btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Education Section -->
    <div class="form-card">
        <div class="card-title">
            <i class="fas fa-graduation-cap"></i> Education
            <button onclick="openModal('educationModal')" style="float: right; background: var(--secondary-color); color: white; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer;">
                <i class="fas fa-plus"></i> Add Education
            </button>
        </div>
        <div class="card-body">
            <?php if(empty($education)): ?>
                <p style="color: #999; text-align: center; padding: 20px;">No education added yet</p>
            <?php else: ?>
                <div class="items-list">
                    <?php foreach($education as $edu): ?>
                        <div class="item-card">
                            <div class="item-header">
                                <span class="item-title"><?php echo htmlspecialchars($edu['degree']); ?></span>
                                <span class="item-date"><?php echo $edu['start_year']; ?> - <?php echo $edu['end_year']; ?></span>
                            </div>
                            <div class="item-org"><?php echo htmlspecialchars($edu['institution']); ?></div>
                            <?php if($edu['description']): ?>
                                <div class="item-description"><?php echo nl2br(htmlspecialchars($edu['description'])); ?></div>
                            <?php endif; ?>
                            <div class="item-actions">
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Delete this education?')">
                                    <input type="hidden" name="action" value="delete_item">
                                    <input type="hidden" name="item_type" value="education">
                                    <input type="hidden" name="item_id" value="<?php echo $edu['id']; ?>">
                                    <button type="submit" class="btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Certifications Section -->
    <div class="form-card">
        <div class="card-title">
            <i class="fas fa-certificate"></i> Certifications
            <button onclick="openModal('certificationModal')" style="float: right; background: var(--secondary-color); color: white; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer;">
                <i class="fas fa-plus"></i> Add Certification
            </button>
        </div>
        <div class="card-body">
            <?php if(empty($certifications)): ?>
                <p style="color: #999; text-align: center; padding: 20px;">No certifications added yet</p>
            <?php else: ?>
                <div class="items-list">
                    <?php foreach($certifications as $cert): ?>
                        <div class="item-card">
                            <div class="item-header">
                                <span class="item-title"><?php echo htmlspecialchars($cert['cert_name']); ?></span>
                                <span class="item-date"><?php echo $cert['year_completed']; ?></span>
                            </div>
                            <div class="item-org">Issued by: <?php echo htmlspecialchars($cert['issuing_org']); ?></div>
                            <?php if($cert['cert_link']): ?>
                                <div><a href="<?php echo $cert['cert_link']; ?>" target="_blank">View Certificate</a></div>
                            <?php endif; ?>
                            <div class="item-actions">
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Delete this certification?')">
                                    <input type="hidden" name="action" value="delete_item">
                                    <input type="hidden" name="item_type" value="certification">
                                    <input type="hidden" name="item_id" value="<?php echo $cert['id']; ?>">
                                    <button type="submit" class="btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Experience Modal -->
<div id="experienceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-briefcase"></i> Add Work Experience</h3>
            <span class="close" onclick="closeModal('experienceModal')">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_experience">
            <div class="modal-body">
                <div class="form-group">
                    <label>Job Title *</label>
                    <input type="text" name="job_title" required placeholder="e.g., Senior Web Developer">
                </div>
                <div class="form-group">
                    <label>Company *</label>
                    <input type="text" name="company" required placeholder="e.g., Tech Company Ltd">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date *</label>
                        <input type="date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date">
                    </div>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_current" value="1"> I currently work here
                    </label>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4" placeholder="Describe your responsibilities and achievements..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn-submit">Add Experience</button>
                <button type="button" class="btn-secondary" onclick="closeModal('experienceModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Education Modal -->
<div id="educationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-graduation-cap"></i> Add Education</h3>
            <span class="close" onclick="closeModal('educationModal')">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_education">
            <div class="modal-body">
                <div class="form-group">
                    <label>Degree / Qualification *</label>
                    <input type="text" name="degree" required placeholder="e.g., Bachelor of Science in Computer Science">
                </div>
                <div class="form-group">
                    <label>Institution *</label>
                    <input type="text" name="institution" required placeholder="e.g., University of Dar es Salaam">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Year *</label>
                        <input type="number" name="start_year" required min="1950" max="2030">
                    </div>
                    <div class="form-group">
                        <label>End Year *</label>
                        <input type="number" name="end_year" required min="1950" max="2030">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Additional details about your education..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn-submit">Add Education</button>
                <button type="button" class="btn-secondary" onclick="closeModal('educationModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Certification Modal -->
<div id="certificationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-certificate"></i> Add Certification</h3>
            <span class="close" onclick="closeModal('certificationModal')">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_certification">
            <div class="modal-body">
                <div class="form-group">
                    <label>Certification Name *</label>
                    <input type="text" name="cert_name" required placeholder="e.g., Certified Project Management Professional">
                </div>
                <div class="form-group">
                    <label>Issuing Organization *</label>
                    <input type="text" name="issuing_org" required placeholder="e.g., Google, Microsoft, PMI">
                </div>
                <div class="form-group">
                    <label>Year Completed *</label>
                    <input type="number" name="year_completed" required min="1980" max="2030">
                </div>
                <div class="form-group">
                    <label>Certificate URL (Optional)</label>
                    <input type="url" name="cert_link" placeholder="https://...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn-submit">Add Certification</button>
                <button type="button" class="btn-secondary" onclick="closeModal('certificationModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = 'auto';
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

document.querySelector('input[name="phone"]')?.addEventListener('input', function(e) {
    let phone = e.target.value;
    if (phone.length === 10 && phone.startsWith('0')) {
        e.target.value = '+255' + phone.substring(1);
    }
});
</script>

<?php include 'includes/footer.php'; ?>