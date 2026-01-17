<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['doctor_id'])) {
    header("Location: login.php");
    exit();
}

// Get total patients count
$patient_count_query = "SELECT COUNT(*) as total FROM patients WHERE doctor_id = {$_SESSION['doctor_id']}";
$patient_count_result = mysqli_query($conn, $patient_count_query);
$patient_count = mysqli_fetch_assoc($patient_count_result)['total'];

// Get today's appointments count
$today = date('Y-m-d');
$appointment_query = "SELECT COUNT(*) as total FROM patient_meetings WHERE doctor_id = {$_SESSION['doctor_id']} AND meeting_date = '$today'";
$appointment_result = mysqli_query($conn, $appointment_query);
$appointment_count = mysqli_fetch_assoc($appointment_result)['total'];

// Get all patients
$patients_query = "SELECT * FROM patients WHERE doctor_id = {$_SESSION['doctor_id']} ORDER BY id DESC";
$patients_result = mysqli_query($conn, $patients_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EHR System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.3rem;
        }
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            margin: 0 5px;
            transition: all 0.3s;
        }
        .nav-link:hover {
            color: white !important;
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
        }
        .nav-link.active {
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            font-weight: 600;
        }
        .welcome-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .stat-card h2 {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .patient-table {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        .badge {
            padding: 5px 10px;
        }
        .btn-action {
            margin: 0 2px;
        }
        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-hospital"></i> EHR System
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="appointments.php">
                        <i class="fas fa-calendar-alt"></i> Appointments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="add_patient.php">
                        <i class="fas fa-user-plus"></i> Add Patient
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="about.php">
                        <i class="fas fa-info-circle"></i> About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="sus_survey.php">
                        <i class="fas fa-clipboard-list"></i> Survey
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> Dr. <?php echo isset($_SESSION['doctor_name']) ? htmlspecialchars($_SESSION['doctor_name']) : 'John Smith'; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="container-fluid p-4">
    <!-- Welcome Section -->
    <div class="welcome-card">
        <h2><i class="fas fa-user-md"></i> Welcome, Dr. <?php echo isset($_SESSION['doctor_name']) ? htmlspecialchars($_SESSION['doctor_name']) : 'John Smith'; ?>!</h2>
        <p class="text-muted mb-0">Manage your patients and their electronic health records</p>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div class="stat-icon text-primary">
                    <i class="fas fa-users"></i>
                </div>
                <h2><?php echo $patient_count; ?></h2>
                <p class="text-muted mb-0">Total Patients</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div class="stat-icon text-success">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h2><?php echo $appointment_count; ?></h2>
                <p class="text-muted mb-0">Appointments Today</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div class="stat-icon text-warning">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <h2><?php echo date('d M Y'); ?></h2>
                <p class="text-muted mb-0">Today's Date</p>
            </div>
        </div>
    </div>

    <!-- Patient List -->
    <div class="patient-table">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><i class="fas fa-list"></i> Patient List</h4>
            <a href="add_patient.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Patient
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
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
                    <?php if ($patients_result && mysqli_num_rows($patients_result) > 0): ?>
                        <?php while ($patient = mysqli_fetch_assoc($patients_result)): ?>
                            <tr>
                                <td><?php echo $patient['id']; ?></td>
                                <td>
                                    <?php if (isset($patient['profile_picture']) && $patient['profile_picture'] && file_exists($patient['profile_picture'])): ?>
                                        <img src="<?php echo htmlspecialchars($patient['profile_picture']); ?>" alt="Patient" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="avatar">
                                            <?php echo strtoupper(substr($patient['patient_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo isset($patient['patient_name']) ? htmlspecialchars($patient['patient_name']) : 'N/A'; ?></strong></td>
                                <td>
                                    <?php if (isset($patient['gender'])): ?>
                                        <span class="badge bg-<?php echo $patient['gender'] == 'Male' ? 'info' : ($patient['gender'] == 'Female' ? 'danger' : 'secondary'); ?>">
                                            <?php echo htmlspecialchars($patient['gender']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo isset($patient['date_of_birth']) ? date('d M Y', strtotime($patient['date_of_birth'])) : 'N/A'; ?></td>
                                <td><?php echo isset($patient['age']) ? $patient['age'] . ' years' : 'N/A'; ?></td>
                                <td>
                                    <?php if (isset($patient['weight']) && $patient['weight']): ?>
                                        <span class="badge bg-success"><?php echo $patient['weight']; ?> kg</span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="view_patient.php?id=<?php echo $patient['id']; ?>" class="btn btn-primary btn-sm btn-action" title="View Details">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="edit_patient.php?id=<?php echo $patient['id']; ?>" class="btn btn-warning btn-sm btn-action" title="Edit Patient">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="delete_patient.php?id=<?php echo $patient['id']; ?>" class="btn btn-danger btn-sm btn-action" 
                                       onclick="return confirm('Are you sure you want to delete this patient?')" title="Delete Patient">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                No patients found. <a href="add_patient.php">Add your first patient</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
