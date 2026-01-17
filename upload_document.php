<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['doctor_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: dashboard.php");
    exit();
}

$patient_id = intval($_POST['patient_id']);
$document_name = mysqli_real_escape_string($conn, $_POST['document_name']);
$document_type = mysqli_real_escape_string($conn, $_POST['document_type']);

// Handle file upload
if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
    $allowed_types = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg',
        'image/jpg',
        'image/png'
    ];
    
    if (in_array($_FILES['document_file']['type'], $allowed_types)) {
        $file_extension = pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);
        $new_filename = 'doc_' . $patient_id . '_' . time() . '.' . $file_extension;
        $upload_path = 'uploads/documents/' . $new_filename;
        
        if (move_uploaded_file($_FILES['document_file']['tmp_name'], $upload_path)) {
            $sql = "INSERT INTO patient_documents (patient_id, doctor_id, document_name, document_type, file_path) 
                    VALUES ($patient_id, {$_SESSION['doctor_id']}, '$document_name', '$document_type', '$upload_path')";
            
            if (mysqli_query($conn, $sql)) {
                header("Location: view_patient.php?id=$patient_id&success=doc_uploaded");
            } else {
                header("Location: view_patient.php?id=$patient_id&error=upload_failed");
            }
        } else {
            header("Location: view_patient.php?id=$patient_id&error=file_move_failed");
        }
    } else {
        header("Location: view_patient.php?id=$patient_id&error=invalid_type");
    }
} else {
    header("Location: view_patient.php?id=$patient_id&error=no_file");
}
exit();
?>
