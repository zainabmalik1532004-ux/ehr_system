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

// Calculate age
$dob = new DateTime($patient['date_of_birth']);
$now = new DateTime();
$age = $now->diff($dob);
$age_string = $age->y . ' years ' . $age->m . ' months';

// Get latest SOAP note
$soap_query = "SELECT * FROM soap_notes WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($soap_query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$soap_result = $stmt->get_result();
$soap = $soap_result->fetch_assoc();

// Get examination sheets
$sheets = [];
$sheet_types = ['Neurologic', 'Vascular', 'Cardiac', 'Respiratory', 'Abdominal', 'General'];
foreach ($sheet_types as $type) {
    $stmt = $conn->prepare("SELECT * FROM examination_sheets WHERE patient_id = ? AND sheet_type = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("is", $patient_id, $type);
    $stmt->execute();
    $result = $stmt->get_result();
    $sheets[$type] = $result->fetch_assoc();
}

// Get anatomical diagram
$diagram_query = "SELECT * FROM anatomical_diagrams WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($diagram_query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$diagram_result = $stmt->get_result();
$diagram = $diagram_result->fetch_assoc();

// Handle SOAP note save
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_soap'])) {
    $subjective = trim($_POST['subjective'] ?? '');
    $objective = trim($_POST['objective'] ?? '');
    $assessment = trim($_POST['assessment'] ?? '');
    $plan = trim($_POST['plan'] ?? '');
    $visit_date = date('Y-m-d');
    
    $stmt = $conn->prepare("INSERT INTO soap_notes (patient_id, doctor_id, visit_date, subjective, objective, assessment, plan) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss", $patient_id, $doctor_id, $visit_date, $subjective, $objective, $assessment, $plan);
    $stmt->execute();
    
    $_SESSION['success'] = "SOAP notes saved successfully!";
    header("Location: view_patient.php?id=" . $patient_id);
    exit();
}

// Handle examination sheet save
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_sheet'])) {
    $sheet_type = $_POST['sheet_type'];
    $sheet_data = trim($_POST['sheet_data'] ?? '');
    
    // Check if sheet exists
    $stmt = $conn->prepare("SELECT sheet_id FROM examination_sheets WHERE patient_id = ? AND sheet_type = ?");
    $stmt->bind_param("is", $patient_id, $sheet_type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing
        $stmt = $conn->prepare("UPDATE examination_sheets SET sheet_data = ? WHERE patient_id = ? AND sheet_type = ?");
        $stmt->bind_param("sis", $sheet_data, $patient_id, $sheet_type);
    } else {
        // Insert new
        $stmt = $conn->prepare("INSERT INTO examination_sheets (patient_id, doctor_id, sheet_type, sheet_data) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $patient_id, $doctor_id, $sheet_type, $sheet_data);
    }
    $stmt->execute();
    
    $_SESSION['success'] = "$sheet_type sheet saved successfully!";
    header("Location: view_patient.php?id=" . $patient_id);
    exit();
}

// Handle diagram save
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_diagram'])) {
    $diagram_data = $_POST['diagram_data'];
    
    // Check if diagram exists
    $stmt = $conn->prepare("SELECT diagram_id FROM anatomical_diagrams WHERE patient_id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing
        $stmt = $conn->prepare("UPDATE anatomical_diagrams SET diagram_data = ? WHERE patient_id = ?");
        $stmt->bind_param("si", $diagram_data, $patient_id);
    } else {
        // Insert new
        $stmt = $conn->prepare("INSERT INTO anatomical_diagrams (patient_id, doctor_id, diagram_data) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $patient_id, $doctor_id, $diagram_data);
    }
    $stmt->execute();
    
    $_SESSION['success'] = "Diagram saved successfully!";
    header("Location: view_patient.php?id=" . $patient_id);
    exit();
}

