<?php
/**
 * audit_logger.php
 *
 * Records every create/update/delete made to sensitive patient data,
 * so changes can always be traced back to who made them and when.
 *
 * Usage:
 *   require_once 'audit_logger.php';
 *   log_audit($conn, $doctor_id, 'UPDATE', 'patients', $patient_id, $old_data, $new_data);
 */

function log_audit($conn, $doctor_id, $action, $table_name, $record_id, $old_data = null, $new_data = null) {
    $old_json = $old_data !== null ? json_encode($old_data) : null;
    $new_json = $new_data !== null ? json_encode($new_data) : null;

    $stmt = $conn->prepare(
        "INSERT INTO audit_logs (doctor_id, action, table_name, record_id, old_values, new_values)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ississ", $doctor_id, $action, $table_name, $record_id, $old_json, $new_json);
    $stmt->execute();
    $stmt->close();
}