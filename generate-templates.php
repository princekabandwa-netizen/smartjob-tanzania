<?php
// This script generates sample template files
$templates = [
    'modern-resume.pdf',
    'creative-resume.docx',
    'simple-resume.pdf',
    'executive-resume.pdf',
    'technical-resume.docx',
    'graduate-resume.pdf'
];

// Create templates directory if not exists
if (!is_dir('templates')) {
    mkdir('templates', 0777, true);
}

// Create placeholder files
foreach ($templates as $template) {
    $filepath = 'templates/' . $template;
    if (!file_exists($filepath)) {
        // Create a simple text file as placeholder
        $content = "This is a placeholder for: " . $template . "\n";
        $content .= "Please replace this with the actual template file.\n";
        $content .= "Generated on: " . date('Y-m-d H:i:s');
        file_put_contents($filepath, $content);
        echo "Created: " . $template . "<br>";
    } else {
        echo "File already exists: " . $template . "<br>";
    }
}

// Also create preview images (placeholder)
$previews = [
    'modern-resume-preview.jpg',
    'creative-resume-preview.jpg',
    'simple-resume-preview.jpg',
    'executive-resume-preview.jpg',
    'technical-resume-preview.jpg',
    'graduate-resume-preview.jpg'
];

foreach ($previews as $preview) {
    $filepath = 'templates/' . $preview;
    if (!file_exists($filepath)) {
        // Create a simple text file as placeholder
        $content = "This is a placeholder preview image for: " . $preview . "\n";
        $content .= "Please replace this with an actual preview image.\n";
        $content .= "Generated on: " . date('Y-m-d H:i:s');
        file_put_contents($filepath, $content);
        echo "Created: " . $preview . "<br>";
    }
}

echo "<br><strong>All template files created successfully!</strong><br>";
echo "<a href='resume-tips.php'>Go back to Resume Tips</a>";
?>