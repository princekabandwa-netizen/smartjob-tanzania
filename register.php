<?php
require_once 'includes/init.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];

    // Validation
    $errors = [];
    
    if (empty($username)) $errors[] = "Username is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($password)) $errors[] = "Password is required";
    if (empty($full_name)) $errors[] = "Full name is required";
    if (strlen($username) < 3) $errors[] = "Username must be at least 3 characters";
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) $errors[] = "Username can only contain letters, numbers, and underscores";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    if (!preg_match('/[A-Z]/', $password)) $errors[] = "Password must contain at least one uppercase letter";
    if (!preg_match('/[0-9]/', $password)) $errors[] = "Password must contain at least one number";
    
    if (empty($errors)) {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Username or email already exists! Please try a different one.";
        }
    }
    
    if (empty($errors)) {
        // Create user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        
        if ($stmt->execute([$username, $email, $hashed_password, $full_name, $phone, $role])) {
            $user_id = $pdo->lastInsertId();
            $current_time = date('H:i:s');
            
            // Log activity
            logUserActivity($pdo, $user_id, 'register', "New user registered as {$role}");
            
            // Create welcome notification
            createNotification($pdo, $user_id, 'system', 'Welcome to SmartJob Tanzania!', 
                              "Thank you for joining {$full_name}! Start exploring jobs today at {$current_time}.", 
                              "dashboard.php");
            
            // Notify admin
            $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
            $stmt->execute();
            $admin = $stmt->fetch();
            
            if ($admin) {
                createNotification($pdo, $admin['id'], 'system', 'New User Registration', 
                                  "New {$role} registered: {$full_name} ({$email})", 
                                  "admin/users.php");
            }
            
            setSuccess("Account created successfully! Welcome to SmartJob Tanzania. Please login to continue.");
            header('Location: login.php');
            exit();
        } else {
            $error = "Registration failed. Please try again or contact support.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

include 'includes/header.php';
?>

<style>
.register-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
    min-height: calc(100vh - 200px);
}

.register-container {
    max-width: 550px;
    margin: 0 auto;
}

.register-card {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.register-header {
    text-align: center;
    margin-bottom: 30px;
}

.register-header h2 {
    font-size: 1.8rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.register-header p {
    color: #666;
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
.form-group select {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.btn-register {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--secondary-color), #c0392b);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 10px;
}

.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(231, 76, 60, 0.3);
}

.login-link {
    text-align: center;
    margin-top: 25px;
    padding-top: 25px;
    border-top: 1px solid #e0e0e0;
}

.login-link a {
    color: var(--secondary-color);
    text-decoration: none;
    font-weight: 500;
}

.login-link a:hover {
    text-decoration: underline;
}

.alert {
    padding: 12px 15px;
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

.password-strength {
    font-size: 12px;
    margin-top: 5px;
}

.password-match {
    font-size: 12px;
    margin-top: 5px;
}

small {
    display: block;
    margin-top: 5px;
    font-size: 11px;
    color: #888;
}

@media (max-width: 768px) {
    .register-card {
        padding: 25px;
        margin: 0 15px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
}
</style>

<section class="register-section">
    <div class="container">
        <div class="register-container">
            <div class="register-card">
                <div class="register-header">
                    <h2><i class="fas fa-user-plus"></i> Create Account</h2>
                    <p>Join SmartJob Tanzania today</p>
                </div>
                
                <?php if($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="register-form" id="registerForm">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" placeholder="Enter your full name">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user-circle"></i> Username <span class="required">*</span></label>
                            <input type="text" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" placeholder="Choose a username">
                            <small>Letters, numbers, and underscores only</small>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email <span class="required">*</span></label>
                            <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="your@email.com">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Password <span class="required">*</span></label>
                            <input type="password" name="password" id="password" required placeholder="At least 6 characters">
                            <div class="password-strength" id="passwordStrength"></div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Confirm Password <span class="required">*</span></label>
                            <input type="password" name="confirm_password" id="confirm_password" required placeholder="Re-enter password">
                            <div class="password-match" id="passwordMatch"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" placeholder="+255 XXX XXX XXX">
                        <small>Optional - Format: +255XXXXXXXXX</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-briefcase"></i> I am a <span class="required">*</span></label>
                        <select name="role" required>
                            <option value="jobseeker" <?php echo (($_POST['role'] ?? '') == 'jobseeker') ? 'selected' : ''; ?>>Job Seeker - Looking for jobs</option>
                            <option value="employer" <?php echo (($_POST['role'] ?? '') == 'employer') ? 'selected' : ''; ?>>Employer - Hiring employees</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-register" id="registerBtn">
                        <i class="fas fa-arrow-right"></i> Create Account
                    </button>
                </form>
                
                <div class="login-link">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Password strength indicator
$('#password').on('keyup', function() {
    var password = $(this).val();
    var strength = 0;
    
    if (password.length >= 6) strength++;
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[$@#&!]+/)) strength++;
    
    var strengthText = '';
    var strengthColor = '';
    
    switch(strength) {
        case 0:
        case 1:
            strengthText = 'Weak';
            strengthColor = '#e74c3c';
            break;
        case 2:
            strengthText = 'Fair';
            strengthColor = '#f39c12';
            break;
        case 3:
            strengthText = 'Good';
            strengthColor = '#3498db';
            break;
        case 4:
        case 5:
            strengthText = 'Strong';
            strengthColor = '#27ae60';
            break;
    }
    
    if (password.length > 0) {
        $('#passwordStrength').html('<span style="color:' + strengthColor + '"><i class="fas fa-shield-alt"></i> Password strength: ' + strengthText + '</span>');
    } else {
        $('#passwordStrength').html('');
    }
    
    checkPasswordMatch();
});

// Password match validation
function checkPasswordMatch() {
    var password = $('#password').val();
    var confirm = $('#confirm_password').val();
    
    if (confirm.length > 0) {
        if (password === confirm) {
            $('#passwordMatch').html('<span style="color:#27ae60"><i class="fas fa-check-circle"></i> Passwords match</span>');
        } else {
            $('#passwordMatch').html('<span style="color:#e74c3c"><i class="fas fa-times-circle"></i> Passwords do not match</span>');
        }
    } else {
        $('#passwordMatch').html('');
    }
}

$('#confirm_password').on('keyup', function() {
    checkPasswordMatch();
});

// Form submission
$('#registerForm').on('submit', function(e) {
    var password = $('#password').val();
    var confirm = $('#confirm_password').val();
    
    if (password !== confirm) {
        e.preventDefault();
        showNotification('error', 'Passwords do not match!', 'Validation Error');
        return false;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        showNotification('error', 'Password must be at least 6 characters!', 'Validation Error');
        return false;
    }
    
    var submitBtn = $('#registerBtn');
    submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Creating Account...');
    submitBtn.prop('disabled', true);
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