// Handle document upload - FIXED VERSION
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
    // Use absolute path for file operations
    $upload_dir = __DIR__ . '/uploads/documents/';
    $relative_dir = 'uploads/documents/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            $_SESSION['error'] = "Failed to create upload directory!";
            header("Location: view_patient.php?id=" . $patient_id);
            exit();
        }
    }
    
    // Check if directory is writable
    if (!is_writable($upload_dir)) {
        $_SESSION['error'] = "Upload directory is not writable! Please run: chmod -R 777 uploads";
        header("Location: view_patient.php?id=" . $patient_id);
        exit();
    }
    
    $file_name = $_FILES['document']['name'];
    $file_tmp = $_FILES['document']['tmp_name'];
    $file_size = $_FILES['document']['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Allowed extensions
    $allowed = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt');
    
    if (in_array($file_ext, $allowed)) {
        if ($file_size <= 10 * 1024 * 1024) { // 10MB max
            // Generate unique filename
            $new_filename = 'doc_' . $patient_id . '_' . time() . '.' . $file_ext;
            $absolute_path = $upload_dir . $new_filename;
            $relative_path = $relative_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $absolute_path)) {
                $stmt = $conn->prepare("INSERT INTO patient_documents (patient_id, doctor_id, document_name, document_path, document_type) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iisss", $patient_id, $doctor_id, $file_name, $relative_path, $file_ext);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Document '$file_name' uploaded successfully!";
                } else {
                    $_SESSION['error'] = "Database error: " . $stmt->error;
                }
            } else {
                $_SESSION['error'] = "Failed to move uploaded file! Upload path: $absolute_path - Please check permissions.";
            }
        } else {
            $_SESSION['error'] = "File size must be less than 10MB!";
        }
    } else {
        $_SESSION['error'] = "File type not allowed! Allowed: JPG, PNG, PDF, DOC, DOCX, TXT";
    }
    
    header("Location: view_patient.php?id=" . $patient_id);
    exit();
}

// Handle document delete
if (isset($_GET['delete_doc'])) {
    $doc_id = $_GET['delete_doc'];
    
    // Get document path first
    $stmt = $conn->prepare("SELECT document_path FROM patient_documents WHERE document_id = ? AND patient_id = ? AND doctor_id = ?");
    $stmt->bind_param("iii", $doc_id, $patient_id, $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $doc = $result->fetch_assoc();
        $file_path = $doc['document_path'];
        
        // Delete file from server
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM patient_documents WHERE document_id = ? AND patient_id = ? AND doctor_id = ?");
        $stmt->bind_param("iii", $doc_id, $patient_id, $doctor_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Document deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete document!";
        }
    }
    
    header("Location: view_patient.php?id=" . $patient_id);
    exit();
}

