<?php
session_start();

if (!isset($_SESSION['doctor_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'db_connect.php';

$doctor_id = $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_name = $_POST['patient_name'];
    $age = $_POST['age'];
    $weight = $_POST['weight'];
    $gender = $_POST['gender'];
    $has_allergies = isset($_POST['has_allergies']) ? 1 : 0;
    $date_of_birth = $_POST['date_of_birth'];
    $medical_history = $_POST['medical_history'];
    
    // Handle image upload
    $patient_image = NULL;
    if (isset($_FILES['patient_image']) && $_FILES['patient_image']['error'] == 0) {
        $upload_dir = 'uploads/';
        
        // Create uploads directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Check if file is an actual image
        $check = getimagesize($_FILES['patient_image']['tmp_name']);
        if ($check !== false) {
            $file_extension = strtolower(pathinfo($_FILES['patient_image']['name'], PATHINFO_EXTENSION));
            $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
            
            if (in_array($file_extension, $allowed_types)) {
                $new_filename = 'patient_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['patient_image']['tmp_name'], $upload_path)) {
                    $patient_image = $upload_path;
                } else {
                    $upload_error = "Failed to upload image.";
                }
            } else {
                $upload_error = "Only JPG, JPEG, PNG & GIF files are allowed.";
            }
        } else {
            $upload_error = "File is not a valid image.";
        }
    }
    
    // Insert into database
    try {
        $stmt = $pdo->prepare("INSERT INTO patients (doctor_id, patient_name, age, weight, gender, has_allergies, date_of_birth, medical_history, patient_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$doctor_id, $patient_name, $age, $weight, $gender, $has_allergies, $date_of_birth, $medical_history, $patient_image]);
        
        header('Location: dashboard.php');
        exit();
    } catch(PDOException $e) {
        $error_message = "Error adding patient: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Patient</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">EHR System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="add_patient.php">Add Patient</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">Welcome, Dr. <?php echo htmlspecialchars($doctor_name); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4>Add New Patient</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($upload_error)): ?>
                            <div class="alert alert-warning"><?php echo $upload_error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <!-- Input 1: Patient Name -->
                            <div class="mb-3">
                                <label class="form-label">Patient Name *</label>
                                <input type="text" name="patient_name" class="form-control" required>
                            </div>
                            
                            <!-- Input 2: Age -->
                            <div class="mb-3">
                                <label class="form-label">Age *</label>
                                <input type="number" name="age" class="form-control" min="0" max="150" required>
                            </div>
                            
                            <!-- Input 3: Weight -->
                            <div class="mb-3">
                                <label class="form-label">Weight (kg) *</label>
                                <input type="number" step="0.01" name="weight" class="form-control" required>
                            </div>
                            
                            <!-- Radio Button: Gender -->
                            <div class="mb-3">
                                <label class="form-label">Gender *</label><br>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="male" value="Male" required>
                                    <label class="form-check-label" for="male">Male</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="female" value="Female" required>
                                    <label class="form-check-label" for="female">Female</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="other" value="Other" required>
                                    <label class="form-check-label" for="other">Other</label>
                                </div>
                            </div>
                            
                            <!-- Checkbox: Has Allergies -->
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="has_allergies" id="has_allergies">
                                    <label class="form-check-label" for="has_allergies">
                                        Patient has known allergies
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Date Input with Datepicker -->
                            <div class="mb-3">
                                <label class="form-label">Date of Birth *</label>
                                <input type="text" name="date_of_birth" id="datepicker" class="form-control" placeholder="Select date" required>
                            </div>
                            
                            <!-- Textarea: Medical History -->
                            <div class="mb-3">
                                <label class="form-label">Medical History *</label>
                                <textarea name="medical_history" class="form-control" rows="5" placeholder="Enter patient's medical history, previous conditions, medications, etc." required></textarea>
                            </div>
                            
                            <!-- Image Upload -->
                            <div class="mb-3">
                                <label class="form-label">Patient Image</label>
                                <input type="file" name="patient_image" class="form-control" accept="image/*">
                                <small class="text-muted">Upload patient photo or medical scan (Optional). Allowed: JPG, PNG, GIF</small>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">Add Patient</button>
                                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize datepicker
        flatpickr("#datepicker", {
            dateFormat: "Y-m-d",
            maxDate: "today"
        });
    </script>
</body>
</html>
