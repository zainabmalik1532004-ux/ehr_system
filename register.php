<?php
session_start();
require_once 'db_connect.php';

$success = '';
$error = '';

// If already logged in, redirect to dashboard
if (isset($_SESSION['doctor_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($full_name) || empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required!";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    }
    elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    }
    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    }
    else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM doctors WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username already exists! Please choose another.";
        }
        else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM doctors WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email already registered! Please use another email.";
            }
            else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new doctor
                $stmt = $conn->prepare("INSERT INTO doctors (full_name, email, username, password) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $full_name, $email, $username, $hashed_password);
                
                if ($stmt->execute()) {
                    $success = "Registration successful! You can now login.";
                    // Clear form
                    $full_name = $email = $username = '';
                } else {
                    $error = "Registration failed! Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Registration - EHR System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 500px;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h2 {
            color: #667eea;
            font-weight: bold;
            margin-top: 10px;
        }
        .register-icon {
            font-size: 3.5rem;
            color: #667eea;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: bold;
            color: white;
        }
        .btn-register:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a4295 100%);
            color: white;
        }
        .password-strength {
            font-size: 0.85rem;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <i class="bi bi-person-plus-fill register-icon"></i>
            <h2>Doctor Registration</h2>
            <p class="text-muted mb-0">Create your EHR System account</p>
        </div>

        <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <div class="text-center mb-3">
            <a href="login.php" class="btn btn-primary">
                <i class="bi bi-box-arrow-in-right"></i> Go to Login
            </a>
        </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST" action="register.php" id="registerForm">
            <div class="mb-3">
                <label for="full_name" class="form-label">
                    <i class="bi bi-person-fill"></i> Full Name <span class="text-danger">*</span>
                </label>
                <input type="text" 
                       class="form-control" 
                       id="full_name" 
                       name="full_name" 
                       placeholder="Enter your full name (e.g., Dr. John Smith)"
                       value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>"
                       required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">
                    <i class="bi bi-envelope-fill"></i> Email Address <span class="text-danger">*</span>
                </label>
                <input type="email" 
                       class="form-control" 
                       id="email" 
                       name="email" 
                       placeholder="doctor@example.com"
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                       required>
            </div>

            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="bi bi-person-badge-fill"></i> Username <span class="text-danger">*</span>
                </label>
                <input type="text" 
                       class="form-control" 
                       id="username" 
                       name="username" 
                       placeholder="Choose a unique username"
                       value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>"
                       required>
                <small class="text-muted">This will be used for login</small>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="bi bi-lock-fill"></i> Password <span class="text-danger">*</span>
                </label>
                <input type="password" 
                       class="form-control" 
                       id="password" 
                       name="password" 
                       placeholder="Create a strong password"
                       required
                       minlength="6">
                <small class="text-muted">At least 6 characters</small>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">
                    <i class="bi bi-lock-check-fill"></i> Confirm Password <span class="text-danger">*</span>
                </label>
                <input type="password" 
                       class="form-control" 
                       id="confirm_password" 
                       name="confirm_password" 
                       placeholder="Re-enter your password"
                       required>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="terms" required>
                <label class="form-check-label" for="terms">
                    I agree to the <a href="#">Terms and Conditions</a>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-register w-100">
                <i class="bi bi-person-plus-fill"></i> Register Account
            </button>
        </form>

        <div class="text-center mt-4">
            <p class="text-muted">Already have an account? <a href="login.php" class="fw-bold">Login here</a></p>
            <p class="text-muted"><a href="index.php" class="fw-bold">← Back to Home</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });
    </script>
</body>
</html>
