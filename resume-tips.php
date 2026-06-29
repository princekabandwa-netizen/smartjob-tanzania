<?php
require_once 'includes/init.php';

// Get site settings for dynamic content
$site_settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
while($row = $stmt->fetch()) {
    $site_settings[$row['setting_key']] = $row['setting_value'];
}

include 'includes/header.php';
?>

<style>
.tips-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
}

.tips-header {
    text-align: center;
    margin-bottom: 50px;
}

.tips-header h1 {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.tips-header p {
    color: #666;
    font-size: 1.1rem;
}

.tips-content {
    max-width: 900px;
    margin: 0 auto 50px;
}

.tip-card {
    background: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    position: relative;
    transition: all 0.3s;
}

.tip-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.12);
}

.tip-number {
    position: absolute;
    top: -15px;
    left: 30px;
    background: var(--secondary-color);
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
}

.tip-card h2 {
    margin-top: 15px;
    color: var(--primary-color);
    font-size: 1.3rem;
}

.tip-card p {
    color: #555;
    line-height: 1.6;
    margin: 15px 0;
}

.tip-card ul {
    list-style: none;
    padding-left: 0;
}

.tip-card ul li {
    padding: 8px 0 8px 30px;
    position: relative;
    color: #555;
}

.tip-card ul li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: var(--secondary-color);
    font-weight: bold;
}

.example-box {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    border-left: 4px solid var(--secondary-color);
    margin: 15px 0;
}

.comparison-box {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 15px 0;
}

.bad-example, .good-example {
    padding: 15px;
    border-radius: 10px;
}

.bad-example {
    background: #fdeaea;
    color: #e74c3c;
    border-left: 4px solid #e74c3c;
}

.good-example {
    background: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

/* Resume Templates Section */
.resume-template-section {
    max-width: 1000px;
    margin: 0 auto 50px;
}

.resume-template-section h2 {
    text-align: center;
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 40px;
}

.templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
}

.template-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    transition: all 0.3s;
    text-align: center;
    padding: 30px 20px;
}

.template-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
}

.template-icon {
    font-size: 60px;
    color: var(--secondary-color);
    margin-bottom: 15px;
}

.template-card h3 {
    color: var(--primary-color);
    margin-bottom: 10px;
    font-size: 1.2rem;
}

.template-card p {
    color: #666;
    font-size: 14px;
    margin-bottom: 5px;
}

.template-preview {
    background: #f8f9fa;
    padding: 5px;
    border-radius: 10px;
    margin: 15px 0;
    min-height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e0e0e0;
}

.template-preview i {
    font-size: 48px;
    color: #ccc;
}

.btn-download {
    display: inline-block;
    padding: 1px 1px;
    background: var(--secondary-color);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
}

.btn-download:hover {
    background: #c0392b;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(231,76,60,0.3);
}

.btn-download i {
    margin-right: 8px;
}

