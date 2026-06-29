<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();
redirectIfNotEmployer();

$user_id = $_SESSION['user_id'];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all job seekers/applicants
$query = "SELECT DISTINCT u.*, 
          (SELECT COUNT(*) FROM applications a WHERE a.jobseeker_id = u.id) as total_applications,
          (SELECT COUNT(*) FROM saved_jobs sj WHERE sj.user_id = u.id) as saved_jobs,
          (SELECT GROUP_CONCAT(DISTINCT j.title SEPARATOR ', ') 
           FROM applications a 
           JOIN jobs j ON a.job_id = j.id 
           WHERE a.jobseeker_id = u.id LIMIT 3) as applied_jobs
          FROM users u 
          WHERE u.role = 'jobseeker'";

if (!empty($search)) {
    $query .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
    $search_param = "%$search%";
}

$query .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($query);
if (!empty($search)) {
    $stmt->execute([$search_param, $search_param, $search_param]);
} else {
    $stmt->execute();
}
$candidates = $stmt->fetchAll();

// Get statistics
$total_candidates = count($candidates);
$total_applications = 0;
foreach($candidates as $candidate) {
    $total_applications += $candidate['total_applications'];
}

include 'includes/header.php';
?>

<style>
.find-candidates-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
    min-height: calc(100vh - 200px);
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
}

.page-header h1 {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.page-header p {
    color: #666;
}

/* Search Bar */
.search-candidates {
    max-width: 600px;
    margin: 0 auto 40px;
}

.search-form {
    position: relative;
}

.search-input-wrapper {
    display: flex;
    gap: 10px;
    background: white;
    padding: 5px;
    border-radius: 50px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.search-input-wrapper i {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
}

.search-input-wrapper input {
    flex: 1;
    padding: 15px 20px 15px 45px;
    border: none;
    border-radius: 50px;
    font-size: 16px;
    background: transparent;
}

.search-input-wrapper input:focus {
    outline: none;
}

.btn-search {
    padding: 12px 30px;
    background: var(--secondary-color);
    color: white;
    border: none;
    border-radius: 50px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-search:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

.btn-clear {
    padding: 12px 25px;
    background: #6c757d;
    color: white;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-block;
}

.btn-clear:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

/* Statistics */
.candidates-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-box {
    background: white;
    padding: 25px;
    text-align: center;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: all 0.3s;
}

.stat-box:hover {
    transform: translateY(-5px);
}

.stat-number {
    font-size: 36px;
    font-weight: bold;
    color: var(--secondary-color);
}

.stat-label {
    color: #666;
    margin-top: 5px;
    font-size: 14px;
}

/* Candidates Grid */
.candidates-grid {
    display: grid;
    gap: 25px;
}

.candidate-card {
    background: white;
    border-radius: 20px;
    padding: 25px;
    display: flex;
    gap: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    transition: all 0.3s;
    position: relative;
}

.candidate-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.12);
}

.candidate-avatar {
    flex-shrink: 0;
    text-align: center;
}

.candidate-avatar i {
    font-size: 80px;
    color: var(--primary-color);
}

.candidate-avatar .avatar-initials {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: bold;
    color: white;
}

.candidate-info {
    flex: 1;
}

.candidate-info h3 {
    font-size: 1.5rem;
    margin-bottom: 5px;
    color: var(--primary-color);
}

.candidate-username {
    color: #666;
    margin-bottom: 15px;
    font-size: 14px;
}

.candidate-username i {
    margin-right: 5px;
}

.candidate-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 12px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: #555;
}

.detail-item i {
    width: 20px;
    color: var(--secondary-color);
}

.candidate-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    padding-top: 10px;
    border-top: 1px solid #e0e0e0;
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 18px;
    font-weight: bold;
    color: var(--primary-color);
}

.stat-label-small {
    font-size: 11px;
    color: #999;
}

.applied-jobs {
    font-size: 13px;
    color: #666;
    margin-top: 10px;
}

.applied-jobs i {
    color: var(--secondary-color);
    margin-right: 5px;
}

