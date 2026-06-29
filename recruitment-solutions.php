<?php
require_once 'includes/init.php';
include 'includes/header.php';

// Get user role if logged in
$isLoggedIn = isLoggedIn();
$isEmployer = $isLoggedIn && isEmployer();
?>

<style>
/* Additional styles for pricing page */
.solutions-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
}

.solutions-header {
    text-align: center;
    margin-bottom: 50px;
}

.solutions-header h1 {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 15px;
}

.solutions-header p {
    font-size: 1.1rem;
    color: #666;
    max-width: 600px;
    margin: 0 auto;
}

.solutions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
    margin-bottom: 60px;
}

.solution-card {
    background: white;
    padding: 30px;
    border-radius: 15px;
    text-align: center;
    transition: all 0.3s ease;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.solution-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.12);
}

.solution-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.solution-icon i {
    font-size: 35px;
    color: white;
}

.solution-card h3 {
    font-size: 1.5rem;
    margin-bottom: 15px;
    color: var(--primary-color);
}

.solution-features {
    list-style: none;
    text-align: left;
    margin: 20px 0;
}

.solution-features li {
    margin: 10px 0;
    padding-left: 25px;
    position: relative;
}

.solution-features li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: var(--secondary-color);
    font-weight: bold;
}

.btn-solution {
    display: inline-block;
    padding: 12px 30px;
    background: linear-gradient(135deg, var(--secondary-color), #c0392b);
    color: white;
    text-decoration: none;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-solution:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
}

/* Pricing Plans */
.solutions-pricing {
    margin: 60px 0;
    text-align: center;
}

.solutions-pricing h2 {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 40px;
}

.pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 20px;
}

.pricing-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    position: relative;
    transition: all 0.3s ease;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.pricing-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.12);
}

.pricing-card.featured {
    border: 2px solid var(--secondary-color);
    transform: scale(1.02);
}

.pricing-card.featured:hover {
    transform: scale(1.02) translateY(-5px);
}

.popular-tag {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--secondary-color);
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.pricing-card h3 {
    font-size: 1.5rem;
    color: var(--primary-color);
    margin-bottom: 20px;
}

.price {
    font-size: 2.5rem;
    font-weight: bold;
    color: var(--primary-color);
    margin: 20px 0;
}

.price span {
    font-size: 1rem;
    color: #666;
    font-weight: normal;
}

.pricing-card ul {
    list-style: none;
    margin: 25px 0;
    text-align: left;
}

.pricing-card li {
    margin: 12px 0;
    padding-left: 25px;
    position: relative;
    color: #555;
}

.pricing-card li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: var(--secondary-color);
    font-weight: bold;
}

.btn-pricing {
    display: inline-block;
    padding: 12px 30px;
    background: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
    width: 100%;
    border: none;
    cursor: pointer;
}

.btn-pricing:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
}

.pricing-card.featured .btn-pricing {
    background: var(--secondary-color);
}

.pricing-card.featured .btn-pricing:hover {
    background: #c0392b;
}

/* CTA Section */
.solutions-cta {
    text-align: center;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
    padding: 60px 40px;
    border-radius: 20px;
    margin-top: 60px;
}

.solutions-cta h2 {
    font-size: 2rem;
    margin-bottom: 15px;
}

.solutions-cta p {
    font-size: 1.1rem;
    margin-bottom: 30px;
    opacity: 0.9;
}

.btn-cta {
    display: inline-block;
    padding: 15px 40px;
    background: var(--secondary-color);
    color: white;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-cta:hover {
    background: #c0392b;
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(5px);
}

.modal-content {
    background: white;
    margin: 5% auto;
    padding: 0;
    width: 90%;
    max-width: 500px;
    border-radius: 15px;
    animation: modalSlideIn 0.3s ease;
    overflow: hidden;
}

@keyframes modalSlideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
}

