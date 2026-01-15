<?php
$upload_dir = 'uploads/';

// Create uploads directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    echo "✅ Created uploads folder<br>";
} else {
    echo "✅ Uploads folder already exists<br>";
}

// Try to change permissions
if (chmod($upload_dir, 0777)) {
    echo "✅ Set correct permissions on uploads folder<br>";
} else {
    echo "⚠️ Could not change permissions (might need manual fix)<br>";
}

// Check what files are in uploads
echo "<br><strong>Files in uploads folder:</strong><br>";
$files = scandir($upload_dir);
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "- " . $file . "<br>";
    }
}

if (count($files) <= 2) {
    echo "<em>No files uploaded yet</em><br>";
}

echo "<br><a href='dashboard.php'>Go to Dashboard</a>";
?>
