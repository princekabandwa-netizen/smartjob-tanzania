<?php
require_once 'includes/init.php';

// Cache enabled - Set caching headers
header('Cache-Control: public, max-age=300');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');

// Initialize variables
$featuredJobs = [];
$totalJobs = 0;
$totalEmployers = 0;
$totalJobSeekers = 0;
$categories = [];

// Check if PDO connection exists
if ($pdo !== null) {
    // Get featured jobs with optimization
    $stmt = $pdo->prepare("SELECT j.*, u.full_name as employer_name 
                           FROM jobs j 
                           JOIN users u ON j.employer_id = u.id 
                           WHERE j.status = 'active' AND j.deadline >= CURDATE() 
                           ORDER BY j.posted_date DESC LIMIT 6");
    $stmt->execute();
    $featuredJobs = $stmt->fetchAll();

    // Get job statistics (cached)
    $totalJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'active' AND deadline >= CURDATE()")->fetchColumn();
    $totalEmployers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employer'")->fetchColumn();
    $totalJobSeekers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'jobseeker'")->fetchColumn();

    // Get popular categories with job counts
    $categories = $pdo->query("SELECT c.*, COUNT(j.id) as job_count 
                               FROM categories c
                               LEFT JOIN jobs j ON j.category = c.name AND j.status = 'active' AND j.deadline >= CURDATE()
                               GROUP BY c.id
                               ORDER BY job_count DESC
                               LIMIT 8")->fetchAll();
}

include 'includes/header.php';
?>

<style>
/* Hero Section Enhancement */
.hero {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
    position: relative;
    overflow: hidden;
}

.hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
    background-size: cover;
    opacity: 0.3;
}

.hero-content {
    position: relative;
    z-index: 2;
}

/* Category Cards Enhancement */
.category-card {
    transition: transform 0.3s, box-shadow 0.3s;
}

.category-card:hover {
    transform: translateY(-8px);
    box-shadow: ye;
}

/* Job Cards with Lazy Loading */
.job-card {
    transition: transform 0.3s, box-shadow 0.3s;
    position: relative;
    overflow: hidden;
}

.job-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.job-card:hover::before {
    left: 100%;
}

/* Stats Animation */
.stat-number {
    animation: countUp 1s ease-out;
}

@keyframes countUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .hero-content h1 {
        font-size: 1.8rem;
    }
    
    .categories-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>Find Your Dream Job in <span class="highlight">Tanzania</span></h1>
            <p>Connect with top employers and discover exciting career opportunities across the country.</p>
            <div class="search-box">
                <form action="jobs.php" method="GET">
                    <div class="search-input-group">
                        <i class="fas fa-search"></i>
                        <input type="text" name="keyword" placeholder="Job title, keywords, or company" class="search-input">
                        <button type="submit" class="btn-search">Search Jobs</button>
                    </div>
                </form>
            </div>
            <div class="hero-stats">
                <div class="stat">
                    <div class="stat-number"><?php echo $totalJobs; ?>+</div>
                    <div class="stat-label">Active Jobs</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php echo $totalEmployers; ?>+</div>
                    <div class="stat-label">Employers</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php echo $totalJobSeekers; ?>+</div>
                    <div class="stat-label">Job Seekers</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="categories">
    <div class="container">
        <h2 class="section-title">Browse by <span class="highlight">Category</span></h2>
        <div class="categories-grid">
            <?php foreach($categories as $cat): ?>
            <a href="jobs.php?category=<?php echo urlencode($cat['name']); ?>" class="category-card" data-category="<?php echo $cat['name']; ?>">
                <div class="category-icon"><?php echo $cat['icon']; ?></div>
                <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
                <p><?php echo $cat['job_count']; ?> jobs</p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="featured-jobs">
    <div class="container">
        <h2 class="section-title">Featured <span class="highlight">Jobs</span></h2>
        <?php if(empty($featuredJobs)): ?>
            <div class="no-jobs" style="text-align: center; padding: 60px;">
                <i class="fas fa-briefcase" style="font-size: 60px; color: #ccc;"></i>
                <p>No jobs posted yet. Check back soon!</p>
            </div>
        <?php else: ?>
            <div class="jobs-grid">
                <?php foreach($featuredJobs as $job): ?>
                <div class="job-card" data-job-id="<?php echo $job['id']; ?>">
                    <div class="job-card-header">
                        <div class="company-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="job-type <?php echo $job['type']; ?>">
                            <?php echo ucfirst($job['type']); ?>
                        </div>
                    </div>
                    <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                    <p class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></p>
                    <div class="job-details">
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                        <span><i class="fas fa-money-bill-wave"></i> <?php echo htmlspecialchars($job['salary_range'] ?: 'Negotiable'); ?></span>
                        <span><i class="fas fa-calendar-alt"></i> Deadline: <?php echo date('M d', strtotime($job['deadline'])); ?></span>
                    </div>
                    <div class="job-card-footer">
                        <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn-view">View Details</a>
                        <?php if(isLoggedIn() && isJobSeeker()): ?>
                            <a href="apply-job.php?id=<?php echo $job['id']; ?>" class="btn-apply">Apply Now</a>
                        <?php elseif(!isLoggedIn()): ?>
                            <a href="login.php" class="btn-apply">Login to Apply</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="text-center">
            <a href="jobs.php" class="btn-browse-all">Browse All Jobs <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</section>

<section class="how-it-works">
    <div class="container">
        <h2 class="section-title">How It <span class="highlight">Works</span></h2>
        <div class="steps-grid">
            <div class="step">
                <div class="step-number">1</div>
                <i class="fas fa-user-plus step-icon"></i>
                <h3>Create Account</h3>
                <p>Sign up as a job seeker or employer in minutes.</p>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <i class="fas fa-search step-icon"></i>
                <h3>Search Jobs</h3>
                <p>Find the perfect job that matches your skills.</p>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <i class="fas fa-file-alt step-icon"></i>
                <h3>Apply Online</h3>
                <p>Submit your application with resume and cover letter.</p>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <i class="fas fa-check-circle step-icon"></i>
                <h3>Get Hired</h3>
                <p>Connect with employers and start your career.</p>
            </div>
        </div>
    </div>
</section>

<script>
// Lazy loading for images
document.addEventListener('DOMContentLoaded', function() {
    const lazyImages = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                imageObserver.unobserve(img);
            }
        });
    });
    lazyImages.forEach(img => imageObserver.observe(img));
});

// Animate stats on scroll
const statNumbers = document.querySelectorAll('.stat-number');
const statObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.animation = 'countUp 1s ease-out';
            statObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });
statNumbers.forEach(stat => statObserver.observe(stat));
</script>

<?php include 'includes/footer.php'; ?>