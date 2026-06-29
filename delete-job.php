<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get job details for logging
$stmt = $pdo->prepare("SELECT title, company_name, employer_id FROM jobs WHERE id = ?");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    setError("Job not found");
    header('Location: dashboard.php');
    exit();
}

// Check permission (employer owns it or admin)
if ($_SESSION['role'] != 'admin' && $job['employer_id'] != $_SESSION['user_id']) {
    setError("You don't have permission to delete this job");
    header('Location: dashboard.php');
    exit();
}
// Add this before deletion
$job_title = $job['title'];
$job_company = $job['company_name'];

// Delete the job
$stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
if ($stmt->execute([$job_id])) {
    $current_time = date('H:i:s');
    
    // Track job deletion
    trackUserAction($pdo, $_SESSION['user_id'], 'job_deleted', 
                   "Deleted job: {$job_title} at {$job_company}", 
                   'job', $job_id, true,
                   'Job Deleted', 
                   "Your job '{$job_title}' was deleted at {$current_time}");
    
    setSuccess("Job '{$job_title}' has been deleted successfully.");
} else {
    setError("Failed to delete the job.");
}


// Delete the job
$stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
if ($stmt->execute([$job_id])) {
    // Log deletion
    logAllUserActions($pdo, $_SESSION['user_id'], 'job_deleted', "Deleted job: {$job['title']} at {$job['company_name']}", 'job', $job_id);
    createActionNotification($pdo, $_SESSION['user_id'], 'system', 'Job Deleted', 
                            "Your job '{$job['title']}' has been deleted.", 
                            "dashboard.php");
    
    setSuccess("Job '{$job['title']}' has been deleted successfully.");
} else {
    setError("Failed to delete the job.");
}

header('Location: dashboard.php');
exit();
?>