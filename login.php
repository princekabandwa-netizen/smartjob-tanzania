<?php
require_once 'includes/init.php';

// Set timezone to Tanzania
date_default_timezone_set('Africa/Dar_es_Salaam');

// Redirect if already logged in
if (isLoggedIn()) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: dashboard.php');
    }
    exit();
}

$error = '';
$success = '';

// Check if there's a success message from registration
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    if (empty($username) || empty($password)) {
        $error = "Please enter both username/email and password!";
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Check if account is active
            if (isset($user['status']) && $user['status'] == 'suspended') {
                // Log failed login due to suspended account
                logAllUserActions($pdo, $user['id'], 'login_failed', "Login attempt on suspended account");
                $error = "Your account has been suspended. Please contact support.";
            } else {
                // Get current time for logging
                $current_time = date('H:i:s');
                $current_date = date('Y-m-d H:i:s');
                
                // Log successful login
                logAllUserActions($pdo, $user['id'], 'login_success', "User logged in successfully at " . $current_time);
                
                // Create notification for successful login
                try {
                    $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link, created_at) 
                                               VALUES (?, 'account', 'Login Successful', ?, 'dashboard.php', ?)");
                    $stmt_notif->execute([$user['id'], "You logged in to your account at " . $current_time, $current_date]);
                } catch (Exception $e) {
                    // Silently fail if notification insert fails
                    error_log("Failed to create login notification: " . $e->getMessage());
                }
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Remember me functionality (30 days)
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    // Store token in database
                    $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, token_expires = ? WHERE id = ?");
                    $stmt->execute([$token, $expires, $user['id']]);
                    
                    // Set cookie
                    setcookie('remember_token', $token, time() + (86400 * 30), "/", "", false, true);
                }
                
                // Redirect based on role
                if ($user['role'] == 'admin') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit();
            }
        } else {
            // Log failed login attempt
            if ($user) {
                logAllUserActions($pdo, $user['id'], 'login_failed', "Failed login attempt with incorrect password at " . date('H:i:s'));
            } else {
                // Log failed login for non-existent user
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                $stmt = $pdo->prepare("INSERT INTO user_action_logs (user_id, action_type, action_details, ip_address, created_at) 
                                      VALUES (0, 'login_failed', ?, ?, NOW())");
                $stmt->execute(["Failed login attempt for username: $username", $ip]);
            }
            $error = "Invalid username/email or password!";
        }
    }
}

// Check for remember me cookie
if (empty($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND token_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        logAllUserActions($pdo, $user['id'], 'login_success', "Auto-login via remember me token");
        
        if ($user['role'] == 'admin') {
            header('Location: admin/index.php');
        } else {
            header('Location: dashboard.php');
        }
        exit();
    }
}

include 'includes/header.php';
?>

<style>
.login-section {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-container {
    max-width: 450px;
    width: 100%;
    margin: 0 auto;
    animation: fadeInUp 0.5s ease;
}

.login-card {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.login-header {
    text-align: center;
    margin-bottom: 30px;
}

.login-header h2 {
    font-size: 1.8rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.login-header p {
    color: #666;
    font-size: 14px;
}

.login-form .form-group {
    margin-bottom: 25px;
}

.login-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--primary-color);
    font-size: 14px;
}

.login-form label i {
    margin-right: 8px;
    color: var(--secondary-color);
}

.input-group {
    position: relative;
}

.input-group input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.3s ease;
    font-family: 'Inter', sans-serif;
}

.input-group input:focus {
    outline: none;
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
}

.password-toggle {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #999;
    transition: color 0.3s;
    z-index: 10;
}

.password-toggle:hover {
    color: var(--secondary-color);
}

.checkbox-group {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 10px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    color: #666;
    font-size: 14px;
}

.checkbox-label input {
    width: 18px;
    height: 18px;
    cursor: pointer;
    margin: 0;
}

.forgot-link {
    color: var(--secondary-color);
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s;
}

.forgot-link:hover {
    text-decoration: underline;
}

.btn-login {
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

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(231, 76, 60, 0.3);
}

.btn-login:active {
    transform: translateY(0);
}

.btn-login:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.login-footer {
    text-align: center;
    margin-top: 25px;
    padding-top: 25px;
    border-top: 1px solid #e0e0e0;
}

.login-footer p {
    color: #666;
    font-size: 14px;
}

.login-footer a {
    color: var(--secondary-color);
    text-decoration: none;
    font-weight: 500;
}

