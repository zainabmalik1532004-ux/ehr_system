<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['doctor_id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($patient_id == 0) {
    header("Location: dashboard.php");
    exit();
}

// Fetch patient data
$sql = "SELECT * FROM patients WHERE id = $patient_id AND doctor_id = {$_SESSION['doctor_id']}";
$result = mysqli_query($conn, $sql);
if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: dashboard.php");
    exit();
}
$patient = mysqli_fetch_assoc($result);

// Handle form submission
if (isset($_POST['update_patient'])) {
    $updates = [];
    
    // PATIENT NAME - Correct column name
    if (isset($_POST['patient_name']) && !empty($_POST['patient_name'])) {
        $patient_name = mysqli_real_escape_string($conn, $_POST['patient_name']);
        $updates[] = "patient_name = '$patient_name'";
    }
    
    // Date of birth and age
    if (isset($_POST['date_of_birth']) && !empty($_POST['date_of_birth'])) {
        $date_of_birth = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
        $updates[] = "date_of_birth = '$date_of_birth'";
        
        try {
            $dob = new DateTime($date_of_birth);
            $now = new DateTime();
            $age = $now->diff($dob)->y;
            $updates[] = "age = $age";
        } catch (Exception $e) {}
    }
    
    // Gender
    if (isset($_POST['gender']) && !empty($_POST['gender'])) {
        $gender = mysqli_real_escape_string($conn, $_POST['gender']);
        $updates[] = "gender = '$gender'";
    }
    
    // Phone
    $phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, $_POST['phone']) : '';
    $updates[] = "phone = '$phone'";
    
    // Email
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
    $updates[] = "email = '$email'";
    
    // Address
    $address = isset($_POST['address']) ? mysqli_real_escape_string($conn, $_POST['address']) : '';
    $updates[] = "address = '$address'";
    
    // Blood Type
    if (isset($_POST['blood_type']) && !empty($_POST['blood_type'])) {
        $blood_type = mysqli_real_escape_string($conn, $_POST['blood_type']);
        $updates[] = "blood_type = '$blood_type'";
    }
    
    // Weight
    if (isset($_POST['weight']) && $_POST['weight'] !== '') {
        $weight = floatval($_POST['weight']);
        $updates[] = "weight = $weight";
    }
    
    // Allergies
    $has_allergies = isset($_POST['has_allergies']) ? 1 : 0;
    $updates[] = "has_allergies = $has_allergies";
    
    // Emergency Contact
    $emergency_contact = isset($_POST['emergency_contact']) ? mysqli_real_escape_string($conn, $_POST['emergency_contact']) : '';
    $updates[] = "emergency_contact = '$emergency_contact'";
    
    // Emergency Phone
    $emergency_phone = isset($_POST['emergency_phone']) ? mysqli_real_escape_string($conn, $_POST['emergency_phone']) : '';
    $updates[] = "emergency_phone = '$emergency_phone'";
    
    // Medical History
    $medical_history = isset($_POST['medical_history']) ? mysqli_real_escape_string($conn, $_POST['medical_history']) : '';
    $updates[] = "medical_history = '$medical_history'";
    
    // Execute update
    if (!empty($updates)) {
        $update_sql = "UPDATE patients SET " . implode(', ', $updates) . " WHERE id = $patient_id AND doctor_id = {$_SESSION['doctor_id']}";
        
        if (mysqli_query($conn, $update_sql)) {
            header("Location: view_patient.php?id=$patient_id");
            exit();
        } else {
            $error_msg = "Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient - <?php echo isset($patient['patient_name']) ? htmlspecialchars($patient['patient_name']) : 'Patient'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .edit-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        .required::after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>

<div class="edit-container">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-0"><i class="fas fa-user-edit"></i> Edit Patient Information</h3>
                <small>Patient ID: <?php echo $patient_id; ?></small>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-light btn-sm me-2">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="view_patient.php?id=<?php echo $patient_id; ?>" class="btn btn-light btn-sm">
                    <i class="fas fa-eye"></i> View Patient
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST">
        <h5 class="mb-3"><i class="fas fa-user"></i> Personal Information</h5>
        
        <!-- PATIENT NAME - CORRECT COLUMN -->
        <div class="row">
            <div class="col-md-12 mb-3">
                <label class="form-label required">Full Name</label>
                <input type="text" class="form-control" name="patient_name" 
                       value="<?php echo isset($patient['patient_name']) ? htmlspecialchars($patient['patient_name']) : ''; ?>" 
                       required placeholder="Enter patient's full name">
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label required">Date of Birth</label>
                <input type="date" class="form-control" name="date_of_birth" 
                       value="<?php echo isset($patient['date_of_birth']) ? $patient['date_of_birth'] : ''; ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Current Age</label>
                <input type="text" class="form-control" 
                       value="<?php echo isset($patient['age']) ? $patient['age'] . ' years' : 'N/A'; ?>" 
                       readonly style="background-color: #e9ecef;">
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label required">Gender</label>
                <select class="form-select" name="gender" required>
                    <option value="Male" <?php echo (isset($patient['gender']) && $patient['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo (isset($patient['gender']) && $patient['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo (isset($patient['gender']) && $patient['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="col-md-4 mb-3">
                <label class="form-label">Blood Type</label>
                <select class="form-select" name="blood_type">
                    <option value="">-- Keep Current --</option>
                    <option value="A+" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                    <option value="A-" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                    <option value="B+" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                    <option value="B-" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                    <option value="AB+" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                    <option value="AB-" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                    <option value="O+" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                    <option value="O-" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'O-') ? 'selected' : ''; ?>>O-</option>
                </select>
                <small class="text-muted">Current: <?php echo isset($patient['blood_type']) && !empty($patient['blood_type']) ? htmlspecialchars($patient['blood_type']) : 'Not set'; ?></small>
            </div>
            
            <div class="col-md-4 mb-3">
                <label class="form-label">Weight (kg)</label>
                <input type="number" step="0.1" class="form-control" name="weight" 
                       value="<?php echo isset($patient['weight']) ? $patient['weight'] : ''; ?>" 
                       placeholder="75.0">
            </div>
        </div>

        <hr class="my-4">
        <h5 class="mb-3"><i class="fas fa-phone"></i> Contact Information</h5>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Phone Number</label>
                <input type="tel" class="form-control" name="phone" 
                       value="<?php echo isset($patient['phone']) ? htmlspecialchars($patient['phone']) : ''; ?>" 
                       placeholder="+1 234 567 8900">
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" name="email" 
                       value="<?php echo isset($patient['email']) ? htmlspecialchars($patient['email']) : ''; ?>" 
                       placeholder="patient@example.com">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Full Address</label>
            <textarea class="form-control" name="address" rows="2" placeholder="Street, City, State, ZIP Code"><?php echo isset($patient['address']) ? htmlspecialchars($patient['address']) : ''; ?></textarea>
        </div>

        <hr class="my-4">
        <h5 class="mb-3"><i class="fas fa-heartbeat"></i> Medical Information</h5>

        <div class="mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="has_allergies" id="has_allergies" 
                       <?php echo (isset($patient['has_allergies']) && $patient['has_allergies']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="has_allergies">
                    <strong>Patient has known allergies</strong>
                </label>
            </div>
            <small class="text-muted">Check this box if the patient has any allergies</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Medical History</label>
            <textarea class="form-control" name="medical_history" rows="5" placeholder="Enter patient's medical history, conditions, surgeries, etc..."><?php echo isset($patient['medical_history']) ? htmlspecialchars($patient['medical_history']) : ''; ?></textarea>
        </div>

        <hr class="my-4">
        <h5 class="mb-3"><i class="fas fa-phone-square"></i> Emergency Contact</h5>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Emergency Contact Name</label>
                <input type="text" class="form-control" name="emergency_contact" 
                       value="<?php echo isset($patient['emergency_contact']) ? htmlspecialchars($patient['emergency_contact']) : ''; ?>" 
                       placeholder="Full Name">
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">Emergency Contact Phone</label>
                <input type="tel" class="form-control" name="emergency_phone" 
                       value="<?php echo isset($patient['emergency_phone']) ? htmlspecialchars($patient['emergency_phone']) : ''; ?>" 
                       placeholder="+1 234 567 8900">
            </div>
        </div>

        <hr class="my-4">

        <div class="d-grid gap-2">
            <button type="submit" name="update_patient" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Update Patient Information
            </button>
            <a href="view_patient.php?id=<?php echo $patient_id; ?>" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
