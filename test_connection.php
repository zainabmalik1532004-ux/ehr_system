<?php
// Test database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'ehr_system';

echo "<h2>Testing Database Connection...</h2>";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Connection failed: " . $conn->connect_error . "</p>";
    exit();
}

echo "<p style='color: green;'>✅ Successfully connected to database!</p>";

// Test if doctors table exists
$result = $conn->query("SHOW TABLES LIKE 'doctors'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ 'doctors' table exists</p>";
} else {
    echo "<p style='color: red;'>❌ 'doctors' table does NOT exist</p>";
}

// Test if patients table exists
$result = $conn->query("SHOW TABLES LIKE 'patients'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ 'patients' table exists</p>";
    
    // Count patients
    $count = $conn->query("SELECT COUNT(*) as total FROM patients");
    $row = $count->fetch_assoc();
    echo "<p>Total patients: " . $row['total'] . "</p>";
} else {
    echo "<p style='color: red;'>❌ 'patients' table does NOT exist</p>";
}

// Test if doctor exists
$result = $conn->query("SELECT COUNT(*) as total FROM doctors");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>Total doctors: " . $row['total'] . "</p>";
    
    if ($row['total'] == 0) {
        echo "<p style='color: orange;'>⚠️ No doctors in database. You need to create one!</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Error checking doctors: " . $conn->error . "</p>";
}

$conn->close();
?>