.template-actions {
    display: flex;
    gap: 3px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-preview {
    display: inline-block;
    padding: 1px 1px;
    background: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
}

.btn-preview:hover {
    background: var(--accent-color);
    transform: translateY(-2px);
}

/* Checklist Section */
.checklist-section {
    max-width: 800px;
    margin: 0 auto 50px;
}

.checklist-section h2 {
    text-align: center;
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 30px;
}

.checklist {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.checklist label {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    border-radius: 8px;
    transition: all 0.3s;
    cursor: pointer;
}

.checklist label:hover {
    background: #f8f9fa;
}

.checklist input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

/* Newsletter Section */
.resources-newsletter {
    max-width: 600px;
    margin: 0 auto 50px;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    padding: 40px;
    border-radius: 15px;
    color: white;
    text-align: center;
}

.resources-newsletter h3 {
    font-size: 1.5rem;
    margin-bottom: 10px;
}

.resources-newsletter p {
    opacity: 0.9;
    margin-bottom: 20px;
}

.newsletter-form {
    display: flex;
    gap: 10px;
    max-width: 500px;
    margin: 0 auto;
}

.newsletter-form input {
    flex: 1;
    padding: 12px 15px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
}

.newsletter-form input:focus {
    outline: none;
}

.newsletter-form button {
    padding: 12px 25px;
    background: var(--secondary-color);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.newsletter-form button:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .tips-header h1 {
        font-size: 1.8rem;
    }
    
    .comparison-box {
        grid-template-columns: 1fr;
    }
    
    .templates-grid {
        grid-template-columns: 1fr;
    }
    
    .checklist {
        grid-template-columns: 1fr;
    }
    
    .newsletter-form {
        flex-direction: column;
    }
    
    .template-actions {
        flex-direction: column;
        align-items: center;
    }
}
</style>

<section class="tips-section">
    <div class="container">
        <div class="tips-header">
            <h1><i class="fas fa-pen-fancy"></i> Resume Writing Tips</h1>
            <p>Create a resume that gets noticed by employers</p>
        </div>

        <div class="tips-content">
            <div class="tip-card">
                <div class="tip-number">01</div>
                <h2>Choose the Right Format</h2>
                <p>Select a resume format that best showcases your experience:</p>
                <ul>
                    <li><strong>Chronological:</strong> Best for candidates with steady work history</li>
                    <li><strong>Functional:</strong> Ideal for highlighting skills over experience</li>
                    <li><strong>Combination:</strong> Mix of both, great for career changers</li>
                </ul>
            </div>

            <div class="tip-card">
                <div class="tip-number">02</div>
                <h2>Tailor to Each Job</h2>
                <p>Customize your resume for every application:</p>
                <ul>
                    <li>Use keywords from the job description</li>
                    <li>Highlight relevant experience first</li>
                    <li>Remove unrelated positions</li>
                    <li>Match your skills to job requirements</li>
                </ul>
            </div>

            <div class="tip-card">
                <div class="tip-number">03</div>
                <h2>Write a Strong Summary</h2>
                <p>Your professional summary should grab attention immediately:</p>
                <div class="example-box">
                    <p><strong>Example:</strong> "Results-driven Marketing Manager with 7+ years of experience in digital strategy, increasing brand engagement by 150% through innovative campaigns."</p>
                </div>
            </div>

            <div class="tip-card">
                <div class="tip-number">04</div>
                <h2>Quantify Achievements</h2>
                <p>Use numbers to demonstrate your impact:</p>
                <div class="comparison-box">
                    <div class="bad-example">
                        <strong>❌ Bad:</strong> "Responsible for increasing sales"
                    </div>
                    <div class="good-example">
                        <strong>✅ Good:</strong> "Increased sales by 45% within 6 months, generating TSh 50M in additional revenue"
                    </div>
                </div>
            </div>

            <div class="tip-card">
                <div class="tip-number">05</div>
                <h2>Include Relevant Keywords</h2>
                <p>Many companies use Applicant Tracking Systems (ATS):</p>
                <ul>
                    <li>Research industry-specific keywords</li>
                    <li>Include technical skills and software</li>
                    <li>Use standard job titles</li>
                    <li>Avoid images and complex formatting</li>
                </ul>
            </div>

            <div class="tip-card">
                <div class="tip-number">06</div>
                <h2>Proofread Carefully</h2>
                <p>Errors can eliminate you from consideration:</p>
                <ul>
                    <li>Read your resume aloud</li>
                    <li>Use spell-check tools</li>
                    <li>Ask someone else to review it</li>
                    <li>Check for consistent formatting</li>
                </ul>
            </div>
        </div>

        <!-- Resume Templates Section -->
        <div class="resume-template-section">
            <h2><i class="fas fa-file-alt"></i> Free Resume Templates</h2>
            <div class="templates-grid">
                <div class="template-card">
                    <div class="template-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <h3>Modern Professional</h3>
                    <p>Clean, contemporary design for corporate roles</p>
                    <div class="template-preview">
                        <i class="fas fa-file-pdf" style="font-size: 48px; color: #e74c3c;"></i>
                    </div>
                    <div class="template-actions">
                        <a href="templates/modern-resume.pdf" class="btn-download" download>
                            <i class="fas fa-download"></i> Download PDF
                        </a>
                        <a href="templates/modern-resume-preview.jpg" class="btn-preview" target="_blank">
                            <i class="fas fa-eye"></i> Preview
                        </a>
                    </div>
                </div>

                <div class="template-card">
                    <div class="template-icon">
                        <i class="fas fa-file-word"></i>
                    </div>
                    <h3>Creative Design</h3>
                    <p>Unique layout for creative industries</p>
                    <div class="template-preview">
                        <i class="fas fa-file-word" style="font-size: 48px; color: #2b5797;"></i>
                    </div>
                    <div class="template-actions">
                        <a href="templates/creative-resume.docx" class="btn-download" download>
                            <i class="fas fa-download"></i> Download DOCX
                        </a>
                        <a href="templates/creative-resume-preview.jpg" class="btn-preview" target="_blank">
                            <i class="fas fa-eye"></i> Preview
                        </a>
                    </div>
                </div>

                <div class="template-card">
                    <div class="template-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3>Simple & Clean</h3>
                    <p>ATS-friendly format for all industries</p>
                    <div class="template-preview">
                        <i class="fas fa-file-alt" style="font-size: 48px; color: #27ae60;"></i>
                    </div>
                    <div class="template-actions">
                        <a href="templates/simple-resume.pdf" class="btn-download" download>
                            <i class="fas fa-download"></i> Download PDF
                        </a>
                        <a href="templates/simple-resume-preview.jpg" class="btn-preview" target="_blank">
                            <i class="fas fa-eye"></i> Preview
                        </a>
                    </div>
                </div>

                <!-- Additional Templates -->
                <div class="template-card">
                    <div class="template-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <h3>Executive</h3>
                    <p>Professional design for senior roles</p>
                    <div class="template-preview">
                        <i class="fas fa-file-pdf" style="font-size: 48px; color: #8e44ad;"></i>
                    </div>
                    <div class="template-actions">
                        <a href="templates/executive-resume.pdf" class="btn-download" download>
                            <i class="fas fa-download"></i> Download PDF
                        </a>
                        <a href="templates/executive-resume-preview.jpg" class="btn-preview" target="_blank">
                            <i class="fas fa-eye"></i> Preview
                        </a>
                    </div>
                </div>

                <div class="template-card">
                    <div class="template-icon">
                        <i class="fas fa-file-word"></i>
                    </div>
                    <h3>Technical</h3>
                    <p>Ideal for IT and technical positions</p>
                    <div class="template-preview">
                        <i class="fas fa-file-word" style="font-size: 48px; color: #f39c12;"></i>
                    </div>
                    <div class="template-actions">
                        <a href="templates/technical-resume.docx" class="btn-download" download>
                            <i class="fas fa-download"></i> Download DOCX
                        </a>
                        <a href="templates/technical-resume-preview.jpg" class="btn-preview" target="_blank">
                            <i class="fas fa-eye"></i> Preview
                        </a>
                    </div>
                </div>

                <div class="template-card">
                    <div class="template-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <h3>Graduate</h3>
                    <p>Perfect for fresh graduates and entry-level</p>
                    <div class="template-preview">
                        <i class="fas fa-file-pdf" style="font-size: 48px; color: #3498db;"></i>
                    </div>
                    <div class="template-actions">
                        <a href="templates/graduate-resume.pdf" class="btn-download" download>
                            <i class="fas fa-download"></i> Download PDF
                        </a>
                        <a href="templates/graduate-resume-preview.jpg" class="btn-preview" target="_blank">
                            <i class="fas fa-eye"></i> Preview
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Checklist Section -->
        <div class="checklist-section">
            <h2><i class="fas fa-check-double"></i> Final Resume Checklist</h2>
            <div class="checklist">
                <label><input type="checkbox"> Resume is 1-2 pages maximum</label>
                <label><input type="checkbox"> Contact information is complete and correct</label>
                <label><input type="checkbox"> Professional email address used</label>
                <label><input type="checkbox"> No spelling or grammar errors</label>
                <label><input type="checkbox"> Consistent formatting throughout</label>
                <label><input type="checkbox"> Achievements quantified with numbers</label>
                <label><input type="checkbox"> Tailored to the specific job</label>
                <label><input type="checkbox"> Saved as PDF for submissions</label>
            </div>
        </div>

        <!-- Newsletter Section -->
        <div class="resources-newsletter">
            <h3><i class="fas fa-envelope"></i> Get Career Tips Delivered</h3>
            <p>Subscribe to our newsletter for weekly career advice and job alerts</p>
            <form class="newsletter-form" method="POST" action="subscribe.php">
                <input type="email" name="email" placeholder="Your email address" required>
                <button type="submit">Subscribe</button>
            </form>
        </div>
    </div>
</section>

<script>
// Auto-check checklist items when clicked
document.querySelectorAll('.checklist input[type="checkbox"]').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        var label = this.closest('label');
        if (this.checked) {
            label.style.color = '#27ae60';
            label.style.textDecoration = 'line-through';
        } else {
            label.style.color = '';
            label.style.textDecoration = '';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>