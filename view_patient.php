<?php
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

// ============ HANDLE ALL FORM SUBMISSIONS ============

// Upload Document
if (isset($_POST['upload_document'])) {
    $document_name = mysqli_real_escape_string($conn, $_POST['document_name']);
    $document_type = mysqli_real_escape_string($conn, $_POST['document_type']);
    
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
        $file_extension = pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);
        $new_filename = 'doc_' . $patient_id . '_' . time() . '.' . $file_extension;
        $upload_path = 'uploads/documents/' . $new_filename;
        
        if (move_uploaded_file($_FILES['document_file']['tmp_name'], $upload_path)) {
            $sql = "INSERT INTO patient_documents (patient_id, doctor_id, document_name, document_type, file_path) 
                    VALUES ($patient_id, {$_SESSION['doctor_id']}, '$document_name', '$document_type', '$upload_path')";
            mysqli_query($conn, $sql);
            $success_msg = "Document uploaded successfully!";
        }
    }
}

// Delete Document
if (isset($_GET['delete_doc'])) {
    $doc_id = intval($_GET['delete_doc']);
    $sql = "SELECT file_path FROM patient_documents WHERE id = $doc_id AND doctor_id = {$_SESSION['doctor_id']}";
    $result = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        if (file_exists($row['file_path'])) {
            unlink($row['file_path']);
        }
        mysqli_query($conn, "DELETE FROM patient_documents WHERE id = $doc_id");
        header("Location: view_patient.php?id=$patient_id");
        exit();
    }
}

// Add Form/Sheet
if (isset($_POST['add_form'])) {
    $form_type = mysqli_real_escape_string($conn, $_POST['form_type']);
    $form_data = mysqli_real_escape_string($conn, $_POST['form_data']);
    
    $sql = "INSERT INTO patient_forms (patient_id, doctor_id, form_type, form_data) 
            VALUES ($patient_id, {$_SESSION['doctor_id']}, '$form_type', '$form_data')";
    mysqli_query($conn, $sql);
    $success_msg = "Form added successfully!";
}

// Save Sheet Data
if (isset($_POST['save_sheet'])) {
    $sheet_type = mysqli_real_escape_string($conn, $_POST['sheet_type']);
    $sheet_data = mysqli_real_escape_string($conn, $_POST['sheet_data']);
    
    // Check if sheet exists
    $check = mysqli_query($conn, "SELECT id FROM patient_forms WHERE patient_id = $patient_id AND form_type = '$sheet_type'");
    if (mysqli_num_rows($check) > 0) {
        // Update existing
        mysqli_query($conn, "UPDATE patient_forms SET form_data = '$sheet_data' WHERE patient_id = $patient_id AND form_type = '$sheet_type'");
        $success_msg = "Sheet data updated successfully!";
    } else {
        // Insert new
        mysqli_query($conn, "INSERT INTO patient_forms (patient_id, doctor_id, form_type, form_data) VALUES ($patient_id, {$_SESSION['doctor_id']}, '$sheet_type', '$sheet_data')");
        $success_msg = "Sheet data saved successfully!";
    }
    header("Location: view_patient.php?id=$patient_id");
    exit();
}

// Save System Detail
if (isset($_POST['save_system_detail'])) {
    $detail_name = mysqli_real_escape_string($conn, $_POST['detail_name']);
    $detail_content = mysqli_real_escape_string($conn, $_POST['detail_content']);
    
    $form_type = 'SystemDetail_' . $detail_name;
    
    // Check if exists
    $check = mysqli_query($conn, "SELECT id FROM patient_forms WHERE patient_id = $patient_id AND form_type = '$form_type'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE patient_forms SET form_data = '$detail_content' WHERE patient_id = $patient_id AND form_type = '$form_type'");
    } else {
        mysqli_query($conn, "INSERT INTO patient_forms (patient_id, doctor_id, form_type, form_data) VALUES ($patient_id, {$_SESSION['doctor_id']}, '$form_type', '$detail_content')");
    }
    
    echo json_encode(['success' => true]);
    exit();
}

// Delete Form
if (isset($_GET['delete_form'])) {
    $form_id = intval($_GET['delete_form']);
    mysqli_query($conn, "DELETE FROM patient_forms WHERE id = $form_id AND doctor_id = {$_SESSION['doctor_id']}");
    header("Location: view_patient.php?id=$patient_id");
    exit();
}

// Add Meeting
if (isset($_POST['add_meeting'])) {
    $meeting_date = mysqli_real_escape_string($conn, $_POST['meeting_date']);
    $meeting_time = mysqli_real_escape_string($conn, $_POST['meeting_time']);
    $duration = mysqli_real_escape_string($conn, $_POST['duration']);
    $notes = mysqli_real_escape_string($conn, $_POST['meeting_notes']);
    
    $sql = "INSERT INTO patient_meetings (patient_id, doctor_id, meeting_date, meeting_time, duration, notes) 
            VALUES ($patient_id, {$_SESSION['doctor_id']}, '$meeting_date', '$meeting_time', '$duration', '$notes')";
    mysqli_query($conn, $sql);
    $success_msg = "Meeting added successfully!";
}

// Delete Meeting
if (isset($_GET['delete_meeting'])) {
    $meeting_id = intval($_GET['delete_meeting']);
    mysqli_query($conn, "DELETE FROM patient_meetings WHERE id = $meeting_id AND doctor_id = {$_SESSION['doctor_id']}");
    header("Location: view_patient.php?id=$patient_id");
    exit();
}