.login-footer a:hover {
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

/* Social Login Section */
.social-login {
    margin-top: 25px;
    text-align: center;
}

.social-login p {
    color: #999;
    font-size: 13px;
    margin-bottom: 15px;
    position: relative;
}

.social-login p::before,
.social-login p::after {
    content: '';
    position: absolute;
    top: 50%;
    width: 30%;
    height: 1px;
    background: #e0e0e0;
}

.social-login p::before {
    left: 0;
}

.social-login p::after {
    right: 0;
}

.social-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.btn-social {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-google {
    background: #db4437;
    color: white;
}

.btn-google:hover {
    background: #c53929;
    transform: translateY(-2px);
}

.btn-facebook {
    background: #4267b2;
    color: white;
}

.btn-facebook:hover {
    background: #365899;
    transform: translateY(-2px);
}

.btn-linkedin {
    background: #0077b5;
    color: white;
}

.btn-linkedin:hover {
    background: #006396;
    transform: translateY(-2px);
}

/* Animations */
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

/* Responsive */
@media (max-width: 480px) {
    .login-card {
        padding: 30px 20px;
    }
    
    .login-header h2 {
        font-size: 1.5rem;
    }
    
    .social-buttons {
        flex-direction: column;
    }
    
    .checkbox-group {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<section class="login-section">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2><i class="fas fa-sign-in-alt"></i> Welcome Back!</h2>
                <p>Login to your SmartJob Tanzania account</p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form" id="loginForm">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username or Email</label>
                    <div class="input-group">
                        <input type="text" name="username" id="username" required 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                               placeholder="Enter your username or email" 
                               autocomplete="username">
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" required 
                               placeholder="Enter your password" 
                               autocomplete="current-password">
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>
                
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                        <span><i class="fas fa-check-circle"></i> Remember me</span>
                    </label>
                    <!-- Add this inside the login form, after the password field -->
<div class="checkbox-group">
    <label class="checkbox-label">
        <input type="checkbox" name="remember">
        <span>Remember me</span>
    </label>
    <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
</div>
                </div>
                
                <button type="submit" class="btn-login" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <!-- Social Login Section (Optional - can be enabled later) -->
            <?php 
            // Check if social login is enabled from settings
            $social_enabled = false;
            if (isset($pdo)) {
                $stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'allow_social_login'");
                $social_enabled = $stmt->fetchColumn() == '1';
            }
            ?>
            <?php if($social_enabled): ?>
            <div class="social-login">
                <p>Or login with</p>
                <div class="social-buttons">
                    <button type="button" class="btn-social btn-google" onclick="socialLogin('google')">
                        <i class="fab fa-google"></i> Google
                    </button>
                    <button type="button" class="btn-social btn-facebook" onclick="socialLogin('facebook')">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </button>
                    <button type="button" class="btn-social btn-linkedin" onclick="socialLogin('linkedin')">
                        <i class="fab fa-linkedin-in"></i> LinkedIn
                    </button>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="login-footer">
                <p>Don't have an account? <a href="register.php">
                    <i class="fas fa-user-plus"></i> Register here
                </a></p>
            </div>
        </div>
    </div>
</section>

<script>
// Toggle password visibility
function togglePassword() {
    var passwordInput = document.getElementById('password');
    var toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Form submission with loading state and validation
document.getElementById('loginForm').addEventListener('submit', function(e) {
    var username = document.getElementById('username').value.trim();
    var password = document.getElementById('password').value;
    
    // Client-side validation
    if (!username) {
        e.preventDefault();
        showNotification('error', 'Please enter your username or email!');
        document.getElementById('username').focus();
        return false;
    }
    
    if (!password) {
        e.preventDefault();
        showNotification('error', 'Please enter your password!');
        document.getElementById('password').focus();
        return false;
    }
    
    // Show loading state
    var submitBtn = document.getElementById('loginBtn');
    var originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
    submitBtn.disabled = true;
    
    // Store original text in case of error (will be restored if needed)
    submitBtn.setAttribute('data-original-text', originalText);
});

// Social login function (to be implemented later)
function socialLogin(provider) {
    showNotification('info', provider.charAt(0).toUpperCase() + provider.slice(1) + ' login coming soon!');
}

// Toast notification function
function showNotification(type, message) {
    if (typeof toastr !== 'undefined') {
        var options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "5000"
        };
        
        if (type === 'success') {
            toastr.success(message, 'Success!', options);
        } else if (type === 'error') {
            toastr.error(message, 'Error!', options);
        } else if (type === 'warning') {
            toastr.warning(message, 'Warning!', options);
        } else {
            toastr.info(message, 'Information', options);
        }
    } else {
        alert(message);
    }
}

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        if (alert) {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                if (alert && alert.parentNode) {
                    alert.remove();
                }
            }, 500);
        }
    });
}, 5000);

// Enter key press handling for better UX
document.getElementById('password').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('loginForm').dispatchEvent(new Event('submit'));
    }
});

// Clear form error on input
document.getElementById('username').addEventListener('input', function() {
    var alertBox = document.querySelector('.alert-error');
    if (alertBox) {
        alertBox.style.opacity = '0';
        setTimeout(function() {
            if (alertBox && alertBox.parentNode) {
                alertBox.remove();
            }
        }, 300);
    }
});

// Focus username field on page load
document.addEventListener('DOMContentLoaded', function() {
    var usernameField = document.getElementById('username');
    if (usernameField && !usernameField.value) {
        usernameField.focus();
    }
});
</script>

<?php include 'includes/footer.php'; ?>