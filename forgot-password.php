<?php
require_once 'includes/init.php';

$error = '';
$success = '';

// Process forgot password request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Please enter your email address";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $token, $expires]);
            
            // Also store in users table for fallback
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $user['id']]);
            
            // Create reset link
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/smartjob-tanzania/reset-password.php?token=" . $token;
            
            // Send email
            $to = $email;
            $subject = "Password Reset Request - SmartJob Tanzania";
            $message = "
            <html>
            <head>
                <title>Password Reset Request</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #2C3E50; color: white; padding: 20px; text-align: center; }
                    .content { padding: 30px; background: #f8f9fa; }
                    .button { display: inline-block; padding: 12px 30px; background: #E74C3C; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                    .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>SmartJob Tanzania</h2>
                        <p>Password Reset Request</p>
                    </div>
                    <div class='content'>
                        <p>Dear {$user['full_name']},</p>
                        <p>We received a request to reset your password for your SmartJob Tanzania account.</p>
                        <p>Click the button below to reset your password:</p>
                        <p style='text-align: center;'>
                            <a href='{$reset_link}' class='button'>Reset Password</a>
                        </p>
                        <p>Or copy and paste this link into your browser:</p>
                        <p><code>{$reset_link}</code></p>
                        <p><strong>Note:</strong> This link will expire in 1 hour.</p>
                        <p>If you didn't request this password reset, please ignore this email.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " SmartJob Tanzania. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: SmartJob Tanzania <noreply@smartjob.co.tz>" . "\r\n";
            
            // Send email (comment out if email not configured)
            // mail($to, $subject, $message, $headers);
            
            // For testing without mail server, show the reset link
            $success = "Password reset instructions have been sent to your email address. 
                        <br><br><strong>Test Link (Remove in production):</strong> 
                        <a href='{$reset_link}' target='_blank'>Click here to reset password</a>";
            
            // Log the request
            logUserActivity($pdo, 0, 'password_reset_request', "Password reset requested for email: {$email}");
            
        } else {
            // Don't reveal that email doesn't exist for security
            $success = "If an account exists with that email, you will receive password reset instructions.";
        }
    }
}

include 'includes/header.php';
?>

<style>
.forgot-section {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.forgot-container {
    max-width: 450px;
    width: 100%;
    margin: 0 auto;
    animation: fadeInUp 0.5s ease;
}

.forgot-card {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.forgot-header {
    text-align: center;
    margin-bottom: 30px;
}

.forgot-header h2 {
    font-size: 1.8rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.forgot-header p {
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
    .forgot-card {
        padding: 30px 20px;
    }
    
    .forgot-header h2 {
        font-size: 1.5rem;
    }
}
</style>

<section class="forgot-section">
    <div class="container">
        <div class="forgot-container">
            <div class="forgot-card">
                <div class="forgot-header">
                    <h2><i class="fas fa-key"></i> Forgot Password?</h2>
                    <p>Enter your email address and we'll send you a link to reset your password.</p>
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
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="forgot-form">
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" name="email" required placeholder="Enter your registered email" autofocus>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Send Reset Link
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
// Auto-hide alerts after 5 seconds
setTimeout(function() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(function() {
            if (alert && alert.parentNode) {
                alert.remove();
            }
        }, 500);
    });
}, 5000);
</script>

<?php include 'includes/footer.php'; ?>