.modal-header .close {
    font-size: 28px;
    cursor: pointer;
    transition: all 0.3s;
}

.modal-header .close:hover {
    transform: scale(1.2);
}

.modal-body {
    padding: 30px;
}

.modal-body .form-group {
    margin-bottom: 20px;
}

.modal-body label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--primary-color);
}

.modal-body input,
.modal-body textarea,
.modal-body select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}

.modal-body textarea {
    resize: vertical;
    min-height: 100px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 10px;
}

.btn-modal-submit {
    flex: 1;
    padding: 12px;
    background: var(--secondary-color);
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
}

.btn-modal-cancel {
    flex: 1;
    padding: 12px;
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

/* Responsive */
@media (max-width: 768px) {
    .pricing-grid {
        grid-template-columns: 1fr;
    }
    
    .pricing-card.featured {
        transform: scale(1);
    }
    
    .pricing-card.featured:hover {
        transform: translateY(-5px);
    }
    
    .solutions-cta {
        padding: 40px 20px;
    }
    
    .solutions-cta h2 {
        font-size: 1.5rem;
    }
}
</style>

<section class="solutions-section">
    <div class="container">
        <div class="solutions-header">
            <h1>Recruitment Solutions for Employers</h1>
            <p>Streamline your hiring process with SmartJob Tanzania's comprehensive recruitment tools</p>
        </div>

        <div class="solutions-grid">
            <div class="solution-card">
                <div class="solution-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <h3>Fast & Easy Job Posting</h3>
                <p>Post job openings in minutes with our intuitive job posting system. Reach thousands of qualified candidates instantly.</p>
                <ul class="solution-features">
                    <li>Unlimited job postings</li>
                    <li>Featured job listings</li>
                    <li>Social media promotion</li>
                </ul>
                <?php if($isEmployer): ?>
                    <a href="post-job.php" class="btn-solution">Post a Job Now</a>
                <?php else: ?>
                    <button onclick="showContactModal('Job Posting Service')" class="btn-solution">Contact Sales</button>
                <?php endif; ?>
            </div>

            <div class="solution-card">
                <div class="solution-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3>Talent Search & Filtering</h3>
                <p>Find the perfect candidate using advanced search filters and AI-powered matching technology.</p>
                <ul class="solution-features">
                    <li>Advanced candidate search</li>
                    <li>Filter by skills & experience</li>
                    <li>AI candidate matching</li>
                </ul>
                <?php if($isEmployer): ?>
                    <a href="find-candidates.php" class="btn-solution">Find Candidates</a>
                <?php else: ?>
                    <button onclick="showContactModal('Talent Search Service')" class="btn-solution">Contact Sales</button>
                <?php endif; ?>
            </div>

            <div class="solution-card">
                <div class="solution-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Application Tracking System</h3>
                <p>Manage all applications in one place with our comprehensive tracking system.</p>
                <ul class="solution-features">
                    <li>Track application status</li>
                    <li>Automated notifications</li>
                    <li>Interview scheduling</li>
                </ul>
                <?php if($isEmployer): ?>
                    <a href="dashboard.php" class="btn-solution">Go to Dashboard</a>
                <?php else: ?>
                    <button onclick="showContactModal('ATS Service')" class="btn-solution">Contact Sales</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="solutions-pricing">
            <h2>Pricing Plans for Employers</h2>
            <div class="pricing-grid">
                <!-- Basic Plan -->
                <div class="pricing-card">
                    <h3>Basic</h3>
                    <div class="price">Free</div>
                    <ul>
                        <li>3 active job postings</li>
                        <li>Basic candidate search</li>
                        <li>Email support</li>
                        <li>30 days job visibility</li>
                    </ul>
                    <?php if($isEmployer): ?>
                        <button onclick="activatePlan('basic')" class="btn-pricing">Get Started</button>
                    <?php else: ?>
                        <button onclick="showRegisterModal('basic')" class="btn-pricing">Get Started</button>
                    <?php endif; ?>
                </div>

                <!-- Professional Plan -->
                <div class="pricing-card featured">
                    <div class="popular-tag">Most Popular</div>
                    <h3>Professional</h3>
                    <div class="price">TSh 150,000<span>/month</span></div>
                    <ul>
                        <li>20 active job postings</li>
                        <li>Advanced candidate search</li>
                        <li>Priority support</li>
                        <li>Featured job listings</li>
                        <li>Candidate matching AI</li>
                    </ul>
                    <?php if($isEmployer): ?>
                        <button onclick="showPaymentModal('professional', 150000)" class="btn-pricing">Upgrade Now</button>
                    <?php else: ?>
                        <button onclick="showRegisterModal('professional')" class="btn-pricing">Get Started</button>
                    <?php endif; ?>
                </div>

                <!-- Enterprise Plan -->
                <div class="pricing-card">
                    <h3>Enterprise</h3>
                    <div class="price">Custom</div>
                    <ul>
                        <li>Unlimited job postings</li>
                        <li>Dedicated account manager</li>
                        <li>Custom integrations</li>
                        <li>API access</li>
                        <li>24/7 phone support</li>
                    </ul>
                    <button onclick="showContactModal('Enterprise Plan')" class="btn-pricing">Contact Sales</button>
                </div>
            </div>
        </div>

        <div class="solutions-cta">
            <h2>Ready to Find Your Next Great Hire?</h2>
            <p>Join hundreds of employers who use SmartJob Tanzania to find top talent</p>
            <?php if($isEmployer): ?>
                <a href="post-job.php" class="btn-cta">Post a Job Now</a>
            <?php else: ?>
                <button onclick="showRegisterModal('employer')" class="btn-cta">Create Employer Account</button>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Contact Sales Modal -->
<div id="contactModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-envelope"></i> Contact Sales Team</h3>
            <span class="close" onclick="closeModal('contactModal')">&times;</span>
        </div>
        <form id="contactForm" action="send-inquiry.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="service_type" id="service_type">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" required>
                </div>
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone">
                </div>
                <div class="form-group">
                    <label>Company Name</label>
                    <input type="text" name="company">
                </div>
                <div class="form-group">
                    <label>Message *</label>
                    <textarea name="message" required placeholder="Tell us about your recruitment needs..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn-modal-submit">Send Inquiry</button>
                <button type="button" class="btn-modal-cancel" onclick="closeModal('contactModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-credit-card"></i> Complete Payment</h3>
            <span class="close" onclick="closeModal('paymentModal')">&times;</span>
        </div>
        <form id="paymentForm" action="process-payment.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="plan" id="payment_plan">
                <input type="hidden" name="amount" id="payment_amount">
                <div class="form-group">
                    <label>Plan Selected</label>
                    <input type="text" id="plan_display" readonly style="background:#f5f5f5;">
                </div>
                <div class="form-group">
                    <label>Amount (TSh)</label>
                    <input type="text" id="amount_display" readonly style="background:#f5f5f5;">
                </div>
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" required>
                </div>
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="tel" name="phone" required>
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" required>
                        <option value="">Select Payment Method</option>
                        <option value="mobile">Mobile Money (M-Pesa, Tigo Pesa, Airtel Money)</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="card">Credit/Debit Card</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Transaction Reference (if any)</label>
                    <input type="text" name="transaction_ref" placeholder="Optional">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn-modal-submit">Proceed to Payment</button>
                <button type="button" class="btn-modal-cancel" onclick="closeModal('paymentModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Registration Redirect Modal -->
<div id="registerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Create Employer Account</h3>
            <span class="close" onclick="closeModal('registerModal')">&times;</span>
        </div>
        <div class="modal-body">
            <p style="text-align: center; margin-bottom: 20px;">To access this plan, you need to create an employer account first.</p>
            <div style="text-align: center;">
                <button onclick="window.location.href='register.php?role=employer'" class="btn-modal-submit" style="margin-bottom: 10px;">
                    Create Employer Account
                </button>
                <p style="margin-top: 15px;">
                    Already have an account? <a href="login.php" style="color: var(--secondary-color);">Login here</a>
                </p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" onclick="closeModal('registerModal')">Close</button>
        </div>
    </div>
</div>

<script>
// Show contact modal
function showContactModal(service) {
    document.getElementById('service_type').value = service;
    document.getElementById('contactModal').style.display = 'block';
}

// Show payment modal
function showPaymentModal(plan, amount) {
    document.getElementById('payment_plan').value = plan;
    document.getElementById('payment_amount').value = amount;
    document.getElementById('plan_display').value = plan.charAt(0).toUpperCase() + plan.slice(1) + ' Plan';
    document.getElementById('amount_display').value = 'TSh ' + amount.toLocaleString() + '/month';
    document.getElementById('paymentModal').style.display = 'block';
}

// Show register modal
function showRegisterModal(plan) {
    document.getElementById('registerModal').style.display = 'block';
    // Store selected plan in session or redirect with parameter
    sessionStorage.setItem('selected_plan', plan);
}

// Activate plan for logged-in employers
function activatePlan(plan) {
    if (confirm('Are you sure you want to activate the ' + plan.toUpperCase() + ' plan?')) {
        $.ajax({
            url: 'ajax/activate-plan.php',
            method: 'POST',
            data: { plan: plan },
            success: function(response) {
                if (response === 'success') {
                    showNotification('success', 'Plan activated successfully! You can now post jobs.');
                    setTimeout(function() {
                        window.location.href = 'post-job.php';
                    }, 2000);
                } else {
                    showNotification('error', 'Failed to activate plan. Please try again.');
                }
            }
        });
    }
}

// Close modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// Handle contact form submission
$('#contactForm').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: 'ajax/send-inquiry.php',
        method: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            if (response === 'success') {
                showNotification('success', 'Inquiry sent successfully! Our team will contact you soon.');
                closeModal('contactModal');
                $('#contactForm')[0].reset();
            } else {
                showNotification('error', 'Failed to send inquiry. Please try again.');
            }
        }
    });
});

