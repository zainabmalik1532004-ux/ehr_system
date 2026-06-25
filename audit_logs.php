<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['doctor_id'])) {
    header("Location: login.php");
    exit();
}

// Show the most recent changes made by this doctor across all patients
$stmt = $conn->prepare(
    "SELECT a.*, p.patient_name
     FROM audit_logs a
     LEFT JOIN patients p ON a.table_name = 'patients' AND a.record_id = p.id
     WHERE a.doctor_id = ?
     ORDER BY a.created_at DESC
     LIMIT 100"
);
$stmt->bind_param("i", $_SESSION['doctor_id']);
$stmt->execute();
$logs = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f6fa; padding: 30px; }
        .log-container { max-width: 1100px; margin: 0 auto; }
        .badge-CREATE { background: #2ecc71; }
        .badge-UPDATE { background: #3498db; }
        .badge-DELETE { background: #e74c3c; }
        pre { font-size: 12px; white-space: pre-wrap; margin: 0; }
    </style>
</head>
<body>
<div class="log-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-history"></i> Audit Log</h3>
        <a href="dashboard.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
    </div>

    <table class="table table-bordered bg-white">
        <thead class="table-light">
            <tr>
                <th>When</th>
                <th>Action</th>
                <th>Patient</th>
                <th>Before</th>
                <th>After</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($log = $logs->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                <td><span class="badge badge-<?php echo $log['action']; ?>"><?php echo $log['action']; ?></span></td>
                <td><?php echo htmlspecialchars($log['patient_name'] ?? ('Patient #' . $log['record_id'])); ?></td>
                <td><pre><?php echo $log['old_values'] ? htmlspecialchars(json_encode(json_decode($log['old_values']), JSON_PRETTY_PRINT)) : '—'; ?></pre></td>
                <td><pre><?php echo $log['new_values'] ? htmlspecialchars(json_encode(json_decode($log['new_values']), JSON_PRETTY_PRINT)) : '—'; ?></pre></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>