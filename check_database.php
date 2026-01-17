<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['doctor_id'])) {
    die("Please login first");
}

// Get patient ID
$patient_id = 2; // Change this to your patient ID

// Fetch patient data
$sql = "SELECT * FROM patients WHERE id = $patient_id";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $patient = mysqli_fetch_assoc($result);
    
    echo "<h2>Database Column Names:</h2>";
    echo "<pre>";
    print_r(array_keys($patient));
    echo "</pre>";
    
    echo "<h2>Patient Data:</h2>";
    echo "<pre>";
    print_r($patient);
    echo "</pre>";
} else {
    echo "No patient found";
}
?>
