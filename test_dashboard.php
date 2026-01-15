<?php
echo "PHP is working!<br>";

require_once 'db_connect.php';
echo "Database connected!<br>";

$result = $conn->query("SELECT * FROM doctors WHERE doctor_id = 1");
if ($result->num_rows > 0) {
    $doctor = $result->fetch_assoc();
    echo "Doctor found: " . $doctor['first_name'] . " " . $doctor['last_name'];
} else {
    echo "No doctor found with ID = 1";
}
?>
