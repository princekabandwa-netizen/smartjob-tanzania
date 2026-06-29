<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();
redirectIfNotEmployer();

$user_id = $_SESSION['user_id'];

// Check if company profile exists
$stmt = $pdo->prepare("SELECT * FROM company_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_name = trim($_POST['company_name']);
    $company_description = trim($_POST['company_description']);
    $website = trim($_POST['website']);
    $industry = trim($_POST['industry']);
    $company_size = $_POST['company_size'];
    $founded_year = $_POST['founded_year'];
    $headquarters = trim($_POST['headquarters']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $facebook = trim($_POST['facebook']);
    $twitter = trim($_POST['twitter']);
    $linkedin = trim($_POST['linkedin']);
    
    // Handle logo upload
    $logo_path = $profile['logo_path'] ?? null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['logo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $logo_path = 'uploads/company_logos/' . time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $filename);
            if (!is_dir('uploads/company_logos')) {
                mkdir('uploads/company_logos', 0777, true);
            }
            move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path);
        }
    }
    
    if ($profile) {
        // Update existing profile
        $stmt = $pdo->prepare("UPDATE company_profiles SET 
            company_name = ?, company_description = ?, website = ?, industry = ?, 
            company_size = ?, founded_year = ?, headquarters = ?, phone = ?, 
            email = ?, facebook = ?, twitter = ?, linkedin = ?, logo_path = ? 
            WHERE user_id = ?");
        $stmt->execute([$company_name, $company_description, $website, $industry, 
            $company_size, $founded_year, $headquarters, $phone, $email, 
            $facebook, $twitter, $linkedin, $logo_path, $user_id]);
    } else {
        // Insert new profile
        $stmt = $pdo->prepare("INSERT INTO company_profiles (user_id, company_name, company_description, 
            website, industry, company_size, founded_year, headquarters, phone, email, 
            facebook, twitter, linkedin, logo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $company_name, $company_description, $website, 
            $industry, $company_size, $founded_year, $headquarters, $phone, $email, 
            $facebook, $twitter, $linkedin, $logo_path]);
    }
    
    $_SESSION['success_message'] = "Company profile updated successfully!";
    header('Location: company-profile.php');
    exit();
}

include 'includes/header.php';
?>

<style>
.company-profile-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
    min-height: calc(100vh - 200px);
}

.profile-container {
    max-width: 1000px;
    margin: 0 auto;
}

.profile-header {
    text-align: center;
    margin-bottom: 40px;
}

