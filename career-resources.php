<?php
require_once 'includes/init.php';
include 'includes/header.php';
?>

<section class="resources-section">
    <div class="container">
        <div class="resources-header">
            <h1><i class="fas fa-graduation-cap"></i> Career Resources</h1>
            <p>Expert advice, tips, and resources to help you advance your career</p>
        </div>

        <div class="resources-grid">
            <div class="resource-card">
                <div class="resource-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3>Resume Writing Guide</h3>
                <p>Learn how to create a professional resume that stands out to employers.</p>
                <ul>
                    <li>Resume templates</li>
                    <li>Common mistakes to avoid</li>
                    <li>Industry-specific tips</li>
                </ul>
                <a href="resume-tips.php" class="btn-read-more">Read More →</a>
            </div>

            <div class="resource-card">
                <div class="resource-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3>Interview Preparation</h3>
                <p>Master the art of job interviews with our comprehensive guide.</p>
                <ul>
                    <li>Common interview questions</li>
                    <li>Tips for virtual interviews</li>
                    <li>Questions to ask employers</li>
                </ul>
                <a href="interview-tips.php" class="btn-read-more">Read More →</a>
            </div>

            <div class="resource-card">
                <div class="resource-icon">
                    <i class="fas fa-chalkboard-user"></i>
                </div>
                <h3>Career Development</h3>
                <p>Plan your career path and develop essential skills for success.</p>
                <ul>
                    <li>Skill development courses</li>
                    <li>Certification guides</li>
                    <li>Career advancement tips</li>
                </ul>
                <a href="career-development.php" class="btn-read-more">Read More →</a>
            </div>

            <div class="resource-card">
                <div class="resource-icon">
                    <i class="fas fa-network-wired"></i>
                </div>
                <h3>Networking Strategies</h3>
                <p>Build a strong professional network to uncover hidden opportunities.</p>
                <ul>
                    <li>LinkedIn optimization</li>
                    <li>Networking events</li>
                    <li>Professional associations</li>
                </ul>
                <a href="networking-tips.php" class="btn-read-more">Read More →</a>
            </div>

            <div class="resource-card">
                <div class="resource-icon">
                    <i class="fas fa-salary"></i>
                </div>
                <h3>Salary Negotiation</h3>
                <p>Learn how to negotiate your salary and benefits effectively.</p>
                <ul>
                    <li>Market research tips</li>
                    <li>Negotiation scripts</li>
                    <li>Benefits to consider</li>
                </ul>
                <a href="salary-negotiation.php" class="btn-read-more">Read More →</a>
            </div>

            <div class="resource-card">
                <div class="resource-icon">
                    <i class="fas fa-laptop-code"></i>
                </div>
                <h3>Remote Work Guide</h3>
                <p>Thrive in remote and hybrid work environments.</p>
                <ul>
                    <li>Remote job search tips</li>
                    <li>Home office setup</li>
                    <li>Time management skills</li>
                </ul>
                <a href="remote-work.php" class="btn-read-more">Read More →</a>
            </div>
        </div>

        <div class="resources-newsletter">
            <h3>Get Career Tips Delivered to Your Inbox</h3>
            <p>Subscribe to our newsletter for weekly career advice and job alerts</p>
            <form class="newsletter-form" method="POST" action="subscribe.php">
                <input type="email" name="email" placeholder="Your email address" required>
                <button type="submit">Subscribe</button>
            </form>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>