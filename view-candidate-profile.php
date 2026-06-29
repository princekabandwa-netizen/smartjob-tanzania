<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();
redirectIfNotEmployer();

$candidate_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$candidate_id) {
    setError("Invalid candidate ID");
    header('Location: find-candidates.php');
    exit();
}

// Get candidate details
$stmt = $pdo->prepare("SELECT u.*, 
                       (SELECT COUNT(*) FROM applications WHERE jobseeker_id = u.id) as total_applications,
                       (SELECT COUNT(*) FROM saved_jobs WHERE user_id = u.id) as saved_jobs
                       FROM users u 
                       WHERE u.id = ? AND u.role = 'jobseeker'");
$stmt->execute([$candidate_id]);
$candidate = $stmt->fetch();

if (!$candidate) {
    setError("Candidate not found");
    header('Location: find-candidates.php');
    exit();
}

// Get candidate's applications with job details
$stmt = $pdo->prepare("SELECT a.*, j.title, j.company_name, j.location, j.type, j.status as job_status
                       FROM applications a 
                       JOIN jobs j ON a.job_id = j.id 
                       WHERE a.jobseeker_id = ? 
                       ORDER BY a.applied_date DESC LIMIT 10");
$stmt->execute([$candidate_id]);
$applications = $stmt->fetchAll();

// Get candidate's profile details
$stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$stmt->execute([$candidate_id]);
$profile = $stmt->fetch();

// Get candidate's education
$stmt = $pdo->prepare("SELECT * FROM user_education WHERE user_id = ? ORDER BY end_year DESC");
$stmt->execute([$candidate_id]);
$education = $stmt->fetchAll();

// Get candidate's experience
$stmt = $pdo->prepare("SELECT * FROM user_experience WHERE user_id = ? ORDER BY end_date DESC");
$stmt->execute([$candidate_id]);
$experience = $stmt->fetchAll();

// Get candidate's certifications
$stmt = $pdo->prepare("SELECT * FROM user_certifications WHERE user_id = ? ORDER BY year_completed DESC");
$stmt->execute([$candidate_id]);
$certifications = $stmt->fetchAll();

include 'includes/header.php';
?>

<style>
.candidate-profile-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
    min-height: calc(100vh - 200px);
}

.profile-container {
    max-width: 1000px;
    margin: 0 auto;
}

/* Profile Header */
.profile-header-card {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    color: white;
    display: flex;
    gap: 30px;
    align-items: center;
    flex-wrap: wrap;
}

.avatar-circle {
    width: 120px;
    height: 120px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: bold;
    border: 3px solid white;
}

.profile-info h1 {
    font-size: 1.8rem;
    margin-bottom: 5px;
}

.profile-info p {
    opacity: 0.9;
    margin-bottom: 5px;
}

.profile-info .badge {
    background: rgba(255,255,255,0.2);
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    display: inline-block;
    margin-right: 10px;
}

.profile-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.btn-contact-profile {
    padding: 10px 25px;
    background: var(--secondary-color);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-contact-profile:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-box {
    background: white;
    padding: 20px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.stat-box .number {
    font-size: 28px;
    font-weight: bold;
    color: var(--secondary-color);
}

.stat-box .label {
    color: #666;
    font-size: 13px;
    margin-top: 5px;
}

/* Info Cards */
.info-card {
    background: white;
    border-radius: 15px;
    margin-bottom: 25px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.card-title {
    background: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #e0e0e0;
    font-weight: 600;
    color: var(--primary-color);
}

.card-title i {
    margin-right: 10px;
    color: var(--secondary-color);
}

.card-body {
    padding: 20px;
}

.skill-tag {
    display: inline-block;
    padding: 5px 15px;
    background: #f0f0f0;
    border-radius: 20px;
    margin: 5px;
    font-size: 13px;
    color: #555;
}

.skill-tag:hover {
    background: var(--secondary-color);
    color: white;
}

/* Item List */
.item-list {
    list-style: none;
    padding: 0;
}

.item-list li {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.item-list li:last-child {
    border-bottom: none;
}

.item-list .title {
    font-weight: 600;
    color: var(--primary-color);
}

.item-list .subtitle {
    color: #666;
    font-size: 13px;
}

.item-list .date {
    color: #999;
    font-size: 12px;
}

.item-list .description {
    color: #555;
    font-size: 13px;
    margin-top: 5px;
}

/* Contact Modal */
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

.btn-send-message {
    flex: 1;
    padding: 12px;
    background: var(--secondary-color);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.btn-cancel-modal {
    flex: 1;
    padding: 12px;
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

@media (max-width: 768px) {
    .profile-header-card {
        flex-direction: column;
        text-align: center;
    }
    
    .profile-actions {
        justify-content: center;
    }
}
</style>

<section class="candidate-profile-section">
    <div class="container">
        <div class="profile-container">
            <!-- Profile Header -->
            <div class="profile-header-card">
                <div class="avatar-circle">
                    <?php echo strtoupper(substr($candidate['full_name'], 0, 2)); ?>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($candidate['full_name']); ?></h1>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($candidate['email']); ?></p>
                    <?php if($candidate['phone']): ?>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($candidate['phone']); ?></p>
                    <?php endif; ?>
                    <div>
                        <span class="badge"><i class="fas fa-user"></i> Job Seeker</span>
                        <span class="badge"><i class="fas fa-calendar"></i> Joined <?php echo date('M Y', strtotime($candidate['created_at'])); ?></span>
                    </div>
                    <div class="profile-actions">
                        <button onclick="showContactModal(<?php echo $candidate['id']; ?>, '<?php echo htmlspecialchars($candidate['full_name']); ?>')" class="btn-contact-profile">
                            <i class="fas fa-envelope"></i> Contact Candidate
                        </button>
                        <a href="find-candidates.php" class="btn-contact-profile" style="background: #6c757d;">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="number"><?php echo $candidate['total_applications']; ?></div>
                    <div class="label">Total Applications</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?php echo $candidate['saved_jobs']; ?></div>
                    <div class="label">Saved Jobs</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?php echo count($education); ?></div>
                    <div class="label">Education</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?php echo count($experience); ?></div>
                    <div class="label">Experience</div>
                </div>
            </div>

            <!-- Skills -->
            <?php if($profile && !empty($profile['skills'])): ?>
            <div class="info-card">
                <div class="card-title"><i class="fas fa-code"></i> Skills</div>
                <div class="card-body">
                    <?php 
                    $skills = explode(',', $profile['skills']);
                    foreach($skills as $skill):
                        $skill = trim($skill);
                        if(!empty($skill)):
                    ?>
                        <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Bio -->
            <?php if($profile && !empty($profile['bio'])): ?>
            <div class="info-card">
                <div class="card-title"><i class="fas fa-align-left"></i> About</div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($profile['bio'])); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Experience -->
            <?php if(!empty($experience)): ?>
            <div class="info-card">
                <div class="card-title"><i class="fas fa-briefcase"></i> Work Experience</div>
                <div class="card-body">
                    <ul class="item-list">
                        <?php foreach($experience as $exp): ?>
                            <li>
                                <div class="title"><?php echo htmlspecialchars($exp['job_title']); ?></div>
                                <div class="subtitle"><?php echo htmlspecialchars($exp['company']); ?></div>
                                <div class="date">
                                    <?php echo date('M Y', strtotime($exp['start_date'])); ?> - 
                                    <?php echo $exp['is_current'] ? 'Present' : date('M Y', strtotime($exp['end_date'])); ?>
                                </div>
                                <?php if($exp['description']): ?>
                                    <div class="description"><?php echo nl2br(htmlspecialchars($exp['description'])); ?></div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <!-- Education -->
            <?php if(!empty($education)): ?>
            <div class="info-card">
                <div class="card-title"><i class="fas fa-graduation-cap"></i> Education</div>
                <div class="card-body">
                    <ul class="item-list">
                        <?php foreach($education as $edu): ?>
                            <li>
                                <div class="title"><?php echo htmlspecialchars($edu['degree']); ?></div>
                                <div class="subtitle"><?php echo htmlspecialchars($edu['institution']); ?></div>
                                <div class="date"><?php echo $edu['start_year']; ?> - <?php echo $edu['end_year']; ?></div>
                                <?php if($edu['description']): ?>
                                    <div class="description"><?php echo nl2br(htmlspecialchars($edu['description'])); ?></div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <!-- Certifications -->
            <?php if(!empty($certifications)): ?>
            <div class="info-card">
                <div class="card-title"><i class="fas fa-certificate"></i> Certifications</div>
                <div class="card-body">
                    <ul class="item-list">
                        <?php foreach($certifications as $cert): ?>
                            <li>
                                <div class="title"><?php echo htmlspecialchars($cert['cert_name']); ?></div>
                                <div class="subtitle"><?php echo htmlspecialchars($cert['issuing_org']); ?></div>
                                <div class="date"><?php echo $cert['year_completed']; ?></div>
                                <?php if($cert['cert_link']): ?>
                                    <div><a href="<?php echo $cert['cert_link']; ?>" target="_blank">View Certificate</a></div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Applications -->
            <?php if(!empty($applications)): ?>
            <div class="info-card">
                <div class="card-title"><i class="fas fa-file-alt"></i> Recent Applications</div>
                <div class="card-body">
                    <ul class="item-list">
                        <?php foreach($applications as $app): ?>
                            <li>
                                <div class="title"><?php echo htmlspecialchars($app['title']); ?></div>
                                <div class="subtitle"><?php echo htmlspecialchars($app['company_name']); ?> - <?php echo htmlspecialchars($app['location']); ?></div>
                                <div class="date">
                                    Applied: <?php echo date('M d, Y', strtotime($app['applied_date'])); ?> 
                                    <span class="status-badge status-<?php echo $app['status']; ?>"><?php echo ucfirst($app['status']); ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
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
                <label>Candidate Name</label>
                <input type="text" id="candidate_name" readonly style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>Your Message</label>
                <textarea id="messageText" rows="5" placeholder="Write your message to this candidate..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; resize:vertical;"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-send-message" onclick="sendMessage()">Send Message</button>
            <button class="btn-cancel-modal" onclick="closeContactModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
function showContactModal(candidateId, candidateName) {
    document.getElementById('candidate_id').value = candidateId;
    document.getElementById('candidate_name').value = candidateName;
    document.getElementById('contactModal').style.display = 'block';
}

function closeContactModal() {
    document.getElementById('contactModal').style.display = 'none';
    document.getElementById('messageText').value = '';
}

function sendMessage() {
    var candidateId = document.getElementById('candidate_id').value;
    var message = document.getElementById('messageText').value.trim();
    
    if (!message) {
        showNotification('error', 'Please enter a message');
        return;
    }
    
    if (message.length < 10) {
        showNotification('error', 'Message must be at least 10 characters');
        return;
    }
    
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
                showNotification('success', 'Message sent successfully!');
                closeContactModal();
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Failed to send message');
        }
    });
}

function showNotification(type, message) {
    if (typeof toastr !== 'undefined') {
        if (type === 'success') toastr.success(message);
        else if (type === 'error') toastr.error(message);
        else toastr.info(message);
    } else {
        alert(message);
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        closeContactModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>