// Handle payment form submission
$('#paymentForm').on('submit', function(e) {
    e.preventDefault();
    
    // Show loading state
    var submitBtn = $(this).find('button[type="submit"]');
    var originalText = submitBtn.text();
    submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');
    submitBtn.prop('disabled', true);
    
    $.ajax({
        url: 'ajax/process-payment.php',
        method: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            if (response === 'success') {
                showNotification('success', 'Payment initiated! You will receive confirmation via SMS/Email.');
                setTimeout(function() {
                    window.location.href = 'dashboard.php';
                }, 3000);
            } else {
                showNotification('error', 'Payment processing failed. Please try again.');
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        },
        error: function() {
            showNotification('error', 'An error occurred. Please try again.');
            submitBtn.html(originalText);
            submitBtn.prop('disabled', false);
        }
    });
});

// Toast notification
function showNotification(type, message) {
    if (typeof toastr !== 'undefined') {
        if (type === 'success') toastr.success(message);
        else if (type === 'error') toastr.error(message);
        else toastr.info(message);
    } else {
        alert(message);
    }
}

// Check if user just registered and show appropriate message
$(document).ready(function() {
    // Check for selected plan from registration
    var selectedPlan = sessionStorage.getItem('selected_plan');
    if (selectedPlan && <?php echo $isEmployer ? 'true' : 'false'; ?>) {
        sessionStorage.removeItem('selected_plan');
        if (selectedPlan !== 'basic') {
            showPaymentModal(selectedPlan, selectedPlan === 'professional' ? 150000 : 500000);
        } else {
            activatePlan(selectedPlan);
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>