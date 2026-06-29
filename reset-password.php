<?php
require_once 'includes/init.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
$error = '';
$success = '';

// Verify token
if (empty($token)) {
    header('Location: forgot-password.php');
    exit();
}

// Check if token is valid
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    // Check in users table as fallback
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$token]);
    $user_reset = $stmt->fetch();
    
    if (!$user_reset) {
        $error = "Invalid or expired reset link. Please request a new password reset.";
    }
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($error)) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    if (!preg_match('/[A-Z]/', $password)) $errors[] = "Password must contain at least one uppercase letter";
    if (!preg_match('/[0-9]/', $password)) $errors[] = "Password must contain at least one number";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        if (isset($reset) && $reset) {
            // Update user password using email from password_resets table
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed_password, $reset['email']]);
            
            // Mark token as used
            $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            
            // Clear reset token in users table
            $stmt = $pdo->prepare("UPDATE users SET reset_token = NULL, reset_token_expires = NULL WHERE email = ?");
            $stmt->execute([$reset['email']]);
            
            $success_email = $reset['email'];
            
        } elseif (isset($user_reset) && $user_reset) {
            // Update user password using id from users table
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
            $stmt->execute([$hashed_password, $user_reset['id']]);
            
            // Also mark any existing token in password_resets as used
            $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE email = ?");
            $stmt->execute([$user_reset['email']]);
            
            $success_email = $user_reset['email'];
        }
        
        // Log the password reset
        logUserActivity($pdo, 0, 'password_reset', "Password reset completed for email: {$success_email}");
        
        $_SESSION['success_message'] = "Password reset successfully! Please login with your new password.";
        header('Location: login.php');
        exit();
    } else {
        $error = implode("<br>", $errors);
    }
}

include 'includes/header.php';
?>

<style>
.reset-section {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.reset-container {
    max-width: 450px;
    width: 100%;
    margin: 0 auto;
    animation: fadeInUp 0.5s ease;
}

.reset-card {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.reset-header {
    text-align: center;
    margin-bottom: 30px;
}

.reset-header h2 {
    font-size: 1.8rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.reset-header p {
    color: #666;
    font-size: 14px;
}

.form-group {
    margin-bottom: 25px;
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

.form-group input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.3s ease;
}

.form-group input:focus {
    outline: none;
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
}

.password-strength {
    font-size: 12px;
    margin-top: 5px;
}

.password-match {
    font-size: 12px;
    margin-top: 5px;
}

.btn-submit {
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
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(231, 76, 60, 0.3);
}

.btn-back {
    display: block;
    text-align: center;
    margin-top: 20px;
    color: var(--secondary-color);
    text-decoration: none;
    font-size: 14px;
}

.btn-back:hover {
    text-decoration: underline;
}

.alert {
    padding: 12px 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideIn 0.3s ease;
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

.alert i {
    font-size: 18px;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@media (max-width: 480px) {
    .reset-card {
        padding: 30px 20px;
    }
    
    .reset-header h2 {
        font-size: 1.5rem;
    }
}
</style>

<section class="reset-section">
    <div class="container">
        <div class="reset-container">
            <div class="reset-card">
                <div class="reset-header">
                    <h2><i class="fas fa-lock"></i> Reset Password</h2>
                    <p>Enter your new password below</p>
                </div>
                
                <?php if($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="reset-form">
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> New Password</label>
                        <input type="password" name="password" id="password" required placeholder="Enter new password">
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" required placeholder="Confirm new password">
                        <div class="password-match" id="passwordMatch"></div>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Reset Password
                    </button>
                    
                    <a href="login.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                </form>
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
</script>

<?php include 'includes/footer.php'; ?>