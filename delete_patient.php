<?php
session_start();
require_once 'db_connect.php';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    header("Location: login.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];

// Check if patient ID is provided
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$patient_id = $_GET['id'];

// Verify that this patient belongs to the logged-in doctor
$stmt = $conn->prepare("SELECT * FROM patients WHERE id = ? AND doctor_id = ?");
$stmt->bind_param("ii", $patient_id, $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Patient not found or doesn't belong to this doctor
    $_SESSION['error'] = "Patient not found or you don't have permission to delete this patient!";
    header("Location: dashboard.php");
    exit();
}

$patient = $result->fetch_assoc();

// Delete the patient
$delete_stmt = $conn->prepare("DELETE FROM patients WHERE id = ? AND doctor_id = ?");
$delete_stmt->bind_param("ii", $patient_id, $doctor_id);

if ($delete_stmt->execute()) {
    $_SESSION['success'] = "Patient '" . $patient['patient_name'] . "' deleted successfully!";
} else {
    $_SESSION['error'] = "Failed to delete patient!";
}

// Redirect back to dashboard
header("Location: dashboard.php");
exit();
?>
