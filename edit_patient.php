<?php
session_start();
require_once 'db_connect.php';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    header("Location: login.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];
$error = '';
$success = '';

// Check if patient ID is provided
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$patient_id = $_GET['id'];

// Get patient information
$stmt = $conn->prepare("SELECT * FROM patients WHERE id = ? AND doctor_id = ?");
$stmt->bind_param("ii", $patient_id, $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: dashboard.php");
    exit();
}

$patient = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_name = trim($_POST['patient_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $weight = trim($_POST['weight'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $blood_type = $_POST['blood_type'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $emergency_phone = trim($_POST['emergency_phone'] ?? '');
    $has_allergies = isset($_POST['has_allergies']) ? 1 : 0;
    $medical_history = trim($_POST['medical_history'] ?? '');
    
    if (empty($patient_name) || empty($gender) || empty($date_of_birth)) {
        $error = "Name, gender, and date of birth are required!";
    } else {
        // Update patient
        $update_query = "UPDATE patients SET 
            patient_name = ?, 
            gender = ?, 
            date_of_birth = ?, 
            weight = ?, 
            age = ?, 
            blood_type = ?, 
            phone = ?, 
            email = ?, 
            address = ?, 
            emergency_contact = ?, 
            emergency_phone = ?, 
            has_allergies = ?, 
            medical_history = ?
            WHERE id = ? AND doctor_id = ?";
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssssissssssisii", 
            $patient_name, $gender, $date_of_birth, $weight, $age, $blood_type,
            $phone, $email, $address, $emergency_contact, $emergency_phone,
            $has_allergies, $medical_history, $patient_id, $doctor_id
        );
        
        if ($stmt->execute()) {
            $success = "Patient updated successfully!";
            // Refresh patient data
            $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            $patient = $stmt->get_result()->fetch_assoc();
        } else {
            $error = "Failed to update patient!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient - EHR System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f5f5f5;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .navbar-brand {
            font-weight: bold;
            color: white !important;
        }
        .edit-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-heart-pulse-fill"></i> EHR System
            </a>
            <a href="dashboard.php" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="edit-container">
            <h2><i class="bi bi-pencil-square"></i> Edit Patient: <?php echo htmlspecialchars($patient['patient_name']); ?></h2>
            <hr>

            <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Patient Name <span class="text-danger">*</span></label>
                        <input type="text" name="patient_name" class="form-control" 
                               value="<?php echo htmlspecialchars($patient['patient_name']); ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required>
                            <option value="Male" <?php echo $patient['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $patient['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" class="form-control" 
                               value="<?php echo $patient['date_of_birth']; ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Weight (kg)</label>
                        <input type="number" step="0.01" name="weight" class="form-control" 
                               value="<?php echo $patient['weight']; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Age</label>
                        <input type="number" name="age" class="form-control" 
                               value="<?php echo $patient['age']; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Blood Type</label>
                        <select name="blood_type" class="form-select">
                            <option value="">Select...</option>
                            <option value="A+" <?php echo $patient['blood_type'] == 'A+' ? 'selected' : ''; ?>>A+</option>
                            <option value="A-" <?php echo $patient['blood_type'] == 'A-' ? 'selected' : ''; ?>>A-</option>
                            <option value="B+" <?php echo $patient['blood_type'] == 'B+' ? 'selected' : ''; ?>>B+</option>
                            <option value="B-" <?php echo $patient['blood_type'] == 'B-' ? 'selected' : ''; ?>>B-</option>
                            <option value="O+" <?php echo $patient['blood_type'] == 'O+' ? 'selected' : ''; ?>>O+</option>
                            <option value="O-" <?php echo $patient['blood_type'] == 'O-' ? 'selected' : ''; ?>>O-</option>
                            <option value="AB+" <?php echo $patient['blood_type'] == 'AB+' ? 'selected' : ''; ?>>AB+</option>
                            <option value="AB-" <?php echo $patient['blood_type'] == 'AB-' ? 'selected' : ''; ?>>AB-</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($patient['phone']); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($patient['email']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control" 
                               value="<?php echo htmlspecialchars($patient['address']); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Emergency Contact</label>
                        <input type="text" name="emergency_contact" class="form-control" 
                               value="<?php echo htmlspecialchars($patient['emergency_contact']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Emergency Phone</label>
                        <input type="tel" name="emergency_phone" class="form-control" 
                               value="<?php echo htmlspecialchars($patient['emergency_phone']); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="has_allergies" class="form-check-input" id="allergies"
                               <?php echo $patient['has_allergies'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="allergies">
                            Patient has allergies
                        </label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Medical History</label>
                    <textarea name="medical_history" class="form-control" rows="4"><?php echo htmlspecialchars($patient['medical_history']); ?></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update Patient
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