// Add Diagnosis
if (isset($_POST['add_diagnosis'])) {
    $diagnosis_category = mysqli_real_escape_string($conn, $_POST['diagnosis_category']);
    $diagnosis_details = mysqli_real_escape_string($conn, $_POST['diagnosis_details']);
    $diagnosis_date = mysqli_real_escape_string($conn, $_POST['diagnosis_date']);
    
    $sql = "INSERT INTO patient_diagnosis (patient_id, doctor_id, diagnosis_category, diagnosis_details, diagnosis_date) 
            VALUES ($patient_id, {$_SESSION['doctor_id']}, '$diagnosis_category', '$diagnosis_details', '$diagnosis_date')";
    mysqli_query($conn, $sql);
    $success_msg = "Diagnosis added successfully!";
}

// Delete Diagnosis
if (isset($_GET['delete_diagnosis'])) {
    $diag_id = intval($_GET['delete_diagnosis']);
    mysqli_query($conn, "DELETE FROM patient_diagnosis WHERE id = $diag_id AND doctor_id = {$_SESSION['doctor_id']}");
    header("Location: view_patient.php?id=$patient_id");
    exit();
}

// Save Notes
if (isset($_POST['save_notes'])) {
    $note_text = mysqli_real_escape_string($conn, $_POST['note_text']);
    
    $sql = "INSERT INTO patient_notes (patient_id, doctor_id, note_text) 
            VALUES ($patient_id, {$_SESSION['doctor_id']}, '$note_text')";
    mysqli_query($conn, $sql);
    $success_msg = "Notes saved successfully!";
}

// Save Drawing
if (isset($_POST['save_drawing'])) {
    $drawing_data = mysqli_real_escape_string($conn, $_POST['drawing_data']);
    $diagram_type = mysqli_real_escape_string($conn, $_POST['diagram_type']);
    
    // Save as a special form type
    $check = mysqli_query($conn, "SELECT id FROM patient_forms WHERE patient_id = $patient_id AND form_type = 'Drawing_$diagram_type'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE patient_forms SET form_data = '$drawing_data' WHERE patient_id = $patient_id AND form_type = 'Drawing_$diagram_type'");
    } else {
        mysqli_query($conn, "INSERT INTO patient_forms (patient_id, doctor_id, form_type, form_data) VALUES ($patient_id, {$_SESSION['doctor_id']}, 'Drawing_$diagram_type', '$drawing_data')");
    }
    echo json_encode(['success' => true]);
    exit();
}

// Update Profile Picture
if (isset($_POST['update_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (in_array($_FILES['profile_picture']['type'], $allowed_types)) {
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $new_filename = 'patient_' . $patient_id . '_' . time() . '.' . $file_extension;
            $upload_path = 'uploads/patients/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                $old_pic = mysqli_query($conn, "SELECT profile_picture FROM patients WHERE id = $patient_id");
                if ($old_pic && $old = mysqli_fetch_assoc($old_pic)) {
                    if (isset($old['profile_picture']) && $old['profile_picture'] && file_exists($old['profile_picture'])) {
                        unlink($old['profile_picture']);
                    }
                }
                
                mysqli_query($conn, "UPDATE patients SET profile_picture = '$upload_path' WHERE id = $patient_id");
                $success_msg = "Profile picture updated!";
            }
        }
    }
}

// ============ FETCH ALL DATA ============

// Patient data
$sql = "SELECT * FROM patients WHERE id = $patient_id AND doctor_id = {$_SESSION['doctor_id']}";
$result = mysqli_query($conn, $sql);
if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: dashboard.php");
    exit();
}
$patient = mysqli_fetch_assoc($result);

// Get patient name parts safely
$patient_name = isset($patient['name']) ? $patient['name'] : 'Unknown Patient';
$name_parts = explode(' ', trim($patient_name));
$first_name = isset($name_parts[0]) ? $name_parts[0] : '';
$last_name = count($name_parts) > 1 ? $name_parts[count($name_parts)-1] : $first_name;

// Documents
$docs = mysqli_query($conn, "SELECT * FROM patient_documents WHERE patient_id = $patient_id ORDER BY id DESC");

// Forms (excluding sheets and drawings)
$forms = mysqli_query($conn, "SELECT * FROM patient_forms WHERE patient_id = $patient_id AND form_type NOT LIKE 'Drawing_%' AND form_type NOT LIKE 'Sheet_%' AND form_type NOT LIKE 'SystemDetail_%' ORDER BY created_date DESC");

// Get all sheet data
$sheet_data = [];
$sheet_query = mysqli_query($conn, "SELECT form_type, form_data FROM patient_forms WHERE patient_id = $patient_id AND form_type LIKE 'Sheet_%'");
while ($row = mysqli_fetch_assoc($sheet_query)) {
    $sheet_data[$row['form_type']] = $row['form_data'];
}

// Get system details
$system_details = [];
$detail_query = mysqli_query($conn, "SELECT form_type, form_data FROM patient_forms WHERE patient_id = $patient_id AND form_type LIKE 'SystemDetail_%'");
while ($row = mysqli_fetch_assoc($detail_query)) {
    $detail_name = str_replace('SystemDetail_', '', $row['form_type']);
    $system_details[$detail_name] = $row['form_data'];
}

