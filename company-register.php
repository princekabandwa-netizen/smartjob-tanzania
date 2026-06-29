<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();
redirectIfNotEmployer();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Check if company already exists
$stmt = $pdo->prepare("SELECT * FROM company_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

// Create company_profiles table if not exists (with correct structure)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS company_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        company_name VARCHAR(200),
        company_description TEXT,
        website VARCHAR(200),
        industry VARCHAR(100),
        company_size VARCHAR(50),
        founded_year INT,
        headquarters VARCHAR(200),
        phone VARCHAR(50),
        email VARCHAR(100),
        facebook VARCHAR(200),
        twitter VARCHAR(200),
        linkedin VARCHAR(200),
        logo_path VARCHAR(500),
        banner_path VARCHAR(500),
        is_verified TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user (user_id)
    )");
} catch (PDOException $e) {
    // Table might already exist, continue
}

// Check and add missing columns if needed
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM company_profiles LIKE 'banner_path'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE company_profiles ADD COLUMN banner_path VARCHAR(500) NULL");
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM company_profiles LIKE 'is_verified'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE company_profiles ADD COLUMN is_verified TINYINT DEFAULT 0");
    }
} catch (PDOException $e) {
    // Columns might already exist, continue
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_name = trim($_POST['company_name']);
    $company_description = trim($_POST['company_description']);
    $website = trim($_POST['website']);
    $industry = $_POST['industry'];
    $company_size = $_POST['company_size'];
    $founded_year = $_POST['founded_year'];
    $headquarters = trim($_POST['headquarters']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $facebook = trim($_POST['facebook']);
    $twitter = trim($_POST['twitter']);
    $linkedin = trim($_POST['linkedin']);
    
    $errors = [];
    
    if (empty($company_name)) $errors[] = "Company name is required";
    if (empty($industry)) $errors[] = "Industry is required";
    if (empty($headquarters)) $errors[] = "Headquarters is required";
    
    // Handle logo upload
    $logo_path = $company['logo_path'] ?? null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        $filename = $_FILES['logo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $file_size = $_FILES['logo']['size'];
        
        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid logo format. Allowed: " . implode(', ', $allowed);
        }
        
        if ($file_size > 2 * 1024 * 1024) {
            $errors[] = "Logo file is too large. Maximum size is 2MB";
        }
        
        if (empty($errors)) {
            if (!is_dir('uploads/company_logos')) {
                mkdir('uploads/company_logos', 0777, true);
            }
            $logo_path = 'uploads/company_logos/' . time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $filename);
            move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path);
        }
    }
    
    // Handle banner upload
    $banner_path = $company['banner_path'] ?? null;
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['banner']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $file_size = $_FILES['banner']['size'];
        
        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid banner format. Allowed: " . implode(', ', $allowed);
        }
        
        if ($file_size > 5 * 1024 * 1024) {
            $errors[] = "Banner file is too large. Maximum size is 5MB";
        }
        
        if (empty($errors)) {
            if (!is_dir('uploads/company_banners')) {
                mkdir('uploads/company_banners', 0777, true);
            }
            $banner_path = 'uploads/company_banners/' . time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $filename);
            move_uploaded_file($_FILES['banner']['tmp_name'], $banner_path);
        }
    }
    
    if (empty($errors)) {
        if ($company) {
            // Update existing company
            $sql = "UPDATE company_profiles SET 
                    company_name = ?, company_description = ?, website = ?, industry = ?, 
                    company_size = ?, founded_year = ?, headquarters = ?, phone = ?, 
                    email = ?, facebook = ?, twitter = ?, linkedin = ?, logo_path = ?";
            $params = [$company_name, $company_description, $website, $industry, 
                      $company_size, $founded_year, $headquarters, $phone, $email, 
                      $facebook, $twitter, $linkedin, $logo_path];
            
            // Check if banner_path column exists
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM company_profiles LIKE 'banner_path'");
                if ($stmt->rowCount() > 0) {
                    $sql .= ", banner_path = ?";
                    $params[] = $banner_path;
                }
            } catch (PDOException $e) {}
            
            $sql .= " WHERE user_id = ?";
            $params[] = $user_id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            // Create new company
            $sql = "INSERT INTO company_profiles 
                    (user_id, company_name, company_description, website, industry, 
                     company_size, founded_year, headquarters, phone, email, 
                     facebook, twitter, linkedin, logo_path";
            $params = [$user_id, $company_name, $company_description, $website, $industry, 
                      $company_size, $founded_year, $headquarters, $phone, $email, 
                      $facebook, $twitter, $linkedin, $logo_path];
            
            // Check if banner_path column exists
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM company_profiles LIKE 'banner_path'");
                if ($stmt->rowCount() > 0) {
                    $sql .= ", banner_path";
                    $params[] = $banner_path;
                }
            } catch (PDOException $e) {}
            
            // Check if is_verified column exists
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM company_profiles LIKE 'is_verified'");
                if ($stmt->rowCount() > 0) {
                    $sql .= ", is_verified";
                    $params[] = 0;
                }
            } catch (PDOException $e) {}
            
            $sql .= ") VALUES (" . implode(',', array_fill(0, count($params), '?')) . ")";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }
        
        // Log activity
        logUserActivity($pdo, $user_id, 'company_registered', "Company registered: {$company_name}");
        
        $_SESSION['success_message'] = "Company registered successfully! You can now post jobs.";
        header('Location: post-job.php');
        exit();
    } else {
        $error = implode("<br>", $errors);
    }
}

