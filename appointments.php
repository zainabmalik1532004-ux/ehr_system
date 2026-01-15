<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Get doctor info
$stmt = $conn->prepare("SELECT * FROM doctors WHERE id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();

// Get all patients for dropdown
$patients_query = "SELECT id, patient_name FROM patients WHERE doctor_id = ? ORDER BY patient_name";
$stmt = $conn->prepare($patients_query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$patients_result = $stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_appointment'])) {
    $patient_id = $_POST['patient_id'] ?? '';
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $appointment_type = trim($_POST['appointment_type'] ?? '');
    $duration_minutes = $_POST['duration_minutes'] ?? 30;
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($patient_id) || empty($appointment_date) || empty($appointment_time) || empty($appointment_type)) {
        $error = "Patient, date, time, and type are required!";
    } else {
        $status = 'Scheduled';
        
        // Check if appointments table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'appointments'");
        if ($table_check->num_rows == 0) {
            $error = "Appointments table doesn't exist! Please create it in phpMyAdmin first.";
        } else {
            $insert_query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, appointment_type, duration_minutes, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            // FIXED: i=int, s=string - 8 parameters total
            $stmt->bind_param("iisssiss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $appointment_type, $duration_minutes, $status, $notes);
            
            if ($stmt->execute()) {
                $success = "Appointment scheduled successfully!";
            } else {
                $error = "Failed to schedule appointment: " . $stmt->error;
            }
        }
    }
}

// Handle delete appointment
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ? AND doctor_id = ?");
    $stmt->bind_param("ii", $delete_id, $doctor_id);
    if ($stmt->execute()) {
        $success = "Appointment deleted successfully!";
        header("Location: appointments.php");
        exit();
    }
}

// Get all appointments
$appointments = null;
$table_exists = $conn->query("SHOW TABLES LIKE 'appointments'");
if ($table_exists->num_rows > 0) {
    $appointments_query = "SELECT a.*, p.patient_name, p.gender 
                           FROM appointments a 
                           JOIN patients p ON a.patient_id = p.id 
                           WHERE a.doctor_id = ? 
                           ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    $stmt = $conn->prepare($appointments_query);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $appointments = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - EHR System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f5f5f5;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: bold;
            color: white !important;
        }
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
        }
        .content-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-top: 30px;
        }
        .appointment-card {
            border: 1px solid #e0e0e0;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-heart-pulse-fill"></i> EHR System
            </a>
            <div class="navbar-nav me-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a class="nav-link active" href="appointments.php">
                    <i class="bi bi-calendar-check"></i> Appointments
                </a>
                <a class="nav-link" href="add_patient.php">
                    <i class="bi bi-person-plus-fill"></i> Add Patient
                </a>
            </div>
            <div class="navbar-nav">
                <span class="nav-link">
                    <i class="bi bi-person-circle"></i> Dr. <?php echo htmlspecialchars($doctor['full_name']); ?>
                </span>
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Messages -->
        <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show mt-3">
            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-3">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Add Appointment Form -->
        <div class="content-card">
            <h2><i class="bi bi-calendar-plus"></i> Schedule New Appointment</h2>
            <hr>
            
            <form method="POST" action="appointments.php">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Select Patient <span class="text-danger">*</span></label>
                        <select name="patient_id" class="form-select" required>
                            <option value="">Choose a patient...</option>
                            <?php 
                            if ($patients_result->num_rows > 0) {
                                while ($patient = $patients_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $patient['id']; ?>">
                                    <?php echo htmlspecialchars($patient['patient_name']); ?>
                                </option>
                            <?php 
                                endwhile;
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Appointment Type <span class="text-danger">*</span></label>
                        <select name="appointment_type" class="form-select" required>
                            <option value="">Select type...</option>
                            <option value="Checkup">Regular Checkup</option>
                            <option value="Follow-up">Follow-up Visit</option>
                            <option value="Consultation">Consultation</option>
                            <option value="Emergency">Emergency</option>
                            <option value="Surgery">Surgery</option>
                            <option value="Lab Test">Lab Test</option>
                            <option value="Vaccination">Vaccination</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Appointment Date <span class="text-danger">*</span></label>
                        <input type="date" name="appointment_date" class="form-control" 
                               min="<?php echo date('Y-m-d'); ?>" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Appointment Time <span class="text-danger">*</span></label>
                        <input type="time" name="appointment_time" class="form-control" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Duration (minutes)</label>
                        <select name="duration_minutes" class="form-select">
                            <option value="15">15 minutes</option>
                            <option value="30" selected>30 minutes</option>
                            <option value="45">45 minutes</option>
                            <option value="60">1 hour</option>
                            <option value="90">1.5 hours</option>
                            <option value="120">2 hours</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" 
                              placeholder="Any special instructions or notes..."></textarea>
                </div>

                <button type="submit" name="add_appointment" class="btn btn-primary">
                    <i class="bi bi-calendar-check"></i> Schedule Appointment
                </button>
            </form>
        </div>

        <!-- Appointments List -->
        <div class="content-card">
            <h2><i class="bi bi-calendar3"></i> All Appointments</h2>
            <hr>

            <?php if ($appointments && $appointments->num_rows > 0): ?>
                <?php while ($apt = $appointments->fetch_assoc()): ?>
                <div class="appointment-card">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <h5 class="mb-1">
                                <i class="bi bi-person-fill"></i> 
                                <?php echo htmlspecialchars($apt['patient_name']); ?>
                            </h5>
                            <span class="badge bg-<?php echo $apt['gender'] == 'Male' ? 'info' : 'danger'; ?>">
                                <?php echo $apt['gender']; ?>
                            </span>
                        </div>

                        <div class="col-md-3">
                            <strong><i class="bi bi-calendar-event"></i> Date:</strong><br>
                            <?php echo date('d M Y', strtotime($apt['appointment_date'])); ?>
                        </div>

                        <div class="col-md-2">
                            <strong><i class="bi bi-clock"></i> Time:</strong><br>
                            <?php echo date('h:i A', strtotime($apt['appointment_time'])); ?>
                        </div>

                        <div class="col-md-2">
                            <strong><i class="bi bi-tag"></i> Type:</strong><br>
                            <?php echo htmlspecialchars($apt['appointment_type']); ?>
                        </div>

                        <div class="col-md-2 text-end">
                            <span class="badge bg-primary mb-2"><?php echo $apt['status']; ?></span><br>
                            <a href="appointments.php?delete_id=<?php echo $apt['appointment_id']; ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Delete this appointment?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </div>

                    <?php if (!empty($apt['notes'])): ?>
                    <div class="row mt-2">
                        <div class="col-12">
                            <strong><i class="bi bi-journal-text"></i> Notes:</strong> 
                            <?php echo htmlspecialchars($apt['notes']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No appointments scheduled yet.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