.profile-header h1 {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.profile-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.profile-form {
    padding: 40px;
}

.form-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.form-section h3 {
    color: var(--primary-color);
    margin-bottom: 20px;
    font-size: 1.3rem;
}

.form-section h3 i {
    color: var(--secondary-color);
    margin-right: 10px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
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

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--secondary-color);
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.logo-upload {
    text-align: center;
    padding: 20px;
    border: 2px dashed #e0e0e0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
}

.logo-upload:hover {
    border-color: var(--secondary-color);
    background: #f8f9fa;
}

.logo-preview {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 15px;
}

.btn-submit {
    width: 100%;
    padding: 14px;
    background: var(--secondary-color);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-submit:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .profile-form {
        padding: 20px;
    }
}
</style>

<section class="company-profile-section">
    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <h1><i class="fas fa-building"></i> Company Profile</h1>
                <p>Showcase your company to attract top talent</p>
            </div>
            
            <div class="profile-card">
                <form method="POST" enctype="multipart/form-data" class="profile-form">
                    <!-- Company Logo -->
                    <div class="form-section">
                        <h3><i class="fas fa-image"></i> Company Logo</h3>
                        <div class="logo-upload" onclick="$('#logo_input').click()">
                            <?php if($profile && $profile['logo_path']): ?>
                                <img src="<?php echo $profile['logo_path']; ?>" class="logo-preview" id="logoPreview">
                            <?php else: ?>
                                <img src="assets/images/default-company.png" class="logo-preview" id="logoPreview">
                            <?php endif; ?>
                            <div>
                                <i class="fas fa-cloud-upload-alt"></i> Click to upload company logo
                            </div>
                            <small>Recommended size: 200x200px</small>
                            <input type="file" name="logo" id="logo_input" style="display: none;" accept="image/*">
                        </div>
                    </div>
                    
                    <!-- Basic Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Company Name *</label>
                            <input type="text" name="company_name" required value="<?php echo htmlspecialchars($profile['company_name'] ?? $_SESSION['full_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-align-left"></i> Company Description</label>
                            <textarea name="company_description" placeholder="Tell job seekers about your company..."><?php echo htmlspecialchars($profile['company_description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-globe"></i> Website</label>
                                <input type="url" name="website" value="<?php echo htmlspecialchars($profile['website'] ?? ''); ?>" placeholder="https://www.yourcompany.com">
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-chart-line"></i> Industry</label>
                                <select name="industry">
                                    <option value="">Select Industry</option>
                                    <option value="Technology" <?php echo ($profile['industry'] ?? '') == 'Technology' ? 'selected' : ''; ?>>Technology</option>
                                    <option value="Healthcare" <?php echo ($profile['industry'] ?? '') == 'Healthcare' ? 'selected' : ''; ?>>Healthcare</option>
                                    <option value="Finance" <?php echo ($profile['industry'] ?? '') == 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                    <option value="Education" <?php echo ($profile['industry'] ?? '') == 'Education' ? 'selected' : ''; ?>>Education</option>
                                    <option value="Manufacturing" <?php echo ($profile['industry'] ?? '') == 'Manufacturing' ? 'selected' : ''; ?>>Manufacturing</option>
                                    <option value="Retail" <?php echo ($profile['industry'] ?? '') == 'Retail' ? 'selected' : ''; ?>>Retail</option>
                                    <option value="Construction" <?php echo ($profile['industry'] ?? '') == 'Construction' ? 'selected' : ''; ?>>Construction</option>
                                    <option value="Hospitality" <?php echo ($profile['industry'] ?? '') == 'Hospitality' ? 'selected' : ''; ?>>Hospitality</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-users"></i> Company Size</label>
                                <select name="company_size">
                                    <option value="">Select Size</option>
                                    <option value="1-10" <?php echo ($profile['company_size'] ?? '') == '1-10' ? 'selected' : ''; ?>>1-10 employees</option>
                                    <option value="11-50" <?php echo ($profile['company_size'] ?? '') == '11-50' ? 'selected' : ''; ?>>11-50 employees</option>
                                    <option value="51-200" <?php echo ($profile['company_size'] ?? '') == '51-200' ? 'selected' : ''; ?>>51-200 employees</option>
                                    <option value="201-500" <?php echo ($profile['company_size'] ?? '') == '201-500' ? 'selected' : ''; ?>>201-500 employees</option>
                                    <option value="500+" <?php echo ($profile['company_size'] ?? '') == '500+' ? 'selected' : ''; ?>>500+ employees</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt"></i> Founded Year</label>
                                <input type="number" name="founded_year" value="<?php echo htmlspecialchars($profile['founded_year'] ?? ''); ?>" placeholder="e.g., 2010">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Headquarters</label>
                            <input type="text" name="headquarters" value="<?php echo htmlspecialchars($profile['headquarters'] ?? ''); ?>" placeholder="City, Country">
                        </div>
                    </div>
                    
                    <!-- Contact Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-address-card"></i> Contact Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-phone"></i> Phone</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" placeholder="+255 XXX XXX XXX">
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? $_SESSION['email'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Media -->
                    <div class="form-section">
                        <h3><i class="fas fa-share-alt"></i> Social Media</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fab fa-facebook"></i> Facebook</label>
                                <input type="url" name="facebook" value="<?php echo htmlspecialchars($profile['facebook'] ?? ''); ?>" placeholder="https://facebook.com/yourcompany">
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fab fa-twitter"></i> Twitter</label>
                                <input type="url" name="twitter" value="<?php echo htmlspecialchars($profile['twitter'] ?? ''); ?>" placeholder="https://twitter.com/yourcompany">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-linkedin"></i> LinkedIn</label>
                            <input type="url" name="linkedin" value="<?php echo htmlspecialchars($profile['linkedin'] ?? ''); ?>" placeholder="https://linkedin.com/company/yourcompany">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit">Save Company Profile</button>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
$('#logo_input').on('change', function(e) {
    var file = e.target.files[0];
    if (file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#logoPreview').attr('src', e.target.result);
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php include 'includes/footer.php'; ?>