// Get patient documents
$docs_query = "SELECT * FROM patient_documents WHERE patient_id = ? ORDER BY uploaded_at DESC";
$stmt = $conn->prepare($docs_query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$documents = $stmt->get_result();
$total_documents = $documents->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Details - <?php echo htmlspecialchars($patient['patient_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f0f0f0;
        }
        .header-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            margin-bottom: 0;
        }
        .sidebar {
            background: #e8e3f3;
            padding: 20px;
            border-radius: 10px;
            position: sticky;
            top: 20px;
        }
        .patient-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #6c757d;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 20px;
        }
        .section-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .section-title {
            background: #d4f1d4;
            padding: 15px;
            margin: -25px -25px 20px -25px;
            border-radius: 10px 10px 0 0;
            font-weight: bold;
        }
        .sheet-checkbox {
            margin-bottom: 10px;
        }
        .anatomical-canvas {
            width: 100%;
            height: 400px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            background: white;
            cursor: crosshair;
        }
        .btn-save {
            background: #28a745;
            color: white;
            border: none;
        }
        .btn-save:hover {
            background: #218838;
            color: white;
        }
        #documentInput {
            display: none;
        }
        .document-item {
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 10px;
            background: #f8f9fa;
        }
        .document-item:hover {
            background: #e9ecef;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header-bar">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h4><i class="bi bi-clipboard-pulse"></i> Handy Patients Enterprise Edition</h4>
                <div>
                    <a href="dashboard.php" class="btn btn-light me-2">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                    <a href="logout.php" class="btn btn-danger">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid mt-4">
        <!-- Success/Error Messages -->
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

        <div class="row">
            <!-- Left Sidebar -->
            <div class="col-md-3">
                <div class="sidebar">
                    <!-- Patient Avatar -->
                    <div class="patient-avatar">
                        <?php echo strtoupper(substr($patient['patient_name'], 0, 1)); ?>
                    </div>
                    
                    <!-- Patient Info -->
                    <div class="text-center mb-3">
                        <h5><strong>Last:</strong> <?php echo htmlspecialchars($patient['patient_name']); ?></h5>
                        <p class="mb-1"><strong>First:</strong> <?php echo htmlspecialchars($patient['patient_name']); ?></p>
                        <p class="mb-1"><strong>Birth:</strong> <?php echo date('d F Y', strtotime($patient['date_of_birth'])); ?></p>
                        <p class="mb-1"><strong>Gender:</strong> <?php echo $patient['gender']; ?></p>
                        <p class="mb-1"><strong>Age:</strong> <?php echo $age_string; ?></p>
                        <p class="mb-1"><strong>Patient ID:</strong> <?php echo $patient['id']; ?></p>
                    </div>

                    <!-- Forms Dropdown -->
                    <div class="mb-3">
                        <h6 class="fw-bold" style="background: #d4d4f0; padding: 10px; border-radius: 5px;">Forms</h6>
                        <select class="form-select" id="formsDropdown">
                            <option>Meeting (Doctor)</option>
                            <option>Consultation Form</option>
                            <option>Lab Results</option>
                        </select>
                    </div>

                    <!-- Sheets Section -->
                    <div>
                        <h6 class="fw-bold" style="background: #d4d4f0; padding: 10px; border-radius: 5px;">Sheets</h6>
                        <?php foreach ($sheet_types as $type): ?>
                        <div class="form-check sheet-checkbox">
                            <input class="form-check-input" type="radio" name="sheetType" id="sheet<?php echo $type; ?>" 
                                   value="<?php echo $type; ?>" <?php echo $type == 'Abdominal' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="sheet<?php echo $type; ?>">
                                <?php echo $type; ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <!-- SOAP Section -->
                <div class="section-card">
                    <div class="section-title" style="background: #d4d4f0;">
                        <h5 class="mb-0">SOAP</h5>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Subjective</label>
                            <textarea name="subjective" class="form-control" rows="3" 
                                      placeholder="Patient's complaints..."><?php echo $soap ? htmlspecialchars($soap['subjective']) : ''; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Objective</label>
                            <textarea name="objective" class="form-control" rows="3" 
                                      placeholder="Examination findings..."><?php echo $soap ? htmlspecialchars($soap['objective']) : ''; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Assessment</label>
                            <textarea name="assessment" class="form-control" rows="3" 
                                      placeholder="Diagnosis..."><?php echo $soap ? htmlspecialchars($soap['assessment']) : ''; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Plan</label>
                            <textarea name="plan" class="form-control" rows="3" 
                                      placeholder="Treatment plan..."><?php echo $soap ? htmlspecialchars($soap['plan']) : ''; ?></textarea>
                        </div>

                        <button type="submit" name="save_soap" class="btn btn-save">
                            <i class="bi bi-save"></i> Save SOAP Notes
                        </button>
                    </form>
                </div>

                <!-- Examination Sheet Section -->
                <div class="section-card">
                    <div class="section-title" style="background: #d4f1d4;">
                        <h5 class="mb-0">Examination Sheet: <span id="currentSheetType">Abdominal</span></h5>
                    </div>
                    
                    <?php foreach ($sheet_types as $type): ?>
                    <div class="sheet-content" id="sheetContent<?php echo $type; ?>" style="display: <?php echo $type == 'Abdominal' ? 'block' : 'none'; ?>;">
                        <form method="POST" action="">
                            <input type="hidden" name="sheet_type" value="<?php echo $type; ?>">
                            <textarea name="sheet_data" class="form-control" rows="6" 
                                      placeholder="Enter <?php echo $type; ?> examination findings..."><?php echo isset($sheets[$type]) && $sheets[$type] ? htmlspecialchars($sheets[$type]['sheet_data']) : ''; ?></textarea>
                            <button type="submit" name="save_sheet" class="btn btn-save mt-3">
                                <i class="bi bi-save"></i> Save <?php echo $type; ?> Sheet
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Anatomical Diagram -->
                <div class="section-card">
                    <div class="section-title" style="background: #d4f1d4;">
                        <h5 class="mb-0">Anatomical Diagram</h5>
                    </div>
                    
                    <canvas id="anatomicalCanvas" class="anatomical-canvas"></canvas>
                    <p class="text-muted text-center mt-2">Click to draw or annotate</p>
                    
                    <form method="POST" action="" id="diagramForm">
                        <input type="hidden" name="diagram_data" id="diagramData">
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-secondary" onclick="setDrawMode()">
                                <i class="bi bi-pencil"></i> Draw
                            </button>
                            <button type="button" class="btn btn-danger" onclick="setEraseMode()">
                                <i class="bi bi-eraser"></i> Erase
                            </button>
                            <button type="button" class="btn btn-warning" onclick="clearCanvas()">
                                <i class="bi bi-x-circle"></i> Clear
                            </button>
                            <button type="submit" name="save_diagram" class="btn btn-save" onclick="saveDiagram()">
                                <i class="bi bi-save"></i> Save Diagram
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Documents Manager -->
                <div class="section-card">
                    <div class="section-title" style="background: #d4f1d4;">
                        <h5 class="mb-0"><i class="bi bi-folder"></i> Documents Manager</h5>
                    </div>
                    
                    <!-- Upload Form -->
                    <form method="POST" action="" enctype="multipart/form-data" id="uploadForm" class="mb-4">
                        <div class="text-center">
                            <label for="documentInput" class="btn btn-primary btn-lg" style="cursor: pointer;">
                                <i class="bi bi-upload"></i> Upload Document
                            </label>
                            <input type="file" name="document" id="documentInput" 
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt"
                                   onchange="this.form.submit()">
                            <p class="text-muted mt-2 mb-0">
                                <small>Allowed: PDF, DOC, DOCX, JPG, PNG, GIF, TXT | Max size: 10MB</small>
                            </p>
                        </div>
                    </form>

                    <hr>

                    <!-- Documents List -->
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-file-earmark-text"></i> Uploaded Documents (<?php echo $total_documents; ?>)
                    </h6>
                    
                    <?php if ($total_documents > 0): ?>
                        <?php 
                        $documents->data_seek(0); // Reset pointer
                        while ($doc = $documents->fetch_assoc()): 
                        ?>
                        <div class="document-item">
                            <div class="row align-items-center">
                                <div class="col-md-5">
                                    <i class="bi bi-file-earmark-<?php 
                                        echo $doc['document_type'] == 'pdf' ? 'pdf' : 
                                             ($doc['document_type'] == 'doc' || $doc['document_type'] == 'docx' ? 'word' : 'image'); 
                                    ?>-fill text-primary"></i>
                                    <strong><?php echo htmlspecialchars($doc['document_name']); ?></strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i> <?php echo date('d M Y, H:i', strtotime($doc['uploaded_at'])); ?>
                                    </small>
                                </div>
                                <div class="col-md-4 text-end">
                                    <a href="<?php echo $doc['document_path']; ?>" target="_blank" class="btn btn-sm btn-primary me-1">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="<?php echo $doc['document_path']; ?>" download class="btn btn-sm btn-success me-1">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                    <a href="view_patient.php?id=<?php echo $patient_id; ?>&delete_doc=<?php echo $doc['document_id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this document?');">
                                        <i class="bi bi-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i> No documents uploaded yet. Click "Upload Document" to add files.
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <div class="text-end mb-4">
                    <a href="dashboard.php" class="btn btn-secondary btn-lg me-2">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sheet switching
        document.querySelectorAll('input[name="sheetType"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const type = this.value;
                document.getElementById('currentSheetType').textContent = type;
                
                // Hide all sheets
                document.querySelectorAll('.sheet-content').forEach(sheet => {
                    sheet.style.display = 'none';
                });
                
                // Show selected sheet
                document.getElementById('sheetContent' + type).style.display = 'block';
            });
        });

        // Canvas drawing
        const canvas = document.getElementById('anatomicalCanvas');
        const ctx = canvas.getContext('2d');
        let drawing = false;
        let drawMode = 'draw';

        canvas.width = canvas.offsetWidth;
        canvas.height = 400;

        // Load saved diagram if exists
        <?php if ($diagram && $diagram['diagram_data']): ?>
        const img = new Image();
        img.onload = function() {
            ctx.drawImage(img, 0, 0);
        };
        img.src = '<?php echo $diagram['diagram_data']; ?>';
        <?php endif; ?>

        canvas.addEventListener('mousedown', () => {
            drawing = true;
            ctx.beginPath();
        });
        canvas.addEventListener('mouseup', () => drawing = false);
        canvas.addEventListener('mousemove', draw);

        function draw(e) {
            if (!drawing) return;
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            ctx.lineWidth = drawMode === 'erase' ? 20 : 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = drawMode === 'erase' ? '#fff' : '#000';
            
            ctx.lineTo(x, y);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(x, y);
        }

        function setDrawMode() {
            drawMode = 'draw';
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
        }

        function setEraseMode() {
            drawMode = 'erase';
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 20;
        }

        function clearCanvas() {
            if (confirm('Clear the entire diagram?')) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }
        }

        function saveDiagram() {
            document.getElementById('diagramData').value = canvas.toDataURL();
            return true;
        }
    </script>
</body>
</html>