.candidate-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.btn-contact {
    padding: 10px 25px;
    background: var(--secondary-color);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-contact:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

.btn-profile {
    padding: 10px 25px;
    background: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-block;
}

.btn-profile:hover {
    background: var(--accent-color);
    transform: translateY(-2px);
}

/* Empty State */
.no-candidates {
    text-align: center;
    padding: 60px;
    background: white;
    border-radius: 20px;
}

.no-candidates i {
    font-size: 60px;
    color: #ccc;
    margin-bottom: 20px;
}

.no-candidates h3 {
    color: var(--primary-color);
    margin-bottom: 10px;
}

.no-candidates p {
    color: #666;
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
    width: 90%;
    max-width: 500px;
    border-radius: 20px;
    animation: modalSlideIn 0.3s ease;
    overflow: hidden;
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
    font-size: 1.3rem;
}

.modal-header .close {
    font-size: 28px;
    cursor: pointer;
    transition: all 0.3s;
    line-height: 1;
}

.modal-header .close:hover {
    transform: scale(1.2);
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

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--primary-color);
}

.form-group label i {
    margin-right: 8px;
    color: var(--secondary-color);
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--secondary-color);
}

.form-group input[readonly] {
    background: #f5f5f5;
    cursor: not-allowed;
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.btn-send-message {
    flex: 1;
    padding: 12px;
    background: var(--secondary-color);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-send-message:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

.btn-cancel-modal {
    flex: 1;
    padding: 12px;
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-cancel-modal:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

small {
    display: block;
    margin-top: 5px;
    font-size: 11px;
    color: #888;
}

/* Responsive */
@media (max-width: 768px) {
    .candidate-card {
        flex-direction: column;
        text-align: center;
    }
    
    .candidate-avatar {
        margin: 0 auto;
    }
    
    .candidate-details {
        grid-template-columns: 1fr;
        text-align: left;
    }
    
    .candidate-stats {
        justify-content: center;
    }
    
    .candidate-actions {
        justify-content: center;
    }
    
    .search-input-wrapper {
        flex-direction: column;
        border-radius: 15px;
        background: white;
        padding: 15px;
    }
    
    .search-input-wrapper input {
        padding: 12px;
    }
    
    .btn-search, .btn-clear {
        text-align: center;
    }
    
    .candidates-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<section class="find-candidates-section">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-users"></i> Find Candidates</h1>
            <p>Browse and connect with qualified job seekers in Tanzania</p>
        </div>

        <!-- Search Bar -->
        <div class="search-candidates">
            <form method="GET" action="" class="search-form">
                <div class="search-input-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search by name, email, or username..." 
                           value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                    <button type="submit" class="btn-search">Search</button>
                    <?php if($search): ?>
                        <a href="find-candidates.php" class="btn-clear">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Statistics -->
        <div class="candidates-stats">
            <div class="stat-box">
                <div class="stat-number"><?php echo $total_candidates; ?></div>
                <div class="stat-label">Total Job Seekers</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $total_applications; ?></div>
                <div class="stat-label">Total Applications</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo round($total_candidates > 0 ? ($total_applications / $total_candidates) : 0); ?></div>
                <div class="stat-label">Avg Applications/Seeker</div>
            </div>
        </div>

        <!-- Candidates List -->
        <?php if(empty($candidates)): ?>
            <div class="no-candidates">
                <i class="fas fa-user-slash"></i>
                <h3>No candidates found</h3>
                <p>Try adjusting your search or check back later</p>
                <?php if($search): ?>
                    <a href="find-candidates.php" class="btn-profile" style="margin-top: 20px;">
                        <i class="fas fa-arrow-left"></i> View All Candidates
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="candidates-grid">
                <?php foreach($candidates as $candidate): ?>
                    <div class="candidate-card" data-candidate-id="<?php echo $candidate['id']; ?>">
                        <div class="candidate-avatar">
                            <?php
                            $initials = strtoupper(substr($candidate['full_name'], 0, 2));
                            ?>
                            <div class="avatar-initials">
                                <?php echo $initials; ?>
                            </div>
                        </div>
                        
                        <div class="candidate-info">
                            <h3><?php echo htmlspecialchars($candidate['full_name']); ?></h3>
                            <div class="candidate-username">
                                <i class="fas fa-at"></i> <?php echo htmlspecialchars($candidate['username']); ?>
                            </div>
                            
                            <div class="candidate-details">
                                <div class="detail-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($candidate['email']); ?></span>
                                </div>
                                <?php if($candidate['phone']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-phone"></i>
                                        <span><?php echo htmlspecialchars($candidate['phone']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="detail-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Joined: <?php echo date('M Y', strtotime($candidate['created_at'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="candidate-stats">
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $candidate['total_applications']; ?></div>
                                    <div class="stat-label-small">Applications</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $candidate['saved_jobs']; ?></div>
                                    <div class="stat-label-small">Saved Jobs</div>
                                </div>
                            </div>
                            
                            <?php if($candidate['applied_jobs']): ?>
                                <div class="applied-jobs">
                                    <i class="fas fa-briefcase"></i> 
                                    Recently applied to: <?php echo htmlspecialchars($candidate['applied_jobs']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="candidate-actions">
                                <button onclick="showContactModal(<?php echo $candidate['id']; ?>, '<?php echo htmlspecialchars($candidate['full_name']); ?>')" 
                                        class="btn-contact">
                                    <i class="fas fa-envelope"></i> Contact Candidate
                                </button>
                                <!-- In find-candidates.php, ensure the view profile link is: -->
<a href="view-candidate-profile.php?id=<?php echo $candidate['id']; ?>" class="btn-profile">
    <i class="fas fa-user"></i> View Profile
</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Contact Modal -->
<div id="contactModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-envelope"></i> Contact Candidate</h3>
            <span class="close" onclick="closeContactModal()">&times;</span>
        </div>
        <div class="modal-body">
            <input type="hidden" id="candidate_id">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Candidate Name</label>
                <input type="text" id="candidate_name" readonly>
            </div>
            <div class="form-group">
                <label><i class="fas fa-comment"></i> Your Message</label>
                <textarea id="messageText" rows="5" placeholder="Write your message to this candidate..."></textarea>
                <small>Minimum 10 characters. Be professional and specific about the opportunity.</small>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-send-message" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i> Send Message
            </button>
            <button class="btn-cancel-modal" onclick="closeContactModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    </div>
</div>

<script>
// Contact Modal Functions
function showContactModal(candidateId, candidateName) {
    document.getElementById('candidate_id').value = candidateId;
    document.getElementById('candidate_name').value = candidateName;
    document.getElementById('contactModal').style.display = 'block';
    document.getElementById('messageText').focus();
}

function closeContactModal() {
    document.getElementById('contactModal').style.display = 'none';
    document.getElementById('messageText').value = '';
}

// Send Message Function
function sendMessage() {
    var candidateId = document.getElementById('candidate_id').value;
    var message = document.getElementById('messageText').value.trim();
    
    if (!message) {
        showNotification('error', 'Please enter a message');
        document.getElementById('messageText').focus();
        return;
    }
    
    if (message.length < 10) {
        showNotification('error', 'Message must be at least 10 characters');
        document.getElementById('messageText').focus();
        return;
    }
    
    // Show loading state
    var sendBtn = document.querySelector('#contactModal .btn-send-message');
    var originalText = sendBtn.innerHTML;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    sendBtn.disabled = true;
    
    $.ajax({
        url: 'ajax/send-message.php',
        method: 'POST',
        data: {
            receiver_id: candidateId,
            message: message
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showNotification('success', response.message || 'Message sent successfully!');
                closeContactModal();
                // Optional: Redirect to messages page
                setTimeout(function() {
                    if (confirm('Message sent! Would you like to view your messages?')) {
                        window.location.href = 'messages.php';
                    }
                }, 1500);
            } else {
                showNotification('error', response.message || 'Failed to send message');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            showNotification('error', 'Failed to send message. Please try again.');
        },
        complete: function() {
            sendBtn.innerHTML = originalText;
            sendBtn.disabled = false;
        }
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('contactModal');
    if (event.target == modal) {
        closeContactModal();
    }
}

// Enter key to send message (Ctrl+Enter or Cmd+Enter)
document.getElementById('messageText').addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        sendMessage();
    }
});

// Toast notification function
function showNotification(type, message) {
    if (typeof toastr !== 'undefined') {
        if (type === 'success') {
            toastr.success(message, 'Success!', {
                closeButton: true,
                progressBar: true,
                timeOut: 5000
            });
        } else if (type === 'error') {
            toastr.error(message, 'Error!', {
                closeButton: true,
                progressBar: true,
                timeOut: 5000
            });
        } else {
            toastr.info(message, 'Information', {
                closeButton: true,
                progressBar: true,
                timeOut: 5000
            });
        }
    } else {
        alert(message);
    }
}

// Search on Enter key
document.querySelector('.search-input-wrapper input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        this.form.submit();
    }
});
</script>

<?php include 'includes/footer.php'; ?>