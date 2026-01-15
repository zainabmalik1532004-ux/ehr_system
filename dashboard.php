<?php
session_start();
require_once 'db_connect.php';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    // Not logged in - redirect to login page
    header("Location: login.php");
    exit();
}

// Get logged-in doctor's ID from session
$doctor_id = $_SESSION['doctor_id'];

// Get doctor information from database
$stmt = $conn->prepare("SELECT * FROM doctors WHERE id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();

// If doctor not found, logout
if (!$doctor) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Get all patients for this doctor
$patients_query = "SELECT * FROM patients WHERE doctor_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($patients_query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$patients_result = $stmt->get_result();
$total_patients = $patients_result->num_rows;

// Get appointments today
$today = date('Y-m-d');
$appointments_query = "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND appointment_date = ?";
$stmt = $conn->prepare($appointments_query);
$stmt->bind_param("is", $doctor_id, $today);
$stmt->execute();
$appointments_result = $stmt->get_result();
$appointments_today = $appointments_result->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - EHR System</title>
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
            font-size: 1.5rem;
            color: white !important;
        }
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
        }
        .nav-link:hover {
            color: white !important;
        }
        .welcome-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            text-align: center;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .patients-table {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .patient-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #6c757d;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-heart-pulse-fill"></i> EHR System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">
                            <i class="bi bi-calendar-check"></i> Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_patient.php">
                            <i class="bi bi-person-plus-fill"></i> Add Patient
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">
                            <i class="bi bi-info-circle-fill"></i> About
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> Dr. <?php echo htmlspecialchars($doctor['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Messages -->
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Welcome Header -->
        <div class="welcome-card">
            <h2><i class="bi bi-person-circle"></i> Welcome, Dr. <?php echo htmlspecialchars($doctor['full_name']); ?>!</h2>
            <p class="text-muted mb-0">Manage your patients and their electronic health records</p>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon text-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h3><?php echo $total_patients; ?></h3>
                    <p class="text-muted mb-0">Total Patients</p>
                </div>
            </div>
            <div class="col-md-4">
                <a href="appointments.php" style="text-decoration: none; color: inherit;">
                    <div class="stat-card">
                        <div class="stat-icon text-success">
                            <i class="bi bi-calendar-check-fill"></i>
                        </div>
                        <h3><?php echo $appointments_today; ?></h3>
                        <p class="text-muted mb-0">Appointments Today</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon text-warning">
                        <i class="bi bi-file-medical-fill"></i>
                    </div>
                    <h3><?php echo date('d M Y'); ?></h3>
                    <p class="text-muted mb-0">Today's Date</p>
                </div>
            </div>
        </div>

        <!-- Patients Table -->
        <div class="patients-table">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="bi bi-list-ul"></i> Patient List</h4>
                <a href="add_patient.php" class="btn btn-primary">
                    <i class="bi bi-person-plus-fill"></i> Add New Patient
                </a>
            </div>

            <?php if ($total_patients > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Date of Birth</th>
                            <th>Age</th>
                            <th>Weight</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($patient = $patients_result->fetch_assoc()): 
                            // Calculate age
                            $dob = new DateTime($patient['date_of_birth']);
                            $now = new DateTime();
                            $age = $now->diff($dob);
                            if ($age->y > 0) {
                                $age_string = $age->y . ' years';
                            } else {
                                $age_string = $age->m . ' months ' . $age->d . ' days';
                            }
                        ?>
                        <tr>
                            <td><?php echo $patient['id']; ?></td>
                            <td>
                                <?php if (!empty($patient['patient_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($patient['patient_image']); ?>" 
                                         alt="Patient" class="patient-photo">
                                <?php else: ?>
                                    <div class="patient-photo">
                                        <?php echo strtoupper(substr($patient['patient_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($patient['patient_name']); ?></strong></td>
                            <td>
                                <span class="badge bg-<?php echo $patient['gender'] == 'Male' ? 'info' : 'danger'; ?>">
                                    <?php echo htmlspecialchars($patient['gender']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($patient['date_of_birth'])); ?></td>
                            <td><?php echo $age_string; ?></td>
                            <td>
                                <?php if (!empty($patient['weight'])): ?>
                                    <span class="badge bg-success"><?php echo $patient['weight']; ?> kg</span>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view_patient.php?id=<?php echo $patient['id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye-fill"></i> View
                                </a>
                                <a href="edit_patient.php?id=<?php echo $patient['id']; ?>" 
                                   class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil-fill"></i> Edit
                                </a>
                                <a href="delete_patient.php?id=<?php echo $patient['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this patient?');">
                                    <i class="bi bi-trash-fill"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle-fill"></i> No patients found. Click "Add New Patient" to get started!
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