// Meetings
$meetings = mysqli_query($conn, "SELECT * FROM patient_meetings WHERE patient_id = $patient_id ORDER BY meeting_date DESC");

// Diagnosis
$diagnosis = mysqli_query($conn, "SELECT * FROM patient_diagnosis WHERE patient_id = $patient_id ORDER BY diagnosis_date DESC");

// Notes
$notes = mysqli_query($conn, "SELECT * FROM patient_notes WHERE patient_id = $patient_id ORDER BY note_date DESC");
$latest_note = ($notes && mysqli_num_rows($notes) > 0) ? mysqli_fetch_assoc($notes) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handy Patients Enterprise Edition - <?php echo htmlspecialchars($patient_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            background: #e8eaf6; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 13px;
        }
        .top-bar {
            background: linear-gradient(180deg, #5c6bc0 0%, #3949ab 100%);
            color: white;
            padding: 8px 15px;
            border-bottom: 2px solid #303f9f;
        }
        .main-container {
            display: flex;
            height: calc(100vh - 60px);
        }
        .left-sidebar {
            width: 280px;
            background: #f5f5f5;
            border-right: 1px solid #ccc;
            overflow-y: auto;
            padding: 15px;
        }
        .center-panel {
            flex: 1;
            background: white;
            overflow-y: auto;
            padding: 15px;
        }
        .right-panel {
            width: 400px;
            background: #fff3e0;
            border-left: 1px solid #ccc;
            overflow-y: auto;
            padding: 15px;
        }
        .patient-photo {
            width: 100px;
            height: 130px;
            background: #9575cd;
            border: 2px solid #7e57c2;
            object-fit: cover;
            cursor: pointer;
        }
        .info-box {
            background: #fff9c4;
            border: 1px solid #f9a825;
            padding: 8px;
            margin-bottom: 10px;
            border-radius: 3px;
            font-size: 12px;
        }
        .section-title {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 8px 12px;
            margin: 15px 0 10px 0;
            font-weight: bold;
            color: #1565c0;
        }
        .list-box {
            border: 1px solid #ddd;
            background: #fafafa;
            max-height: 150px;
            overflow-y: auto;
            padding: 8px;
            margin-bottom: 10px;
        }
        .list-item {
            padding: 5px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .list-item:hover {
            background: #e3f2fd;
        }
        .list-item.selected {
            background: #2196f3;
            color: white;
            font-weight: bold;
        }
        .sheet-badge {
            background: #4caf50;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
        }
        .diagram-area {
            width: 100%;
            height: 400px;
            border: 2px solid #bdbdbd;
            background: white;
            position: relative;
            overflow: hidden;
        }
        #drawingCanvas {
            position: absolute;
            top: 0;
            left: 0;
            cursor: crosshair;
            z-index: 10;
        }
        .drawing-tools {
            margin-bottom: 10px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .tool-btn {
            padding: 5px 10px;
            margin: 2px;
            border: 1px solid #ccc;
            background: white;
            cursor: pointer;
            border-radius: 3px;
        }
        .tool-btn.active {
            background: #2196f3;
            color: white;
        }
        .color-picker {
            width: 40px;
            height: 30px;
            border: 1px solid #ccc;
            cursor: pointer;
        }
        .btn-sm-custom {
            padding: 2px 8px;
            font-size: 11px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        table.data-table th {
            background: #e8eaf6;
            border: 1px solid #ccc;
            padding: 5px;
            text-align: left;
        }
        table.data-table td {
            border: 1px solid #ddd;
            padding: 5px;
        }
        .empty-row {
            text-align: center;
            color: #999;
            font-style: italic;
        }
        .diagram-selector {
            display: flex;
            gap: 5px;
            margin-bottom: 10px;
        }
        .diagram-btn {
            flex: 1;
            padding: 5px;
            border: 1px solid #ccc;
            background: white;
            cursor: pointer;
            font-size: 11px;
        }
        .diagram-btn.active {
            background: #ff9800;
            color: white;
            font-weight: bold;
        }
        .system-detail-section {
            margin-bottom: 10px;
        }
        .system-detail-header {
            background: #e3f2fd;
            padding: 8px;
            cursor: pointer;
            border-radius: 3px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .system-detail-content {
            padding: 10px;
            background: white;
            border: 1px solid #ddd;
            margin-top: 5px;
            border-radius: 3px;
        }
        .editable-area {
            width: 100%;
            border: 1px solid #ddd;
            padding: 5px;
            min-height: 60px;
            font-size: 12px;
        }
    </style>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <i class="fas fa-hospital"></i>
            <strong>Handy Patients Enterprise Edition</strong>
        </div>
        <div>
            <a href="dashboard.php" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php if (isset($success_msg)): ?>
    <div class="alert alert-success alert-dismissible fade show m-2" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="main-container">
    <!-- LEFT SIDEBAR -->
    <div class="left-sidebar">
        <!-- Patient Photo and Basic Info -->
        <div class="text-center mb-3">
            <?php if (isset($patient['profile_picture']) && $patient['profile_picture'] && file_exists($patient['profile_picture'])): ?>
                <img src="<?php echo htmlspecialchars($patient['profile_picture']); ?>" class="patient-photo" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal" alt="Patient">
            <?php else: ?>
                <div class="patient-photo d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal">
                    <i class="fas fa-user fa-3x text-white"></i>
                </div>
            <?php endif; ?>
            <small class="d-block mt-1 text-muted" style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal">
                <i class="fas fa-camera"></i> Click to change photo
            </small>
        </div>

        <div class="info-box">
            <div><strong>Last:</strong> <?php echo htmlspecialchars($last_name); ?></div>
            <div><strong>First:</strong> <?php echo htmlspecialchars($first_name); ?></div>
            <div><strong>Birth:</strong> <?php echo isset($patient['date_of_birth']) ? date('d M Y', strtotime($patient['date_of_birth'])) : 'N/A'; ?></div>
            <div><strong>Age:</strong> <?php echo isset($patient['age']) ? $patient['age'] : '0'; ?> years</div>
            <div><strong>Gender:</strong> <?php echo isset($patient['gender']) ? htmlspecialchars($patient['gender']) : 'N/A'; ?></div>
            <div><strong>Patient ID:</strong> <?php echo $patient_id; ?></div>
        </div>

        <div class="info-box">
            <div><strong>Phone:</strong> <?php echo isset($patient['phone']) && $patient['phone'] ? htmlspecialchars($patient['phone']) : 'N/A'; ?></div>
            <div><strong>Email:</strong> <?php echo isset($patient['email']) && $patient['email'] ? htmlspecialchars($patient['email']) : 'N/A'; ?></div>
            <div><strong>Address:</strong> <?php echo isset($patient['address']) && $patient['address'] ? htmlspecialchars($patient['address']) : 'N/A'; ?></div>
        </div>

        <div class="info-box">
            <div><strong>Blood Type:</strong> <span class="badge bg-danger"><?php echo isset($patient['blood_type']) && $patient['blood_type'] ? htmlspecialchars($patient['blood_type']) : 'N/A'; ?></span></div>
            <div><strong>Weight:</strong> <?php echo isset($patient['weight']) && $patient['weight'] ? htmlspecialchars($patient['weight']).' kg' : 'N/A'; ?></div>
            <div><strong>Allergies:</strong> 
                <?php if (isset($patient['has_allergies']) && $patient['has_allergies']): ?>
                    <span class="badge bg-warning text-dark">Yes</span>
                <?php else: ?>
                    <span class="badge bg-success">No</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="info-box">
            <div><strong>Emergency Contact:</strong></div>
            <div><?php echo isset($patient['emergency_contact']) && $patient['emergency_contact'] ? htmlspecialchars($patient['emergency_contact']) : 'N/A'; ?></div>
            <div><?php echo isset($patient['emergency_phone']) && $patient['emergency_phone'] ? htmlspecialchars($patient['emergency_phone']) : ''; ?></div>
        </div>

        <div class="section-title">Forms</div>
        <div class="list-box">
            <?php 
            if ($forms && mysqli_num_rows($forms) > 0):
                mysqli_data_seek($forms, 0);
                while ($form = mysqli_fetch_assoc($forms)): 
            ?>
                <div class="list-item">
                    <small><?php echo htmlspecialchars($form['form_type']); ?></small>
                    <a href="?id=<?php echo $patient_id; ?>&delete_form=<?php echo $form['id']; ?>" 
                       class="btn btn-danger btn-sm-custom" 
                       onclick="return confirm('Delete this form?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <small class="text-muted">No forms yet</small>
            <?php endif; ?>
        </div>
        <button class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#addFormModal">
            <i class="fas fa-plus"></i> Add Form
        </button>

        <div class="section-title">Sheets</div>
        <div class="list-box" id="sheetsList">
            <div class="list-item sheet-item" data-sheet="Neurologic">
                <small>Neurologic</small>
                <?php if (isset($sheet_data['Sheet_Neurologic'])): ?>
                    <span class="sheet-badge">Saved</span>
                <?php endif; ?>
            </div>
            <div class="list-item sheet-item" data-sheet="Vascular">
                <small>Vascular</small>
                <?php if (isset($sheet_data['Sheet_Vascular'])): ?>
                    <span class="sheet-badge">Saved</span>
                <?php endif; ?>
            </div>
            <div class="list-item sheet-item" data-sheet="Cardiac">
                <small>Cardiac</small>
                <?php if (isset($sheet_data['Sheet_Cardiac'])): ?>
                    <span class="sheet-badge">Saved</span>
                <?php endif; ?>
            </div>
            <div class="list-item sheet-item" data-sheet="Respiratory">
                <small>Respiratory</small>
                <?php if (isset($sheet_data['Sheet_Respiratory'])): ?>
                    <span class="sheet-badge">Saved</span>
                <?php endif; ?>
            </div>
            <div class="list-item sheet-item" data-sheet="GI_Abdomen">
                <small>GI: Abdomen</small>
                <?php if (isset($sheet_data['Sheet_GI_Abdomen'])): ?>
                    <span class="sheet-badge">Saved</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button class="btn btn-secondary btn-sm" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <!-- CENTER PANEL -->
    <div class="center-panel">
        <h5 class="mb-3">
            <i class="fas fa-user-md"></i> Patient Record: <strong><?php echo htmlspecialchars($patient_name); ?></strong>
        </h5>

        <!-- Meetings Section -->
        <div class="section-title">
            Meetings / Appointments
            <button class="btn btn-sm btn-primary float-end btn-sm-custom" data-bs-toggle="modal" data-bs-target="#addMeetingModal">
                <i class="fas fa-plus"></i> Add
            </button>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Duration</th>
                    <th>Notes</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($meetings && mysqli_num_rows($meetings) > 0):
                    mysqli_data_seek($meetings, 0);
                    while ($meeting = mysqli_fetch_assoc($meetings)): 
                ?>
                <tr>
                    <td><?php echo date('d M Y', strtotime($meeting['meeting_date'])); ?></td>
                    <td><?php echo isset($meeting['meeting_time']) ? htmlspecialchars($meeting['meeting_time']) : ''; ?></td>
                    <td><?php echo isset($meeting['duration']) ? htmlspecialchars($meeting['duration']) : ''; ?></td>
                    <td><?php echo isset($meeting['notes']) ? htmlspecialchars($meeting['notes']) : ''; ?></td>
                    <td><span class="badge bg-success"><?php echo isset($meeting['status']) ? htmlspecialchars($meeting['status']) : 'Scheduled'; ?></span></td>
                    <td>
                        <a href="?id=<?php echo $patient_id; ?>&delete_meeting=<?php echo $meeting['id']; ?>" 
                           class="btn btn-danger btn-sm-custom" 
                           onclick="return confirm('Delete?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr><td colspan="6" class="empty-row">No meetings scheduled</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Diagnosis Section -->
        <div class="section-title">
            Diagnosis
            <button class="btn btn-sm btn-primary float-end btn-sm-custom" data-bs-toggle="modal" data-bs-target="#addDiagnosisModal">
                <i class="fas fa-plus"></i> Add
            </button>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Details</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($diagnosis && mysqli_num_rows($diagnosis) > 0):
                    mysqli_data_seek($diagnosis, 0);
                    while ($diag = mysqli_fetch_assoc($diagnosis)): 
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($diag['diagnosis_category']); ?></strong></td>
                    <td><?php echo htmlspecialchars($diag['diagnosis_details']); ?></td>
                    <td><?php echo date('d M Y', strtotime($diag['diagnosis_date'])); ?></td>
                    <td>
                        <a href="?id=<?php echo $patient_id; ?>&delete_diagnosis=<?php echo $diag['id']; ?>" 
                           class="btn btn-danger btn-sm-custom" 
                           onclick="return confirm('Delete?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr><td colspan="4" class="empty-row">No diagnosis recorded</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Notes Section -->
        <div class="section-title">Clinical Notes</div>
        <form method="POST" class="mb-3">
            <textarea class="form-control form-control-sm" name="note_text" rows="4" placeholder="Write clinical notes here..."><?php echo $latest_note ? htmlspecialchars($latest_note['note_text']) : ''; ?></textarea>
            <button type="submit" name="save_notes" class="btn btn-primary btn-sm mt-2">
                <i class="fas fa-save"></i> Save Notes
            </button>
        </form>

        <!-- Documents Section -->
        <div class="section-title">
            Documents Manager
            <button class="btn btn-sm btn-primary float-end btn-sm-custom" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                <i class="fas fa-upload"></i> Upload
            </button>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Document Name</th>
                    <th>Type</th>
                    <th>Upload Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($docs && mysqli_num_rows($docs) > 0):
                    mysqli_data_seek($docs, 0);
                    while ($doc = mysqli_fetch_assoc($docs)): 
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                    <td><span class="badge bg-info"><?php echo htmlspecialchars($doc['document_type']); ?></span></td>
                    <td><?php echo isset($doc['upload_date']) ? date('d M Y H:i', strtotime($doc['upload_date'])) : 'N/A'; ?></td>
                    <td>
                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" class="btn btn-success btn-sm-custom" download>
                            <i class="fas fa-download"></i>
                        </a>
                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" class="btn btn-primary btn-sm-custom" target="_blank">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="?id=<?php echo $patient_id; ?>&delete_doc=<?php echo $doc['id']; ?>" 
                           class="btn btn-danger btn-sm-custom" 
                           onclick="return confirm('Delete this document?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr><td colspan="4" class="empty-row">No documents uploaded</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Medical History -->
        <div class="section-title">Medical History</div>
        <div class="p-2 border rounded">
            <?php echo (isset($patient['medical_history']) && $patient['medical_history']) ? nl2br(htmlspecialchars($patient['medical_history'])) : '<em class="text-muted">No medical history recorded</em>'; ?>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right-panel">
        <h6 class="text-center mb-3"><strong id="currentDiagramTitle">Digestive System</strong></h6>
        
        <!-- Diagram Selector -->
        <div class="diagram-selector">
            <button class="diagram-btn active" data-diagram="digestive">Digestive</button>
            <button class="diagram-btn" data-diagram="cardiac">Cardiac</button>
            <button class="diagram-btn" data-diagram="respiratory">Respiratory</button>
            <button class="diagram-btn" data-diagram="neurologic">Neurologic</button>
        </div>

        <!-- Drawing Tools -->
        <div class="drawing-tools">
            <button class="tool-btn active" id="drawBtn" title="Draw"><i class="fas fa-pencil-alt"></i> Draw</button>
            <button class="tool-btn" id="eraseBtn" title="Erase"><i class="fas fa-eraser"></i> Erase</button>
            <button class="tool-btn" id="clearBtn" title="Clear All"><i class="fas fa-trash"></i> Clear</button>
            <input type="color" id="colorPicker" class="color-picker" value="#ff0000" title="Color">
            <input type="range" id="brushSize" min="1" max="10" value="3" style="width: 80px;" title="Brush Size">
            <button class="tool-btn" id="saveDrawingBtn" title="Save"><i class="fas fa-save"></i> Save</button>
        </div>

        <div class="diagram-area" id="diagramArea">
            <img id="diagramImage" src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/88/Digestive_system_diagram_en.svg/400px-Digestive_system_diagram_en.svg.png" 
                 style="width: 100%; height: 100%; object-fit: contain; position: absolute;" 
                 alt="System Diagram">
            <canvas id="drawingCanvas" width="400" height="400"></canvas>
        </div>

        <div class="mt-3">
            <h6><strong>System Details</strong></h6>
            
            <!-- Digestive Inspection -->
            <div class="system-detail-section">
                <div class="system-detail-header" onclick="toggleDetail('digestive_inspection')">
                    <span>Digestive Inspection</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="system-detail-content" id="digestive_inspection" style="display:none;">
                    <textarea class="editable-area" id="detail_digestive_inspection" placeholder="Enter examination findings..."><?php echo isset($system_details['digestive_inspection']) ? htmlspecialchars($system_details['digestive_inspection']) : ''; ?></textarea>
                    <button class="btn btn-primary btn-sm mt-2" onclick="saveSystemDetail('digestive_inspection')">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </div>

            <!-- Liver -->
            <div class="system-detail-section">
                <div class="system-detail-header" onclick="toggleDetail('liver')">
                    <span>Liver</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="system-detail-content" id="liver" style="display:none;">
                    <textarea class="editable-area" id="detail_liver" placeholder="Enter liver examination findings..."><?php echo isset($system_details['liver']) ? htmlspecialchars($system_details['liver']) : ''; ?></textarea>
                    <button class="btn btn-primary btn-sm mt-2" onclick="saveSystemDetail('liver')">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </div>

            <!-- Rectal -->
            <div class="system-detail-section">
                <div class="system-detail-header" onclick="toggleDetail('rectal')">
                    <span>Rectal</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="system-detail-content" id="rectal" style="display:none;">
                    <textarea class="editable-area" id="detail_rectal" placeholder="Enter rectal examination findings..."><?php echo isset($system_details['rectal']) ? htmlspecialchars($system_details['rectal']) : ''; ?></textarea>
                    <button class="btn btn-primary btn-sm mt-2" onclick="saveSystemDetail('rectal')">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </div>

            <!-- Digestive Palpation -->
            <div class="system-detail-section">
                <div class="system-detail-header" onclick="toggleDetail('digestive_palpation')">
                    <span>Digestive Palpation</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="system-detail-content" id="digestive_palpation" style="display:none;">
                    <textarea class="editable-area" id="detail_digestive_palpation" placeholder="Enter palpation findings..."><?php echo isset($system_details['digestive_palpation']) ? htmlspecialchars($system_details['digestive_palpation']) : ''; ?></textarea>
                    <button class="btn btn-primary btn-sm mt-2" onclick="saveSystemDetail('digestive_palpation')">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals continue below... -->

<!-- Upload Photo Modal -->
<div class="modal fade" id="uploadPhotoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Patient Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="file" class="form-control" name="profile_picture" accept="image/*" required>
                    <small class="text-muted">Allowed: JPG, PNG, GIF</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_picture" class="btn btn-primary btn-sm">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Document Name</label>
                        <input type="text" class="form-control form-control-sm" name="document_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select form-select-sm" name="document_type">
                            <option value="PDF">PDF</option>
                            <option value="Excel">Excel</option>
                            <option value="Word">Word</option>
                            <option value="Image">Image</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File</label>
                        <input type="file" class="form-control form-control-sm" name="document_file" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="upload_document" class="btn btn-primary btn-sm">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Form Modal -->
<div class="modal fade" id="addFormModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Form Type</label>
                        <select class="form-select form-select-sm" name="form_type" required>
                            <option value="Meeting (Doctor)">Meeting (Doctor)</option>
                            <option value="Full Status (Doctor)">Full Status (Doctor)</option>
                            <option value="Assistant">Assistant</option>
                            <option value="Billing">Billing</option>
                            <option value="Reports">Reports</option>
                            <option value="Statistics">Statistics</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Form Data</label>
                        <textarea class="form-control form-control-sm" name="form_data" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_form" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Sheet Data Modal -->
<div class="modal fade" id="sheetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sheetModalTitle">Sheet Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body" id="sheetModalBody">
                    <!-- Dynamic content loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="save_sheet" class="btn btn-primary btn-sm" id="saveSheetBtn">Save Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Meeting Modal -->
<div class="modal fade" id="addMeetingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Meeting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control form-control-sm" name="meeting_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Time</label>
                        <input type="time" class="form-control form-control-sm" name="meeting_time">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Duration</label>
                        <input type="text" class="form-control form-control-sm" name="duration" placeholder="e.g., 30 min">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control form-control-sm" name="meeting_notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_meeting" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Diagnosis Modal -->
<div class="modal fade" id="addDiagnosisModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Diagnosis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <input type="text" class="form-control form-control-sm" name="diagnosis_category" placeholder="e.g., Cardiovascular" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Details</label>
                        <textarea class="form-control form-control-sm" name="diagnosis_details" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control form-control-sm" name="diagnosis_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_diagnosis" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// System Detail Toggle
function toggleDetail(detailId) {
    const content = document.getElementById(detailId);
    if (content.style.display === 'none') {
        content.style.display = 'block';
    } else {
        content.style.display = 'none';
    }
}

// Save System Detail
function saveSystemDetail(detailName) {
    const content = document.getElementById('detail_' + detailName).value;
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'save_system_detail=1&detail_name=' + detailName + '&detail_content=' + encodeURIComponent(content)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('System detail saved successfully!');
        }
    });
}

// Drawing Canvas Setup
const canvas = document.getElementById('drawingCanvas');
const ctx = canvas.getContext('2d');
let isDrawing = false;
let currentTool = 'draw';
let currentColor = '#ff0000';
let brushSize = 3;
let currentDiagram = 'digestive';

// Diagram images
const diagramImages = {
    digestive: 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/88/Digestive_system_diagram_en.svg/400px-Digestive_system_diagram_en.svg.png',
    cardiac: 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e5/Heart_diagram-en.svg/400px-Heart_diagram-en.svg.png',
    respiratory: 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9a/Respiratory_system_complete_en.svg/400px-Respiratory_system_complete_en.svg.png',
    neurologic: 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1a/Brain_diagram.svg/400px-Brain_diagram.svg.png'
};

const diagramTitles = {
    digestive: 'Digestive System',
    cardiac: 'Cardiac System',
    respiratory: 'Respiratory System',
    neurologic: 'Neurologic System'
};

// Tool buttons
document.getElementById('drawBtn').addEventListener('click', () => {
    currentTool = 'draw';
    document.querySelectorAll('.tool-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('drawBtn').classList.add('active');
});

document.getElementById('eraseBtn').addEventListener('click', () => {
    currentTool = 'erase';
    document.querySelectorAll('.tool-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('eraseBtn').classList.add('active');
});

document.getElementById('clearBtn').addEventListener('click', () => {
    if (confirm('Clear all drawings?')) {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
});

document.getElementById('colorPicker').addEventListener('change', (e) => {
    currentColor = e.target.value;
});

document.getElementById('brushSize').addEventListener('input', (e) => {
    brushSize = e.target.value;
});

// Drawing functions
canvas.addEventListener('mousedown', startDrawing);
canvas.addEventListener('mousemove', draw);
canvas.addEventListener('mouseup', stopDrawing);
canvas.addEventListener('mouseout', stopDrawing);

function startDrawing(e) {
    isDrawing = true;
    const rect = canvas.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    ctx.beginPath();
    ctx.moveTo(x, y);
}

function draw(e) {
    if (!isDrawing) return;
    
    const rect = canvas.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    
    if (currentTool === 'draw') {
        ctx.strokeStyle = currentColor;
        ctx.lineWidth = brushSize;
        ctx.lineCap = 'round';
        ctx.lineTo(x, y);
        ctx.stroke();
    } else if (currentTool === 'erase') {
        ctx.clearRect(x - brushSize * 2, y - brushSize * 2, brushSize * 4, brushSize * 4);
    }
}

function stopDrawing() {
    isDrawing = false;
}

// Save drawing
document.getElementById('saveDrawingBtn').addEventListener('click', () => {
    const drawingData = canvas.toDataURL();
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'save_drawing=1&drawing_data=' + encodeURIComponent(drawingData) + '&diagram_type=' + currentDiagram
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Drawing saved successfully!');
        }
    });
});

// Diagram selector
document.querySelectorAll('.diagram-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const diagram = this.getAttribute('data-diagram');
        currentDiagram = diagram;
        
        // Update active button
        document.querySelectorAll('.diagram-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        // Change diagram image
        document.getElementById('diagramImage').src = diagramImages[diagram];
        document.getElementById('currentDiagramTitle').textContent = diagramTitles[diagram];
        
        // Clear canvas for new diagram
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    });
});

// Sheet selection and saved data display
const sheetForms = {
    Neurologic: `
        <input type="hidden" name="sheet_type" value="Sheet_Neurologic">
        <div class="mb-3">
            <label>Consciousness Level</label>
            <select class="form-select form-select-sm" name="consciousness">
                <option>Alert</option>
                <option>Drowsy</option>
                <option>Confused</option>
                <option>Unconscious</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Pupils</label>
            <input type="text" class="form-control form-control-sm" name="pupils" placeholder="Size, reaction">
        </div>
        <div class="mb-3">
            <label>Motor Response</label>
            <textarea class="form-control form-control-sm" name="motor_response" rows="2"></textarea>
        </div>
        <div class="mb-3">
            <label>Notes</label>
            <textarea class="form-control form-control-sm" name="notes" rows="3"></textarea>
        </div>
        <input type="hidden" name="sheet_data" id="sheetDataInput">
    `,
    Vascular: `
        <input type="hidden" name="sheet_type" value="Sheet_Vascular">
        <div class="mb-3">
            <label>Peripheral Pulses</label>
            <input type="text" class="form-control form-control-sm" name="pulses" placeholder="Present/Absent, strength">
        </div>
        <div class="mb-3">
            <label>Capillary Refill</label>
            <input type="text" class="form-control form-control-sm" name="capillary_refill" placeholder="< 2 seconds">
        </div>
        <div class="mb-3">
            <label>Skin Color & Temperature</label>
            <input type="text" class="form-control form-control-sm" name="skin_temp">
        </div>
        <div class="mb-3">
            <label>Notes</label>
            <textarea class="form-control form-control-sm" name="notes" rows="3"></textarea>
        </div>
        <input type="hidden" name="sheet_data" id="sheetDataInput">
    `,
    Cardiac: `
        <input type="hidden" name="sheet_type" value="Sheet_Cardiac">
        <div class="row mb-3">
            <div class="col-6">
                <label>Heart Rate (bpm)</label>
                <input type="number" class="form-control form-control-sm" name="heart_rate">
            </div>
            <div class="col-6">
                <label>Rhythm</label>
                <select class="form-select form-select-sm" name="rhythm">
                    <option>Regular</option>
                    <option>Irregular</option>
                </select>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-6">
                <label>Blood Pressure</label>
                <input type="text" class="form-control form-control-sm" name="blood_pressure" placeholder="120/80">
            </div>
            <div class="col-6">
                <label>Heart Sounds</label>
                <select class="form-select form-select-sm" name="heart_sounds">
                    <option>Normal S1, S2</option>
                    <option>Murmur present</option>
                    <option>Irregular</option>
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label>Notes</label>
            <textarea class="form-control form-control-sm" name="notes" rows="3"></textarea>
        </div>
        <input type="hidden" name="sheet_data" id="sheetDataInput">
    `,
    Respiratory: `
        <input type="hidden" name="sheet_type" value="Sheet_Respiratory">
        <div class="row mb-3">
            <div class="col-6">
                <label>Respiratory Rate</label>
                <input type="number" class="form-control form-control-sm" name="resp_rate" placeholder="per minute">
            </div>
            <div class="col-6">
                <label>SpO2 (%)</label>
                <input type="number" class="form-control form-control-sm" name="spo2" placeholder="98">
            </div>
        </div>
        <div class="mb-3">
            <label>Breath Sounds</label>
            <select class="form-select form-select-sm" name="breath_sounds">
                <option>Clear bilaterally</option>
                <option>Wheezing</option>
                <option>Crackles</option>
                <option>Diminished</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Pattern</label>
            <select class="form-select form-select-sm" name="pattern">
                <option>Regular</option>
                <option>Irregular</option>
                <option>Labored</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Notes</label>
            <textarea class="form-control form-control-sm" name="notes" rows="3"></textarea>
        </div>
        <input type="hidden" name="sheet_data" id="sheetDataInput">
    `,
    GI_Abdomen: `
        <input type="hidden" name="sheet_type" value="Sheet_GI_Abdomen">
        <div class="mb-3">
            <label>Inspection</label>
            <input type="text" class="form-control form-control-sm" name="inspection" placeholder="Flat, distended, scars">
        </div>
        <div class="mb-3">
            <label>Bowel Sounds</label>
            <select class="form-select form-select-sm" name="bowel_sounds">
                <option>Normal</option>
                <option>Hyperactive</option>
                <option>Hypoactive</option>
                <option>Absent</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Palpation</label>
            <textarea class="form-control form-control-sm" name="palpation" rows="2" placeholder="Tender areas, masses"></textarea>
        </div>
        <div class="mb-3">
            <label>Notes</label>
            <textarea class="form-control form-control-sm" name="notes" rows="3"></textarea>
        </div>
        <input type="hidden" name="sheet_data" id="sheetDataInput">
    `
};

// Saved sheet data from PHP
const savedSheetData = <?php echo json_encode($sheet_data); ?>;

document.querySelectorAll('.sheet-item').forEach(item => {
    item.addEventListener('click', function() {
        // Remove previous selection
        document.querySelectorAll('.sheet-item').forEach(i => i.classList.remove('selected'));
        this.classList.add('selected');
        
        const sheetType = this.getAttribute('data-sheet');
        const sheetKey = 'Sheet_' + sheetType;
        const modal = new bootstrap.Modal(document.getElementById('sheetModal'));
        
        document.getElementById('sheetModalTitle').textContent = sheetType + ' Examination';
        document.getElementById('sheetModalBody').innerHTML = sheetForms[sheetType] || '<p>Form not available</p>';
        
        // Load saved data if exists
        if (savedSheetData[sheetKey]) {
            try {
                const data = JSON.parse(savedSheetData[sheetKey]);
                Object.keys(data).forEach(key => {
                    const input = document.querySelector(`[name="${key}"]`);
                    if (input) {
                        input.value = data[key];
                    }
                });
            } catch (e) {
                console.log('Error loading saved data');
            }
        }
        
        // Collect form data before submit
        document.getElementById('saveSheetBtn').onclick = function() {
            const formData = {};
            const inputs = document.querySelectorAll('#sheetModalBody input, #sheetModalBody select, #sheetModalBody textarea');
            inputs.forEach(input => {
                if (input.name && input.name !== 'sheet_data' && input.name !== 'sheet_type') {
                    formData[input.name] = input.value;
                }
            });
            document.getElementById('sheetDataInput').value = JSON.stringify(formData);
        };
        
        modal.show();
    });
});
</script>
</body>
</html>