include 'includes/header.php';
?>

<style>
.company-register-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
    min-height: calc(100vh - 200px);
}

.company-register-container {
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

.register-card {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.form-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.form-section h3 {
    color: var(--primary-color);
    margin-bottom: 20px;
    font-size: 1.2rem;
}

.form-section h3 i {
    color: var(--secondary-color);
    margin-right: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
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
}

textarea {
    resize: vertical;
    min-height: 100px;
}

small {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #888;
}

.file-upload-area {
    border: 2px dashed #ccc;
    border-radius: 10px;
    padding: 30px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.file-upload-area:hover {
    border-color: var(--secondary-color);
    background: #f8f9fa;
}

.file-upload-area i {
    font-size: 36px;
    color: var(--secondary-color);
    margin-bottom: 10px;
}

.file-upload-area input {
    display: none;
}

.file-name {
    margin-top: 10px;
    color: #27ae60;
    font-size: 14px;
}

.company-logo-preview {
    max-width: 150px;
    max-height: 100px;
    margin-top: 10px;
    display: block;
    border: 1px solid #e0e0e0;
    padding: 5px;
    border-radius: 8px;
}

.company-banner-preview {
    max-width: 100%;
    max-height: 120px;
    margin-top: 10px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
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

.btn-skip {
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

.btn-skip:hover {
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

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .register-card {
        padding: 25px;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<section class="company-register-section">
    <div class="container">
        <div class="company-register-container">
            <div class="page-header">
                <h1><i class="fas fa-building"></i> <?php echo $company ? 'Update Company Profile' : 'Register Your Company'; ?></h1>
                <p><?php echo $company ? 'Update your company information' : 'Register your company to start posting jobs and finding talent'; ?></p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['info_message'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <?php echo $_SESSION['info_message']; unset($_SESSION['info_message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="register-card">
                <form method="POST" action="" enctype="multipart/form-data" id="companyForm">
                    <!-- Company Logo & Banner -->
                    <div class="form-section">
                        <h3><i class="fas fa-image"></i> Company Media</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-image"></i> Company Logo</label>
                                <div class="file-upload-area" onclick="document.getElementById('logo_input').click()">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p><strong>Click to upload company logo</strong></p>
                                    <small>Recommended: PNG, JPG, SVG. Max 2MB</small>
                                    <input type="file" name="logo" id="logo_input" accept="image/*">
                                </div>
                                <?php if($company && $company['logo_path']): ?>
                                    <img src="<?php echo $company['logo_path']; ?>" class="company-logo-preview" alt="Company Logo">
                                <?php endif; ?>
                                <div id="logo_name" class="file-name"></div>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-image"></i> Company Banner</label>
                                <div class="file-upload-area" onclick="document.getElementById('banner_input').click()">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p><strong>Click to upload company banner</strong></p>
                                    <small>Recommended: PNG, JPG. Max 5MB</small>
                                    <input type="file" name="banner" id="banner_input" accept="image/*">
                                </div>
                                <?php if($company && $company['banner_path']): ?>
                                    <img src="<?php echo $company['banner_path']; ?>" class="company-banner-preview" alt="Company Banner">
                                <?php endif; ?>
                                <div id="banner_name" class="file-name"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Basic Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Company Name <span class="required">*</span></label>
                            <input type="text" name="company_name" required value="<?php echo $company ? htmlspecialchars($company['company_name']) : ''; ?>" placeholder="e.g., Tech Solutions Tanzania Ltd">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-align-left"></i> Company Description</label>
                            <textarea name="company_description" rows="4" placeholder="Describe your company, mission, and values..."><?php echo $company ? htmlspecialchars($company['company_description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-globe"></i> Website</label>
                                <input type="url" name="website" value="<?php echo $company ? htmlspecialchars($company['website']) : ''; ?>" placeholder="https://www.yourcompany.com">
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-chart-line"></i> Industry <span class="required">*</span></label>
                                <select name="industry" required>
                                    <option value="">Select Industry</option>
                                    <option value="Technology" <?php echo ($company && $company['industry'] == 'Technology') ? 'selected' : ''; ?>>Technology</option>
                                    <option value="Healthcare" <?php echo ($company && $company['industry'] == 'Healthcare') ? 'selected' : ''; ?>>Healthcare</option>
                                    <option value="Finance" <?php echo ($company && $company['industry'] == 'Finance') ? 'selected' : ''; ?>>Finance</option>
                                    <option value="Education" <?php echo ($company && $company['industry'] == 'Education') ? 'selected' : ''; ?>>Education</option>
                                    <option value="Manufacturing" <?php echo ($company && $company['industry'] == 'Manufacturing') ? 'selected' : ''; ?>>Manufacturing</option>
                                    <option value="Retail" <?php echo ($company && $company['industry'] == 'Retail') ? 'selected' : ''; ?>>Retail</option>
                                    <option value="Construction" <?php echo ($company && $company['industry'] == 'Construction') ? 'selected' : ''; ?>>Construction</option>
                                    <option value="Hospitality" <?php echo ($company && $company['industry'] == 'Hospitality') ? 'selected' : ''; ?>>Hospitality</option>
                                    <option value="Agriculture" <?php echo ($company && $company['industry'] == 'Agriculture') ? 'selected' : ''; ?>>Agriculture</option>
                                    <option value="Energy" <?php echo ($company && $company['industry'] == 'Energy') ? 'selected' : ''; ?>>Energy</option>
                                    <option value="Telecommunications" <?php echo ($company && $company['industry'] == 'Telecommunications') ? 'selected' : ''; ?>>Telecommunications</option>
                                    <option value="Transportation" <?php echo ($company && $company['industry'] == 'Transportation') ? 'selected' : ''; ?>>Transportation</option>
                                    <option value="Media" <?php echo ($company && $company['industry'] == 'Media') ? 'selected' : ''; ?>>Media</option>
                                    <option value="Non-Profit" <?php echo ($company && $company['industry'] == 'Non-Profit') ? 'selected' : ''; ?>>Non-Profit</option>
                                    <option value="Government" <?php echo ($company && $company['industry'] == 'Government') ? 'selected' : ''; ?>>Government</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-users"></i> Company Size</label>
                                <select name="company_size">
                                    <option value="">Select Size</option>
                                    <option value="1-10" <?php echo ($company && $company['company_size'] == '1-10') ? 'selected' : ''; ?>>1-10 employees</option>
                                    <option value="11-50" <?php echo ($company && $company['company_size'] == '11-50') ? 'selected' : ''; ?>>11-50 employees</option>
                                    <option value="51-200" <?php echo ($company && $company['company_size'] == '51-200') ? 'selected' : ''; ?>>51-200 employees</option>
                                    <option value="201-500" <?php echo ($company && $company['company_size'] == '201-500') ? 'selected' : ''; ?>>201-500 employees</option>
                                    <option value="500+" <?php echo ($company && $company['company_size'] == '500+') ? 'selected' : ''; ?>>500+ employees</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt"></i> Founded Year</label>
                                <input type="number" name="founded_year" value="<?php echo $company ? htmlspecialchars($company['founded_year']) : ''; ?>" placeholder="e.g., 2010" min="1900" max="<?php echo date('Y'); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Headquarters <span class="required">*</span></label>
                            <input type="text" name="headquarters" required value="<?php echo $company ? htmlspecialchars($company['headquarters']) : ''; ?>" placeholder="e.g., Dar es Salaam, Tanzania">
                        </div>
                    </div>
                    
                    <!-- Contact Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-address-card"></i> Contact Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-phone"></i> Phone</label>
                                <input type="tel" name="phone" value="<?php echo $company ? htmlspecialchars($company['phone']) : ''; ?>" placeholder="+255 XXX XXX XXX">
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" name="email" value="<?php echo $company ? htmlspecialchars($company['email']) : ''; ?>" placeholder="contact@yourcompany.com">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Media -->
                    <div class="form-section">
                        <h3><i class="fas fa-share-alt"></i> Social Media</h3>
                        <div class="form-group">
                            <label><i class="fab fa-facebook"></i> Facebook</label>
                            <input type="url" name="facebook" value="<?php echo $company ? htmlspecialchars($company['facebook']) : ''; ?>" placeholder="https://facebook.com/yourcompany">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-twitter"></i> Twitter</label>
                            <input type="url" name="twitter" value="<?php echo $company ? htmlspecialchars($company['twitter']) : ''; ?>" placeholder="https://twitter.com/yourcompany">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-linkedin"></i> LinkedIn</label>
                            <input type="url" name="linkedin" value="<?php echo $company ? htmlspecialchars($company['linkedin']) : ''; ?>" placeholder="https://linkedin.com/company/yourcompany">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="fas fa-save"></i> <?php echo $company ? 'Update Company' : 'Register Company'; ?>
                        </button>
                        <a href="post-job.php" class="btn-skip">
                            <i class="fas fa-arrow-right"></i> <?php echo $company ? 'Skip to Job Posting' : 'Skip for Now'; ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
// File upload handlers
document.getElementById('logo_input').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (file) {
        document.getElementById('logo_name').innerHTML = '<i class="fas fa-check-circle"></i> Selected: ' + file.name;
    }
});

document.getElementById('banner_input').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (file) {
        document.getElementById('banner_name').innerHTML = '<i class="fas fa-check-circle"></i> Selected: ' + file.name;
    }
});

// Preview logo image
document.getElementById('logo_input').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.querySelector('.company-logo-preview');
            if (img) {
                img.src = e.target.result;
            } else {
                var preview = document.createElement('img');
                preview.className = 'company-logo-preview';
                preview.src = e.target.result;
                document.querySelector('.file-upload-area').after(preview);
            }
        };
        reader.readAsDataURL(file);
    }
});

// Preview banner image
document.getElementById('banner_input').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.querySelector('.company-banner-preview');
            if (img) {
                img.src = e.target.result;
            } else {
                var preview = document.createElement('img');
                preview.className = 'company-banner-preview';
                preview.src = e.target.result;
                document.querySelector('.file-upload-area').after(preview);
            }
        };
        reader.readAsDataURL(file);
    }
});

// Form submission validation
document.getElementById('companyForm').addEventListener('submit', function(e) {
    var companyName = document.querySelector('input[name="company_name"]').value.trim();
    var industry = document.querySelector('select[name="industry"]').value;
    var headquarters = document.querySelector('input[name="headquarters"]').value.trim();
    
    if (!companyName || !industry || !headquarters) {
        e.preventDefault();
        showNotification('error', 'Please fill in all required fields');
        return false;
    }
    
    var submitBtn = document.getElementById('submitBtn');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
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